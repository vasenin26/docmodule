# Orchestrator API - Вопросы и Ответы

## 1. Жизненный цикл задачи

### Что происходит после резервирования?

После резервирования задача остается в статусе `wait`, но помечена как зарезервированная:
- `agent_id` = ID оркестратора
- `agent_uuid` = UUID воркера
- `reserved_until` = время окончания резервирования
- `status` = `wait` (НЕ меняется при резервировании!)

**Воркер ДОЛЖЕН вызвать `POST /api/agent/task`** с указанием своего `agent_uuid`. Только тогда:
- `status` меняется на `processing`
- Задача официально начинает выполняться

### Как воркер узнает детали задачи?

**Проблема в текущей реализации:** `OrchestratorTaskDTO` действительно возвращает только базовую информацию.

**Решение:**
1. Оркестратор получает task_id через `GET /api/v1/orchestrator/tasks/next`
2. Оркестратор резервирует задачу через `POST /api/v1/orchestrator/tasks/{taskId}/reserve`
3. Оркестратор запускает воркер с параметрами: `task_id`, `agent_uuid`, JWT токен оркестратора
4. **Воркер вызывает `POST /api/agent/task`** с `agent_uuid` - этот endpoint уже возвращает полные данные задачи:
   - `handler` (класс обработчика)
   - `handler_options` (параметры задачи, включая chat, prompt и т.д.)
   - `project_id`
   - Все остальные данные

### Аутентификация воркера

**Воркер использует JWT токен оркестратора!**

Вот как это работает:
1. Оркестратор аутентифицирован с токеном агента с ID=5
2. Оркестратор резервирует задачу: `agent_id=5`, `agent_uuid=worker-123`
3. Оркестратор передает воркеру:
   - JWT токен оркестратора (агента ID=5)
   - UUID воркера (`worker-123`)
4. Воркер вызывает `POST /api/agent/task` с:
   - `Authorization: Bearer {orchestrator_token}`
   - `Body: { "agent_uuid": "worker-123" }`
5. Backend проверяет:
   - JWT токен валиден → определяет `agent_id=5`
   - Ищет задачу где `agent_id=5` AND `agent_uuid=worker-123`
   - Находит зарезервированную задачу и меняет `status` на `processing`

## 2. Обработка конфликтов и ошибок

### Что делать при 409 Conflict?

**Рекомендуемая стратегия:**

```
1. Получить 409 Conflict
2. Запросить следующую задачу: GET /api/v1/orchestrator/tasks/next
3. Попробовать зарезервировать её
4. Если снова 409 → повторить с небольшой задержкой
```

**НЕ стоит:**
- Ждать и повторять резервирование той же задачи (waste of time)
- Отменять операцию полностью (теряем задачу)

### Что если воркер упал во время выполнения?

**Текущая реализация имеет два механизма:**

1. **Резервирование истекло, status = wait:**
   - Задача автоматически становится доступной через `availableForOrchestrator()` scope
   - Другой оркестратор/агент может её получить
   - **Это корректный сценарий** - воркер не успел начать выполнение

2. **Резервирование истекло, status = processing:**
   - **Проблема!** Задача "зависла"
   - Текущий механизм: `AgentTask::stuck($minutesAgo)` scope
   - Админ может сбросить через `POST /api/admin/agent/reset-stuck`
   - Задачи в `processing` более N минут → сбрасываются в `wait`

### Поле timeout - для чего?

**`timeout` в секундах** - это таймаут **выполнения задачи**, НЕ резервирования:
- Резервирование управляется через `reserve_seconds` в запросе
- `timeout` сохраняется в задаче для информирования воркера
- Воркер должен завершить работу до истечения `timeout`
- Если воркер превысит `timeout` → задача считается "зависшей"

**Рекомендация:** `timeout` должен быть больше `reserve_seconds`:
- `reserve_seconds = 300` (5 минут на старт)
- `timeout = 1800` (30 минут на выполнение)

## 3. Резервирование и назначение

### Когда status меняется на processing?

**Только при вызове `POST /api/agent/task`!**

Lifecycle:
```
1. Создание: status = wait, agent_id = null, agent_uuid = null
2. Резервирование: status = wait, agent_id = 5, agent_uuid = worker-123, reserved_until = +5min
3. Старт воркера: POST /api/agent/task → status = processing
4. Завершение: PUT /api/agent/task/{id} → status = success/failed
```

### Как воркер докажет право на задачу?

**Проверка в `AgentTaskManagerService::assignTaskToAgent()`:**

