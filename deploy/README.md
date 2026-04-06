# Автоматический деплой через SSH

## Архитектура

1. **GitHub Actions** - сборка и публикация Docker образов
2. **GitHub Container Registry** - хранение образов
3. **SSH подключение** - прямое подключение к серверу для деплоя
4. **Deploy скрипт** - автоматическое обновление приложения
5. **Traefik** (на сервере) - TLS и маршрутизация `docsmodule.ru` / `www.docsmodule.ru` во внешнюю Docker-сеть `web` (без `nginx-proxy` в compose)

## Traefik и сеть Docker

- Стек приложения подключается к **внешней** сети Traefik (по умолчанию имя сети: `web`).
- Перед первым запуском убедитесь, что Traefik подключён к этой же сети, например:  
  `docker network connect web traefik` (имя контейнера Traefik уточните через `docker ps`).
- В `.env` на сервере можно задать `TRAEFIK_NETWORK=web` (значение по умолчанию в compose — `web`).
- Для HTTPS в labels используется `certresolver=myresolver` и entrypoint `websecure` (как у других сервисов на этом сервере). При другом resolver/entrypoint задайте `TRAEFIK_ENTRYPOINTS=...` в `.env`.

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

Создайте файл `.env` в каталоге деплоя (CI кладёт compose в `/opt/docmodule`, там же должен лежать `.env`):

```bash
# Образ из GHCR (при деплое скрипт обновит APP_IMAGE на конкретный тег)
APP_IMAGE=ghcr.io/<owner>/<repo>:<tag>

# Traefik (опционально, если не совпадает с умолчанием)
TRAEFIK_NETWORK=web
# TRAEFIK_ENTRYPOINTS=websecure

# Опционально: базовый URL для дополнительной проверки с хоста после деплоя
# HEALTH_PUBLIC_URL=https://docsmodule.ru

# Основные настройки приложения
APP_ENV=production
APP_DEBUG=false
APP_URL=https://docsmodule.ru

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

Для pull приватного образа из GHCR на сервере задайте `GHCR_USERNAME` и `GHCR_TOKEN` (токен с правом `read:packages`).

### 5. Настройка GitHub Secrets и Variables

В **Settings → Secrets and variables → Actions**:

**Secrets**

- `SERVER_SSH_KEY` — приватный SSH-ключ для входа на сервер

**Variables** (repository или environment `Production`)

- `SERVER_HOST` — хост или IP сервера
- `SERVER_USER` — пользователь SSH
- `SERVER_PORT` — порт SSH (часто `22`)

### 6. Настройка файрвола

```bash
# Разрешение портов
sudo ufw allow 80
sudo ufw allow 443
sudo ufw allow 22   # SSH
sudo ufw enable
```

### 7. Первоначальный деплой

CI при пуше в `main` сам скачивает `deploy.sh` и `docker-compose.prod.yaml` в `/opt/docmodule` и запускает деплой.

Ручной первый запуск (если нужно без CI):

```bash
sudo mkdir -p /opt/docmodule
sudo chown $USER:$USER /opt/docmodule
cp deploy/deploy.sh /opt/docmodule/
cp docker-compose.prod.yaml /opt/docmodule/docker-compose.yaml
# Создайте /opt/docmodule/.env (см. выше), задайте APP_IMAGE и секреты

cd /opt/docmodule
docker compose -f docker-compose.yaml --env-file .env up -d

docker compose -f docker-compose.yaml ps
```

Порты 80/443 на хосте занимает Traefik; стек `docmodule` их не публикует.

## Использование

### Автоматический деплой

**Деплой запускается при push в ветку `main`** (workflow `../.github/workflows/deploy.yml`):

1. Создаётся релизный тег вида `vYYYY.WW.Z`
2. Собирается и публикуется образ в GHCR
3. По SSH обновляются файлы в `/opt/docmodule` и выполняется `./deploy.sh deploy <image:tag>`

### Ручной деплой

```bash
cd /opt/docmodule

# Деплой конкретной версии
./deploy.sh deploy ghcr.io/your-username/docmodule:vYYYY.WW.Z

# Откат к предыдущей версии
./deploy.sh rollback

# Создание бэкапа
./deploy.sh backup

# Проверка здоровья
./deploy.sh health

# Просмотр текущей версии
./deploy.sh version
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
