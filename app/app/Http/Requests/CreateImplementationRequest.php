<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateImplementationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $techplane = $this->route('techplane');
        
        if (!$user || !$techplane) {
            return false;
        }
        
        // Загружаем связи если они не загружены
        if (!$techplane->relationLoaded('task')) {
            $techplane->load('task.pageVersion.page.project');
        }
        
        // Проверяем доступ через проект страницы
        return $techplane->task->pageVersion->page->project->canAccess($user);
    }

    public function rules(): array
    {
        return [
            // Нет полей для валидации, создание происходит автоматически
        ];
    }
}