```php
// Воркер отправляет: { "agent_uuid": "worker-123" }
// JWT токен даёт agent_id = 5

// Поиск задачи:
$task = AgentTask::where('agent_id', 5)        // Зарезервирована оркестратором
               ->where('agent_uuid', 'worker-123')  // Для этого воркера
               ->where('status', 'wait')        // Ещё не начата
               ->first();
```

### Может ли воркер "украсть" задачу?

**НЕТ, защита на уровне БД:**

1. Если задача зарезервирована (`agent_id=5, agent_uuid=worker-123`):
   - Только воркер с `agent_uuid=worker-123` + токен агента ID=5 может её взять
   - Другой воркер не найдет эту задачу в выборке

2. Scope `availableForOrchestrator()` исключает активно зарезервированные задачи:
   ```php
   WHERE (agent_id IS NULL AND agent_uuid IS NULL)
   OR (reserved_until < NOW())  // Только истекшие
   ```

3. `FOR UPDATE SKIP LOCKED` дополнительно защищает от race conditions

## 4. Управление проектами и ключами

### Когда обновляется public_key?

**Типичный workflow:**

1. **Оркестратор запускается** → проверяет наличие SSH ключа для проекта
2. **Ключа нет?** → Оркестратор генерирует пару ключей (private/public)
3. **Сохранение:**
   - Public key → `PUT /api/v1/orchestrator/projects/{projectId}/key`
   - Private key → хранится в секретном хранилище (Vault, K8s Secret)
4. **При запуске воркера:**
   - Оркестратор монтирует private key в контейнер
   - Воркер использует его для git clone
   - Public key уже добавлен в GitHub/GitLab через API

### Кто генерирует ключи?

**Оркестратор генерирует ключи!**

Пример логики оркестратора:
```python
def ensure_project_ssh_key(project_id):
    # Получаем задачу
    task = get_next_task()
    
    if not task['public_key']:  # Ключа нет
        # Генерируем пару ключей
        private_key, public_key = generate_ssh_keypair()
        
        # Сохраняем public в БД
        update_project_key(project_id, public_key)
        
        # Сохраняем private в Vault
        vault.store(f"project-{project_id}-ssh", private_key)
        
        # Добавляем в GitHub
        github.add_deploy_key(repo, public_key)
    
    return private_key
```

### Может ли агент без cross-project access обновлять чужие ключи?

**✅ НЕТ, ПРОВЕРКА РЕАЛИЗОВАНА!**

`OrchestratorController::updateProjectKey()` **проверяет** `has_cross_project_access`:

```php
public function updateProjectKey(
    UpdateProjectKeyRequest $request,
    string $projectId
): JsonResponse {
    $agent = $request->get('agent');
    $project = Project::findOrFail($projectId);
    
    // КРИТИЧЕСКАЯ ПРОВЕРКА:
    if (!$agent->hasCrossProjectAccess() && $project->id !== $agent->project_id) {
        Log::warning('Orchestrator: Access denied to update project key', [
            'agent_id' => $agent->id,
            'agent_project_id' => $agent->project_id,
            'target_project_id' => $project->id,
        ]);
        
        return response()->json(['error' => 'Access denied to this project'], 403);
    }
    
    $project->update(['public_key' => $request->getPublicKey()]);
    // ...
}
```

**Логика проверки:**
- Агент с `has_cross_project_access = true` → может обновлять ключи любого проекта
- Агент с `has_cross_project_access = false` → может обновлять только ключ своего проекта (`agent->project_id`)

## 5. Поле context_id

### Что такое context_id?

**Context ID = уникальный идентификатор рабочего окружения** для выполнения задач.

**Применение:**
- Docker volume name для персистентного хранилища
- Workspace identifier для переиспользования окружения
- Isolation boundary между разными контекстами

### Кто и когда его устанавливает?

**Варианты:**

1. **При создании задачи (рекомендуется):**
   ```php
   AgentTask::create([
       'context_id' => "ctx-" . Str::uuid(),
       'timeout' => 1800,
       // ...
   ]);
   ```

2. **При резервировании оркестратором:**
   - Оркестратор может установить/переопределить context_id
   - Если несколько задач должны выполняться в одном окружении

### Как используется на стороне оркестратора?

**Пример использования:**

```python
def execute_task(task):
    context_id = task['context_id'] or f"ctx-{uuid4()}"
    
    # Создаем/переиспользуем Docker volume
    volume = docker.volumes.create(name=context_id)
    
    # Запускаем контейнер с этим volume
    container = docker.run(
        image="worker:latest",
        volumes={context_id: '/workspace'},
        environment={
            'TASK_ID': task['id'],
            'AGENT_UUID': worker_uuid,
        }
    )
    
    # После выполнения:
    # - Можно удалить volume
    # - Или оставить для следующей задачи с тем же context_id
```

