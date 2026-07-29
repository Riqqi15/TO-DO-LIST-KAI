# Backend Docker Environment Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Run MySQL, phpMyAdmin, and Mailpit through Docker Compose while Laravel, PHP, and Vite continue running directly on Windows.

**Architecture:** Docker Compose owns development infrastructure and a persistent MySQL volume. Laravel connects from Windows through mapped host ports, so database and SMTP hosts use `127.0.0.1`; phpMyAdmin connects to MySQL through the internal Compose service name.

**Tech Stack:** Docker Desktop 29, Docker Compose 5, MySQL 8.4, phpMyAdmin 5.2 Apache, Mailpit 1.30, Laravel 12.

---

### Task 1: Define the infrastructure stack

**Files:**
- Create: `compose.yaml`

- [ ] **Step 1: Create the Compose services**

```yaml
name: todo-list-kai

services:
  mysql:
    image: mysql:8.4
    restart: unless-stopped
    ports:
      - "${FORWARD_DB_PORT:-3307}:3306"
    environment:
      MYSQL_DATABASE: "${DB_DATABASE}"
      MYSQL_USER: "${DB_USERNAME}"
      MYSQL_PASSWORD: "${DB_PASSWORD}"
      MYSQL_ROOT_PASSWORD: "${DB_ROOT_PASSWORD}"
    volumes:
      - mysql_data:/var/lib/mysql
    healthcheck:
      test:
        - CMD-SHELL
        - mysqladmin ping -h localhost -uroot -p$${MYSQL_ROOT_PASSWORD} --silent
      interval: 5s
      timeout: 5s
      retries: 10
      start_period: 20s

  phpmyadmin:
    image: phpmyadmin:5.2-apache
    restart: unless-stopped
    ports:
      - "8080:80"
    environment:
      PMA_HOST: mysql
      PMA_PORT: 3306
      UPLOAD_LIMIT: 64M
    depends_on:
      mysql:
        condition: service_healthy

  mailpit:
    image: axllent/mailpit:v1.30.0
    restart: unless-stopped
    ports:
      - "1025:1025"
      - "8025:8025"
    environment:
      MP_MAX_MESSAGES: 1000

volumes:
  mysql_data:
```

- [ ] **Step 2: Validate the Compose file**

Run:

```powershell
docker compose config --quiet
```

Expected: exit code `0` with no configuration error.

### Task 2: Configure Laravel development environment

**Files:**
- Modify: `.env.example`
- Modify locally: `.env`

- [ ] **Step 1: Replace SQLite defaults with MySQL development defaults**

Use these values in `.env.example` and the ignored local `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=todo_list_kai
DB_USERNAME=todo_user
DB_PASSWORD=todo_local_password
DB_ROOT_PASSWORD=root_local_password
FORWARD_DB_PORT=3307
```

- [ ] **Step 2: Configure SMTP preview through Mailpit**

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="todo@kai.local"
MAIL_FROM_NAME="${APP_NAME}"
```

- [ ] **Step 3: Clear Laravel configuration cache**

Run:

```powershell
& 'C:\xampp\php\php.exe' artisan config:clear
```

Expected: Laravel reports that the configuration cache was cleared.

### Task 3: Start and initialize backend services

**Files:**
- Runtime: Docker containers and named volume `todo-list-kai_mysql_data`

- [ ] **Step 1: Pull and start the stack**

Run:

```powershell
docker compose up -d --wait
```

Expected: MySQL becomes healthy; phpMyAdmin and Mailpit are running.

- [ ] **Step 2: Run Laravel migrations**

Run:

```powershell
& 'C:\xampp\php\php.exe' artisan migrate --force
```

Expected: Laravel migrations complete against MySQL.

### Task 4: Verify services and record configuration

**Files:**
- Verify: `compose.yaml`
- Verify: `.env.example`
- Verify locally: `.env`
- Commit: `docs/superpowers/plans/2026-07-29-backend-docker-environment.md`

- [ ] **Step 1: Inspect service health**

Run:

```powershell
docker compose ps
```

Expected: MySQL is healthy and both web tools are running.

- [ ] **Step 2: Verify Laravel database connection**

Run:

```powershell
& 'C:\xampp\php\php.exe' artisan migrate:status
```

Expected: all base migrations report `Ran`.

The XAMPP PHP runtime is used because it provides the `pdo_mysql` extension
required by Laravel's MySQL connection. The separate PHP installation under
`C:\Program Files\PHP` contains the driver file but does not enable the
extension in its active `php.ini`.

- [ ] **Step 3: Verify local web endpoints**

Run HTTP requests against:

```text
http://127.0.0.1:8080
http://127.0.0.1:8025
```

Expected: both return successful HTTP responses.

- [ ] **Step 4: Check tracked changes**

Run:

```powershell
git diff --check
git status -sb
```

Expected: `.env` remains ignored; only `compose.yaml`, `.env.example`, and the
plan are tracked changes.

- [ ] **Step 5: Commit the backend environment**

```powershell
git add compose.yaml .env.example docs/superpowers/plans/2026-07-29-backend-docker-environment.md
git commit -m "chore: add Docker backend services"
```
