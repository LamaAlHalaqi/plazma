<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function register(Request $request)
    {
       $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        $otp = rand(100000, 999999);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'otp'      => $otp,
        ]);

        // إرسال OTP إلى البريد
        Mail::raw("رمز التحقق الخاص بك هو: $otp", function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('رمز التحقق من التسجيل');
        });


        return response()->json([
            'message' => 'تم إنشاء الحساب وتم إرسال رمز التحقق إلى بريدك.',

        ], 201);
    }

   public function verifyOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'otp'   => 'required|digits:6',
    ]);

    $user = User::where('email', $request->email)->where('otp', $request->otp)->first();
   // /** @var User $user */
   // $user = Auth::user();

    if (!$user) {
        return response()->json(['message' => 'OTP غير صحيح'], 400);
    }

    $user->update([
        'is_verified' => true,
        'otp' => null, //  حذف الرمز بعد التحقق
    ]);
// توليد Access Token
        $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'تم التحقق من البريد الإلكتروني بنجاح.',
         'token'   => $token,
            'user'    => $user
    ]);
    }


public function login(Request $request)
{
    // 1- Validate input
    $credentials = $request->validate([
        'email' => 'required|string|email',
        'password' => 'required|string',
    ]);

    // 2- Find the user by email
    $user = User::where('email', $credentials['email'])->first();

    // 3- Check if user exists and password matches
    if (! $user || ! Hash::check($credentials['password'], $user->password)) {
        return response()->json([
            'message' => 'Invalid login credentials.'
        ], 401);
       $user=User::where('email', $request->email)->FirstOrFail();
       //$token=$user->creatToken('athe_token')->plainTextToken;
    }

    // 4- Create token
   $token = $user->createToken('auth_token')->plainTextToken;

    // 5- Return token
    return response()->json([
'token'=>$token,
        'massage'=> 'login successfully',
        'User'=>$user,201
    ]);

}




public function logout(Request $request)
{
    /** @var User $user */
    $user = Auth::user();

    if (!$user) {
        return response()->json(['message' => 'المستخدم غير مصادق أو التوكن غير صحيح.'], 401);
    }

     if (method_exists($user, 'currentAccessToken')) {
        /** @var PersonalAccessToken|null $token */
        $token = $user->currentAccessToken();

        if ($token) {
            $token->delete();
            return response()->json(['message' => 'تم تسجيل الخروج بنجاح.']);
        }
    }

    return response()->json(['message' => 'لم يتم العثور على التوكن الحالي.'], 401);
}



}
