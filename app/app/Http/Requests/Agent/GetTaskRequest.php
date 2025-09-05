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
            'agent_uuid' => [
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
            'agent_uuid.required' => 'Agent UUID is required',
            'agent_uuid.uuid' => 'Agent UUID must be a valid UUID',
            'agent_uuid.max' => 'Agent UUID cannot exceed 36 characters',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'agent_uuid' => 'agent identifier',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $agentUuid = $this->input('agent_uuid');
            
            // Дополнительная проверка формата UUID
            if ($agentUuid && !$this->isValidUuid($agentUuid)) {
                $validator->errors()->add('agent_uuid', 'Invalid UUID format');
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
     * Get the validated agent UUID
     */
    public function getAgentUuid(): string
    {
        return $this->validated('agent_uuid');
    }
}
