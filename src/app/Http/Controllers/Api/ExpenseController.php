<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Pocket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'pocket_id' => 'required|uuid|exists:user_pockets,id',
            'amount'    => 'required|integer|min:1',
            'notes'     => 'nullable|string|max:255',
        ]);

        $user = Auth::user();

        $pocket = Pocket::where('id', $validated['pocket_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        return DB::transaction(function () use ($validated, $user, $pocket) {
            $expense = Expense::create([
                'user_id'   => $user->id,
                'pocket_id' => $pocket->id,
                'amount'    => $validated['amount'],
                'notes'     => $validated['notes'] ?? null,
            ]);

            $pocket->decrement('balance', $validated['amount']);
            $pocket->refresh();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Berhasil menambahkan expense.',
                'data'    => [
                    'id'              => $expense->id,
                    'pocket_id'       => $pocket->id,
                    'current_balance' => $pocket->balance,
                ],
            ]);
        });
    }
}
