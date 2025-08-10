# Интеграция с LLM

Система активно использует LLM для генерации контента. Взаимодействие с LLM происходит с помощью интерфейса `TaskDescriptionGeneratorInterface`.

## Доступные библиотеки для PHP

### 1. OpenAI PHP Client (Рекомендуемая)

**Библиотека:** `openai-php/client`  
**Описание:** Сообщественная PHP библиотека для взаимодействия с OpenAI API  
**Trust Score:** 7.1/10  
**Код сниппетов:** 137

#### Установка

```bash
composer require openai-php/client
composer require guzzlehttp/guzzle
```

#### Базовая настройка

```php
use OpenAI\OpenAI;

$yourApiKey = getenv('YOUR_API_KEY');
$client = OpenAI::client($yourApiKey);

$result = $client->chat()->create([
    'model' => 'gpt-4o',
    'messages' => [
        ['role' => 'user', 'content' => 'Hello!'],
    ],
]);

echo $result->choices[0]->message->content;
```

#### Расширенная конфигурация

```php
$client = OpenAI::factory()
    ->withApiKey($yourApiKey)
    ->withOrganization('your-organization')
    ->withProject('Your Project')
    ->withBaseUri('openai.example.com/v1')
    ->withHttpClient($httpClient = new \GuzzleHttp\Client([]))
    ->withHttpHeader('X-My-Header', 'foo')
    ->withQueryParam('my-param', 'bar')
    ->make();
```

### 2. OpenAI PHP для Laravel

**Библиотека:** `openai-php/laravel`  
**Описание:** Специальная библиотека для интеграции с Laravel  
**Trust Score:** 7.1/10  
**Код сниппетов:** 15

#### Установка

```bash
composer require openai-php/laravel
php artisan openai:install
```

#### Настройка в .env

```env
OPENAI_API_KEY=sk-...
OPENAI_ORGANIZATION=org-...
OPENAI_PROJECT=proj_...
OPENAI_BASE_URL=
OPENAI_REQUEST_TIMEOUT=
```

#### Использование в Laravel

```php
use OpenAI\Laravel\Facades\OpenAI;

$result = OpenAI::chat()->create([
    'model' => 'gpt-4o-mini',
    'messages' => [
        ['role' => 'user', 'content' => 'Hello!'],
    ],
]);

echo $result->choices[0]->message->content;
```

## Основные возможности API

### 1. Chat Completions (Чат-завершения)

#### Базовый запрос

```php
$response = $client->chat()->create([
    'model' => 'gpt-3.5-turbo',
    'messages' => [
        ['role' => 'user', 'content' => 'Hello!'],
    ],
]);

$response->id; // 'chatcmpl-6pMyfj1HF4QXnfvjtfzvufZSQq6Eq'
$response->object; // 'chat.completion'
$response->created; // 1677701073
$response->model; // 'gpt-3.5-turbo-0301'

foreach ($response->choices as $choice) {
    $choice->index; // 0
    $choice->message->role; // 'assistant'
    $choice->message->content; // '\n\nHello there! How can I assist you today?'
    $choice->finishReason; // 'stop'
}

$response->usage->promptTokens; // 9
$response->usage->completionTokens; // 12
$response->usage->totalTokens; // 21
```

#### Стриминг ответов

```php
$stream = $client->chat()->createStreamed([
    'model' => 'gpt-4o',
    'messages' => [
        ['role' => 'user', 'content' => 'Hello!'],
    ],
]);

foreach($stream as $response){
    $response->choices[0]->toArray();
}
// 1. iteration => ['index' => 0, 'delta' => ['role' => 'assistant'], 'finish_reason' => null]
// 2. iteration => ['index' => 0, 'delta' => ['content' => 'Hello'], 'finish_reason' => null]
// 3. iteration => ['index' => 0, 'delta' => ['content' => '!'], 'finish_reason' => null]
```

#### С функциями (Function Calling)

```php
$response = $client->chat()->create([
    'model' => 'gpt-3.5-turbo-0613',
    'messages' => [
        ['role' => 'user', 'content' => 'What\'s the weather like in Boston?'],
    ],
    'tools' => [
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_current_weather',
                'description' => 'Get the current weather in a given location',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'location' => [
                            'type' => 'string',
                            'description' => 'The city and state, e.g. San Francisco, CA',
                        ],
                        'unit' => [
                            'type' => 'string',
                            'enum' => ['celsius', 'fahrenheit']
                        ],
                    ],
                    'required' => ['location'],
                ],
            ],
        ]
    ]
]);

foreach ($response->choices as $choice) {
    $choice->message->toolCalls[0]->id; // 'call_123'
    $choice->message->toolCalls[0]->type; // 'function'
    $choice->message->toolCalls[0]->function->name; // 'get_current_weather'
    $choice->message->toolCalls[0]->function->arguments; // "{\n  \"location\": \"Boston, MA\"\n}"
    $choice->finishReason; // 'tool_calls'
}
```

### 2. Embeddings (Векторные представления)

