<?php

namespace App\Http\Requests\Prompt;

use App\Common\Enums\PromptType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePromptRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');
        
        // Если project - это модель (через route model binding)
        if (is_object($project) && isset($project->owner_id)) {
            return $project->owner_id === $this->user()->id;
        }
        
        // Если project - это ID (строка/число)
        if (is_string($project) || is_numeric($project)) {
            $projectModel = \App\Models\Project::find($project);
            return $projectModel && $projectModel->owner_id === $this->user()->id;
        }
        
        return false;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(PromptType::class)],
            'content' => ['required', 'string', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Тип промпта обязателен',
            'type.enum' => 'Неверный тип промпта',
            'content.required' => 'Содержимое промпта не может быть пустым',
            'content.max' => 'Содержимое промпта не может превышать 10000 символов',
        ];
    }
}
