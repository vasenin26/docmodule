<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => 'required|string|max:65535',
            'attachments_add' => ['sometimes', 'array'],
            'attachments_add.*' => ['integer', 'exists:page_versions,id'],
            'attachments_remove' => ['sometimes', 'array'],
            'attachments_remove.*' => ['integer', 'exists:page_versions,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Описание задачи обязательно для заполнения',
            'content.string' => 'Описание задачи должно быть текстом',
            'content.max' => 'Описание задачи не может превышать 65535 символов',
            'attachments_add.array' => 'attachments_add должен быть массивом',
            'attachments_add.*.integer' => 'ID версий должны быть числами',
            'attachments_add.*.exists' => 'Некоторые версии страниц не найдены',
            'attachments_remove.array' => 'attachments_remove должен быть массивом',
            'attachments_remove.*.integer' => 'ID версий должны быть числами',
            'attachments_remove.*.exists' => 'Некоторые версии страниц не найдены',
        ];
    }

    public function isResetChat(): bool
    {
        return (bool)($this->input('reset_chat') ?? true);
    }
}
