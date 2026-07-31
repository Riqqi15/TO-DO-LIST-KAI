# Docker Local Runtime Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make `docker compose up -d --build` start Laravel web, migrations, queue processing, scheduling, MySQL, phpMyAdmin, and Mailpit for local backend testing.

**Architecture:** Build one reusable PHP 8.4 CLI image with Laravel's required runtime extensions, Composer dependencies, application source, and compiled frontend assets. Run that image in four Compose services (`migrate`, `app`, `queue`, and `scheduler`) and override database/mail hosts with Docker service names while keeping secrets in the local `.env`.

**Tech Stack:** Docker Compose, PHP 8.4 CLI, Laravel 12, MySQL 8.4, Mailpit

---

## File Map

- Create `Dockerfile`: reusable local PHP runtime with Composer and PHP extensions.
- Create `.dockerignore`: prevent secrets, Git metadata, logs, and local dependency trees from entering the build context.
- Modify `compose.yaml`: add Laravel migration, web, queue, and scheduler services.
- Modify `docs/ai-handoff/BACKEND_PROGRESS.md`: replace the Windows multi-terminal startup instructions with the verified Docker workflow.

### Task 1: Add the PHP Runtime Image

**Files:**
- Create: `Dockerfile`
- Create: `.dockerignore`

- [ ] **Step 1: Add the runtime Dockerfile**

```dockerfile
FROM php:8.4-cli-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        libicu-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install -j"$(nproc)" \
        intl \
        pcntl \
        pdo_mysql \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
```

- [ ] **Step 2: Exclude secrets and unnecessary local files**

```dockerignore
.env
.git
.github
.idea
.vscode
node_modules
vendor
storage/logs/*
laravel-server*.log
npm-debug.log*
```

- [ ] **Step 3: Build the image to verify extensions compile**

Run:

```powershell
docker compose build app
```

Expected: build exits `0` and produces the shared Laravel runtime image.

### Task 2: Add Laravel Compose Services

**Files:**
- Modify: `compose.yaml`

- [ ] **Step 1: Define a reusable Laravel service anchor**

Add an `x-laravel-service` block with the shared local image build, working
directory, `.env` runtime injection, Docker host overrides, and `unless-stopped`
restart policy.

- [ ] **Step 2: Add the one-shot migration service**

Add `migrate` with:

```yaml
command: php artisan migrate --force
restart: "no"
depends_on:
  mysql:
    condition: service_healthy
```

- [ ] **Step 3: Add the web service**

Add `app` with:

```yaml
command: php artisan serve --host=0.0.0.0 --port=8000
ports:
  - "8000:8000"
depends_on:
  migrate:
    condition: service_completed_successfully
  mailpit:
    condition: service_started
```

Include an HTTP healthcheck against `http://127.0.0.1:8000/login`.

- [ ] **Step 4: Add queue and scheduler services**

Add:

```yaml
queue:
  command: php artisan queue:work --tries=3 --timeout=60 --sleep=1

scheduler:
  command: php artisan schedule:work
```

Both services depend on successful migration and Mailpit startup.

- [ ] **Step 5: Validate the Compose model**

Run:

```powershell
docker compose config --quiet
```

Expected: exit `0` with no Compose validation errors.

### Task 3: Replace Windows Processes with Docker

**Files:**
- No source files.

- [ ] **Step 1: Identify the exact project PHP processes**

Inspect PHP command lines and select only the To Do List KAI web server,
`schedule:work`, and `queue:work` processes.

- [ ] **Step 2: Stop the selected Windows processes**

Stop only the verified project process IDs so port `8000` and reminder processing
are not duplicated.

- [ ] **Step 3: Start the complete stack**

Run:

```powershell
docker compose up -d --build
```

Expected: `migrate` exits `0`; `app`, `queue`, `scheduler`, `mysql`,
`phpmyadmin`, and `mailpit` are running.

### Task 4: Verify Runtime Behavior

**Files:**
- No source files.

- [ ] **Step 1: Verify service state**

Run:

```powershell
docker compose ps -a
```

Expected: MySQL is healthy, app becomes healthy, queue/scheduler are running,
and migrate exited `0`.

- [ ] **Step 2: Verify the application**

Run:

```powershell
curl.exe -sS -o NUL -w "%{http_code}" http://127.0.0.1:8000/login
```

Expected: `200`.

- [ ] **Step 3: Verify scheduler and queue**

Run:

```powershell
docker compose exec -T scheduler php artisan schedule:list
docker compose exec -T queue php artisan queue:failed
```

Expected: reminder schedule is listed every minute and no failed jobs are found.

- [ ] **Step 4: Verify Mailpit remains reachable**

Run:

```powershell
curl.exe -sS -o NUL -w "%{http_code}" http://127.0.0.1:8025
```

Expected: `200`.

### Task 5: Update Runtime Documentation

**Files:**
- Modify: `docs/ai-handoff/BACKEND_PROGRESS.md`

- [ ] **Step 1: Replace startup commands**

Document `docker compose up -d --build` as the primary local startup command and
state that Laravel web, migration, queue, and scheduler now run in Docker.

- [ ] **Step 2: Record fresh verification evidence**

Record the actual Compose service states, HTTP results, scheduler listing, and
queue failure result from Task 4. Do not include `.env` values or credentials.

- [ ] **Step 3: Check documentation and working tree**

Run:

```powershell
git diff --check
git status --short
```

Expected: no whitespace errors; only intended Docker/runtime documentation plus
the user's pre-existing UI changes are listed.
