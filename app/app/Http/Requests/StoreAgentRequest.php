<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAgentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $project = $this->route('project');
        return $project && $project->canAccess($this->user());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'min:3',
                Rule::unique('agents')->where(function ($query) {
                    return $query->where('project_id', $this->route('project')->id);
                }),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Название агента обязательно',
            'name.string' => 'Название агента должно быть строкой',
            'name.max' => 'Название агента не может превышать 255 символов',
            'name.min' => 'Название агента должно содержать минимум 3 символа',
            'name.unique' => 'Агент с таким названием уже существует в проекте',
        ];
    }
}
