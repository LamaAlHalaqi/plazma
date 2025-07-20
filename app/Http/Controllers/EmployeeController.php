<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Response;


class EmployeeController extends Controller
{

    // حفظ بيانات الموظف الجديد
    public function store(Request $request)
    {


        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'غير مسموح لك بتنفيذ هذه العملية'
            ], Response::HTTP_FORBIDDEN);
        }
        // تحقق من صحة البيانات المدخلة
        $validatedData = $request->validate([
            'department_id'    => 'required|exists:departments,id',
            'name'             => 'required|string|max:255',
            'email'            => 'required|email|unique:employees,email',
            'phone'            => 'required|string|max:20',
            'work_start_time'  => 'required|date_format:H:i:s',
            'work_end_time'    => 'required|date_format:H:i:s|after:work_start_time',
        ]);

        // إنشاء موظف جديد بالبيانات الموثقة
        $employee = Employee::create($validatedData);

        // إرجاع رد مع بيانات الموظف الجديد
        return response()->json([
            'message' => 'تم إنشاء الموظف بنجاح',
            'employee' => $employee
        ], 201);
    }

    public function deleteEmployee(Request $request, $id)
    { if ($request->user()->role !== 'admin') {
        return response()->json([
            'status' => 'error',
            'code' => 403,
            'message' => 'غير مسموح لك بتنفيذ هذه العملية'
        ], Response::HTTP_FORBIDDEN);
    }

        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json(['message' => 'الموظف غير موجود'], 404);
        }

        $employee->delete();

        return response()->json(['message' => 'تم حذف الموظف بنجاح']);
    }
    public function updateEmployee(Request $request, $id)
    {
        // التحقق من صلاحية المستخدم
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'غير مسموح لك بتنفيذ هذه العملية'
            ], Response::HTTP_FORBIDDEN);
        }

        // البحث عن الموظف حسب ID
        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'الموظف غير موجود'
            ], Response::HTTP_NOT_FOUND);
        }

        // التحقق من صحة البيانات المطلوبة (مثال)
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:employees,email,' . $id,
            'phone' => 'sometimes|required|string|max:20',
            'work_start_time' => 'sometimes|required|date_format:H:i:s',
            'work_end_time' => 'sometimes|required|date_format:H:i:s',
            // أضف باقي الحقول حسب الحاجة
        ]);

        // تحديث بيانات الموظف
        $employee->update($validatedData);

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'تم تحديث بيانات الموظف بنجاح',
            'data' => $employee
        ], Response::HTTP_OK);
    }



    public function index()
    {
        $employees = \App\Models\Employee::all();

        return response()->json([
            'status' => 'success',
            'data' => $employees
        ]);
    }


}
