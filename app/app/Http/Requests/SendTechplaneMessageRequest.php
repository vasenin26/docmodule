<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendTechplaneMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $techplane = $this->route('techplane');
        
        if (!$user || !$techplane) {
            return false;
        }
        
        // Проверяем доступ через проект страницы
        return $techplane->task->pageVersion->page->project->canAccess($user);
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:4000'],
        ];
    }
}
