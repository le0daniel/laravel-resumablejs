<?php
/**
 * Created by PhpStorm.
 * User: leodanielstuder
 * Date: 01.06.19
 * Time: 17:44
 */

namespace le0daniel\Laravel\ResumableJs\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\App;
use le0daniel\Laravel\ResumableJs\Contracts\UploadHandler;
use le0daniel\Laravel\ResumableJs\Models\FileUpload;
use le0daniel\Laravel\ResumableJs\Upload\CatFileCombiner;
use le0daniel\Laravel\ResumableJs\Upload\FileCombinerContract;
use Symfony\Component\Process\Process;

class CompleteAndProcessUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var FileUpload
     */
    public $fileUpload;
    protected $directory;

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
     * @return FileCombinerContract
     */
    protected function getFileCombiner(): FileCombinerContract
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
     * Handle the uploaded file
     *
     * @param bool $withReturn
     * @return array|null
     * @throws \Exception
     */
    public function handle(bool $withReturn = false)
    {
        $uploadedFile = $this->combineFiles();

        /** @var UploadHandler $handler */
        $handler = App::make($this->fileUpload->handler);

        if ($uploadedFile->getSize() !== $this->fileUpload->size) {
            unlink($uploadedFile->getRealPath());
            return null;
        }

        // Get the file mimetype
        $finfo = new \finfo(FILEINFO_MIME);
        $resource = fopen($uploadedFile->getRealPath(), 'r+');
        $mimetype = $finfo->buffer(fread($resource, 1024), FILEINFO_MIME_TYPE);
        fclose($resource);

        // Verify mime type
        if ($mimetype !== $this->fileUpload->type) {
            unlink($uploadedFile);
            return null;
        }

        // Let the handler do it's job
        $result = $handler->process($uploadedFile, $this->fileUpload);

        if ($withReturn) {
            return $result;
        }

        // Because it's async, broadcast
        $handler->broadcast($this->fileUpload, $result);
    }
}