# Автоматический деплой через SSH

## Архитектура

1. **GitHub Actions** - сборка и публикация Docker образов
2. **GitHub Container Registry** - хранение образов
3. **SSH подключение** - прямое подключение к серверу для деплоя
4. **Deploy скрипт** - автоматическое обновление приложения

## Настройка на сервере

### 1. Подготовка сервера

```bash
# Установка Docker и Docker Compose
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Установка Node.js для webhook сервера
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# Установка дополнительных утилит
sudo apt-get install -y jq curl
```

### 2. Настройка GitHub Container Registry

1. Перейдите в Settings репозитория → Secrets and variables → Actions
2. Убедитесь, что `GITHUB_TOKEN` доступен (автоматически)
3. Настройте доступ к packages в Settings → Actions → General

### 3. Настройка SSH ключей

```bash
# Создание SSH ключа для GitHub Actions
ssh-keygen -t ed25519 -f ~/.ssh/github_deploy -N ""

# Добавление публичного ключа в authorized_keys
cat ~/.ssh/github_deploy.pub >> ~/.ssh/authorized_keys

# Настройка прав
chmod 600 ~/.ssh/github_deploy
chmod 644 ~/.ssh/authorized_keys
```

### 4. Настройка переменных окружения

Создайте файл `.env` на сервере:

```bash
# Основные настройки приложения
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# База данных
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=docmodule_prod
DB_USERNAME=docmodule_user
DB_PASSWORD=secure_password

# Другие сервисы
OPENAI_API_KEY=your_openai_key
```

### 5. Настройка GitHub Secrets

В настройках репозитория добавьте:

- `SERVER_HOST` - IP адрес вашего сервера
- `SERVER_USER` - имя пользователя для SSH
- `SERVER_SSH_KEY` - содержимое приватного ключа (~/.ssh/github_deploy)
- `SERVER_PORT` - порт SSH (обычно 22)

### 6. Настройка файрвола

```bash
# Разрешение портов
sudo ufw allow 80
sudo ufw allow 443
sudo ufw allow 22   # SSH
sudo ufw enable
```

### 7. Первоначальный деплой

```bash
# Создание директории для деплоя
sudo mkdir -p /opt/deploy
sudo chown $USER:$USER /opt/deploy

# Копирование файлов деплоя
cp deploy/deploy.sh /opt/deploy/
chmod +x /opt/deploy/deploy.sh

# Копирование compose файла
cp docker-compose.prod.yaml /opt/deploy/

# Запуск приложения
cd /opt/deploy
docker compose -f docker-compose.prod.yaml up -d

# Проверка статуса
docker compose -f docker-compose.prod.yaml ps
```

## Использование

### Автоматический деплой

**Деплой происходит только при создании тега в формате vX.X.X:**

```bash
# Создание и пуш тега для деплоя
git tag v1.0.0
git push origin v1.0.0
```

При создании тега GitHub Actions автоматически:
1. Соберет Docker образ с тегом версии
2. Опубликует его в GitHub Container Registry
3. Подключится к серверу по SSH
4. Запустит скрипт деплоя для обновления приложения

### Ручной деплой

```bash
# Деплой конкретной версии
/opt/deploy/deploy.sh deploy ghcr.io/your-username/docmodule:latest

# Откат к предыдущей версии
/opt/deploy/deploy.sh rollback

# Создание бэкапа
/opt/deploy/deploy.sh backup

# Проверка здоровья
/opt/deploy/deploy.sh health

# Просмотр текущей версии
/opt/deploy/deploy.sh version
```

### Мониторинг

```bash
# Логи деплоя
tail -f /var/log/deploy.log

# Статус приложения
docker compose -f docker-compose.prod.yaml ps
docker compose -f docker-compose.prod.yaml logs -f

# Проверка SSH подключения
ssh -i ~/.ssh/github_deploy $USER@localhost "echo 'SSH connection works'"
```

## Безопасность

1. **SSH ключи** - используйте ed25519 ключи с паролем
2. **HTTPS** - настройте SSL сертификаты
3. **Firewall** - ограничьте SSH доступ только с нужных IP
4. **Права доступа** - настройте правильные права на файлы и директории

## Troubleshooting

### Проблемы с доступом к registry

```bash
# Логин в GitHub Container Registry
echo $GITHUB_TOKEN | docker login ghcr.io -u USERNAME --password-stdin
```

### Проблемы с SSH

```bash
# Проверка SSH ключей
ls -la ~/.ssh/
ssh-keygen -l -f ~/.ssh/github_deploy.pub

# Тест SSH подключения
ssh -i ~/.ssh/github_deploy $USER@localhost "echo 'SSH works'"

# Проверка прав на файлы
ls -la /opt/deploy/
sudo chown -R $USER:$USER /opt/deploy/
chmod +x /opt/deploy/deploy.sh
```

### Проблемы с Docker

```bash
# Проверка статуса Docker
sudo systemctl status docker

# Перезапуск Docker
sudo systemctl restart docker
```
