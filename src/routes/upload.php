<?php

\Illuminate\Support\Facades\Route::namespace('le0daniel\\Laravel\\ResumableJs\\Http\\Controllers')
    ->prefix('upload')
    ->group(function(){

        \Illuminate\Support\Facades\Route::post('init','UploadController@init');

        \Illuminate\Support\Facades\Route::get('','UploadController@check');
        \Illuminate\Support\Facades\Route::post('','UploadController@upload');

        \Illuminate\Support\Facades\Route::post('complete','UploadController@complete');
    });
