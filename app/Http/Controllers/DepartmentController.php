<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Storage;
class DepartmentController extends Controller
{
    // عرض كل الأقسام فقط
    public function index()
    {
        $departments = Department::all();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => $departments,
            'message' => 'تم جلب الأقسام بنجاح'
        ], Response::HTTP_OK);
    }

// إضافة قسم جديد

    public function store(Request $request)
    {
        // تحقق من صلاحية المستخدم قبل أي شيء
        /** @var User $user */
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'غير مسموح لك بتنفيذ هذه العملية'
            ], Response::HTTP_FORBIDDEN);
        }

        // تحقق من صحة البيانات
        $request->validate([
            'name' => 'required|string|max:255|unique:departments,name',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // إنشاء القسم
        $department = new Department();
        $department->name = $request->name;

        if ($request->hasFile('icon')) {
            $image = $request->file('icon');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/departments', $imageName);
            $department->icon = $imageName;
        }

        $department->save();

        return response()->json([
            'status' => 'success',
            'code' => 201,
            'data' => $department,
            'message' => 'تم إنشاء القسم بنجاح'
        ], Response::HTTP_CREATED);
    }


// عرض قسم محدد مع خدماته
    public function show($id)
    {
        $department = Department::with('services')->find($id);

        if (!$department) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'القسم غير موجود'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => $department,
            'message' => 'تم جلب القسم بنجاح'
        ], Response::HTTP_OK);
    }

    // تعديل قسم

    public function update(Request $request, $id)
    {
        // تحقق من أن المستخدم أدمن
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'ليس لديك صلاحية تعديل الأقسام'
            ], Response::HTTP_FORBIDDEN);
        }

        $department = Department::find($id);

        if (!$department) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'القسم غير موجود'
            ], Response::HTTP_NOT_FOUND);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:departments,name,' . $id,
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $department->name = $validatedData['name'];

        if ($request->hasFile('icon')) {
            if ($department->icon) {
                Storage::delete('public/departments/' . $department->icon);
            }

            $image = $request->file('icon');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/departments', $imageName);
            $department->icon = $imageName;
        }

        $department->save();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => $department,
            'message' => 'تم تعديل القسم بنجاح'
        ], Response::HTTP_OK);
    }

// حذف قسم
    public function destroy(Request $request, $id)
    {
        // تحقق من أن المستخدم أدمن
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'ليس لديك صلاحية حذف الأقسام'
            ], Response::HTTP_FORBIDDEN);
        }

        $department = Department::find($id);

        if (!$department) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'القسم غير موجود'
            ], Response::HTTP_NOT_FOUND);
        }

        if ($department->icon) {
            Storage::delete('public/departments/' . $department->icon);
        }

        $department->delete();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'تم حذف القسم بنجاح'
        ], Response::HTTP_OK);
    }
}
