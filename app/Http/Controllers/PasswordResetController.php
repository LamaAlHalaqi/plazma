<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;

class PasswordResetController extends Controller
{
    // إرسال الكود إلى البريد
    public function sendCode(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $code = rand(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Str::random(60),
                'code' => $code,
                'created_at' => Carbon::now()
            ]
        );

        // إرسال الكود
        Mail::raw("Your password reset code is: $code", function ($message) use ($request) {
            $message->to($request->email)
                    ->subject('Password Reset Code');
        });

        return response()->json(['message' => 'Code sent to your email']);
    }

    // إعادة تعيين كلمة المرور
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string',
            'password' => 'required|confirmed|min:6',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('code', $request->code)
            ->first();

        if (! $record) {
            return response()->json(['message' => 'Invalid code or email'], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // حذف الرمز بعد الاستخدام
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Password has been reset successfully']);
    }
}
