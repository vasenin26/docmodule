<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendImplementationMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $implementation = $this->route('implementation');
        
        if (!$user || !$implementation) {
            return false;
        }
        
        // Загружаем связи если они не загружены
        if (!$implementation->relationLoaded('techplane')) {
            $implementation->load('techplane.task.pageVersion.page.project');
        }
        
        // Проверяем доступ через проект страницы
        return $implementation->techplane->task->pageVersion->page->project->canAccess($user);
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:4000'],
        ];
    }
}
