# Laravel без Sail: запуск через Docker Compose

## 1) Поднять контейнеры

```bash
docker compose down --remove-orphans
docker compose up -d --build
```

## 2) Установить backend зависимости

```bash
docker compose exec app composer install
```

## 3) Подготовить Laravel

```bash
docker compose exec app php artisan key:generate
docker compose exec app sh -c "mkdir -p database && touch database/database.sqlite"
docker compose exec app php artisan migrate
```

## 4) Собрать frontend (prod)

```bash
docker compose run --rm node sh -c "npm install && npm run build"
```

## 5) Открыть проект

- Приложение: http://localhost:8080

## Dev-режим Vite (по желанию)

```bash
docker compose --profile frontend up -d node
```

- Vite: http://localhost:5173
