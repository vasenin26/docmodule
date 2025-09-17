<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendActualizationMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => 'required|string|max:4000',
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Сообщение обязательно для заполнения',
            'message.string' => 'Сообщение должно быть строкой',
            'message.max' => 'Сообщение не может превышать 4000 символов',
        ];
    }
}


