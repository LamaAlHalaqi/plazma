<?php
namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Service;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        // 1. الموظفين الذين دوامهم يشمل الوقت المطلوب وينتمون للقسم المحدد
        $employeesWorking = Employee::where('work_start_time', '<=', $start)
            ->where('work_end_time', '>=', $end)
            ->where('department_id', $departmentId)
            ->pluck('id');

        // 2. الموظفين المحجوزين في هذا الوقت
        $reservedEmployeeIds = Reservation::where(function ($query) use ($start, $end) {
            $query->whereBetween('start_time', [$start, $end])
                ->orWhereBetween('end_time', [$start, $end])
                ->orWhere(function ($q) use ($start, $end) {
                    $q->where('start_time', '<=', $start)
                        ->where('end_time', '>=', $end);
                });
        })->pluck('employee_id');

        // 3. المتاحين = من يعملون + ليسوا محجوزين
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
            'payment_method' => ['required', 'in:cash,online,points'],
            'notes'          => ['nullable', 'string']
        ]);

        $user = Auth::user();
        $service = Service::findOrFail($validated['service_id']);
        $employee = Employee::findOrFail($validated['employee_id']);
        $start = Carbon::parse($validated['start_time']);
        $end = Carbon::parse($validated['end_time']);

        // تحقق من أن الوقت ضمن دوام الموظف
        if (
            $start->format('H:i:s') < $employee->work_start_time ||
            $end->format('H:i:s') > $employee->work_end_time
        ) {
            return response()->json([
                'status' => 'error',
                'message' => 'وقت الحجز خارج وقت دوام الموظف.'
            ], 422);
        }

        // تحقق من عدم وجود حجز متضارب للموظف
        $conflict = Reservation::where('employee_id', $employee->id)
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

        // إدارة الدفع
        $amountPaid = 0;
        $pointsUsed = 0;
        $pointsEarned = $service->points ?? 0; // النقاط التي يكسبها المستخدم بعد الحجز

        if ($validated['payment_method'] === 'points') {
            // تحقق من توفر نقاط كافية بناءً على نقاط تكلفة الخدمة
            if ($user->points < $service->points_cost) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'عدد النقاط غير كافٍ لإجراء الحجز.'
                ], 422);
            }

            // خصم نقاط تكلفة الخدمة من المستخدم
            $user->points -= $service->points_cost;
            $pointsUsed = $service->points_cost;

        } else {
            // الدفع نقداً أو أونلاين
            $amountPaid = $service->price;

            // إضافة النقاط المكتسبة
            $user->points += $pointsEarned;
        }

        $user->save();

        // إنشاء الحجز
        $reservation = Reservation::create([
            'user_id'        => $user->id,
            'service_id'     => $service->id,
            'employee_id'    => $employee->id,
            'start_time'     => $start,
            'end_time'       => $end,
            'payment_method' => $validated['payment_method'],
            'amount_paid'    => $amountPaid,
            'points_used'    => $pointsUsed,
            'points_earned'  => $pointsEarned,
            'notes'          => $validated['notes'] ?? null,
            'status'         => 'confirmed',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم إنشاء الحجز بنجاح.',
            'data' => $reservation
        ], 201);
    }


// عرض كل الحجوزات
    public function index()
    {
        // جلب كل الحجوزات مع بيانات المستخدم، الخدمة، والموظف المرتبطين (عبر العلاقات)
        $reservations = Reservation::with(['user', 'service', 'employee'])->get();

        return response()->json([
            'status' => 'success',
            'data' => $reservations
        ]);
    }
}
