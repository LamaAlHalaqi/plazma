<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::with('department')->get();
        $services->each->append('icon_url');

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => $services,
            'message' => 'تم جلب الخدمات بنجاح'
        ], Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'غير مسموح لك بتنفيذ هذه العملية'
            ], Response::HTTP_FORBIDDEN);
        }

        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255|unique:services,name',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'points' => 'nullable|integer',
            'points_cost' => 'required|integer',
            'duration' => 'nullable|integer',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $serviceData = $request->only([
            'department_id', 'name', 'description', 'price',
            'points', 'points_cost', 'duration'
        ]);

        if ($request->hasFile('icon')) {
            $image = $request->file('icon');
            $imageName = Str::uuid() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('services', $imageName, 'public');

            $serviceData['icon'] = $imageName;
        }

        $service = Service::create($serviceData);

        return response()->json([
            'status' => 'success',
            'code' => 201,
            'data' => $service->fresh()->append('icon_url'),
            'message' => 'تم إنشاء الخدمة بنجاح'
        ], Response::HTTP_CREATED);
    }


    public function show($id)
    {
        $service = Service::with('department')->find($id);

        if (!$service) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'الخدمة غير موجودة'
            ], Response::HTTP_NOT_FOUND);
        }

        $service->append('icon_url');

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => $service,
            'message' => 'تم جلب تفاصيل الخدمة بنجاح'
        ], Response::HTTP_OK);
    }

    public function update(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'ليس لديك صلاحية تعديل الخدمات'
            ], Response::HTTP_FORBIDDEN);
        }

        $service = Service::find($id);

        if (!$service) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'الخدمة غير موجودة'
            ], Response::HTTP_NOT_FOUND);
        }

        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255|unique:services,name,' . $id,
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'points' => 'nullable|integer',
            'points_cost' => 'required|integer',
            'duration' => 'nullable|integer',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $service->fill($request->only([
            'department_id', 'name', 'description', 'price',
            'points', 'points_cost', 'duration'
        ]));

        if ($request->hasFile('icon')) {
            // حذف الصورة القديمة
            if ($service->icon) {
                \Storage::delete('public/services/' . $service->icon);
            }


            $image = $request->file('icon');
            $imageName = Str::uuid() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/services', $imageName);
            $service->icon = $imageName;
        }

        $service->save();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => $service->fresh()->append('icon_url'),
            'message' => 'تم تعديل الخدمة بنجاح'
        ], Response::HTTP_OK);
    }


    public function destroy(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'ليس لديك صلاحية حذف الخدمات'
            ], Response::HTTP_FORBIDDEN);
        }

        $service = Service::find($id);

        if (!$service) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'الخدمة غير موجودة'
            ], Response::HTTP_NOT_FOUND);
        }

        if ($service->icon) {
            \Storage::delete('public/services/' . $service->icon);
        }

        $service->delete();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'تم حذف الخدمة بنجاح'
        ], Response::HTTP_OK);
    }

    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string'
        ]);

        $searchText = $request->q;

        $services = Service::where('name', 'LIKE', "%{$searchText}%")
            ->orWhere('description', 'LIKE', "%{$searchText}%")
            ->with('department')
            ->get();

        $services->each->append('icon_url');

        if ($services->isEmpty()) {
            return response()->json([
                'message' => 'لم يتم العثور على أي نتائج.',
                'data' => []
            ], 200);
        }

        return response()->json([
            'message' => 'تم العثور على نتائج.',
            'data' => $services
        ], 200);
    }
}
