<?php
/**
 * Created by PhpStorm.
 * User: leodanielstuder
 * Date: 01.06.19
 * Time: 16:23
 */

namespace le0daniel\Laravel\ResumableJs\Upload;


use Faker\Provider\File;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use le0daniel\Laravel\ResumableJs\Contracts\UploadHandler;
use le0daniel\Laravel\ResumableJs\Jobs\CompleteAndProcessUpload;
use le0daniel\Laravel\ResumableJs\Models\FileUpload;
use League\Flysystem\Adapter\Local;

class Manager
{
    /** @var string */
    protected $diskName;
    /** @var Filesystem */
    protected $disk;

    /** @var string */
    protected $path;

    /**
     * Manager constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->diskName = config('resumablejs.storage.disk');
        $this->disk = \Storage::disk($this->diskName);
        $this->path = config('resumablejs.storage.folder');

        if (!$this->disk->getAdapter() instanceof Local) {
            throw new \Exception('A local disk is required for the Chunk uploading feature');
        }
    }

    /**
     * @return string
     */
    protected function getChunkPath(string $token, int $chunkNumber): string
    {
        return $this->getChunkDirectory($token) . '/chunk-' . $chunkNumber;
    }

    /**
     * @param string $token
     * @param bool $absolute
     * @return string
     */
    protected function getChunkDirectory(string $token, bool $absolute = false): string
    {
        $path = $this->path . '/' . $token;

        if ($absolute) {
            return $this->disk->getAdapter()->getPathPrefix() . '/' . $path;
        }
        return $path;
    }

    /**
     * @param FileUpload $fileUpload
     * @return bool
     */
    protected function validateChunks(FileUpload $fileUpload): bool
    {
        $chunksOkey = true;
        $currentChunk = 0;
        while ($currentChunk < $fileUpload->chunks) {
            $currentChunk++;

            if (!$this->hasCompletedChunk($fileUpload, $currentChunk)) {
                $chunksOkey = false;
                break;
            }
        }

        return $chunksOkey;
    }

    /**
     * Check if the chunk was completed already
     *
     * @param FileUpload $fileUpload
     * @param int $chunkNumber
     * @return bool
     */
    public function hasCompletedChunk(FileUpload $fileUpload, int $chunkNumber): bool
    {
        $pathName = $this->getChunkPath($fileUpload->token, $chunkNumber);

        if (!$this->disk->exists($pathName)) {
            return false;
        }

        $fileSize = $this->disk->size($pathName);
        $chunkSize = config('resumablejs.chunk_size');

        // Bigger than allowed
        if ($fileSize > $chunkSize) {
            return false;
        }

        // Chunk size mismatch
        if ($chunkNumber < $fileUpload->chunks && $fileSize !== $chunkSize) {
            return false;
        }

        return true;
    }

    /**
     * @param FileUpload $fileUpload
     * @param int $chunkNumber
     * @param UploadedFile $file
     * @throws \Exception
     */
    public function handleChunk(FileUpload $fileUpload, int $chunkNumber, UploadedFile $file)
    {
        $filename = $this->getChunkPath($fileUpload->token, $chunkNumber);

        if (!$file->isValid()) {
            throw new \Exception('Invalid file');
        }

        if ($this->hasCompletedChunk($fileUpload, $chunkNumber)) {
            throw new \Exception('Already uploaded');
        }

        if ($this->disk->exists($filename)) {
            $this->disk->delete($filename);
        }

        $file->storeAs(
            dirname($filename),
            basename($filename),
            $this->diskName
        );
    }

    /**
     * @param FileUpload $fileUpload
     * @return array
     * @throws \Throwable
     */
    public function process(FileUpload $fileUpload): array
    {
        $fileUpload->is_complete = true;
        $fileUpload->saveOrFail();

        /** @var UploadHandler $handler */
        $handler = App::make($fileUpload->handler);
        $async = false;

        // Handle async if enabled
        if (config('resumablejs.async', false) && ($handler->processAsync() || $fileUpload->size > (100 * 1024 * 1024))) {
            $async = true;
        }

        // Some chunks are not valid
        if (!$this->validateChunks($fileUpload)) {
            return [
                'success' => false,
            ];
        }

        /* Process Sync */
        $processerJob = new CompleteAndProcessUpload(
            $fileUpload,
            $this->getChunkDirectory($fileUpload->token, true)
        );

        /* Process Async */
        if ($async) {
            return $this->dispatchAndReturn($processerJob);
        }

        $result = $processerJob->handle(true);

        return [
            'success' => is_array($result),
            'async' => false,
            'data' => $result
        ];
    }

    /**
     * Dispatch the job and return the correct response
     *
     * @param CompleteAndProcessUpload $job
     * @return array
     */
    protected function dispatchAndReturn(CompleteAndProcessUpload $job): array
    {
        dispatch($job)
            ->onQueue(
                config('resumablejs.queue', 'default')
            );

        // Return the result
        return [
            'success' => true,
            'async' => true,
            'broadcastKey' => $job->getBroadcastKey(),
        ];
    }

}