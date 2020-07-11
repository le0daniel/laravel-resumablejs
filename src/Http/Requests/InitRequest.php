<?php

namespace le0daniel\Laravel\ResumableJs\Http\Requests;

final class InitRequest extends JsonRequest
{
    public function rules() {
        return [
            'name' => 'required|string',
            'size' => 'required|integer|min:1',
            'type' => 'required|string',
            'payload' => 'nullable|array',
        ];
    }
}
