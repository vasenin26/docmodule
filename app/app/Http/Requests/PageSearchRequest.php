<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PageSearchRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'min:1', 'max:200'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}


