<?php

namespace App\Http\Requests\Orchestrator;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectKeyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Авторизация через middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'public_key' => [
                'required',
                'string',
                'min:100', // Минимальная длина SSH ключа
                'max:10000', // Максимальная длина
                'regex:/^ssh-(rsa|ed25519|dss|ecdsa)/', // Начинается с ssh-
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'public_key.required' => 'Публичный ключ обязателен',
            'public_key.string' => 'Публичный ключ должен быть строкой',
            'public_key.min' => 'Публичный ключ слишком короткий',
            'public_key.max' => 'Публичный ключ слишком длинный',
            'public_key.regex' => 'Публичный ключ должен начинаться с ssh-rsa, ssh-ed25519, ssh-dss или ssh-ecdsa',
        ];
    }

    /**
     * Get the validated public key
     */
    public function getPublicKey(): string
    {
        return $this->validated('public_key');
    }
}
