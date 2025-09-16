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
        
        if ($task->project_id) {
            return \App\Models\Project::query()
                ->whereKey($task->project_id)
                ->first()?->canAccess($user) ?? false;
        }
        
        return $task->pageVersion?->page?->project?->canAccess($user) ?? false;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:4000'],
        ];
    }
}
