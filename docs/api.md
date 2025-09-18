## Руководство по API (`/api`)

### Общие правила

- **Аутентификация**: все маршруты под префиксом `/api/agent` защищены middleware `agent.jwt` и требуют заголовок `Authorization: Bearer <token>`.
- **Контент**: `Content-Type: application/json`. Тело и ответы — JSON.
- **Идентификатор агента**: для работы с задачами обязателен `agent_uuid` (UUID v4, max 36), передаётся в теле запроса.
- **Коды ошибок**:
  - 401: `{"error":"Token required"}` или `{"error":"Invalid or expired token"}`
  - 403: `{"error":"Access denied ..."}` — нет доступа к ресурсу проекта агента
  - 404: `{"error":"... not found"}` — ресурс не найден
  - 500: `{"error":"Failed to update task"}` — ошибка сервера при обновлении задачи
  - 503: `{"error":"Service temporarily unavailable","message":"Please try again later"}` — сервис временно недоступен

---

### Healthcheck

GET `/api/health`

- Ответ 200: `"ok"`
- Аутентификация: не требуется

---

### Агент: задачи

POST `/api/agent/task`

- Назначение: выдать задачу агенту
- Тело запроса:
```json
{
  "agent_uuid": "aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee"
}
```
- Ответ 200:
```json
{
  "id": 123,
  "type": "some_task_type",
  "agent_uuid": "aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee",
  "project_id": 45,
  "result_required": true,
  "chat": { "messages": [] }
}
```
- Нет задач 404:
```json
{ "task_id": null, "message": "No tasks available" }
```
- Ошибка 503: см. общие коды

PUT `/api/agent/task/{id}`

- Назначение: сохранить прогресс/результат
- Тело запроса:
```json
{
  "completed": true,
  "agent_uuid": "aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee",
  "chat": [
    { "role": "system", "content": "..." },
    { "role": "user", "content": "..." },
    { "role": "assistant", "content": "..." }
  ],
  "stats": {
    "prompt_tokens": 100,
    "completion_tokens": 200,
    "total_tokens": 300
  },
  "result": "произвольный текст результата"
}
```
- Ответ 200:
```json
{ "status": "updated", "message": "Task progress saved" }
```
- Ошибки:
  - 404: `{ "error": "Task not found, not assigned to this agent, or not in processing state" }`
  - 500: `{ "error": "Failed to update task" }`

Требования валидации:
- `completed`: required boolean
- `agent_uuid`: required string UUID v4, max 36
- `chat`: nullable array сообщений (каждое: `role` ∈ {user, assistant, system}, `content` string)
- `stats`: required object; поля — неотрицательные целые; `total_tokens >= prompt_tokens + completion_tokens`
- `result`: nullable string, max ~16MB

---

### Агент: страницы

GET `/api/agent/page/version/{id}`

- Назначение: получить версию страницы по ID версии
- Ответ 200:
```json
{
  "title": "Заголовок",
  "content": "Текст",
  "pageId": 10,
  "versionId": 25,
  "previousVersionId": 24
}
```
- Ошибки: 403 при доступе к чужому проекту, 404 если версия не найдена

GET `/api/agent/page/{id}`

- Назначение: получить страницу по ID
- Ответ 200 (DTO текущей версии):
```json
{
  "id": 10,
  "title": "Заголовок",
  "content": "Текст",
  "files": [ /* массив файлов текущей версии */ ]
}
```
- Ошибки: 404 если страница не найдена/недоступна

GET `/api/agent/pages`

- Назначение: список всех страниц проекта агента
- Ответ 200 (массив DTO):
```json
[
  { "id": 10, "title": "Стр 1" },
  { "id": 11, "title": "Стр 2" }
]
```

GET `/api/agent/pages/hierarchy`

- Назначение: иерархия страниц (опционально от корня из запроса, см. `GetPageHierarchyRequest`)
- Ответ 200 (массив узлов):
```json
[
  {
    "id": 1,
    "title": "Раздел",
    "children": [
      { "id": 2, "title": "Подраздел", "children": [] }
    ]
  }
]
```

GET `/api/agent/page/{id}/children`

- Назначение: дочерние страницы
- Ответ 200:
```json
[
  { "id": 21, "title": "Child A" },
  { "id": 22, "title": "Child B" }
]
```
- Ошибки: 403 при отсутствии доступа

GET `/api/agent/page/{id}/parent`

- Назначение: родительская страница
- Ответ 200:
```json
{ "id": 5, "title": "Родитель", "content": "...", "files": [] }
```
- Если родителя нет: `null`
- Ошибки: 403 при отсутствии доступа

GET `/api/agent/page/{id}/related`

- Назначение: связанные страницы
- Ответ 200 (массив как в списке страниц):
```json
[
  { "id": 31, "title": "Связанная 1" }
]
```
- Ошибки: 403 при отсутствии доступа

GET `/api/agent/page/{id}/actualization`

- Назначение: страница с данными для актуализации
- Ответ 200 (как `/page/{id}`, DTO текущей версии)
- Ошибки: 403 при отсутствии доступа, 404 если страница не найдена

GET `/api/agent/page/{id}/files`

- Назначение: файлы, связанные со страницей
- Ответ 200: массив файлов (структура зависит от сервиса проекта; возвращается как есть)
- Ошибки: 403 при отсутствии доступа

GET `/api/agent/page/{id}/tasks`

- Назначение: история задач по странице
- Ответ 200:
```json
[
  {
    "id": 1001,
    "status": "processing|success|failed|wait",
    "created_at": "2025-01-01T10:00:00.000Z",
    "updated_at": "2025-01-01T10:05:00.000Z",
    "creator": { "id": 7, "name": "User", "email": "u@example.com" },
    "techplane": { "id": 55, "title": "Техплан" }
  }
]
```
- Ошибки: 403 при отсутствии доступа

---

### Админ: агентские инструменты (требует `auth` и `admin`)

GET `/api/admin/agent/stats`

- Ответ 200:
```json
{
  "waiting": 0,
  "processing": 1,
  "completed": 2,
  "failed": 0
}
```

GET `/api/admin/agent/stuck`

- Ответ 200: массив «застрявших» задач со связанными `project` и `creator` (полная структура модели `AgentTask` c отношениями)

POST `/api/admin/agent/reset-stuck`

- Назначение: сброс зависших задач
- Ответ 200:
```json
{ "reset_count": 3 }
```


