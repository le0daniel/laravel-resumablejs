<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Async and Queue
    |--------------------------------------------------------------------------
    |
    | As large files require longer to process, its a good idea to process them
    | in the background
    |
    | If set to true, you have the option to force process a file in the
    | background depending on your handler and files larger than 100Mb are
    | always processed in the background.
    |
    */
    'async' => false,
    'queue' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Upload Chunk size
    |--------------------------------------------------------------------------
    |
    | This is the size of each chunk in Bytes. All chunks must have exactly
    | this size except the last one (which usually is less).
    |
    */
    'chunk_size' => 10 * 1024 * 1024,

    /*
    |--------------------------------------------------------------------------
    | Tmp Directory to store the uploaded files
    |--------------------------------------------------------------------------
    |
    | This directory must exist and be writable. All chunks are stored there
    | during the upload process.
    */
    'tmp_directory' => storage_path('tmp'),

    /**
     * Request Keys.
     */
    'request_keys' => [
        'chunk_number' => 'resumableChunkNumber',
    ],


    /**
    |--------------------------------------------------------------------------
    | Handlers
    |--------------------------------------------------------------------------
    |
    | Specify a list of handlers. Handlers must extend the UploadHandler
    | {@see le0daniel\LaravelResumableJs\Contracts\UploadHandler}
    |
    */
    'handlers' => [
        //'basic' => YourHandler::class,
    ],


];
