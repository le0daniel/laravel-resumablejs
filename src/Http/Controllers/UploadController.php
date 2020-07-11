<?php
/**
 * Created by PhpStorm.
 * User: leodanielstuder
 * Date: 01.06.19
 * Time: 14:35
 */

namespace le0daniel\Laravel\ResumableJs\Http\Controllers;

use Exception;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use le0daniel\Laravel\ResumableJs\Contracts\UploadHandler;
use le0daniel\Laravel\ResumableJs\Http\Requests\CheckRequest;
use le0daniel\Laravel\ResumableJs\Http\Requests\CompleteRequest;
use le0daniel\Laravel\ResumableJs\Http\Requests\InitRequest;
use le0daniel\Laravel\ResumableJs\Http\Requests\UploadRequest;
use le0daniel\Laravel\ResumableJs\Http\Responses\ApiResponse;
use le0daniel\Laravel\ResumableJs\Models\FileUpload;
use le0daniel\Laravel\ResumableJs\Upload\Manager;
use le0daniel\Laravel\ResumableJs\Utility\Files;
use le0daniel\Laravel\ResumableJs\Utility\Tokens;

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

    public function check(CheckRequest $request, Manager $manager)
    {
        $attributes = $request->validated();
        $complete = $manager->hasCompletedChunk(
            $this->getUncompletedFileUpload($attributes['token']),
            $request->getChunkNumber()
        );

        return response('', $complete ? 200 : 204);
    }

    public function upload(UploadRequest $request, Manager $manager)
    {
        $attributes = $request->validated();

        $manager->handleChunk(
            $this->getUncompletedFileUpload($attributes['token']),
            $request->getChunkNumber(),
            $request->file('file')
        );

        return response('', 200);
    }

    public function complete(CompleteRequest $request, Manager $manager)
    {
        $attributes = $request->validated();
        return $manager->process(
            $this->getUncompletedFileUpload($attributes['token'])
        );
    }

}
