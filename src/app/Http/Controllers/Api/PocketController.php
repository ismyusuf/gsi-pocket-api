<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pocket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PocketController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'initial_balance' => 'required|integer|min:0',
        ]);

        $user = Auth::user();

        $pocket = Pocket::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'balance' => $validated['initial_balance'],
        ]);

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Berhasil membuat pocket baru.',
            'data' => [
                'id' => $pocket->id,
            ],
        ]);
    }
}
