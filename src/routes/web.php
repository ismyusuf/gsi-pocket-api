<?php

use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name'    => 'GSI Pocket API',
        'version' => '1.0.0',
        'status'  => 'running',
    ]);
});

Route::get('/reports/{id}', [ReportController::class, 'stream']);
