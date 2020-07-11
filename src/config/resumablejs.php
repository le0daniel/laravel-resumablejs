<?php

use le0daniel\Laravel\ResumableJs\Upload\NativeFileCombiner;

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
    'async' => true,
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
    | Storage
    |--------------------------------------------------------------------------
    |
    | Specify the disk and folder to use.
    | It's important, that is must be a local drive.
    |
    */
    'storage' => [
        'disk' => 'local',
        'folder' => 'chunks',
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | Class used to combine all files together
    | Must implement FileCombiner
    |
    */
    'file_combiner' => NativeFileCombiner::class,

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    |
    | Disable mime type validation depending on system.
    */
    'validate_mime_type' => true,

    /**
     * Request Keys.
     */
    'request_keys' => [
        'chunk_number' => 'resumableChunkNumber'
    ],


    /*
    |--------------------------------------------------------------------------
    | Handlers
    |--------------------------------------------------------------------------
    |
    | Specify a list of handlers. Handlers must extend the HandlerContract
    | (le0daniel\Laravel\ResumableJs\Contracts\UploadHandler)
    |
    */
    'handlers' => [
        //'basic' => \le0daniel\Laravel\ResumableJs\Handlers\BasicHandler::class,
    ],


];
