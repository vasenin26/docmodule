<?php

namespace App\Http\Requests;

use App\Models\PageVersion;
use App\Models\VersionDiffTask;
use Illuminate\Foundation\Http\FormRequest;

class TaskDetachPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var VersionDiffTask $task */
        $task = $this->route('task');
        /** @var PageVersion $pageVersion */
        $pageVersion = $this->route('pageVersion');
        if (!$task || !$pageVersion || !$this->user()) return false;
        if ((int)$task->created_by !== (int)$this->user()->id) return false;
        return (int) $pageVersion->page->project_id === (int) $task->project_id;
    }

    public function rules(): array
    {
        return [];
    }
}


