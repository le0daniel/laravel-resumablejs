<?php
/**
 * Created by PhpStorm.
 * User: leodanielstuder
 * Date: 01.06.19
 * Time: 14:43
 */

namespace le0daniel\Laravel\ResumableJs\Contracts;


use Illuminate\Http\Request;
use le0daniel\Laravel\ResumableJs\Models\FileUpload;

interface UploadHandler
{
    /**
     * Return the middleware to load
     * @return array|null
     */
    public function middleware(): ?array;

    /**
     * Validate the upload of throw an exception
     *
     * @param FileUpload $fileUpload
     * @throws \Exception
     * @return void
     */
    public function validateOrFail(FileUpload $fileUpload, array $payload, Request $request): void;

    /**
     * Return the payload which should be added to the File upload
     * Ex.: If you upload an Image, save the user_id for processing later
     *
     * @param FileUpload $fileUpload
     * @param array $payload
     * @param Request $request
     * @return array|null
     */
    public function payload(FileUpload $fileUpload, array $payload, Request $request):?array;

    /**
     * Bool if the file should be processed async
     *
     * @return bool
     */
    public function processAsync(): bool;

    /**
     * Process the uploaded file
     *
     * @param \SplFileInfo $file
     * @param FileUpload $fileUpload
     * @return array
     */
    public function process(\SplFileInfo $file, FileUpload $fileUpload): array;

    /**
     * Broadcast the event on the correct channel when uploading async
     *
     * @param FileUpload $fileUpload
     * @param array $processedData
     * @return void
     */
    public function broadcast(FileUpload $fileUpload, array $processedData);
}