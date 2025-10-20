<?php

namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;

class CreateSubtaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Авторизация через проверку agent_id в контроллере
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type' => [
                'required',
                'string',
                'min:1',
                'max:255',
            ],
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
            'type.required' => 'Task type is required',
            'type.string' => 'Task type must be a string',
            'type.min' => 'Task type cannot be empty',
            'type.max' => 'Task type cannot exceed 255 characters',
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
            'type' => 'task type',
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
     * Get the validated task type
     */
    public function getTaskType(): string
    {
        return $this->validated('type');
    }

    /**
     * Get the validated agent UUID
     */
    public function getAgentUuid(): string
    {
        return $this->validated('agent_uuid');
    }
}
