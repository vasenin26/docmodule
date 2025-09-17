<?php

namespace App\Http\Controllers;

use App\Http\Requests\PageSearchRequest;
use App\Models\PageVersion;
use Illuminate\Http\JsonResponse;

class PageSearchController extends Controller
{
    public function index(PageSearchRequest $request): JsonResponse
    {
        $q = $request->get('query');
        $limit = min((int)($request->get('limit', 20)), 100);

        $items = PageVersion::query()
            ->select(['page_versions.id as id', 'page_versions.title as title'])
            ->join('pages', 'page_versions.page_id', '=', 'pages.id')
            ->whereColumn('pages.version_id', 'page_versions.id')
            ->where(function ($w) use ($q) {
                $w->where('page_versions.title', 'like', "%{$q}%")
                  ->orWhere('page_versions.content', 'like', "%{$q}%");
            })
            ->orderByDesc('page_versions.created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'id' => (int)$row->id,
                'title' => (string)$row->title,
                'version' => null,
            ]);

        return response()->json(['data' => $items]);
    }
}


