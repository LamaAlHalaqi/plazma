<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
class ProfileController extends Controller
{
    // ✅ عرض بيانات البروفايل
    public function show()
    {
        $user = Auth::user();

        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'gender' => $user->gender,
            'profile_image' => $user->profile_image
                ? asset('storage/' . $user->profile_image)
                : null,
        ]);
    }

    // ✅ تعديل بيانات البروفايل
   public function updateProfile(Request $request)
{


    // ✅ تحقق من البيانات المُرسلة
    $validated = $request->validate([
        'name' => 'nullable|string|max:255',
         "email"=>'nullable|string|max:255',
        'phone' => 'nullable|string|max:20',
        'gender' => 'nullable|in:male,female',
        'profile_image' => 'nullable|image|max:2048',
    ]);


    // ✅ جلب ID المستخدم من التوكن
    $userId = Auth::id();


    // ✅ جلب المستخدم من قاعدة البيانات بشكل صريح
    $user = User::findOrFail($userId); // سيتم رمي استثناء إن لم يوجد

    // ✅ معالجة الصورة إن وُجدت
    if ($request->hasFile('profile_image')) {
        $image = $request->file('profile_image');
        $imageName = time() . '.' . $image->getClientOriginalExtension();
        $path = $image->storeAs('profile_images', $imageName, 'public');
        $validated['profile_image'] = $path;
    }

    // ✅ تحديث المستخدم ثم الحفظ
    $user->fill($validated);
    $user->save();

    return response()->json([
        'message' => 'تم تحديث الملف الشخصي بنجاح.',
        'user' => $user->fresh(),
    ]);
}
}
