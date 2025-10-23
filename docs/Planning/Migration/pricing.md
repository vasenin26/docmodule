# Миграция и внедрение Pricing через GenerationModel

Цель
- Внедрить механизм расчета стоимости задач агента через GenerationModel и field agent_model.
- Добавить миграцию для поля cost в agent_tasks и обеспечить хранение в RUB*1000.

Контекст
- Стоимость рассчитывается по формуле: cost_raw = (prompt_tokens / 1_000_000) * price_in + (completion_tokens / 1_000_000) * price_out
- Цена в GenerationModel задается как price_in и price_out за 1 000 000 токенов.
- Стоимость сохраняется в AgentTask.cost как целое число (RUB*1000), но не возвращается через API.

Пошаговый план миграции
1. Добавить миграцию: database/migrations/2025_10_25_000002_add_cost_to_agent_tasks_table.php
2. Обновить модель AgentTask: включить cost в $fillable и add комментарий о формате хранения.
3. Реализовать PricingService: app/app/Services/Pricing/PricingService.php
4. Интегрировать в AgentTaskManagerService и TaskController: после подсчета токенов вызвать PricingService и сохранять cost.
5. Обновить AgentTaskResource: исключить cost из выходного формата.
6. Обновить документацию: AgentTask.md и миграционные заметки.
7. Написать unit-тест PricingService: tests/Unit/PricingServiceTest.php
8. (Опционально) Написать интеграционный тест на отсутствие экспозиции cost в API и корректность сохранения cost в БД: tests/Feature/AgentTaskCostApiTest.php

Риски и митигации
- Риск: отсутствие данных price_in/price_out. Митигация: возвращать null и держать cost NULL.
- Риск: неточные параметры цены (price_in/price_out).
- Риск: изменение вывода API требует регрессионного тестирования.
- Риск: миграции могут затронуть одинаковые таблицы и индексы.

Миграция обратима: down() удаляет колонку cost.
