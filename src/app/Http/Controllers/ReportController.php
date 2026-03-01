<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function stream(Request $request, string $id): BinaryFileResponse
    {
        $path = storage_path("app/reports/{$id}.xlsx");

        abort_unless(file_exists($path), 404, 'Report not found.');

        return response()->download($path, "{$id}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
