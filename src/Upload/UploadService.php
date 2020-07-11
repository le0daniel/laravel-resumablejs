<?php
/**
 * Created by PhpStorm.
 * User: leodanielstuder
 * Date: 01.06.19
 * Time: 16:23
 */

namespace le0daniel\LaravelResumableJs\Upload;

use Illuminate\Http\UploadedFile;
use le0daniel\LaravelResumableJs\Contracts\UploadHandler;
use le0daniel\LaravelResumableJs\Jobs\AsyncProcessingJob;
use le0daniel\LaravelResumableJs\Models\FileUpload;
use le0daniel\LaravelResumableJs\Utility\Files;
use le0daniel\LaravelResumableJs\Utility\Resources;
use le0daniel\LaravelResumableJs\Utility\Tokens;
use RuntimeException;
use SplFileInfo;

final class UploadService
{
    private function verifyChunks(FileUpload $fileUpload): void
    {
        $currentChunk = 1;
        while ($currentChunk <= $fileUpload->chunks) {
            if (!Files::hasTmpChunkFile($fileUpload->token, $currentChunk)) {
                throw new InvalidChunksException('Chunk file could not be located.');
            }
            $currentChunk++;
        }
    }

    private function getChunks(FileUpload $fileUpload): array
    {
        $chunks = [];
        $currentChunk = 1;
        while ($currentChunk <= $fileUpload->chunks) {
            $chunks[] = Files::getChunkFileName($fileUpload->token, $currentChunk);
            $currentChunk++;
        }

        return $chunks;
    }

    public function handleChunk(FileUpload $fileUpload, int $chunkNumber, UploadedFile $file)
    {
        if (!$file->isValid()) {
            throw new RuntimeException('Invalid file');
        }

        $filename = Files::getTmpChunkFileForWriting($fileUpload->token, $chunkNumber);
        Resources::combine($file->getRealPath(), $filename);
    }

    private function shouldProcessAsync(UploadHandler $handler, FileUpload $fileUpload): bool
    {
        return
            config('resumablejs.async', false)
            && (
                $handler->processAsync() || $fileUpload->size > (100 * 1024 * 1024)
            );
    }

    private function combineChunks(FileUpload $fileUpload): string
    {
        $combinedFile = Files::tmp('tmp');
        $chunks = $this->getChunks($fileUpload);

        Resources::combine($chunks, $combinedFile);
        Files::deleteIfExists(...$chunks);

        return $combinedFile;
    }

    private function process(UploadHandler $handler, FileUpload $fileUpload): array
    {
        $uploadedFile = new SplFileInfo($this->combineChunks($fileUpload));

        try {
            $handler->fileValidation($uploadedFile, $fileUpload);
            $response = $handler->handle($uploadedFile, $fileUpload);
        } catch (\Exception $exception) {
            Files::deleteIfExists($uploadedFile);
            throw $exception;
        }

        Files::deleteIfExists($uploadedFile);
        return $response ?? [];
    }

    private function dispatchAsync(UploadHandler $handler, FileUpload $fileUpload): array
    {
        $broadcastingKey = sprintf('upload-%s-%d', Tokens::generateRandom(16), $fileUpload->id);
        $handler->broadcastWillProcessAsync($fileUpload, $broadcastingKey);
        dispatch(new AsyncProcessingJob($fileUpload, $broadcastingKey))
            ->onQueue(config('resumablejs.queue'));
        return [
            'async' => true,
            'broadcasting_key' => $broadcastingKey,
        ];
    }

    public function processAsync(FileUpload $fileUpload, string $broadcastKey): void
    {
        /** @var UploadHandler $handler */
        $handler = app()->make($fileUpload->handler);

        try {
            $response = $this->process($handler, $fileUpload);
            $handler->broadcastProcessedAsync($fileUpload, $broadcastKey, $response);
        } catch (\Exception $exception) {
            $handler->broadcastFailedAsyncProcessing($fileUpload, $broadcastKey, $exception);
            throw $exception;
        }
    }

    public function completeUpload(FileUpload $fileUpload): array
    {
        $fileUpload->is_complete = true;
        $fileUpload->saveOrFail();

        // Some chunks are not valid
        $this->verifyChunks($fileUpload);

        /** @var UploadHandler $handler */
        $handler = app()->make($fileUpload->handler);
        return $this->shouldProcessAsync($handler, $fileUpload)
            ? $this->dispatchAsync($handler, $fileUpload)
            : $this->process($handler, $fileUpload);
    }

}
