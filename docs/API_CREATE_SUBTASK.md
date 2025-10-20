# API: Создание подзадачи

## Описание

API для создания подзадачи к существующей задаче агента. Подзадача наследует проект и создателя от родительской задачи, но получает собственный чат и может иметь любой тип.

## Эндпоинт

```
POST /api/agent/task/{id}/subtasks
```

### Параметры URL
- `{id}` - ID родительской задачи (integer)

### Заголовки
```
Authorization: Bearer {jwt_token}
Content-Type: application/json
```

### Тело запроса
```json
{
    "type": "string",
    "agent_uuid": "uuid"
}
```

#### Параметры запроса

| Параметр | Тип | Обязательный | Описание |
|----------|-----|--------------|----------|
| `type` | string | Да | Тип подзадачи (любая строка, 1-255 символов) |
| `agent_uuid` | string (UUID) | Да | UUID агента, создающего подзадачу |

## Примеры запросов

### Успешное создание подзадачи

**Запрос:**
```bash
curl -X POST "http://localhost:8000/api/agent/task/123/subtasks" \
  -H "Authorization: Bearer your_jwt_token" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "custom_analysis",
    "agent_uuid": "550e8400-e29b-41d4-a716-446655440000"
  }'
```

**Ответ (201 Created):**
```json
{
    "id": 456
}
```

### Ошибка: Родительская задача не найдена

**Ответ (404 Not Found):**
```json
{
    "status": "error",
    "message": "Parent task not found"
}
```

### Ошибка: Нет доступа к родительской задаче

**Ответ (403 Forbidden):**
```json
{
    "status": "error",
    "message": "Forbidden: parent task not owned by this agent"
}
```

### Ошибка: Неверные данные

**Ответ (422 Unprocessable Entity):**
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "type": [
            "Task type is required"
        ],
        "agent_uuid": [
            "Agent UUID is required"
        ]
    }
}
```

### Ошибка: Неавторизованный запрос

**Ответ (401 Unauthorized):**
```json
{
    "message": "Unauthenticated."
}
```

## Коды ответов

| Код | Описание |
|-----|----------|
| 201 | Подзадача успешно создана |
| 400 | Неверный запрос |
| 401 | Неавторизованный запрос |
| 403 | Нет доступа к родительской задаче |
| 404 | Родительская задача не найдена |
| 422 | Ошибки валидации |
| 500 | Внутренняя ошибка сервера |

## Особенности подзадач

### Наследование свойств
Подзадача автоматически наследует от родительской задачи:
- `project_id` - ID проекта
- `created_by` - ID создателя
- `agent_id` - ID агента
- `agent_uuid` - UUID агента

### Собственные свойства
Подзадача получает:
- Новый пустой чат (`LLMChat`)
- `parent_id` - ссылка на родительскую задачу
- `type` - указанный тип (любая строка)
- `handler` - null
- `handler_options` - пустой массив
- `result_required` - false
- `status` - "wait"

### Типы задач
Подзадачу можно создать с **любым типом** (строка):
- `"text"` - текстовая задача
- `"code"` - задача с кодом
- `"analysis"` - аналитическая задача
- `"custom_type"` - любой пользовательский тип
- И т.д.

## Авторизация

Для создания подзадачи агент должен:
1. Иметь валидный JWT токен
2. Быть владельцем родительской задачи (совпадают `agent_id` и `agent_uuid`)

## Логирование

Все операции создания подзадач логируются с информацией:
- ID созданной подзадачи
- ID родительской задачи
- ID агента и UUID
- Тип подзадачи
- ID проекта
- Время создания

## Примеры использования

### Создание аналитической подзадачи
```bash
curl -X POST "http://localhost:8000/api/agent/task/123/subtasks" \
  -H "Authorization: Bearer your_jwt_token" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "data_analysis",
    "agent_uuid": "550e8400-e29b-41d4-a716-446655440000"
  }'
```

### Создание задачи генерации кода
```bash
curl -X POST "http://localhost:8000/api/agent/task/123/subtasks" \
  -H "Authorization: Bearer your_jwt_token" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "code_generation",
    "agent_uuid": "550e8400-e29b-41d4-a716-446655440000"
  }'
```

### Создание пользовательской задачи
```bash
curl -X POST "http://localhost:8000/api/agent/task/123/subtasks" \
  -H "Authorization: Bearer your_jwt_token" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "my_custom_task_type",
    "agent_uuid": "550e8400-e29b-41d4-a716-446655440000"
  }'
```

## Безопасность

- Все запросы должны быть авторизованы
- Агент может создавать подзадачи только для своих задач
- Подозрительная активность логируется
- UUID агента проверяется на соответствие формату

## Ограничения

- Максимальная длина типа задачи: 255 символов
- Тип задачи не может быть пустым
- Родительская задача должна существовать и принадлежать агенту
- Один агент не может создавать подзадачи для задач других агентов
