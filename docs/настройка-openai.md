# Настройка OpenAI для проекта

## Установка зависимостей

Добавьте в `composer.json`:

```bash
composer require openai-php/client
composer require guzzlehttp/guzzle
```

## Настройка переменных окружения

Добавьте следующие переменные в ваш `.env` файл:

```env
# OpenAI Configuration
OPENAI_API_KEY=sk-your-openai-api-key-here
OPENAI_ORGANIZATION=org-your-organization-id
OPENAI_MODEL=gpt-4o-mini
OPENAI_BASE_URL=
OPENAI_REQUEST_TIMEOUT=30
```

### Получение API ключа

1. Зарегистрируйтесь на [OpenAI Platform](https://platform.openai.com/)
2. Перейдите в раздел [API Keys](https://platform.openai.com/api-keys)
3. Создайте новый API ключ
4. Скопируйте ключ в переменную `OPENAI_API_KEY`

### Модели OpenAI

Доступные модели для генерации описаний задач:

- `gpt-4o-mini` (рекомендуется) - быстрая и экономичная
- `gpt-4o` - более качественные ответы, но дороже
- `gpt-3.5-turbo` - устаревшая, но дешевая модель

## Конфигурация

Настройки хранятся в `config/services.php`:

```php
'openai' => [
    'api_key' => env('OPENAI_API_KEY'),
    'organization' => env('OPENAI_ORGANIZATION'),
    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    'base_url' => env('OPENAI_BASE_URL'),
    'timeout' => env('OPENAI_REQUEST_TIMEOUT', 30),
],
```

## Использование

Система автоматически использует OpenAI генератор, если настроен API ключ. В противном случае используется заглушка.

### Проверка работы

Запустите тесты:

```bash
php artisan test --filter=OpenAIDescriptionGeneratorTest
```

### Мониторинг расходов

Все запросы к OpenAI API логируются с информацией о токенах:

```php
Log::info('OpenAI API usage', [
    'prompt_tokens' => $response->usage->promptTokens,
    'completion_tokens' => $response->usage->completionTokens,
    'total_tokens' => $response->usage->totalTokens,
    'model' => $this->model,
]);
```

## Безопасность

- Никогда не коммитьте API ключи в репозиторий
- Используйте переменные окружения
- Ограничивайте доступ к API ключам
- Мониторьте использование токенов

## Troubleshooting

### Ошибка "OpenAI API key is not configured"

Проверьте, что переменная `OPENAI_API_KEY` установлена в `.env` файле.

### Ошибки API

Проверьте логи Laravel для деталей ошибок:

```bash
tail -f storage/logs/laravel.log
```

### Высокие расходы

- Используйте модель `gpt-4o-mini` вместо `gpt-4o`
- Уменьшите `max_tokens` в настройках
- Кэшируйте результаты где возможно
