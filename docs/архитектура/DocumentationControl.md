# DocumentationControl - Архитектура управления документацией

## Обзор

`DocumentationControl` - это центральный сервис оркестрации для управления процессами документации в системе. Он реализует принцип разделения ответственности и обеспечивает централизованное управление сложными бизнес-процессами.

## Архитектурные принципы

### 1. Разделение ответственности
- **DocumentationControl** - только оркестрация сложных процессов
- **DraftService** - управление черновиками
- **TaskService** - управление задачами
- **PageContextService** - получение данных о страницах (без изменений)

### 2. Dependency Injection
Все зависимости инжектируются через конструкторы, что обеспечивает:
- Лучшую тестируемость
- Слабое связывание компонентов
- Легкость замены реализаций

### 3. Типобезопасность
Использование DTO обеспечивает строгую типизацию данных и предотвращает ошибки на этапе компиляции.

## Компоненты архитектуры

### DTO (Data Transfer Objects)

#### PageDataDTO
```php
class PageDataDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $content,
        public readonly array $files = []
    ) {}
}
```
- Инкапсулирует данные страницы
- Обеспечивает иммутабельность через `readonly`
- Предоставляет методы `fromArray()` и `toArray()`

#### DraftApprovalResultDTO
```php
class DraftApprovalResultDTO
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly bool $taskCreated = false,
        public readonly ?int $taskId = null,
        public readonly ?string $taskError = null
    ) {}
}
```
- Представляет результат утверждения черновика
- Включает информацию о создании задачи
- Статические методы для создания типизированных результатов

#### PageAggregateDTO
```php
class PageAggregateDTO
{
    public function __construct(
        public readonly Page $page,
        public readonly ?PageVersion $currentDraft,
        public readonly bool $hasActiveDraft,
        public readonly bool $canCreateTask,
        public readonly ?Actualization $actualizationInfo,
        public readonly bool $hasActiveActualization,
        public readonly bool $isActualized
    ) {}
}
```
- Агрегирует все данные о странице в одном объекте
- Упрощает передачу данных между слоями

### Интерфейсы

#### DocumentationControlInterface
```php
interface DocumentationControlInterface
{
    public function updatePageWithDraftLogic(Page $page, PageDataDTO $data): PageVersion;
    public function approveDraftWithTask(Page $page, bool $createTask = false): DraftApprovalResultDTO;
    public function getCurrentPageAggregate(Page $page): PageAggregateDTO;
}
```

#### DraftServiceInterface
```php
interface DraftServiceInterface
{
    public function createDraft(Page $page, PageDataDTO $data): PageVersion;
    public function getCurrentDraft(Page $page): ?PageVersion;
    public function deleteDraft(Page $page): bool;
    public function hasActiveDraft(Page $page): bool;
    public function approveDraft(Page $page, PageVersion $draft): void;
}
```

#### TaskServiceInterface
```php
interface TaskServiceInterface
{
    public function createTaskForPage(Page $page, ?int $userId = null): PageDiffDescription;
    public function canCreateTaskForPage(Page $page): bool;
}
```

### Сервисы

#### DocumentationControl
Основной сервис оркестрации, который:
- Координирует работу между DraftService и TaskService
- Обеспечивает логирование всех операций
- Обрабатывает ошибки и возвращает структурированные результаты

#### DraftService
Сервис управления черновиками:
- Создание и обновление черновиков
- Получение текущего черновика
- Удаление черновиков
- Утверждение черновиков

#### TaskService
Сервис управления задачами:
- Создание задач для страниц
- Проверка возможности создания задач
- Делегирует работу к TaskManagementService

## Использование в контроллерах

### PageController
```php
class PageController extends Controller
{
    public function __construct(
        protected DocumentationControlInterface $documentationControl,
        protected DraftServiceInterface $draftService,
        protected TaskServiceInterface $taskService
    ) {}

    public function update(UpdatePageRequest $request, string $id)
    {
        $pageData = PageDataDTO::fromArray($request->validated());
        $draft = $this->documentationControl->updatePageWithDraftLogic($page, $pageData);
        
        return redirect()->back()
            ->with('success', $page->hasActiveDraft() ? 'Черновик обновлен.' : 'Черновик создан.');
    }
}
```