```php
$response = $client->embeddings()->create([
    'model' => 'text-similarity-babbage-001',
    'input' => 'The food was delicious and the waiter...',
]);

$response->object; // 'list'

foreach ($response->embeddings as $embedding) {
    $embedding->object; // 'embedding'
    $embedding->embedding; // [0.018990106880664825, -0.0073809814639389515, ...]
    $embedding->index; // 0
}

$response->usage->promptTokens; // 8
$response->usage->totalTokens; // 8
```

### 3. Генерация изображений

```php
$response = $client->images()->create([
    'model' => 'dall-e-3',
    'prompt' => 'A cute baby sea otter',
    'n' => 1,
    'size' => '1024x1024',
    'response_format' => 'url',
]);

$response->created; // 1589478378

foreach ($response->data as $data) {
    $data->url; // 'https://oaidalleapiprodscus.blob.core.windows.net/private/...'
    $data->b64_json; // null
}
```

### 4. Обработка аудио

```php
$response = $client->audio()->transcriptions()->create([
    'file' => fopen('audio.mp3', 'r'),
    'model' => 'whisper-1',
    'response_format' => 'verbose_json',
    'timestamp_granularities' => ['word', 'segment'],
]);

$response->text; // 'Hello, this is a test.'
$response->language; // 'english'
$response->duration; // 2.5
```

## Тестирование

### Фейковые ответы для тестов

```php
use OpenAI\Testing\ClientFake;
use OpenAI\Responses\Completions\CreateResponse;

$client = new ClientFake([
    CreateResponse::fake([
        'choices' => [
            [
                'text' => 'awesome!',
            ],
        ],
    ]),
]);

$completion = $client->completions()->create([
    'model' => 'gpt-3.5-turbo-instruct',
    'prompt' => 'PHP is ',
]);

expect($completion['choices'][0]['text'])->toBe('awesome!');
```

### В Laravel

```php
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Completions\CreateResponse;

OpenAI::fake([
    CreateResponse::fake([
        'choices' => [
            [
                'text' => 'awesome!',
            ],
        ],
    ]),
]);

$completion = OpenAI::completions()->create([
    'model' => 'gpt-4o-mini',
    'prompt' => 'PHP is ',
]);

expect($completion['choices'][0]['text'])->toBe('awesome!');
```

### Проверка отправленных запросов

```php
// assert completion create request was sent
OpenAI::assertSent(Completions::class, function (string $method, array $parameters): bool {
    return $method === 'create' &&
        $parameters['model'] === 'gpt-4o-mini' &&
        $parameters['prompt'] === 'PHP is ';
});
```

## Azure OpenAI Service

```php
$client = OpenAI::factory()
    ->withBaseUri('{your-resource-name}.openai.azure.com/openai/deployments/{deployment-id}')
    ->withHttpHeader('api-key', '{your-api-key}')
    ->withQueryParam('api-version', '{version}')
    ->make();

// A basic sample completion call using the Azure client:
$result = $client->completions()->create([
    'prompt' => 'PHP is'
]);
```

## Обработка ошибок

```php
$client = new ClientFake([
    new \OpenAI\Exceptions\ErrorException([
        'message' => 'The model `gpt-1` does not exist',
        'type' => 'invalid_request_error',
        'code' => null,
    ], 404)
]);

// the `ErrorException` will be thrown
$completion = $client->completions()->create([
    'model' => 'gpt-3.5-turbo-instruct',
    'prompt' => 'PHP is ',
]);
```

## Лучшие практики

### 1. Управление токенами

- Всегда проверяйте `usage->totalTokens` для контроля расходов
- Используйте `max_tokens` для ограничения длины ответов
- Кэшируйте эмбеддинги для повторного использования

### 2. Обработка ошибок

```php
try {
    $response = $client->chat()->create([
        'model' => 'gpt-4o',
        'messages' => [
            ['role' => 'user', 'content' => 'Hello!'],
        ],
    ]);
} catch (\OpenAI\Exceptions\ErrorException $e) {
    // Обработка ошибок API
    Log::error('OpenAI API Error: ' . $e->getMessage());
} catch (\Exception $e) {
    // Обработка других ошибок
    Log::error('Unexpected error: ' . $e->getMessage());
}
```

### 3. Ретеи и таймауты

```php
$client = OpenAI::factory()
    ->withHttpClient(new \GuzzleHttp\Client([
        'timeout' => 30,
        'retry_on_status' => [429, 500, 502, 503, 504],
        'max_retry_attempts' => 3,
    ]))
    ->make();
```

### 4. Безопасность

- Никогда не храните API ключи в коде
- Используйте переменные окружения
- Ограничивайте доступ к API ключам
- Мониторьте использование токенов

## Интеграция в проект

В нашем проекте используется интерфейс `TaskDescriptionGeneratorInterface` для абстракции работы с LLM:

```php
interface TaskDescriptionGeneratorInterface
{
    public function generateDescription(string $diff): string;
}
```

Это позволяет легко переключаться между различными провайдерами LLM и тестировать систему без реальных API вызовов.

## Полезные ссылки

- [OpenAI PHP Client GitHub](https://github.com/openai-php/client)
- [OpenAI PHP Laravel GitHub](https://github.com/openai-php/laravel)
- [OpenAI API Documentation](https://platform.openai.com/docs)
- [OpenAI Cookbook](https://github.com/openai/openai-cookbook) 