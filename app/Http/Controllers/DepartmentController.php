<?php


namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class DepartmentController extends Controller
{
    // عرض كل الأقسام فقط
    public function index()
    {
        $departments = Department::all();

        $departments->transform(function ($department) {
            $department->icon_url = $department->icon ? Storage::url('departments/' . $department->icon) : null;
            return $department;
        });

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
        // تأكد من صلاحية المستخدم
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'غير مسموح لك بتنفيذ هذه العملية'
            ], Response::HTTP_FORBIDDEN);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:departments,name',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $department = new Department();
        $department->name = $request->name;

        if ($request->hasFile('icon')) {
            $image = $request->file('icon');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('departments', $imageName);
            $department->icon = $imageName;
        }

        $department->save();

        $department->icon_url = $department->icon ? Storage::url('departments/' . $department->icon) : null;

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

        $department->icon_url = $department->icon ? Storage::url('departments/' . $department->icon) : null;

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

        $department->icon_url = $department->icon ? Storage::url('departments/' . $department->icon) : null;

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
