<?php

namespace App\Http\Requests;

use App\Common\Enums\AgentTaskType;
use Illuminate\Foundation\Http\FormRequest;

class StoreGenerationModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'generation_type' => ['required', 'string', 'in:'.implode(',', AgentTaskType::values())],
            'model_id' => ['required', 'integer', 'exists:generation_models,id'],
        ];
    }
}


