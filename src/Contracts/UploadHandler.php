<?php
/**
 * Created by PhpStorm.
 * User: leodanielstuder
 * Date: 01.06.19
 * Time: 14:43
 */

namespace le0daniel\LaravelResumableJs\Contracts;


use Illuminate\Http\Request;
use le0daniel\LaravelResumableJs\Models\FileUpload;

abstract class UploadHandler
{
    /**
     * Return the middleware to load on the init call.
     * By default, no middlewares are applied.
     * @return array|null
     */
    abstract public function middleware(): ?array;

    /**
     * Validation rules for the payload
     */
    abstract public function payloadRules(): ?array;

    /**
     * Perform some other checks after the validation is done.
     * This is also the place where you can add attributes to the payload.
     * You can use {$fileUpload->appendToPayload($key, $value)} for that.
     * @param FileUpload $fileUpload
     */
    public function afterValidation(FileUpload $fileUpload): void {

    }

    /**
     * Return the payload which should be added to the File upload
     * Ex.: If you upload an Image, save the user_id for processing later
     *
     * @param FileUpload $fileUpload
     * @param array $payload
     * @param Request $request
     * @return array|null
     */
    abstract public function payload(FileUpload $fileUpload, array $payload, Request $request):?array;

    /**
     * Bool if the file should be processed async
     *
     * @return bool
     */
    abstract public function processAsync(): bool;

    /**
     * Process the uploaded file
     *
     * @param \SplFileInfo $file
     * @param FileUpload $fileUpload
     * @return array
     */
    abstract public function process(\SplFileInfo $file, FileUpload $fileUpload): array;

    /**
     * Broadcast the event on the correct channel when uploading async
     *
     * @param FileUpload $fileUpload
     * @param string $broadcastKey
     * @param array $processedData
     * @return void
     */
    abstract public function broadcast(FileUpload $fileUpload, string $broadcastKey, array $processedData);
}
