<?php

namespace le0daniel\LaravelResumableJs\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class JsonRequest extends FormRequest
{

    final public function validationData(): ?array
    {
        return $this->isJson()
            ? $this->json()->all()
            : $this->all();
    }

}
