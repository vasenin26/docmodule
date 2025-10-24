<?php

namespace App\Http\Requests;

use App\Common\Enums\AgentTaskType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseSummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period' => [
                'required',
                'string',
                Rule::in(['day', 'week', 'month'])
            ],
            'date_from' => [
                'nullable',
                'date',
                'before_or_equal:date_to'
            ],
            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from'
            ],
            'task_type' => [
                'nullable',
                'string',
                Rule::in(AgentTaskType::values())
            ],
            'project_id' => [
                'nullable',
                'integer',
                'exists:projects,id'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'period.in' => 'Период должен быть: day, week или month',
            'task_type.in' => 'Недопустимый тип задачи',
            'project_id.exists' => 'Проект не найден'
        ];
    }
}
