<?php

namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;

class GetPageChildrenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Авторизация через JWT middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id' => 'required|integer|min:1',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'id.required' => 'Page ID is required',
            'id.integer' => 'Page ID must be an integer',
            'id.min' => 'Page ID must be greater than 0',
        ];
    }

    /**
     * Get the page ID from route
     */
    public function getPageId(): int
    {
        return (int) $this->route('id');
    }
}
