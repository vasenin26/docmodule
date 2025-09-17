<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskAttachPageRequest;
use App\Http\Requests\TaskDetachPageRequest;
use App\Models\PageVersion;
use App\Models\VersionDiffTask;
use Illuminate\Http\JsonResponse;

class TaskAttachmentController extends Controller
{
    public function store(TaskAttachPageRequest $request, VersionDiffTask $task): JsonResponse
    {
        $task->pageVersions()->syncWithoutDetaching([(int)$request->integer('page_version_id')]);
        return response()->json(['success' => true]);
    }

    public function destroy(TaskDetachPageRequest $request, VersionDiffTask $task, PageVersion $pageVersion): JsonResponse
    {
        $task->pageVersions()->detach($pageVersion->id);
        return response()->json(['success' => true]);
    }
}


