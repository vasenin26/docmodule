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
        
        if (!$implementation->relationLoaded('techplane')) {
            $implementation->load('techplane.task.pageVersion.page.project');
        }
        
        $task = $implementation->techplane?->task;
        if ($task && $task->project_id) {
            return \App\Models\Project::query()
                ->whereKey($task->project_id)
                ->first()?->canAccess($user) ?? false;
        }
        
        return $implementation->techplane->task->pageVersion?->page?->project?->canAccess($user) ?? false;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:4000'],
        ];
    }
}
