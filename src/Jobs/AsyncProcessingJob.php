<?php
/**
 * Created by PhpStorm.
 * User: leodanielstuder
 * Date: 01.06.19
 * Time: 17:44
 */

namespace le0daniel\LaravelResumableJs\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use le0daniel\LaravelResumableJs\Models\FileUpload;
use le0daniel\LaravelResumableJs\Upload\UploadService;

class AsyncProcessingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $fileUpload;
    protected $broadcastKey;

    /**
     * CompleteAndProcessUpload constructor.
     * @param FileUpload $fileUpload
     */
    public function __construct(FileUpload $fileUpload, string $broadcastKey)
    {
        $this->fileUpload = $fileUpload;
        $this->broadcastKey = $broadcastKey;
    }

    public function handle(UploadService $service)
    {
        $service->processAsync($this->fileUpload, $this->broadcastKey);
    }
}
