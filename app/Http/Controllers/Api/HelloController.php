<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HelloController extends Controller
{
    public function index()
    {
        return response()->json([
            'message' => 'Hello from HelloController!'
        ]);
    }
    public function store(Request $request)
{
    // استرجاع البيانات من الطلب
    $data = $request->only(['name', 'email']);

    // ترجيع البيانات نفسها كرد
    return response()->json([
        'message' => 'Data received successfully!',
        'data' => $data
    ]);
}

}
