<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\IncomeController;
use App\Http\Controllers\Api\PocketController;

Route::post('auth/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('/auth/profile', [AuthController::class, 'profile']);
    Route::get('/pockets/total-balance', [PocketController::class, 'totalBalance']);
    Route::get('/pockets', [PocketController::class, 'index']);
    Route::post('/pockets', [PocketController::class, 'store']);
    Route::post('/pockets/{id}/create-report', [PocketController::class, 'createReport']);
    Route::post('/incomes', [IncomeController::class, 'store']);
    Route::post('/expenses', [ExpenseController::class, 'store']);
});
