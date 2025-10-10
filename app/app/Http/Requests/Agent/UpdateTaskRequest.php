<?php

namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'completed' => [
                'required',
                'boolean',
            ],
            'agent_uuid' => [
                'required',
                'string',
                'uuid',
                'max:36',
            ],
            'chat' => [
                'nullable',
                'array',
            ],
            'stats' => [
                'required',
                'array',
            ],
            'stats.prompt_tokens' => [
                'nullable',
                'integer',
                'min:0',
                'max:1000000', // Разумный лимит
            ],
            'stats.completion_tokens' => [
                'nullable',
                'integer',
                'min:0',
                'max:1000000',
            ],
            'stats.total_tokens' => [
                'nullable',
                'integer',
                'min:0',
                'max:2000000',
            ],
            'result' => [
                'nullable',
                'string',
                'max:16777215', // TEXT field limit
            ],
            // Прогресс заполнения контекста (0..1), мягкая валидация — клампим вручную
            'context_fill' => [
                'nullable',
                'numeric',
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
            'chat.nullable' => 'Chat must be an array or null',
            'chat.array' => 'Chat must be an array of messages',
            'chat.min' => 'At least one chat message is required',
            'chat.max' => 'Too many chat messages (max: 1000)',
            'chat.*.role.required' => 'Message role is required',
            'chat.*.role.in' => 'Message role must be user, assistant, or system',
            'chat.*.content.required' => 'Message content is required',
            'chat.*.content.max' => 'Message content is too long (max: 65535 characters)',
            'stats.required' => 'Token statistics are required',
            'stats.array' => 'Stats must be an object',
            'stats.*.integer' => 'Token count must be an integer',
            'stats.*.min' => 'Token count cannot be negative',
            'stats.*.max' => 'Token count is too large',
            'result.max' => 'Result is too long (max: 16MB)',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateAgentUuid($validator);
            $this->validateTokenStats($validator);
        });
    }

    /**
     * Валидация agent_uuid
     */
    private function validateAgentUuid($validator): void
    {
        $agentUuid = $this->input('agent_uuid');

        if ($agentUuid && !$this->isValidUuid($agentUuid)) {
            $validator->errors()->add('agent_uuid', 'Invalid UUID format');
        }
    }

    /**
     * Валидация статистики токенов
     */
    private function validateTokenStats($validator): void
    {
        $stats = $this->input('stats', []);

        // Проверяем консистентность токенов
        $prompt = $stats['prompt_tokens'] ?? null;
        $completion = $stats['completion_tokens'] ?? null;
        $total = $stats['total_tokens'] ?? null;

        if ($prompt !== null && $completion !== null && $total !== null) {
            if ($total < ($prompt + $completion)) {
                $validator->errors()->add('stats.total_tokens', 'Total tokens cannot be less than sum of prompt and completion tokens');
            }
        }
    }

    /**
     * Проверка валидности UUID
     */
    private function isValidUuid(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
    }

    /**
     * Get validated data with helper methods
     */
    public function getAgentUuid(): string
    {
        return $this->validated('agent_uuid');
    }

    public function getTaskId(): int
    {
        return (int) $this->route('id');
    }

    public function getChatMessages(): array
    {
        return $this->validated('chat') ?? [];
    }

    public function getTokenStats(): array
    {
        return $this->validated('stats');
    }

    public function getResult(): ?string
    {
        return $this->validated('result');
    }

    public function isCompleted(): bool
    {
        return $this->validated('completed');
    }

    /**
     * Получить значение context_fill с клампом в диапазон [0, 1]
     */
    public function getContextFill(): ?float
    {
        $raw = $this->input('context_fill');
        if ($raw === null || $raw === '') {
            return null;
        }
        $value = is_numeric($raw) ? (float)$raw : null;
        return $this->clampFloat($value);
    }

    /**
     * Мягкое ограничение значения в диапазон [0, 1]
     */
    private function clampFloat(?float $value): ?float
    {
        if ($value === null) {
            return null;
        }
        if ($value < 0.0) {
            return 0.0;
        }
        if ($value > 1.0) {
            return 1.0;
        }
        return $value;
    }
}
