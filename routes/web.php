<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VisaApplicationController;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/storage/link', function (){
    $targetFolder = '/home/oggitrav/oggi-panel/storage/app/public';
    $linkFolder = '/home/oggitrav/oggi-panel/public/storage';
    symlink($targetFolder,$linkFolder);
    echo 'Symlink process successfully completed';
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
