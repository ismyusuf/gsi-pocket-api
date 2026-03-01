<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateReportJob;
use App\Models\Pocket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PocketController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $pockets = Pocket::where('user_id', $user->id)
            ->get()
            ->map(fn($pocket) => [
                'id'              => $pocket->id,
                'name'            => $pocket->name,
                'current_balance' => $pocket->balance,
            ]);

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Berhasil.',
            'data'    => $pockets,
        ]);
    }

    public function totalBalance()
    {
        $user = Auth::user();

        $total = Pocket::where('user_id', $user->id)->sum('balance');

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Berhasil menambahkan expense.',
            'data'    => [
                'total' => $total,
            ],
        ]);
    }

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

    public function createReport(Request $request, string $id)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:INCOME,EXPENSE',
            'date' => 'required|date_format:Y-m-d',
        ]);

        $user = Auth::user();

        $pocket = Pocket::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $timestamp = time();
        $uuid = Str::uuid()->toString();
        $filename = "{$uuid}-{$timestamp}";

        GenerateReportJob::dispatch(
            $pocket->id,
            $user->id,
            $validated['type'],
            $validated['date'],
            $filename,
        );

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Report sedang dibuat. Silahkan check berkala pada link berikut.',
            'data'    => [
                'link' => url("reports/{$filename}"),
            ],
        ]);
    }
}
