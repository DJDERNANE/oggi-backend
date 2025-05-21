<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VisaApplicationController;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/storage/link', function () {
    $target = storage_path('app/public');
    $link = public_path('storage');

    if (!file_exists($link)) {
        symlink($target, $link);
        return 'Symlink created';
    } else {
        return 'Symlink already exists';
    }
});


Route::get('/file-preview', function () {
    $path = 'visas/01JVTF6KN6SWA91SXQQA60G5MQ.pdf';

    if (!Storage::disk('public')->exists($path)) {
        abort(404);
    }

    return response()->file(storage_path("app/public/{$path}"));
});

Route::get('/login', function () {
    return response()->json(['message' => 'Login page']);
})->name('login');
Route::get('/visa/download', [VisaApplicationController::class, 'download'])->name('visa.download');
Route::get('/visa-applications/{visaApplication}/download-all', [VisaApplicationController::class, 'downloadAllFiles'])
    ->name('visa.downloadAll');
