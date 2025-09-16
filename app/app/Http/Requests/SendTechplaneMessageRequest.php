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
        
        $task = $techplane->task;
        if ($task && $task->project_id) {
            return \App\Models\Project::query()
                ->whereKey($task->project_id)
                ->first()?->canAccess($user) ?? false;
        }
        
        return $techplane->task->pageVersion?->page?->project?->canAccess($user) ?? false;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:4000'],
        ];
    }
}
