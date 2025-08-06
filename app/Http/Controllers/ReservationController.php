<?php

namespace App\Http\Controllers;
use App\Notifications\ReservationConfirmed;

use App\Models\Reservation;
use App\Models\Service;
use App\Models\Employee;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;

class ReservationController extends Controller
{
    public function getAvailableEmployees(Request $request)
    {
        $validated = $request->validate([
            'start_time' => ['required', 'date_format:H:i:s'],
            'end_time' => ['required', 'date_format:H:i:s', 'after:start_time'],
            'department_id' => ['required', 'exists:departments,id'],
        ]);

        $start = $validated['start_time'];
        $end = $validated['end_time'];
        $departmentId = $validated['department_id'];

        $employeesWorking = Employee::where('work_start_time', '<=', $start)
            ->where('work_end_time', '>=', $end)
            ->where('department_id', $departmentId)
            ->pluck('id');

        $reservedEmployeeIds = Reservation::where(function ($query) use ($start, $end) {
            $query->whereBetween('start_time', [$start, $end])
                ->orWhereBetween('end_time', [$start, $end])
                ->orWhere(function ($q) use ($start, $end) {
                    $q->where('start_time', '<=', $start)
                        ->where('end_time', '>=', $end);
                });
        })->pluck('employee_id');

        $availableEmployees = Employee::whereIn('id', $employeesWorking)
            ->whereNotIn('id', $reservedEmployeeIds)
            ->get();

        return response()->json($availableEmployees);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_id'     => ['required', 'exists:services,id'],
            'employee_id'    => ['required', 'exists:employees,id'],
            'start_time'     => ['required', 'date'],
            'end_time'       => ['required', 'date', 'after:start_time'],
            'notes'          => ['nullable', 'string']
        ]);

        return DB::transaction(function () use ($validated) {
            $user     = Auth::user();
            $service  = Service::findOrFail($validated['service_id']);
            $employee = Employee::findOrFail($validated['employee_id']);
            $start    = Carbon::parse($validated['start_time']);
            $end      = Carbon::parse($validated['end_time']);

            // التحقق من وقت دوام الموظف
            if (
                $start->format('H:i:s') < $employee->work_start_time ||
                $end->format('H:i:s') > $employee->work_end_time
            ) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'وقت الحجز خارج وقت دوام الموظف.'
                ], 422);
            }

            // التحقق من وجود تعارض في الحجوزات مع قفل للحماية من التزامن
            $conflict = Reservation::where('employee_id', $employee->id)
                ->where('status', '!=', 'cancelled')
                ->lockForUpdate()
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('start_time', [$start, $end])
                        ->orWhereBetween('end_time', [$start, $end])
                        ->orWhere(function ($q2) use ($start, $end) {
                            $q2->where('start_time', '<=', $start)
                                ->where('end_time', '>=', $end);
                        });
                })
                ->exists();

            if ($conflict) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'الموظف محجوز بالفعل في هذا الوقت.'
                ], 409);
            }

            // إنشاء الحجز
            $reservation = Reservation::create([
                'user_id'        => $user->id,
                'service_id'     => $service->id,
                'employee_id'    => $employee->id,
                'start_time'     => $start,
                'end_time'       => $end,
                'payment_method' => 'cash',
                'status'         => 'pending',
                'notes'          => $validated['notes'] ?? null,
                'amount_paid'    => 0,
                'points_used'    => 0,
                'points_earned'  => 0,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'تم إنشاء الحجز بنجاح بانتظار التأكيد.',
                'data'    => $reservation
            ], 201);
        });
    }


    public function index()
    {
        $reservations = Reservation::with(['user', 'service', 'employee'])->get();

        return response()->json([
            'status' => 'success',
            'data' => $reservations
        ]);
    }

    public function confirm(Request $request, $id)
    {
        $request->validate([
            'payment_method' => 'required|in:cash,points,online',
            'points_used' => 'nullable|integer|min:0',
            'payment_receipt' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $reservation = Reservation::findOrFail($id);

        if ($reservation->status !== 'pending') {
            return response()->json([
                'message' => 'لا يمكن تأكيد هذا الحجز.',
            ], 422);
        }

        $user = auth()->user();
        $service = $reservation->service;
        $pointsCost = $service->points_cost ?? 0;
        $price = $service->price;

        $paymentMethod = $request->payment_method;
        $pointsUsedInput = $request->points_used ?? 0;

        if ($paymentMethod === 'points') {
            if ($pointsCost <= 0) {
                return response()->json([
                    'message' => 'هذه الخدمة لا يمكن دفعها بالنقاط فقط.',
                ], 422);
            }
            if ($user->points < $pointsCost) {
                return response()->json([
                    'message' => 'رصيد النقاط غير كافٍ للدفع بهذه الطريقة.',
                ], 422);
            }

            $user->points -= $pointsCost;
            $user->save();

            $reservation->points_used = $pointsCost;
            $reservation->amount_paid = 0;

            $pointsEarned = $service->points ?? 0;
            $user->points += $pointsEarned;
            $user->save();

            $reservation->points_earned = $pointsEarned;
            $reservation->payment_method = $paymentMethod;
            $reservation->status = 'confirmed';
            $reservation->save();

            return response()->json([
                'status' => 'success',
                'message' => 'اتم تأكيد الحجز بنجاح .',
                'data' => $reservation->load('service'),
            ]);
        }

        if ($paymentMethod === 'online') {
            $cashShamId = '045104cd6e45be1bce2b62486f6d4c87';
            $amount = $price;
            $qrContent = "$cashShamId:$amount";

            $result = Builder::create()
                ->writer(new PngWriter())
                ->data($qrContent)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
                ->size(300)
                ->margin(10)
                ->build();
            $qrImageBase64 = base64_encode($result->getString());

            if (!$request->hasFile('payment_receipt')) {
                return response()->json([
                    'status' => 'awaiting_receipt',
                    'qr_code' => 'data:image/png;base64,' . $qrImageBase64,
                    'message' => 'ادفع باستخدام تطبيق كاش شام ثم ارفع صورة الإيصال.',
                    'reservation_id' => $reservation->id,
                    'amount' => $amount
                ]);
            }

            $receiptPath = $request->file('payment_receipt')->store('receipts', 'public');

            Payment::create([
                'reservation_id' => $reservation->id,
                'product_order_id' => null,
                'payment_method' => 'online',
                'amount' => $amount,
                'receipt_path' => $receiptPath,
                'status' => 'pending',
            ]);

            $reservation->payment_method = $paymentMethod;
            $reservation->amount_paid = $amount;
            $reservation->status = 'pending';
            $reservation->save();

            return response()->json([
                'status' => 'awaiting_admin_confirmation',
                'message' => 'تم رفع إيصال الدفع. يرجى الانتظار حتى يتم التحقق من الدفع من قبل الإدارة.',
                'amount' => $amount
            ]);
        }

        $reservation->payment_method = $paymentMethod;
        $reservation->status = 'confirmed';
        $reservation->amount_paid = $price;

        $pointsEarned = $service->points ?? 0;
        $user->points += $pointsEarned;
        $user->save();

        $reservation->points_earned = $pointsEarned;
        $reservation->save();

        return response()->json([
            'status' => 'success',
            'message' => 'تم تأكيد الحجز بنجاح يرجى انتظار اشعار من المركز بالموافقة على الحجز.',
            'data' => $reservation->load('service'),
        ]);
    }
    public function adminConfirmPayment(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|in:confirm,reject',
        ]);

        // التحقق من صلاحية الإدمن
        if (auth()->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'غير مصرح لك بتأكيد الدفع'
            ], 403);
        }

        $reservation = Reservation::findOrFail($id);

        // لا يمكن تأكيد حجز غير معلق
        if ($reservation->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'لا يمكن تأكيد/رفض حجز غير معلق.'
            ], 422);
        }

        // جلب سجل الدفع فقط إذا كانت الطريقة أونلاين
        $payment = null;
        if ($reservation->payment_method === 'online') {
            $payment = Payment::where('reservation_id', $id)->first();

            if (!$payment) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'لا يوجد إيصال دفع لهذا الحجز الأونلاين.'
                ], 422);
            }

            if ($payment->status !== 'pending') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'لا يمكن تأكيد/رفض دفع سبق معالجته.'
                ], 422);
            }
        }

        // معالجة التأكيد أو الرفض
        DB::transaction(function () use ($request, $reservation, $payment) {
            if ($request->action === 'confirm') {
                $reservation->status = 'confirmed';

                // إذا كان الدفع أونلاين، نؤكد الإيصال
                if ($reservation->payment_method === 'online' && $payment) {
                    $payment->status = 'confirmed';
                    $payment->save();
                }

                // منح النقاط المكتسبة (لجميع طرق الدفع)
                $user = $reservation->user;
                $service = $reservation->service;
                $pointsEarned = $service->points ?? 0;

                $user->points += $pointsEarned;
                $user->save();
                $reservation->points_earned = $pointsEarned;

                // إرسال إشعار التأكيد
                $user->notifyNow(new ReservationConfirmed($reservation));
            } else { // إذا كان الرفض
                $reservation->status = 'cancelled';

                // إذا كان الدفع أونلاين، نرفض الإيصال
                if ($reservation->payment_method === 'online' && $payment) {
                    $payment->status = 'rejected';
                    $payment->save();
                }

                // إذا كان الدفع بالنقاط، نعيد النقاط للمستخدم
                if ($reservation->payment_method === 'points') {
                    $user = $reservation->user;
                    $user->points += $reservation->points_used;
                    $user->save();
                }
            }

            $reservation->save();
        });

        return response()->json([
            'status' => 'success',
            'message' => $request->action === 'confirm' ? 'تم تأكيد الحجز بنجاح.' : 'تم رفض الحجز بنجاح.',
            'data' => $reservation->load('service'),
        ]);
    }
    public function cancel($id)
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json([
                'status' => 'error',
                'message' => 'الحجز غير موجود.'
            ], 404);
        }

        $now = Carbon::now();
        $reservationTime = Carbon::parse($reservation->start_time);
        $secondsDiff = $now->diffInSeconds($reservationTime, false);

        if ($secondsDiff <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'هذا الموعد انتهى أو بدأ بالفعل ولا يمكن إلغاؤه.'
            ], 422);
        }

        if ($secondsDiff <= 43200) {
            return response()->json([
                'status' => 'error',
                'message' => 'لا يمكن إلغاء الحجز قبل أقل من 12 ساعة من الموعد.'
            ], 422);
        }

        $reservation->status = 'cancelled';
        $reservation->save();

        // تحديث حالة الدفع إلى "ملغاة" إذا كان موجودًا
        $payment = Payment::where('reservation_id', $reservation->id)->first();
        if ($payment) {
            $payment->status = 'rejected';
            $payment->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم إلغاء الحجز بنجاح.'
        ]);
    }
    public function getNotifications(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'غير مصرح لك بالوصول إلى الإشعارات.'
            ], 401);
        }

        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($notification) {
                $data = $notification->data;

                return [
                    'id' => $notification->id,
                    'type' => class_basename($notification->type),
                    'title' => 'تأكيد الحجز',
                    'message' => $data['message'] ?? 'تم تأكيد حجزك',
                    'reservation_id' => $data['reservation_id'] ?? null,
                    'service_name' => $data['service_name'] ?? 'غير معروف',
                    'start_time' => $data['start_time'] ?? null,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at->diffForHumans(),
                    'is_read' => $notification->read_at !== null
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $notifications
        ]);
    }
}
