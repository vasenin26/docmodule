# DocModule - Система управления документацией

Проект построен на Laravel 12 с использованием:
- **Backend**: Laravel + Inertia.js
- **Frontend**: Vue 3 + TypeScript + Tailwind CSS
- **UI Framework**: Reka UI (компоненты)
- **База данных**: SQLite (по умолчанию)

## Функционал

### Интеграция с LLM (Large Language Models)

Система поддерживает интеграцию с OpenAI API для автоматической генерации описаний задач на основе изменений в коде:

#### Возможности LLM интеграции:
- ✅ **Автоматическая генерация описаний** - создание описаний задач из diff'ов кода
- ✅ **Поддержка OpenAI API** - использование GPT моделей для анализа изменений
- ✅ **Fallback механизм** - автоматический переход на заглушку при недоступности API
- ✅ **Мониторинг расходов** - логирование использования токенов
- ✅ **Гибкая конфигурация** - настройка моделей и параметров через .env

#### Доступные библиотеки:
- **openai-php/client** - основная библиотека для работы с OpenAI API
- **openai-php/laravel** - Laravel-специфичная интеграция

Подробная документация: [Интеграция с LLM](docs/интеграция%20с%20LLM.md)

### Система управления страницами документации

Система предоставляет полный функционал для управления страницами документации с поддержкой:

#### Основные возможности:
- ✅ **CRUD операции** - создание, чтение, обновление, удаление страниц
- ✅ **Версионирование** - автоматическое создание новых версий при редактировании
- ✅ **Иерархия** - поддержка дочерних страниц
- ✅ **Markdown поддержка** - редактирование и отображение Markdown
- ✅ **Поиск и фильтрация** - поиск по названию и содержимому
- ✅ **Предварительный просмотр** - просмотр Markdown в реальном времени

#### Структура страницы:
- `id` - уникальный идентификатор
- `title` - название страницы
- `content` - содержимое в формате Markdown
- `created_by` - ID пользователя-создателя
- `created_at` - дата создания
- `base_id` - ID базовой страницы для версионирования
- `parent_id` - ID родительской страницы
- `current` - флаг текущей версии

## Установка и запуск

### Требования
- Docker и Docker Compose
- Node.js 18+ (для разработки)
- OpenAI API ключ (опционально, для LLM интеграции)

### Запуск проекта

1. Клонируйте репозиторий:
```bash
git clone <repository-url>
cd docmodule
```

2. Запустите контейнеры:
```bash
docker-compose up -d
```

3. Установите зависимости PHP:
```bash
docker-compose exec -u local development composer install
```

4. Установите зависимости Node.js:
```bash
docker-compose exec -u local development npm install
```

5. Настройте базу данных:
```bash
docker-compose exec -u local development php artisan migrate
docker-compose exec -u local development php artisan db:seed
```

6. (Опционально) Настройте OpenAI API для LLM интеграции:
```bash
# Добавьте в .env файл:
OPENAI_API_KEY=sk-your-openai-api-key-here
OPENAI_MODEL=gpt-4o-mini
```

7. Соберите фронтенд:
```bash
docker-compose exec -u local development npm run build
```

8. Запустите сервер разработки:
```bash
docker-compose exec -u local development php artisan serve --host=0.0.0.0 --port=8000
```

Приложение будет доступно по адресу: http://localhost:8000

### Учетные данные по умолчанию:
- Email: `admin@example.com`
- Пароль: `password`

## Использование

### Создание страницы
1. Перейдите в раздел "Страницы"
2. Нажмите "Создать страницу"
3. Заполните название и содержимое
4. Сохраните страницу

### Редактирование страницы
1. Откройте страницу для просмотра
2. Нажмите "Редактировать"
3. Внесите изменения
4. При сохранении автоматически создается новая версия

### Создание дочерних страниц
1. Откройте родительскую страницу
2. В разделе "Дочерние страницы" нажмите "Добавить дочернюю страницу"
3. Заполните форму и сохраните

### Управление версиями
1. Откройте страницу
2. Нажмите "Версии"
3. Просматривайте историю версий
4. Восстанавливайте нужные версии

## API Endpoints

### Основные маршруты:
- `GET /pages` - список страниц
- `GET /pages/create` - форма создания
- `POST /pages` - сохранение новой страницы
- `GET /pages/{page}/edit` - форма редактирования
- `PUT /pages/{page}` - обновление страницы
- `GET /pages/{page}` - просмотр страницы
- `DELETE /pages/{page}` - удаление страницы

### Дополнительные маршруты:
- `GET /pages/{page}/versions` - список версий
- `POST /pages/{page}/restore/{version}` - восстановление версии

## Разработка

### Структура проекта:
```
app/
├── Models/
│   └── Page.php                    # Модель страницы
├── Http/
│   ├── Controllers/
│   │   └── PageController.php      # Контроллер страниц
│   └── Requests/
│       └── PageRequest.php         # Валидация
├── Services/
│   ├── TaskDescriptionGenerator/   # LLM интеграция
│   │   ├── TaskDescriptionGeneratorInterface.php
│   │   ├── OpenAIDescriptionGenerator.php
│   │   └── StubDescriptionGenerator.php
│   └── TaskTracker/                # Интеграция с таск-трекерами
│       ├── TaskTrackerInterface.php
│       ├── TaskTrackerService.php
│       └── FakeIntegration.php
├── database/
│   ├── migrations/
│   │   └── create_pages_table.php  # Миграция таблицы
│   ├── factories/
│   │   └── PageFactory.php         # Фабрика для тестов
│   └── seeders/
│       └── PageSeeder.php          # Сидер данных
└── resources/
    └── js/
        ├── pages/                   # Vue страницы
        │   ├── Index.vue
        │   ├── Create.vue
        │   ├── Edit.vue
        │   ├── Show.vue
        │   └── Versions.vue
        └── components/              # Vue компоненты
            ├── MarkdownRenderer.vue
            ├── MarkdownPreview.vue
            └── CreateChildPage.vue
```

### Команды для разработки:

Сборка фронтенда:
```bash
docker-compose exec -u local development npm run build
```

Запуск в режиме разработки:
```bash
docker-compose exec -u local development npm run dev
```

Запуск тестов:
```bash
docker-compose exec -u local development php artisan test
```

## Технологии

- **Laravel 12** - PHP фреймворк
- **Inertia.js** - мост между Laravel и Vue
- **Vue 3** - JavaScript фреймворк
- **TypeScript** - типизированный JavaScript
- **Tailwind CSS** - CSS фреймворк
- **Reka UI** - компонентная библиотека
- **Marked** - Markdown парсер
- **SQLite** - база данных
- **OpenAI API** - интеграция с LLM для генерации описаний

## Лицензия

MIT License


### Команды

```
Сравни текущую ветку с веткой main и создай задача на основе различиях в документации из папки /docs
Исследуй документацию, чтобы лучше понимать изменеиня в документации. Создай чёткое об подобробное описание задачи необходимое для приведения кодовой базы в соответсвие с документацийе.
```

```
Создай технический план на основе задачи. Исследук код чтобы создать оптимальный технический план со всем подробностями необходимыми для реализации задачи. Технический план должен описывать порядок действий для того, чтобы выполнить все требования из задачи.
```