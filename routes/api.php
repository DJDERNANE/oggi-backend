<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\VisaController;

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/me', [AuthController::class, 'me']);
});
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/', function () {
    return 'Hello World';
});
Route::get('/destinations', [VisaController::class, 'destinations']);
Route::post('/destinations/total', [VisaController::class, 'totalVisaPrice']);
Route::post('/destinations/passengers', [VisaController::class, 'PassengersVisasInfo']);
Route::get('/destinations/{id}/visas', [VisaController::class, 'visasTypes']);
