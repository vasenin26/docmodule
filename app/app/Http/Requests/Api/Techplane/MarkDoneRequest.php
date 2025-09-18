<?php

namespace App\Http\Requests\Api\Techplane;

use Illuminate\Foundation\Http\FormRequest;

class MarkDoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mergeRequestUrl' => ['required', 'url', 'starts_with:http://,https://'],
        ];
    }
}
