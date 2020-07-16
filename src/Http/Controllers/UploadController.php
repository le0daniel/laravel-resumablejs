<?php
/**
 * Created by PhpStorm.
 * User: leodanielstuder
 * Date: 01.06.19
 * Time: 14:35
 */

namespace le0daniel\LaravelResumableJs\Http\Controllers;

use Exception;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use le0daniel\LaravelResumableJs\Contracts\UploadHandler;
use le0daniel\LaravelResumableJs\Http\Requests\CompleteRequest;
use le0daniel\LaravelResumableJs\Http\Requests\InitRequest;
use le0daniel\LaravelResumableJs\Http\Requests\UploadRequest;
use le0daniel\LaravelResumableJs\Http\Responses\ApiResponse;
use le0daniel\LaravelResumableJs\Models\FileUpload;
use le0daniel\LaravelResumableJs\Upload\InvalidChunksException;
use le0daniel\LaravelResumableJs\Upload\UploadProcessingException;
use le0daniel\LaravelResumableJs\Upload\UploadService;

final class UploadController extends BaseController
{
    use ValidatesRequests;

    private UploadHandler $handler;
    private const INIT_REQUEST_NAME = 'resumablejs.init';

    public function __construct(Request $request)
    {
        $this->verifyHasHandlers();
        if ($this->isInitCall($request)) {
            $this->setHandler($request->get('handler', ''));
            $this->applyHandlerMiddleware();
        }
    }

    private function verifyHasHandlers(): void
    {
        if (empty(config('resumablejs.handlers', false))) {
            throw new \RuntimeException('No upload handlers (resumablejs.handlers) defined in your config.');
        }
    }

    private function isInitCall(Request $request): bool
    {
        return $request->route()->getName() === self::INIT_REQUEST_NAME;
    }

    private function applyHandlerMiddleware(): void
    {
        if ($middleware = $this->handler->middleware()) {
            $this->middleware($middleware);
        }
    }

    private function setHandler(string $handlerName): void
    {
        $handlers = config('resumablejs.handlers');

        if (!array_key_exists($handlerName, $handlers)) {
            abort(404, 'Handler not found.');
        }

        $this->handler = App::make($handlers[$handlerName]);
    }

    private function getUncompletedFileUpload(string $token): FileUpload
    {
        return FileUpload::where('token', '=', $token)
            ->where('is_complete', '=', 0)
            ->firstOrFail();
    }

    public function init(InitRequest $request, UploadService $manager): ApiResponse
    {
        try {
            return ApiResponse::successful(['token' => $manager->init($this->handler, $request)->token]);
        } catch (Exception $exception) {
            Log::debug('Validation failed.', ['exception' => $exception]);
            return ApiResponse::error('Input validation failed', 422);
        }
    }

    public function upload(UploadRequest $request, UploadService $manager)
    {
        $attributes = $request->validated();

        $manager->uploadChunk(
            $this->getUncompletedFileUpload($attributes['token']),
            $request->getChunkNumber(),
            $request->file('file')
        );

        return response('', 200);
    }

    public function complete(CompleteRequest $request, UploadService $manager): ApiResponse
    {
        $attributes = $request->validated();

        try {
            return ApiResponse::successful(
                $manager->completeUpload($this->getUncompletedFileUpload($attributes['token']))
            );
        } catch (InvalidChunksException $exception) {
            return ApiResponse::error('Could not locate the chunks. Did you upload all chunk files?', 422);
        } catch (UploadProcessingException $exception) {
            return ApiResponse::error($exception->getUserMessage() ?? 'Internal Error', 422);
        }
    }

}
