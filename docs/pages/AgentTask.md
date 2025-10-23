# AgentTask

Документация по AgentTask

## Стоимость задач (pricing)

- Добавлен расчет стоимости задачи через GenerationModel и связь через поле `agent_model`.
- Стоимость хранится в БД в поле `cost` (nullable), как число в RUB*1000 для повышения точности до 0.001.
- Стоимость не возвращается в API: `AgentTaskResource` исключает поле `cost` из выходных данных.
- Механизм расчета активируется после подсчета токенов и обновления полей `prompt_tokens` и `completion_tokens`.
- Если `agent_model` не установлен или соответствующая `GenerationModel` не найдена — `cost` остаётся `NULL`.

## Архитектура расчета

- `PricingService` рассчитывает `cost` на основе заданной модели в `GenerationModel` (`price_in`, `price_out`).
- Связь выполняется через `AgentTask.agent_model` (имя модели).
- Формула: `cost = (prompt_tokens / 1_000_000) * price_in + (completion_tokens / 1_000_000) * price_out`; результат хранится как RUB*1000.
- `AgentTaskManagerService` вызывает `PricingService` и сохраняет стоимость в поле `cost`.
