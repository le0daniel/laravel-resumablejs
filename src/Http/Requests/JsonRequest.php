<?php

namespace le0daniel\Laravel\ResumableJs\Http\Requests;

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
