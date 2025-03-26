<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VisaApplicationController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/login', function () {
    return response()->json(['message' => 'Login page']);
})->name('login');
Route::get('/visa/download', [VisaApplicationController::class, 'download'])->name('visa.download');
Route::get('/visa-applications/{visaApplication}/download-all', [VisaApplicationController::class, 'downloadAllFiles'])
    ->name('visa.downloadAll');
