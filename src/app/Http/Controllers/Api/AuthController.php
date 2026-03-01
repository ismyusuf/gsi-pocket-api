<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!$token = Auth::attempt($credentials)) {
            return response()->json([
                'status' => 401,
                'error' => true,
                'message' => 'Email atau password salah.',
                'data' => null
            ], 401);
        }

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Berhasil login.',
            'data' => [
                'token' => $token
            ]
        ]);
    }

    public function profile()
    {
        $user = auth('api')->user();

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Berhasil login.',
            'data' => [
                'full_name' => $user->full_name,
                'email' => $user->email,
            ]
        ]);
    }
}
