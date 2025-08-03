<?php
namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Service;
use App\Models\Employee;
use Endroid\QrCode\ErrorCorrectionLevel\High;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

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

        $user = Auth::user();
        $service = Service::findOrFail($validated['service_id']);
        $employee = Employee::findOrFail($validated['employee_id']);
        $start = Carbon::parse($validated['start_time']);
        $end = Carbon::parse($validated['end_time']);

        if (
            $start->format('H:i:s') < $employee->work_start_time ||
            $end->format('H:i:s') > $employee->work_end_time
        ) {
            return response()->json([
                'status' => 'error',
                'message' => 'وقت الحجز خارج وقت دوام الموظف.'
            ], 422);
        }

        $conflict = Reservation::where('employee_id', $employee->id)
            ->where('status', '!=', 'cancelled')
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
                'status' => 'error',
                'message' => 'الموظف محجوز بالفعل في هذا الوقت.'
            ], 409);
        }

        $reservation = Reservation::create([
            'user_id'        => $user->id,
            'service_id'     => $service->id,
            'employee_id'    => $employee->id,
            'start_time'     => $start,
            'end_time'       => $end,
            'payment_method' => 'cash',
            'status'         => 'pending',
            'notes'          => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم إنشاء الحجز بنجاح بانتظار التأكيد.',
            'data' => $reservation
        ], 201);
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
            'payment_method' => 'required|in:cash,points,electronic',
            'points_used' => 'nullable|integer|min:0',
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
        }

        if ($paymentMethod === 'electronic') {
            $cashShamId = '045104cd6e45be1bce2b62486f6d4c87';
            $amount = $price;
            $qrContent = "$cashShamId:$amount";

            $result = Builder::create()
                ->writer(new PngWriter())
                ->data($qrContent)
                ->encoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
                ->errorCorrectionLevel(new High())

                ->size(300)
                ->margin(10)
                ->build();

            $qrImageBase64 = base64_encode($result->getString());

            return response()->json([
                'status' => 'awaiting_payment',
                'qr_code' => 'data:image/png;base64,' . $qrImageBase64,
                'message' => 'امسح الكود عبر تطبيق كاش شام لإتمام الدفع.',
                'amount' => $amount
            ]);
        }

        $reservation->payment_method = $paymentMethod;
        $reservation->status = 'confirmed';
        $reservation->save();

        return response()->json([
            'status' => 'success',
            'message' => 'تم تأكيد الحجز بنجاح.',
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

        return response()->json([
            'status' => 'success',
            'message' => 'تم إلغاء الحجز بنجاح.'
        ]);
    }
}
