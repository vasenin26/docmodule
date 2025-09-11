#!/bin/bash

set -e

# Конфигурация
APP_NAME="docmodule"
COMPOSE_FILE="docker-compose.prod.yaml"
BACKUP_DIR="/opt/backups"
LOG_FILE="/var/log/deploy.log"

# Функция логирования
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Функция для создания бэкапа
create_backup() {
    log "Creating backup..."
    
    BACKUP_NAME="backup_$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$BACKUP_DIR/$BACKUP_NAME"
    
    # Бэкап базы данных
    docker compose -f "$COMPOSE_FILE" exec -T db pg_dump -U "${DB_USERNAME:-docmodule_user}" "${DB_DATABASE:-docmodule_prod}" > "$BACKUP_DIR/$BACKUP_NAME/database.sql"
    
    # Бэкап volumes
    docker run --rm -v docmodule_app_storage:/data -v "$BACKUP_DIR/$BACKUP_NAME":/backup alpine tar czf /backup/storage.tar.gz -C /data .
    
    log "Backup created: $BACKUP_NAME"
}

# Функция для отката
rollback() {
    log "Rolling back to previous version..."
    
    # Остановка текущих контейнеров
    docker compose -f "$COMPOSE_FILE" down
    
    # Откат к предыдущему образу
    docker tag "${APP_NAME}_app:backup" "${APP_NAME}_app:latest"
    
    # Запуск откаченной версии
    docker compose -f "$COMPOSE_FILE" up -d
    
    log "Rollback completed"
}

# Функция проверки здоровья приложения
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

# Функция обновления приложения
update_app() {
    local image_tag="$1"
    
    log "Starting deployment of image: $image_tag"
    
    # Создание бэкапа
    create_backup
    
    # Сохранение текущего образа как backup
    docker tag "${APP_NAME}_app:latest" "${APP_NAME}_app:backup" 2>/dev/null || true
    
    # Обновление образа
    log "Pulling new image: $image_tag"
    docker pull "$image_tag"
    
    # Обновление тега образа в docker-compose
    sed -i "s|image: ghcr.io/vasenin26/docmodule:.*|image: $image_tag|g" "$COMPOSE_FILE"
    
    # Остановка приложения
    log "Stopping current application..."
    docker compose -f "$COMPOSE_FILE" stop app
    
    # Запуск обновленного приложения
    log "Starting updated application..."
    docker compose -f "$COMPOSE_FILE" up -d app
    
    # Ожидание запуска
    sleep 30
    
    # Проверка здоровья
    if health_check; then
        log "Deployment successful"
        
        # Очистка старых образов
        docker image prune -f
        docker system prune -f
        
        # Очистка старых бэкапов (оставляем последние 5)
        ls -t "$BACKUP_DIR" | tail -n +6 | xargs -r -I {} rm -rf "$BACKUP_DIR"/{}
        
    else
        log "Deployment failed, rolling back..."
        rollback
        exit 1
    fi
}

# Функция для выполнения миграций
run_migrations() {
    log "Running database migrations..."
    
    # Ожидание доступности базы данных
    docker compose -f "$COMPOSE_FILE" exec db pg_isready -U "${DB_USERNAME:-docmodule_user}" -d "${DB_DATABASE:-docmodule_prod}"
    
    # Выполнение миграций
    docker compose -f "$COMPOSE_FILE" exec app php artisan migrate --force
    
    log "Migrations completed"
}

# Обработка webhook запроса
handle_webhook() {
    log "Received webhook request"
    
    # Чтение данных из stdin
    local payload=$(cat)
    local image=$(echo "$payload" | jq -r '.image // empty')
    local environment=$(echo "$payload" | jq -r '.environment // empty')
    local version=$(echo "$payload" | jq -r '.version // empty')
    local tag=$(echo "$payload" | jq -r '.tag // empty')
    
    if [ -z "$image" ]; then
        log "Error: No image specified in webhook payload"
        exit 1
    fi
    
    if [ "$environment" != "production" ]; then
        log "Ignoring deployment for environment: $environment"
        exit 0
    fi
    
    # Проверка формата тега (должен быть vX.X.X)
    if [[ -n "$tag" && ! "$tag" =~ ^v[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
        log "Error: Invalid tag format. Expected vX.X.X, got: $tag"
        exit 1
    fi
    
    log "Deploying version: $version (tag: $tag)"
    update_app "$image"
    run_migrations
    
    # Создание файла с информацией о текущей версии
    echo "{
        \"version\": \"$version\",
        \"tag\": \"$tag\",
        \"image\": \"$image\",
        \"deployed_at\": \"$(date -Iseconds)\",
        \"deployed_by\": \"webhook\"
    }" > /opt/current_version.json
    
    log "Version information saved to /opt/current_version.json"
}

# Основная логика
main() {
    case "${1:-}" in
        "webhook")
            handle_webhook
            ;;
        "deploy")
            if [ -z "$2" ]; then
                echo "Usage: $0 deploy <image_tag>"
                exit 1
            fi
            update_app "$2"
            run_migrations
            ;;
        "rollback")
            rollback
            ;;
        "backup")
            create_backup
            ;;
        "health")
            health_check
            ;;
        "version")
            if [ -f "/opt/current_version.json" ]; then
                cat /opt/current_version.json | jq '.'
            else
                echo "No version information available"
                exit 1
            fi
            ;;
        *)
            echo "Usage: $0 {webhook|deploy|rollback|backup|health|version}"
            echo ""
            echo "Commands:"
            echo "  webhook    - Handle deployment webhook from CI/CD"
            echo "  deploy     - Deploy specific image tag"
            echo "  rollback   - Rollback to previous version"
            echo "  backup     - Create manual backup"
            echo "  health     - Check application health"
            echo "  version    - Show current deployed version"
            exit 1
            ;;
    esac
}

# Загрузка переменных окружения
if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
fi

# Создание необходимых директорий
mkdir -p "$BACKUP_DIR"
mkdir -p "$(dirname "$LOG_FILE")"

# Запуск основной функции
main "$@"
