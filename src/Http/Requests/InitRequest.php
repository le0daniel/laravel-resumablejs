<?php

namespace le0daniel\LaravelResumableJs\Http\Requests;

final class InitRequest extends JsonRequest
{
    public function rules() {
        return [
            'handler' => 'required|string',
            'name' => 'required|string',
            'size' => 'required|integer|min:1',
            'type' => 'required|string',
            'payload' => 'nullable|array',
        ];
    }
}
