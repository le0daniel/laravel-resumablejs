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
use Illuminate\Support\Facades\Validator;
use le0daniel\LaravelResumableJs\Contracts\UploadHandler;
use le0daniel\LaravelResumableJs\Http\Requests\CheckRequest;
use le0daniel\LaravelResumableJs\Http\Requests\CompleteRequest;
use le0daniel\LaravelResumableJs\Http\Requests\InitRequest;
use le0daniel\LaravelResumableJs\Http\Requests\UploadRequest;
use le0daniel\LaravelResumableJs\Http\Responses\ApiResponse;
use le0daniel\LaravelResumableJs\Models\FileUpload;
use le0daniel\LaravelResumableJs\Upload\InvalidChunksException;
use le0daniel\LaravelResumableJs\Upload\UploadProcessingException;
use le0daniel\LaravelResumableJs\Upload\UploadService;
use le0daniel\LaravelResumableJs\Utility\Files;
use le0daniel\LaravelResumableJs\Utility\Tokens;

final class UploadController extends BaseController
{
    private const UPLOAD_INIT_URI = 'upload/init';
    use ValidatesRequests;

    private UploadHandler $handler;

    public function __construct(Request $request)
    {
        if (!$this->hasDefinedHandlers()) {
            abort('No handlers  defined', 422);
        }

        if ($this->isInitCall($request)) {
            $this->applyHandler(
                $this->getHandler($request->get('handler', ''))
            );
        }
    }

    private function hasDefinedHandlers(): bool
    {
        return !empty(config('resumablejs.handlers', false));
    }

    private function isInitCall(Request $request): bool
    {
        return $request->route()->uri === self::UPLOAD_INIT_URI;
    }

    private function applyHandler(UploadHandler $handler): void
    {
        $this->handler = $handler;
        if ($middleware = $handler->middleware()) {
            $this->middleware($middleware);
        }
    }

    private function getHandler(string $handlerName): UploadHandler
    {
        $handlers = config('resumablejs.handlers');

        if (!array_key_exists($handlerName, $handlers)) {
            abort(404, 'Handler not found.');
        }

        return App::make($handlers[$handlerName]);
    }

    private function getUncompletedFileUpload(string $token): FileUpload
    {
        return FileUpload::where('token', '=', $token)
            ->where('is_complete', '=', 0)
            ->firstOrFail();
    }

    private function getChunks(int $fileSize): int
    {
        return ceil($fileSize / config('resumablejs.chunk_size'));
    }

    private function createFileUpload(array $attributes): FileUpload
    {
        return new FileUpload(
            [
                'name' => basename($attributes['name']),
                'size' => (int)$attributes['name'],
                'type' => $attributes['type'],
                'extension' => Files::getExtension($attributes['name']),
                'chunks' => $this->getChunks($attributes['size']),
            ]
        );
    }

    private function validatePayload(array $payload): array
    {
        if (!$rules = $this->handler->payloadRules()) {
            return [];
        }

        return Validator::make($payload, $rules)->validate();
    }

    public function init(InitRequest $request): ApiResponse
    {
        $attributes = $request->validated();
        $attributes['payload'] = $this->validatePayload($attributes['payload']);

        $fileUpload = $this->createFileUpload($attributes);
        try {
            $this->handler->afterValidation($fileUpload);
        } catch (Exception $exception) {
            Log::debug('Validation failed.', ['exception' => $exception]);
            abort(422, 'Invalid File');
        }

        $fileUpload->token = Tokens::generateRandom();
        $fileUpload->handler = get_class($this->handler);
        $fileUpload->saveOrFail();

        return ApiResponse::successful(
            [
                'token' => $fileUpload->token,
            ]
        );
    }

    public function upload(UploadRequest $request, UploadService $manager)
    {
        $attributes = $request->validated();

        $manager->handleChunk(
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
            $response = $manager->completeUpload(
                $this->getUncompletedFileUpload($attributes['token'])
            );
        } catch (InvalidChunksException $exception) {
            return ApiResponse::error('Could not locate the chunks. Did you upload all chunk files?', 422);
        } catch (UploadProcessingException $exception) {
            return ApiResponse::error($exception->getUserMessage() ?? 'Internal Error', 422);
        }

        return ApiResponse::successful($response);
    }

}
