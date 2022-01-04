<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadController;

Route::get('/', function () {
    return view('home');
});

Route::get('/upload', [UploadController::class, 'index']);
Route::get('/progress', [UploadController::class, 'progress']);

Route::post('/upload/file', [UploadController::class, 'uploadFileAndStoreInDatabase'])
    ->name('processFile');

Route::get('/progress/data', [UploadController::class, 'progressForCsvStoreProcess'])
    ->name('csvStoreProcess');