<?php

namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Авторизация через проверку agent_id
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'agent_id' => [
                'required',
                'string',
                'uuid',
                'max:36',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'agent_id.required' => 'Agent ID is required',
            'agent_id.uuid' => 'Agent ID must be a valid UUID',
            'agent_id.max' => 'Agent ID cannot exceed 36 characters',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'agent_id' => 'agent identifier',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $agentId = $this->input('agent_id');
            
            // Дополнительная проверка формата UUID
            if ($agentId && !$this->isValidUuid($agentId)) {
                $validator->errors()->add('agent_id', 'Invalid UUID format');
            }
        });
    }

    /**
     * Проверка валидности UUID
     */
    private function isValidUuid(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
    }

    /**
     * Get the validated agent ID
     */
    public function getAgentId(): string
    {
        return $this->validated('agent_id');
    }
}
