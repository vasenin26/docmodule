<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendTaskMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $task = $this->route('task');
        
        if (!$user || !$task) {
            return false;
        }
        
        // Проверяем доступ через проект страницы
        return $task->pageVersion->page->project->canAccess($user);
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:4000'],
        ];
    }
}
