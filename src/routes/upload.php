<?php

use Illuminate\Support\Facades\Route;
use le0daniel\Laravel\ResumableJs\Http\Controllers\UploadController;

Route::
prefix('upload')
    ->group(
        static function () {
            Route::post('init', [UploadController::class, 'init']);

            Route::get('', [UploadController::class, 'check']);
            Route::post('', [UploadController::class, 'upload']);

            Route::post('complete', [UploadController::class, 'complete']);
        }
    );