### ActualizationController
```php
class ActualizationController extends Controller
{
    public function __construct(
        protected ActualizationService $actualizationService,
        protected DraftServiceInterface $draftService
    ) {}

    public function store(StoreActualizationRequest $request, Page $page)
    {
        $pageData = new PageDataDTO(
            title: $page->title ?? '',
            content: $page->content ?? '',
            files: $page->files ?? []
        );
        
        $draft = $this->draftService->createDraft($page, $pageData);
        $actualization = $this->actualizationService->initiate($page, $request->user());
        
        return response()->json(['success' => true]);
    }
}
```

## Преимущества новой архитектуры

### 1. Улучшенная тестируемость
- Каждый сервис можно тестировать изолированно
- Легко создавать моки для зависимостей
- Четкое разделение ответственности

### 2. Лучшая производительность
- Зависимости разрешаются один раз при создании контроллера
- Нет повторных вызовов `app()` в методах
- Кэширование разрешенных зависимостей

### 3. Соответствие принципам SOLID
- **Single Responsibility**: каждый сервис отвечает за одну область
- **Open/Closed**: легко расширять функциональность
- **Dependency Inversion**: зависимости от абстракций, а не от конкретных классов

### 4. Улучшенная обработка ошибок
- Структурированные результаты через DTO
- Централизованное логирование
- Четкие сообщения об ошибках

### 5. Типобезопасность
- IDE предоставляет автодополнение
- Ошибки типов обнаруживаются на этапе компиляции
- Лучшая документация кода

## Логирование

Все важные операции логируются:

```php
Log::info('Creating draft for page', ['page_id' => $page->id]);
Log::info('Approving draft with task', ['page_id' => $page->id, 'create_task' => $createTask]);
Log::info('Task created for approved draft', ['task_id' => $task->id]);
```

## Регистрация сервисов

Сервисы регистрируются в `AppServiceProvider`:

```php
$this->app->bind(DraftServiceInterface::class, DraftService::class);
$this->app->bind(TaskServiceInterface::class, TaskService::class);
$this->app->bind(DocumentationControlInterface::class, DocumentationControl::class);
```

## Тестирование

### Unit-тесты
- Тесты для каждого сервиса изолированно
- Моки для зависимостей
- Тесты для DTO классов

### Feature-тесты
- Тесты интеграции между компонентами
- Тесты API endpoints
- Тесты полных пользовательских сценариев

## Миграция с старой архитектуры

### Что изменилось
1. Логика версионирования вынесена из контроллеров в сервисы
2. Добавлены DTO для типизации данных
3. Внедрен Dependency Injection
4. Централизована оркестрация процессов

### Что осталось без изменений
1. PageContextService - работает как раньше
2. TaskManagementService - используется через TaskService
3. Модели Page и PageVersion - без изменений
4. API endpoints - без изменений

## Рекомендации по использованию

### 1. Всегда используйте DTO
```php
// Хорошо
$pageData = PageDataDTO::fromArray($request->validated());

// Плохо
$data = $request->validated();
```

### 2. Используйте Dependency Injection
```php
// Хорошо
public function __construct(
    protected DocumentationControlInterface $documentationControl
) {}

// Плохо
$documentationControl = app(DocumentationControlInterface::class);
```

### 3. Обрабатывайте ошибки
```php
try {
    $result = $this->documentationControl->approveDraftWithTask($page, true);
    if ($result->taskCreated) {
        return redirect()->route('tasks.show', $result->taskId);
    }
} catch (\Exception $e) {
    return redirect()->back()->with('error', $e->getMessage());
}
```

### 4. Логируйте важные операции
Все сервисы уже включают логирование, но можно добавить дополнительное:

```php
Log::info('User approved draft', [
    'user_id' => auth()->id(),
    'page_id' => $page->id,
    'draft_id' => $draft->id
]);
```
