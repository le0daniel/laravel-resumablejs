<?php
/**
 * Created by PhpStorm.
 * User: leodanielstuder
 * Date: 01.06.19
 * Time: 17:44
 */

namespace le0daniel\Laravel\ResumableJs\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use le0daniel\Laravel\ResumableJs\Contracts\UploadHandler;
use le0daniel\Laravel\ResumableJs\Models\FileUpload;
use le0daniel\Laravel\ResumableJs\Upload\CatFileCombiner;
use le0daniel\Laravel\ResumableJs\Contracts\FileCombiner;

class CompleteAndProcessUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var FileUpload
     */
    public $fileUpload;
    protected $directory;

    /**
     * @var \SplFileInfo
     */
    protected $completeFile;

    /**
     * CompleteAndProcessUpload constructor.
     * @param FileUpload $fileUpload
     */
    public function __construct(FileUpload $fileUpload, string $directory)
    {
        $this->fileUpload = $fileUpload;
        $this->directory = $directory;
    }

    /**
     * @return FileCombiner
     */
    protected function getFileCombiner(): FileCombiner
    {
        return App::make(CatFileCombiner::class);
    }

    /**
     * Combines the files togethder
     *
     * @return \SplFileInfo
     * @throws \Exception
     */
    protected function combineFiles(): \SplFileInfo
    {
        $outputFileName = $this->directory . '/completed-upload';
        $chunks = [];
        for ($i = 1; $i <= $this->fileUpload->chunks; $i++) {
            $chunks[] = $this->directory . '/chunk-' . $i;
        }

        $success = $this->getFileCombiner()->combineFiles(
            $chunks,
            $outputFileName
        );

        if ($success) {
            foreach ($chunks as $file) {
                unlink($file);
            }
        } else {
            throw new \Exception('Failed to combine the chunks into one file. Combiner: ' . get_class($this->getFileCombiner()));
        }

        return new \SplFileInfo($outputFileName);
    }

    /**
     * Make sure the filesize is the same as in the init call
     *
     * @throws \Exception
     */
    protected function validateFileSizeOrFail()
    {
        if ($this->completeFile->getSize() !== $this->fileUpload->size) {
            throw new \Exception('Invalid file size');
        }
    }

    /**
     * Check if it is the same mime type as described by the initcall
     *
     * @throws \Exception
     */
    protected function validateMimeTypeOrFail()
    {
        // Get the file mimetype
        $finfo = new \finfo(FILEINFO_MIME);
        $resource = fopen($this->completeFile->getRealPath(), 'r+');
        $mimetype = $finfo->buffer(fread($resource, 1024), FILEINFO_MIME_TYPE);
        fclose($resource);

        // Verify mime type
        if ($mimetype !== $this->fileUpload->type) {
            throw new \Exception('Invalid mime type');
        }
    }

    /**
     * Handle the uploaded file
     *
     * @param bool $withReturn
     * @return array|boolean|null
     * @throws \Exception
     */
    public function handle(bool $withReturn = false)
    {
        try {
            $this->completeFile = $this->combineFiles();

            $this->validateFileSizeOrFail();
            $this->validateMimeTypeOrFail();

            /** @var UploadHandler $handler */
            $handler = App::make($this->fileUpload->handler);

            // Let the handler do it's job
            $result = $handler->process($this->completeFile, $this->fileUpload);
        } catch (\Exception $e) {
            Log::error($e->getMessage(),$e->getTrace());
            if (isset($this->completeFile) && file_exists($this->completeFile->getRealPath())) {
                unlink($this->completeFile->getRealPath());
            }
            return false;
        }

        // Delete the uploaded file
        unlink($this->completeFile->getRealPath());

        if ($withReturn) {
            return $result;
        }

        // Because it's async, broadcast
        $handler->broadcast($this->fileUpload, $result);
    }
}