<?php

namespace le0daniel\Laravel\ResumableJs\Http\Requests;

final class CheckRequest extends JsonRequest
{
    use InteractsWithChunkNumer;

    public function rules() {
        return [
            'token' => 'required|string|size:64|exists:fileuploads,token',
            $this->chunkNumberKey() => 'required|integer|min:1',
        ];
    }



}
