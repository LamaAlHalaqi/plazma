<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class OfferController extends Controller
{
    // إضافة عرض جديد
    public function store(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'ليس لديك صلاحية إضافة العروض'
            ], Response::HTTP_FORBIDDEN);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:offers,name',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
            'description' => 'nullable|string',
            'points' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $offer = new Offer($validatedData);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('offers', $imageName);
            $offer->image = $imageName;
        }

        $offer->save();

        // إضافة رابط الصورة الكامل بعد الحفظ
        if ($offer->image) {
            $offer->image = asset('storage/offers/' . $offer->image);
        }

        return response()->json([
            'status' => 'success',
            'code' => 201,
            'data' => $offer,
            'message' => 'تم إنشاء العرض بنجاح'
        ], Response::HTTP_CREATED);
    }

    // عرض العروض النشطة فقط
    public function index()
    {
        $now = Carbon::now();

        $offers = Offer::where('end_datetime', '>', $now)
            ->select('id', 'name', 'image')
            ->get()
            ->map(function ($offer) {
                if ($offer->image) {
                    $offer->image = asset('storage/offers/' . $offer->image);
                }
                return $offer;
            });

        return response()->json([
            'status' => 200,
            'message' => 'تم جلب العروض النشطة بنجاح',
            'data' => $offers
        ]);
    }

    // عرض عرض محدد
    public function show($id)
    {
        $offer = Offer::find($id);

        if (!$offer) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'العرض غير موجود'
            ], 404);
        }

        if ($offer->image) {
            $offer->image = asset('storage/offers/' . $offer->image);
        }

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => $offer,
            'message' => 'تم جلب تفاصيل العرض بنجاح'
        ]);
    }

    // تعديل العرض
    public function update(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'ليس لديك صلاحية تعديل العروض'
            ], 403);
        }

        $offer = Offer::find($id);
        if (!$offer) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'العرض غير موجود'
            ], 404);
        }

        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255|unique:offers,name,' . $id,
            'discount_percentage' => 'sometimes|numeric|min:0|max:100',
            'start_datetime' => 'sometimes|date',
            'end_datetime' => 'sometimes|date|after:start_datetime',
            'description' => 'nullable|string',
            'points' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $offer->fill($validatedData);

        if ($request->hasFile('image')) {
            // حذف الصورة القديمة
            if ($offer->image) {
                Storage::delete('public/offers/' . $offer->image);
            }

            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/offers', $imageName);
            $offer->image = $imageName;
        }

        $offer->save();

        // رابط الصورة الكامل
        if ($offer->image) {
            $offer->image = asset('storage/offers/' . $offer->image);
        }

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => $offer,
            'message' => 'تم تعديل العرض بنجاح'
        ]);
    }

    // حذف عرض
    public function destroy($id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json([
                'message' => 'غير مصرح لك بالحذف'
            ], 403);
        }

        $offer = Offer::find($id);
        if (!$offer) {
            return response()->json([
                'message' => 'العرض غير موجود'
            ], 404);
        }

        if ($offer->image) {
            Storage::delete('public/offers/' . $offer->image);
        }

        $offer->delete();

        return response()->json([
            'message' => 'تم حذف العرض بنجاح'
        ]);
    }
}
