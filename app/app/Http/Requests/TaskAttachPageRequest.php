<?php

namespace App\Http\Requests;

use App\Models\PageVersion;
use App\Models\VersionDiffTask;
use Illuminate\Foundation\Http\FormRequest;

class TaskAttachPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var VersionDiffTask $task */
        $task = $this->route('task');
        if (!$task || !$this->user()) return false;
        if ((int)$task->created_by !== (int)$this->user()->id) return false;

        $versionId = (int) $this->input('page_version_id');
        if (!$versionId) return false;
        $pv = PageVersion::with('page')->find($versionId);
        return $pv && (int) $pv->page->project_id === (int) $task->project_id;
    }

    public function rules(): array
    {
        return [
            'page_version_id' => ['required', 'integer', 'exists:page_versions,id'],
        ];
    }
}


