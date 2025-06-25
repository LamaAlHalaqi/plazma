<?php

use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ServiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HelloController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/hello', function () {
    return response()->json(['message' => 'Hello from Laravel 11 API!']);
});
Route::get('/hello', [HelloController::class, 'index']);
Route::post('/hello2', [HelloController::class, 'store']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp ', [AuthController::class, 'verifyOtp']);

Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::post('/send-reset-code', [PasswordResetController::class, 'sendCode']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

Route::post('/loginAsAdmin', [AdminController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::post('/profile', [ProfileController::class, 'updateProfile']);
    Route::middleware( 'admin')->group(function () {
    Route::get('/admin/dashboard', function () {
        return response()->json(['message' => '1-Welcome to Admin Dashboard !!']);
    });
});

});




Route::middleware('auth:sanctum')->prefix('departments')->group(function () {
    Route::get('/', [DepartmentController::class, 'index']); // عرض كل الأقسام مع الخدمات
    Route::post('/', [DepartmentController::class, 'store']); // إضافة قسم جديد (تحقق دور الأدمن داخل الكنترولر)
    Route::get('{id}', [DepartmentController::class, 'show']); // عرض قسم محدد مع خدماته
    Route::post('{id}', [DepartmentController::class, 'update']); // تعديل قسم (تحقق دور الأدمن داخل الكنترولر)
    Route::delete('{id}', [DepartmentController::class, 'destroy']); // حذف قسم (تحقق دور الأدمن داخل الكنترولر)
});

// الخدمات - كل الراوتات محمية بالتحقق من تسجيل الدخول
Route::middleware('auth:sanctum')->prefix('services')->group(function () {
    Route::get('/search', [ServiceController::class, 'search']);
    Route::get('/', [ServiceController::class, 'index']); // عرض كل الخدمات
    Route::post('/', [ServiceController::class, 'store']); // إضافة خدمة جديدة (تحقق دور الأدمن داخل الكنترولر)
    Route::get('{id}', [ServiceController::class, 'show']); // عرض خدمة محددة
    Route::delete('{id}', [ServiceController::class, 'destroy']); // حذف خدمة (تحقق دور الأدمن داخل الكنترولر)
    Route::post('{id}', [ServiceController::class, 'update']); // تعديل خدمة (تحقق دور الأدمن داخل الكنترولر)
});
