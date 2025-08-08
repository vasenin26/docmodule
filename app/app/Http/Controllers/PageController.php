<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Page::where('current', true)
            ->with(['creator', 'children.creator']);

        // Фильтрация по родительской странице
        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        } else {
            $query->whereNull('parent_id');
        }

        // Поиск по названию и содержимому
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $pages = $query->orderBy('created_at', 'desc')->paginate(20);

        return Inertia::render('pages/Index', [
            'pages' => $pages,
            'filters' => $request->only(['search', 'parent_id']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $parentPage = null;
        if ($request->has('parent_id')) {
            $parentPage = Page::where('current', true)
                ->where('id', $request->parent_id)
                ->first();
        }

        return Inertia::render('pages/Create', [
            'parentPage' => $parentPage,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'parent_id' => 'nullable|exists:pages,id',
        ]);

        $page = Page::create([
            'title' => $request->title,
            'content' => $request->content,
            'created_by' => Auth::id(),
            'parent_id' => $request->parent_id,
            'current' => true,
        ]);

        return redirect()->route('pages.index')
            ->with('success', 'Страница успешно создана.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $page = Page::where('current', true)
            ->with(['creator', 'children.creator', 'parent'])
            ->findOrFail($id);

        return Inertia::render('pages/Show', [
            'page' => $page,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $page = Page::where('current', true)->findOrFail($id);

        return Inertia::render('pages/Edit', [
            'page' => $page,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
        ]);

        $page = Page::where('current', true)->findOrFail($id);

        // Создаем новую версию страницы
        $newVersion = $page->createNewVersion([
            'title' => $request->title,
            'content' => $request->content,
        ]);

        return redirect()->route('pages.index')
            ->with('success', 'Страница успешно обновлена.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $page = Page::where('current', true)->findOrFail($id);

        // Удаляем все версии страницы
        Page::where('base_id', $page->base_id ?? $page->id)->delete();

        return redirect()->route('pages.index')
            ->with('success', 'Страница успешно удалена.');
    }

    /**
     * Показать версии страницы
     */
    public function versions(string $id)
    {
        $page = Page::findOrFail($id);
        $baseId = $page->base_id ?? $page->id;

        $versions = Page::where('base_id', $baseId)
            ->orWhere('id', $baseId)
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('pages/Versions', [
            'page' => $page,
            'versions' => $versions,
        ]);
    }

    /**
     * Восстановить версию страницы
     */
    public function restore(string $id, string $versionId)
    {
        $page = Page::findOrFail($id);
        $version = Page::findOrFail($versionId);

        // Создаем новую версию на основе выбранной
        $newVersion = $page->createNewVersion([
            'title' => $version->title,
            'content' => $version->content,
        ]);

        return redirect()->route('pages.index')
            ->with('success', 'Версия страницы восстановлена.');
    }
}
