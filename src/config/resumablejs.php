<?php

return [

    /**
     * Enable async processing, will queue to large files or
     * if the handler says so
     */
    'async' => true,
    'queue' => 'default',

    /**
     * The size of the chunks.
     * Only the last chunk is allowed to be smaller.
     */
    'chunk_size' => 10 * 1024 * 1024,

    /**
     * Where to store the chunks
     */
    'storage' => [
        'disk' => 'local',
        'folder' => 'chunks',
    ],

    /**
     * Upload Handlers
     * ----------------------------------------------------------------------
     * Each handler must implement the UploadHandler Interface.
     * The name of the handler, which is used to process the file must be
     * given on the upload request.
     */
    'handlers' => [
        'basic' => \le0daniel\Laravel\ResumableJs\Handlers\BasicHandler::class,
    ],


];