### Может ли context_id быть null?

**ДА!** Поле nullable:
- `null` = одноразовая задача, временное окружение
- Заполнено = задача требует персистентного контекста

## 6. Сценарии использования

### Workflow с общим контекстом

**Если несколько задач должны выполняться в одном Docker volume:**

**Стратегия 1: Последовательное выполнение**
```python
# Оркестратор
def process_context_tasks(context_id):
    while True:
        # Получаем задачу с определенным context_id
        task = get_next_task_by_context(context_id)
        if not task:
            break
            
        # Резервируем
        reserve_task(task['id'], reserve_seconds=300)
        
        # Выполняем В ТОМ ЖЕ контейнере
        worker_uuid = execute_in_container(
            context_id=context_id,
            task_id=task['id']
        )
        
        # Ждем завершения
        wait_for_completion(task['id'])
    
    # Очистка
    docker.volumes.remove(context_id)
```

**Стратегия 2: Параллельное выполнение (если задачи независимы)**
```python
# Получить все задачи с context_id
tasks = get_all_tasks_by_context(context_id)

# Зарезервировать все сразу
for task in tasks:
    reserve_task(task['id'], reserve_seconds=1800)

# Запустить параллельно в разных контейнерах,
# но с одним volume
for task in tasks:
    start_worker_async(
        context_id=context_id,  # Общий volume!
        task_id=task['id']
    )
```

### Обычный агент vs оркестратор через API

**Обычный агент через `/api/agent/*` (PUSH модель):**
```python
while True:
    response = post('/api/agent/task', {
        'agent_uuid': my_uuid
    })
    
    if response.status == 200:
        task = response.json()
        execute_task(task)
        update_task(task['id'], completed=True)
    elif response.status == 204:
        sleep(5)  # Нет задач
```

**Обычный агент через `/api/v1/orchestrator/*` (PULL модель):**
```python
while True:
    # Получить информацию о задаче БЕЗ назначения
    task = get('/api/v1/orchestrator/tasks/next')
    
    if not task:
        sleep(5)
        continue
    
    # Решить, брать ли задачу (может проверить timeout, context_id и т.д.)
    if should_take_task(task):
        # Зарезервировать
        reserve_task(task['id'], reserve_seconds=300)
        
        # Назначить себе
        full_task = post('/api/agent/task', {'agent_uuid': my_uuid})
        
        # Выполнить
        execute_task(full_task)
```

**Разница:**
- `/api/agent/task` сразу назначает задачу (атомарно)
- `/api/v1/orchestrator/*` позволяет "посмотреть и решить" перед назначением

**Зачем обычному агенту Orchestrator API:**
- Проверить `timeout` перед началом (достаточно ли времени?)
- Проверить `context_id` (есть ли нужный volume?)
- Получить `public_key` заранее (подготовить окружение)
- **Более гибкий контроль** над тем, какие задачи брать

## 7. Мониторинг и отладка

### Как отследить, какой воркер выполняет задачу?

**Через поле `agent_uuid`:**

```sql
SELECT 
    id,
    status,
    agent_id,
    agent_uuid,
    reserved_until,
    created_at,
    updated_at
FROM agent_tasks
WHERE status = 'processing';
```

**Логирование:**

Текущая реализация логирует:
- `Orchestrator: Task reserved` (с agent_uuid)
- `Task assigned to agent` (с agent_uuid)

**Рекомендуемое улучшение:**
```php
// В OrchestratorTaskService::reserveTask()
Log::info('Orchestrator: Task reserved', [
    'task_id' => $task->id,
    'orchestrator_agent_id' => $agent->id,
    'worker_uuid' => $agentUuid,
    'reserved_seconds' => $seconds,
    'worker_container_id' => $containerInfo ?? null, // Если известен
]);
```

### Резервирование истекло, но status = processing?

**Это ВАЛИДНЫЙ сценарий:**

```
Timeline:
00:00 - Резервирование: reserved_until = 00:05, status = wait
00:04 - Воркер стартует: POST /api/agent/task → status = processing
00:05 - Резервирование истекает, но это OK!
00:30 - Воркер завершает: status = success
```

**reserved_until относится только к периоду ДО начала выполнения!**

После `status = processing` резервирование не имеет значения.

