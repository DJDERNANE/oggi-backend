<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\VisaController;
use App\Http\Controllers\API\VisaApplicationController;

Route::group(['middleware' => 'auth:sanctum'], function () {

    // auth routes 
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/me', [AuthController::class, 'me']);

    // visa application routes 
    Route::post('/visa-applications', [VisaApplicationController::class, 'store']);
    Route::get('/visa-applications', [VisaApplicationController::class, 'index']);
    // Route::get('/visa-applications/{id}', [VisaApplicationController::class, 'show']);
    // Route::get('/visa-applications/{id}/files', [VisaAplicationFileController::class, 'index']);
    // Route::post('/visa-applications/{id}/files', [VisaAplicationFileController::class, 'store']);
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
