<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VisaApplicationController;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/storage/link', function () {
    $target = '/home/oggitrav/oggi-panel/storage/app/public';
    $link = '/home/oggitrav/public_html/storage';

    if (!file_exists($link)) {
        symlink($target, $link);
        return 'Symlink created successfully in public_html.';
    } else {
        return 'Symlink already exists.';
    }
});


Route::get('/', function () {
    return response()->json(['message' => 'Login page']);
})->name('login');
Route::get('/visa/download', [VisaApplicationController::class, 'download'])->name('visa.download');
Route::get('/visa-applications/{visaApplication}/download-all', [VisaApplicationController::class, 'downloadAllFiles'])
    ->name('visa.downloadAll');
