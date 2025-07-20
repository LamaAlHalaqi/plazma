<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    public function toggleFavorite($serviceId)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'يجب تسجيل الدخول'], 401);
        }

        $service = Service::findOrFail($serviceId);

        // إذا موجودة، نحذفها من المفضلة
        if ($user->favoriteServices()->where('service_id', $serviceId)->exists()) {
            $user->favoriteServices()->detach($serviceId);
            return response()->json(['message' => 'تمت إزالة الخدمة من المفضلة']);
        }

        // إذا مش موجودة، نضيفها
        $user->favoriteServices()->attach($serviceId);
        return response()->json(['message' => 'تمت إضافة الخدمة إلى المفضلة']);
    }

    // جلب قائمة المفضلة للمستخدم
    public function getFavorites()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'يجب تسجيل الدخول'], 401);
        }

        $favorites = $user->favoriteServices()->get();

        return response()->json($favorites);
    }
    public function removeFavorite($serviceId)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'يجب تسجيل الدخول'], 401);
        }

        $service = Service::findOrFail($serviceId);

        if ($user->favoriteServices()->where('service_id', $serviceId)->exists()) {
            $user->favoriteServices()->detach($serviceId);
            return response()->json(['message' => 'تمت إزالة الخدمة من المفضلة']);
        }

        return response()->json(['message' => 'الخدمة غير موجودة في المفضلة'], 404);
    }




}
