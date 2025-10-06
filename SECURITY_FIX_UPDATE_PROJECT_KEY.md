# Security Fix: Project Key Update Authorization

## Проблема

**Критическая уязвимость безопасности** была обнаружена в endpoint `PUT /api/v1/orchestrator/projects/{projectId}/key`.

### До исправления:
Любой агент мог обновить SSH публичный ключ **любого** проекта, независимо от значения флага `has_cross_project_access`.

### Сценарий атаки:
```bash
# Агент проекта ID=1 (без has_cross_project_access)
# Может обновить ключ проекта ID=5 (чужого!)
curl -X PUT http://localhost:8000/api/v1/orchestrator/projects/5/key \
  -H "Authorization: Bearer {TOKEN_AGENT_PROJECT_1}" \
  -d '{"public_key": "ssh-rsa MALICIOUS_KEY..."}'
  
# ✅ 200 OK - ключ обновлен!
```

## Исправление

### Добавлена проверка доступа

**Файл:** `app/app/Http/Controllers/Api/OrchestratorController.php`

**Метод:** `updateProjectKey()`

```php
public function updateProjectKey(
    UpdateProjectKeyRequest $request,
    string $projectId
): JsonResponse {
    $agent = $request->get('agent');

    try {
        $project = Project::findOrFail($projectId);

        // ✅ КРИТИЧЕСКАЯ ПРОВЕРКА: только агенты с has_cross_project_access
        // могут обновлять ключи чужих проектов
        if (!$agent->hasCrossProjectAccess() && $project->id !== $agent->project_id) {
            Log::warning('Orchestrator: Access denied to update project key', [
                'agent_id' => $agent->id,
                'agent_project_id' => $agent->project_id,
                'target_project_id' => $project->id,
                'has_cross_project_access' => $agent->has_cross_project_access,
            ]);

            return response()->json([
                'error' => 'Access denied to this project'
            ], 403);
        }

        $project->update([
            'public_key' => $request->getPublicKey(),
        ]);

        Log::info('Orchestrator: Project key updated', [
            'project_id' => $projectId,
            'agent_id' => $agent->id,
            'has_cross_project_access' => $agent->has_cross_project_access,
            'key_length' => strlen($request->getPublicKey()),
        ]);

        return response()->json([
            'message' => 'Public key updated successfully'
        ], 200);

    } catch (ModelNotFoundException $e) {
        // ...
    }
}
```

## Логика авторизации

### Агент с `has_cross_project_access = true` (Оркестратор)
✅ Может обновлять ключи **всех** проектов

### Агент с `has_cross_project_access = false` (Обычный агент)
✅ Может обновлять ключ **только своего** проекта (`agent->project_id`)
❌ **403 Forbidden** при попытке обновить чужой проект

## После исправления

### Сценарий 1: Обычный агент пытается обновить чужой проект
```bash
# Агент проекта ID=1 (без has_cross_project_access)
curl -X PUT http://localhost:8000/api/v1/orchestrator/projects/5/key \
  -H "Authorization: Bearer {TOKEN_AGENT_PROJECT_1}" \
  -d '{"public_key": "ssh-rsa KEY..."}'

# ❌ 403 Forbidden
{
  "error": "Access denied to this project"
}
```

### Сценарий 2: Обычный агент обновляет свой проект
```bash
# Агент проекта ID=1
curl -X PUT http://localhost:8000/api/v1/orchestrator/projects/1/key \
  -H "Authorization: Bearer {TOKEN_AGENT_PROJECT_1}" \
  -d '{"public_key": "ssh-rsa KEY..."}'

# ✅ 200 OK
{
  "message": "Public key updated successfully"
}
```

### Сценарий 3: Оркестратор обновляет любой проект
```bash
# Оркестратор (has_cross_project_access = true)
curl -X PUT http://localhost:8000/api/v1/orchestrator/projects/5/key \
  -H "Authorization: Bearer {ORCHESTRATOR_TOKEN}" \
  -d '{"public_key": "ssh-rsa KEY..."}'

# ✅ 200 OK
{
  "message": "Public key updated successfully"
}
```

## Логирование

### При успешном обновлении
```json
{
  "level": "info",
  "message": "Orchestrator: Project key updated",
  "context": {
    "project_id": "5",
    "agent_id": 3,
    "has_cross_project_access": true,
    "key_length": 564
  }
}
```

### При отказе в доступе
```json
{
  "level": "warning",
  "message": "Orchestrator: Access denied to update project key",
  "context": {
    "agent_id": 2,
    "agent_project_id": 1,
    "target_project_id": 5,
    "has_cross_project_access": false
  }
}
```

## Проверка исправления

### Тестовые сценарии

1. **Создать обычного агента:**
```bash
docker compose exec -u local development php artisan tinker
```
```php
$agent = \App\Models\Agent::create([
    'name' => 'Regular Agent',
    'uuid' => \Str::uuid(),
    'token' => '',
    'project_id' => 1,
    'has_cross_project_access' => false,
]);

$token = app(\App\Services\AgentJwtService::class)->generateToken($agent);
$agent->update(['token' => $token]);
echo "Token: $token\n";
```

2. **Попытаться обновить чужой проект:**
```bash
curl -X PUT http://localhost:8000/api/v1/orchestrator/projects/2/key \
  -H "Authorization: Bearer {REGULAR_AGENT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"public_key": "ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQC..."}'
```

**Ожидаемый результат:** `403 Forbidden`

3. **Обновить свой проект:**
```bash
curl -X PUT http://localhost:8000/api/v1/orchestrator/projects/1/key \
  -H "Authorization: Bearer {REGULAR_AGENT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"public_key": "ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQC..."}'
```

**Ожидаемый результат:** `200 OK`

## Влияние на существующий код

### Обратная совместимость
✅ **Полная обратная совместимость сохранена**

- Оркестраторы с `has_cross_project_access = true` продолжают работать как раньше
- Добавлена только проверка безопасности, не изменяющая API

### Изменения в поведении
- **Обычные агенты** теперь **НЕ могут** обновлять ключи чужих проектов (раньше могли - это была уязвимость)
- Все попытки нарушения доступа **логируются** с уровнем `warning`

## Статус

✅ **Исправлено**
✅ **Протестировано**
✅ **Документация обновлена**

---

**Дата исправления:** 2025-10-06  
**Файлы изменены:** 
- `app/app/Http/Controllers/Api/OrchestratorController.php`
- `docs/ORCHESTRATOR_API_FAQ.md`

