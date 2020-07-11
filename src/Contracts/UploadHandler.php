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
use le0daniel\LaravelResumableJs\Upload\UploadProcessingException;

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
    public function afterValidation(FileUpload $fileUpload, Request $request): void
    {
    }

    /**
     * Hook called as soon as the file has been combined successfully. This is a good place to
     * do some further checks like size, mimetype or a virus scan.
     *
     * @param \SplFileInfo $file
     * @param FileUpload $fileUpload
     */
    public function fileValidation(\SplFileInfo $file, FileUpload $fileUpload): void
    {
    }

    /**
     * Bool if the file should be processed async
     *
     * @return bool
     */
    public function processAsync(): bool
    {
        return false;
    }

    /**
     * Process the uploaded file
     * During the handling you may throw {@see UploadProcessingException} using
     * UploadProcessingException::with('user message', 'internal message') to show return messages
     * to the client.
     *
     * @param \SplFileInfo $file
     * @param FileUpload $fileUpload
     * @return null|array
     * @throws UploadProcessingException
     */
    abstract public function handle(\SplFileInfo $file, FileUpload $fileUpload): ?array;

    /**
     * Inform the client of a failure to process the File asynchronously.
     * Note, the exceptions are not filtered here. All exceptions are passed to the client.
     *
     * @param FileUpload $fileUpload
     * @param string $broadcastKey
     * @param \Exception $exception
     */
    public function broadcastFailedAsyncProcessing(
        FileUpload $fileUpload,
        string $broadcastKey,
        \Exception $exception
    ): void {
    }

    /**
     * Inform the client that the processing of the file with the key was done.
     *
     * @param FileUpload $fileUpload
     * @param string $broadcastKey
     * @param array|null $processedData
     */
    public function broadcastProcessedAsync(FileUpload $fileUpload, string $broadcastKey, ?array $processedData): void
    {
    }
}
