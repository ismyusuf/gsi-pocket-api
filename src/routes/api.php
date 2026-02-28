<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::post('auth/login', [AuthController::class, 'login']);
Route::middleware('auth:api')->get('/auth/profile', [AuthController::class, 'profile']);
