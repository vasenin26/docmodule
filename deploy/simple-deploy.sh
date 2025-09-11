#!/bin/bash

# Простой скрипт деплоя без webhook сервера
# Можно запускать вручную или по cron

set -e

# Конфигурация
APP_NAME="docmodule"
COMPOSE_FILE="docker-compose.prod.yaml"
REGISTRY="ghcr.io/vasenin26/docmodule"
LOG_FILE="/var/log/deploy.log"

# Функция логирования
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Функция проверки новых версий
check_for_updates() {
    local latest_tag=$(curl -s "https://api.github.com/repos/vasenin26/docmodule/releases/latest" | jq -r '.tag_name')
    local current_tag=""
    
    if [ -f "/opt/current_version.json" ]; then
        current_tag=$(jq -r '.tag' /opt/current_version.json)
    fi
    
    if [ "$latest_tag" != "$current_tag" ] && [ "$latest_tag" != "null" ]; then
        log "Found new version: $latest_tag (current: $current_tag)"
        deploy_version "$latest_tag"
    else
        log "No updates available (current: $current_tag)"
    fi
}

# Функция деплоя версии
deploy_version() {
    local tag="$1"
    local image="$REGISTRY:$tag"
    
    log "Deploying version: $tag"
    
    # Создание бэкапа
    create_backup
    
    # Обновление образа
    log "Pulling new image: $image"
    docker pull "$image"
    
    # Обновление compose файла
    sed -i "s|image: $REGISTRY:.*|image: $image|g" "$COMPOSE_FILE"
    
    # Перезапуск приложения
    log "Restarting application..."
    docker compose -f "$COMPOSE_FILE" stop app
    docker compose -f "$COMPOSE_FILE" up -d app
    
    # Ожидание запуска
    sleep 30
    
    # Проверка здоровья
    if health_check; then
        log "Deployment successful"
        
        # Сохранение информации о версии
        echo "{
            \"version\": \"$tag\",
            \"tag\": \"$tag\",
            \"image\": \"$image\",
            \"deployed_at\": \"$(date -Iseconds)\",
            \"deployed_by\": \"simple-deploy\"
        }" > /opt/current_version.json
        
        # Очистка старых образов
        docker image prune -f
        
    else
        log "Deployment failed"
        exit 1
    fi
}

# Функция для создания бэкапа
create_backup() {
    log "Creating backup..."
    
    local backup_name="backup_$(date +%Y%m%d_%H%M%S)"
    mkdir -p "/opt/backups/$backup_name"
    
    # Бэкап базы данных
    docker compose -f "$COMPOSE_FILE" exec -T db pg_dump -U "${DB_USERNAME:-laravel}" "${DB_DATABASE:-laravel}" > "/opt/backups/$backup_name/database.sql"
    
    log "Backup created: $backup_name"
}

# Функция проверки здоровья
health_check() {
    log "Performing health check..."
    
    local max_attempts=30
    local attempt=1
    
    while [ $attempt -le $max_attempts ]; do
        if curl -f http://localhost/health >/dev/null 2>&1; then
            log "Health check passed"
            return 0
        fi
        
        log "Health check attempt $attempt/$max_attempts failed, waiting..."
        sleep 10
        ((attempt++))
    done
    
    log "Health check failed after $max_attempts attempts"
    return 1
}

# Основная логика
main() {
    case "${1:-}" in
        "check")
            check_for_updates
            ;;
        "deploy")
            if [ -z "$2" ]; then
                echo "Usage: $0 deploy <tag>"
                exit 1
            fi
            deploy_version "$2"
            ;;
        "status")
            if [ -f "/opt/current_version.json" ]; then
                cat /opt/current_version.json | jq '.'
            else
                echo "No version information available"
            fi
            ;;
        *)
            echo "Usage: $0 {check|deploy|status}"
            echo ""
            echo "Commands:"
            echo "  check     - Check for new versions and deploy if available"
            echo "  deploy    - Deploy specific version"
            echo "  status    - Show current deployed version"
            exit 1
            ;;
    esac
}

# Загрузка переменных окружения
if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
fi

# Создание необходимых директорий
mkdir -p /opt/backups
mkdir -p "$(dirname "$LOG_FILE")"

# Запуск основной функции
main "$@"
