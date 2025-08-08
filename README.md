# DocModule - Система управления документацией

Проект построен на Laravel 12 с использованием:
- **Backend**: Laravel + Inertia.js
- **Frontend**: Vue 3 + TypeScript + Tailwind CSS
- **UI Framework**: Reka UI (компоненты)
- **База данных**: SQLite (по умолчанию)

## Функционал

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

6. Соберите фронтенд:
```bash
docker-compose exec -u local development npm run build
```

7. Запустите сервер разработки:
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

## Лицензия

MIT License