**Для зависших задач используется:**
- Поле `updated_at` (время последнего обновления)
- Scope `stuck($minutesAgo)` проверяет: `status = processing AND updated_at < NOW() - interval`

**Воркер должен периодически обновлять задачу:**
```python
# В процессе выполнения
while working:
    do_some_work()
    
    # Каждые 5 минут - heartbeat
    if time.time() - last_update > 300:
        update_task(task_id, completed=False, progress_data={...})
        last_update = time.time()
```

## 8. Масштабирование

### Несколько оркестраторов одновременно

**Координация через БД с `FOR UPDATE SKIP LOCKED`:**

**Сценарий: 5 оркестраторов одновременно запрашивают задачи**

```python
# Оркестратор 1:
task1 = get_next_task()  # Блокирует task_id=10, получает её

# Оркестратор 2 (одновременно):
task2 = get_next_task()  # task_id=10 заблокирована → SKIP LOCKED → получает task_id=11

# Оркестратор 3 (одновременно):
task3 = get_next_task()  # task_id=10,11 заблокированы → получает task_id=12
```

**При резервировании:**

```python
# Оркестратор 1 резервирует task_id=10
reserve_task(10, reserve_seconds=300)  
# БД: UPDATE ... WHERE id=10 AND status=wait AND (reserved_until < NOW() OR reserved_until IS NULL)
# → Успех, updated 1 row

# Оркестратор 2 пытается зарезервировать task_id=10 (race condition)
reserve_task(10, reserve_seconds=300)
# БД: UPDATE ... WHERE id=10 AND status=wait AND (reserved_until < NOW() OR reserved_until IS NULL)
# → Провал, updated 0 rows → Exception "Task is already reserved"
```

**Защита в `OrchestratorTaskService::reserveTask()`:**

```php
DB::transaction(function () use ($task, $agent, $seconds, $agentUuid) {
    // Перезагружаем с блокировкой
    $task = AgentTask::where('id', $task->id)
        ->lockForUpdate()  // Эксклюзивная блокировка
        ->first();
    
    // Проверяем резервирование
    if ($task->isReserved()) {
        throw new \Exception('Already reserved');
    }
    
    // Резервируем
    $task->reserve($seconds, $agent->id, $agentUuid);
}, 5);
```

### Могут ли два оркестратора зарезервировать одну задачу?

**НЕТ, невозможно благодаря:**

1. **Транзакция с блокировкой:** `lockForUpdate()` внутри транзакции
2. **Проверка состояния:** `isReserved()` проверяет актуальное состояние
3. **Атомарный UPDATE:** резервирование происходит одной операцией

**Worst case сценарий:**
```
Оркестратор 1: BEGIN TRANSACTION
Оркестратор 2: BEGIN TRANSACTION

Оркестратор 1: SELECT * FROM agent_tasks WHERE id=10 FOR UPDATE
               ↓ Получает эксклюзивную блокировку

Оркестратор 2: SELECT * FROM agent_tasks WHERE id=10 FOR UPDATE
               ↓ ЖДЁТ освобождения блокировки

Оркестратор 1: UPDATE agent_tasks SET reserved_until=... WHERE id=10
Оркестратор 1: COMMIT
               ↓ Освобождает блокировку

Оркестратор 2: ↓ Получает блокировку, читает УЖЕ ОБНОВЛЕННУЮ строку
               ↓ isReserved() = true → Exception
Оркестратор 2: ROLLBACK
```

**Итого: второй оркестратор получит Exception и перейдет к следующей задаче.**

## Рекомендации по улучшению

### 1. ✅ ИСПРАВЛЕНО: Добавлена проверка доступа к проекту в updateProjectKey()
### 2. Добавить heartbeat механизм для воркеров
### 3. Расширить OrchestratorTaskDTO:
```php
public function toArray(): array
{
    return [
        'id' => $this->id,
        'project_id' => $this->project_id,
        'context_id' => $this->context_id,
        'timeout' => $this->timeout,
        'public_key' => $this->public_key,
        'type' => $this->type,  // Добавить
        'created_at' => $this->created_at,  // Добавить
    ];
}
```

### 4. Добавить endpoint для продления резервирования:
```php
POST /api/v1/orchestrator/tasks/{taskId}/extend
{
    "extend_seconds": 300
}
```

### 5. Добавить метрики и мониторинг:
- Количество активных резервирований
- Среднее время резервирования → выполнение
- Количество истекших резервирований
- Количество зависших задач

---

**Дата создания:** 2025-10-06  
**Версия API:** 1.0

