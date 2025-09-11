#!/bin/bash

# Скрипт для создания релиза и автоматического деплоя

set -e

# Проверка наличия git
if ! command -v git &> /dev/null; then
    echo "Git не установлен"
    exit 1
fi

# Проверка наличия jq
if ! command -v jq &> /dev/null; then
    echo "jq не установлен. Установите: apt install jq"
    exit 1
fi

# Функция для получения следующей версии
get_next_version() {
    local version_type="$1"  # major, minor, patch
    
    # Получение последнего тега
    local last_tag=$(git describe --tags --abbrev=0 2>/dev/null || echo "v0.0.0")
    local version=${last_tag#v}  # Убираем префикс 'v'
    
    # Разбор версии
    IFS='.' read -ra VERSION_PARTS <<< "$version"
    local major=${VERSION_PARTS[0]:-0}
    local minor=${VERSION_PARTS[1]:-0}
    local patch=${VERSION_PARTS[2]:-0}
    
    case "$version_type" in
        "major")
            ((major++))
            minor=0
            patch=0
            ;;
        "minor")
            ((minor++))
            patch=0
            ;;
        "patch")
            ((patch++))
            ;;
        *)
            echo "Неверный тип версии. Используйте: major, minor, patch"
            exit 1
            ;;
    esac
    
    echo "v${major}.${minor}.${patch}"
}

# Функция для проверки статуса git
check_git_status() {
    if ! git diff-index --quiet HEAD --; then
        echo "Ошибка: Есть несохраненные изменения"
        echo "Сохраните изменения перед созданием релиза"
        exit 1
    fi
    
    if [ "$(git branch --show-current)" != "main" ]; then
        echo "Предупреждение: Вы не на ветке main"
        read -p "Продолжить? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi
}

# Функция для создания CHANGELOG
update_changelog() {
    local version="$1"
    local changelog_file="CHANGELOG.md"
    
    if [ ! -f "$changelog_file" ]; then
        echo "# Changelog" > "$changelog_file"
        echo "" >> "$changelog_file"
    fi
    
    # Получение коммитов с последнего тега
    local last_tag=$(git describe --tags --abbrev=0 2>/dev/null || echo "")
    local commits=$(git log --pretty=format:"- %s" ${last_tag:+$last_tag..HEAD} 2>/dev/null || echo "")
    
    # Добавление новой версии в начало файла
    local temp_file=$(mktemp)
    echo "# Changelog" > "$temp_file"
    echo "" >> "$temp_file"
    echo "## [$version] - $(date +%Y-%m-%d)" >> "$temp_file"
    echo "" >> "$temp_file"
    
    if [ -n "$commits" ]; then
        echo "$commits" >> "$temp_file"
    else
        echo "- Initial release" >> "$temp_file"
    fi
    
    echo "" >> "$temp_file"
    
    # Добавление остального содержимого (пропуская заголовок)
    if [ -f "$changelog_file" ]; then
        tail -n +3 "$changelog_file" >> "$temp_file"
    fi
    
    mv "$temp_file" "$changelog_file"
}

# Основная функция
main() {
    local version_type="${1:-patch}"
    local create_pr="${2:-false}"
    
    echo "🚀 Создание релиза..."
    
    # Проверки
    check_git_status
    
    # Получение следующей версии
    local new_version=$(get_next_version "$version_type")
    echo "📋 Новая версия: $new_version"
    
    # Обновление CHANGELOG
    echo "📝 Обновление CHANGELOG..."
    update_changelog "$new_version"
    
    # Коммит изменений
    echo "💾 Коммит изменений..."
    git add CHANGELOG.md
    git commit -m "chore: bump version to $new_version"
    
    # Создание тега
    echo "🏷️  Создание тега $new_version..."
    git tag -a "$new_version" -m "Release $new_version"
    
    # Пуш изменений
    echo "📤 Отправка изменений..."
    git push origin main
    git push origin "$new_version"
    
    echo ""
    echo "✅ Релиз $new_version создан успешно!"
    echo ""
    echo "📋 Что произошло:"
    echo "   - Создан тег $new_version"
    echo "   - Обновлен CHANGELOG.md"
    echo "   - Изменения отправлены в репозиторий"
    echo "   - GitHub Actions запустит автоматический деплой"
    echo ""
    echo "🔍 Отследить деплой можно здесь:"
    echo "   https://github.com/$(git config --get remote.origin.url | sed 's/.*github.com[:/]\([^.]*\).*/\1/')/actions"
    echo ""
    echo "📊 Проверить статус деплоя на сервере:"
    echo "   /opt/deploy/deploy.sh version"
}

# Показать помощь
show_help() {
    echo "Использование: $0 [VERSION_TYPE]"
    echo ""
    echo "VERSION_TYPE:"
    echo "  patch  - Обновление patch версии (1.0.0 -> 1.0.1) [по умолчанию]"
    echo "  minor  - Обновление minor версии (1.0.0 -> 1.1.0)"
    echo "  major  - Обновление major версии (1.0.0 -> 2.0.0)"
    echo ""
    echo "Примеры:"
    echo "  $0           # Создать patch релиз"
    echo "  $0 minor     # Создать minor релиз"
    echo "  $0 major     # Создать major релиз"
}

# Обработка аргументов
case "${1:-}" in
    "help"|"-h"|"--help")
        show_help
        ;;
    "patch"|"minor"|"major"|"")
        main "${1:-patch}"
        ;;
    *)
        echo "Ошибка: Неверный аргумент '$1'"
        echo ""
        show_help
        exit 1
        ;;
esac
