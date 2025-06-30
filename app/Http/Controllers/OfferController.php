<?php

namespace App\Http\Controllers;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class OfferController extends Controller
{
    // عرض جميع العروض مع الفلاتر
    public function index(Request $request)
    {
        $query = Offer::with(['service.department']);

        // فلترة حسب الحالة
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->whereDate('start_date', '<=', now())
                      ->whereDate('end_date', '>=', now());
            } elseif ($request->status === 'expired') {
                $query->whereDate('end_date', '<', now());
            }
        }

        // فلترة حسب القسم
        if ($request->has('department_id')) {
            $query->whereHas('service', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        $offers = $query->get();

        $data = $offers->map(function ($offer) {
            return [
                'id' => $offer->id,
                'title' => $offer->title,
                'description' => $offer->description,
                'discount_percent' => $offer->discount_percent,
                'start_date' => $offer->start_date,
                'end_date' => $offer->end_date,
                'status' => $offer->status,
                'service' => [
                    'id' => $offer->service->id,
                    'name' => $offer->service->name,
                    'icon' => $offer->service->icon ? asset('storage/services/' . $offer->service->icon) : null,
                    'department' => $offer->service->department->name ?? null,
                ]
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    // عرض عرض واحد
    public function show($id)
    {
        $offer = Offer::with('service.department')->find($id);

        if (!$offer) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'العرض غير موجود'
            ]);
        }

        $data = [
            'id' => $offer->id,
            'title' => $offer->title,
            'description' => $offer->description,
            'discount_percent' => $offer->discount_percent,
            'start_date' => $offer->start_date,
            'end_date' => $offer->end_date,
            'status' => $offer->status,
            'service' => [
                'id' => $offer->service->id,
                'name' => $offer->service->name,
                'icon' => $offer->service->icon ? asset('storage/services/' . $offer->service->icon) : null,
                'department' => $offer->service->department->name ?? null
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    // إنشاء عرض
    public function store(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        $request->validate([
            'service_id' => 'required|exists:services,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_percent' => 'required|numeric|min:1|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $offer = Offer::create($request->only([
            'service_id', 'title', 'description', 'discount_percent', 'start_date', 'end_date'
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'تم إنشاء العرض بنجاح',
            'data' => $offer
        ], Response::HTTP_CREATED);
    }

    // تعديل عرض
    public function update(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        $offer = Offer::find($id);
        if (!$offer) {
            return response()->json(['message' => 'العرض غير موجود'], 404);
        }

        $request->validate([
            'service_id' => 'required|exists:services,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_percent' => 'required|numeric|min:1|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $offer->update($request->only([
            'service_id', 'title', 'description', 'discount_percent', 'start_date', 'end_date'
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'تم تعديل العرض بنجاح',
            'data' => $offer
        ]);
    }

    // حذف عرض
    public function destroy(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        $offer = Offer::find($id);
        if (!$offer) {
            return response()->json(['message' => 'العرض غير موجود'], 404);
        }

        $offer->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف العرض بنجاح'
        ]);
    }
}
