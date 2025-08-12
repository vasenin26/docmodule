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

        // Добавляем информацию о черновиках для каждой страницы
        $pages->getCollection()->transform(function ($page) {
            $page->hasActiveDraft = $page->hasActiveDraft();
            return $page;
        });

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
            'base_id' => null, // Для первой версии base_id = null
            'previous_version_id' => null, // Для первой версии previous_version_id = null
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
        $page = Page::with(['creator', 'children.creator', 'parent'])
            ->findOrFail($id);

        // Добавляем информацию о черновике
        $page->currentDraft = $page->getCurrentDraft();

        return Inertia::render('pages/Show', [
            'page' => $page,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $page = Page::findOrFail($id);
        $currentDraft = $page->getCurrentDraft();

        return Inertia::render('pages/Edit', [
            'page' => $page,
            'currentDraft' => $currentDraft,
            'hasActiveDraft' => $currentDraft !== null,
            'errors' => (object) [],
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
        $currentDraft = $page->getCurrentDraft();

        if ($currentDraft) {
            // Обновляем существующий черновик
            $currentDraft->update([
                'title' => $request->title,
                'content' => $request->content,
            ]);
            
            return redirect()->back()
                ->with('success', 'Черновик обновлен.');
        } else {
            // Создаем новый черновик
            $draft = $page->createDraft([
                'title' => $request->title,
                'content' => $request->content,
            ]);
            
            return redirect()->back()
                ->with('success', 'Черновик создан.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $page = Page::where('current', true)->findOrFail($id);

        // Удаляем все версии страницы
        $baseId = $page->base_id ?? $page->id;
        Page::where('base_id', $baseId)->delete();

        return redirect()->route('pages.index')
            ->with('success', 'Страница успешно удалена.');
    }

    /**
     * Показать версии страницы
     */
    public function versions(string $id)
    {
        $page = Page::findOrFail($id);

        // Получаем полную цепочку версий
        $versions = $page->getVersionChain();

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

        // Проверяем, что версия принадлежит той же цепочке
        $pageChain = $page->getVersionChain();
        $versionInChain = $pageChain->where('id', $versionId)->first();

        if (!$versionInChain) {
            return redirect()->back()->with('error', 'Версия не найдена в цепочке страницы.');
        }

        // Создаем новую версию на основе выбранной
        $newVersion = $page->createNewVersion([
            'title' => $version->title,
            'content' => $version->content,
        ]);

        return redirect()->route('pages.index')
            ->with('success', 'Версия страницы восстановлена.');
    }

    /**
     * Утвердить черновик
     */
    public function approveDraft(string $id)
    {
        $draft = Page::findOrFail($id);
        
        // Проверяем, является ли страница черновиком
        if (!$draft->isDraft()) {
            return redirect()->back()->with('error', 'Страница не является черновиком.');
        }
        
        // Утверждаем черновик
        $draft->approveDraft();
        
        return redirect()->route('pages.show', $draft->id)
            ->with('success', 'Черновик утвержден.');
    }

    /**
     * Получить данные черновика
     */
    public function getDraft(string $id)
    {
        $page = Page::where('current', true)->findOrFail($id);
        $draft = $page->getCurrentDraft();
        
        if (!$draft) {
            return response()->json(['error' => 'Черновик не найден'], 404);
        }
        
        return response()->json($draft);
    }

    /**
     * Удалить черновик
     */
    public function deleteDraft(string $id)
    {
        $page = Page::where('current', true)->findOrFail($id);
        $draft = $page->getCurrentDraft();
        
        if (!$draft) {
            return redirect()->back()->with('error', 'Черновик не найден.');
        }
        
        $draft->delete();
        
        return redirect('/')
            ->with('success', 'Черновик удален.');
    }
}
