<?php

namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;

class GetPageHierarchyRequest extends FormRequest
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
            'root_page_id' => 'nullable|integer|min:1',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'root_page_id.integer' => 'Root page ID must be an integer',
            'root_page_id.min' => 'Root page ID must be greater than 0',
        ];
    }

    /**
     * Get the root page ID from query parameters
     */
    public function getRootPageId(): ?int
    {
        $rootPageId = $this->input('root_page_id');
        return $rootPageId ? (int) $rootPageId : null;
    }
}
