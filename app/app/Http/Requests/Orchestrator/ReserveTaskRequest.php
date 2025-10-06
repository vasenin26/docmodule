<?php

namespace App\Http\Requests\Orchestrator;

use Illuminate\Foundation\Http\FormRequest;

class ReserveTaskRequest extends FormRequest
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
            'reserve_seconds' => [
                'required',
                'integer',
                'min:1',
                'max:3600', // Максимум 1 час
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
            'reserve_seconds.required' => 'Время резервирования обязательно',
            'reserve_seconds.integer' => 'Время резервирования должно быть целым числом',
            'reserve_seconds.min' => 'Минимальное время резервирования - 1 секунда',
            'reserve_seconds.max' => 'Максимальное время резервирования - 3600 секунд (1 час)',
            'agent_uuid.required' => 'UUID воркера обязателен',
            'agent_uuid.uuid' => 'UUID воркера должен быть валидным UUID',
        ];
    }

    /**
     * Get the validated reserve seconds
     */
    public function getReserveSeconds(): int
    {
        return $this->validated('reserve_seconds');
    }

    /**
     * Get the validated agent UUID
     */
    public function getAgentUuid(): string
    {
        return $this->validated('agent_uuid');
    }
}
