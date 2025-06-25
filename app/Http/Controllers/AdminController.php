<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;



class AdminController extends Controller
{
      public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // البحث عن الأدمن فقط
        $admin = User::where('email', $request->email)
                     ->where('role', 'admin')
                     ->first();

        // التحقق من البيانات
        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json(['message' => 'Invalid credentials or not an admin'], 401);
        }

        // إنشاء التوكن
        $token = $admin->createToken('admin_token')->plainTextToken;

        // الرد
        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
           // 'token_type' => 'Bearer',
            'user' => $admin
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}
