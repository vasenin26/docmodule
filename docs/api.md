# API

## GET /api/project/{project}/generation-models

Возвращает список моделей генерации, доступных в рамках проекта, вместе с типом генерации, указанным в pivot-таблице project_generation_models.

Авторизация:
- Маршрут защищён middleware agent.jwt
- Агент доступен в запросе как $request->agent

Параметры пути:
- project (integer) — ID проекта (Route Model Binding). Если проект не найден — возвращается 404.

Проверка доступа:
- Доступ проверяется методом Project::canAgentAccess(Agent $agent). В случае отсутствия доступа возвращается 403.

Пример запроса:
GET /api/project/123/generation-models

Пример успешного ответа (200):
[
  {
    "name": "TextGenerationV1",
    "generation_type": "text",
  },
  {
    "name": "ImageGenX",
    "generation_type": "image",
  }
]

Замечания:
- Поле generation_type берётся из pivot-таблицы project_generation_models. Если соответствующая запись отсутствует, поле может быть null.
- Формат ответа — массив объектов с полями name и generation_type.
- Маршрут использует контроллер App\Http\Controllers\Api\ProjectController::generationModels
