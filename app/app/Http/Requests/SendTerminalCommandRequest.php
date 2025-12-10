<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendTerminalCommandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Команда не может быть пустой',
            'message.string' => 'Команда должна быть строкой',
            'message.min' => 'Команда не может быть пустой',
        ];
    }
}

