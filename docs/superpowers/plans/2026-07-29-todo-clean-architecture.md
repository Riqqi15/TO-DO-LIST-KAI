# To Do List Clean Architecture Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Establish the approved lightweight feature-first structure with real, used files instead of empty scaffold directories.

**Architecture:** Laravel keeps HTTP adapters in its conventional folders while Todo domain vocabulary lives under `app/Domain/Todo`. The Inertia Page remains a thin entry point; feature UI stays under `resources/js/features/todo`, reusable page framing lives in `resources/js/layouts`, and folders are created only when they contain code.

**Tech Stack:** PHP 8.2 enums, Laravel 12, Inertia Laravel 3, Vue 3 Composition API, Vite 7, Tailwind CSS 4.

---

### Task 1: Establish Todo domain vocabulary

**Files:**
- Create: `app/Domain/Todo/Enums/TodoStatus.php`
- Create: `app/Domain/Todo/Enums/TodoPriority.php`

- [ ] **Step 1: Create the Todo status enum**

```php
<?php

namespace App\Domain\Todo\Enums;

enum TodoStatus: string
{
    case Pending = 'pending';
    case BelumSelesai = 'belum_selesai';
    case Selesai = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::BelumSelesai => 'Belum Selesai',
            self::Selesai => 'Selesai',
        };
    }
}
```

- [ ] **Step 2: Create the Todo priority enum**

```php
<?php

namespace App\Domain\Todo\Enums;

enum TodoPriority: string
{
    case Rendah = 'rendah';
    case Sedang = 'sedang';
    case Tinggi = 'tinggi';

    public function label(): string
    {
        return match ($this) {
            self::Rendah => 'Rendah',
            self::Sedang => 'Sedang',
            self::Tinggi => 'Tinggi',
        };
    }
}
```

- [ ] **Step 3: Validate both PHP files**

Run:

```powershell
& 'C:\Program Files\PHP\php.exe' -l app\Domain\Todo\Enums\TodoStatus.php
& 'C:\Program Files\PHP\php.exe' -l app\Domain\Todo\Enums\TodoPriority.php
```

Expected: both commands report `No syntax errors detected`.

### Task 2: Establish Todo frontend constants

**Files:**
- Create: `resources/js/features/todo/constants/todo-options.js`

- [ ] **Step 1: Create the status and priority options**

```js
export const TODO_STATUSES = [
    { value: 'pending', label: 'Pending' },
    { value: 'belum_selesai', label: 'Belum Selesai' },
    { value: 'selesai', label: 'Selesai' },
];

export const TODO_PRIORITIES = [
    { value: 'rendah', label: 'Rendah' },
    { value: 'sedang', label: 'Sedang' },
    { value: 'tinggi', label: 'Tinggi' },
];
```

### Task 3: Extract the application layout

**Files:**
- Create: `resources/js/layouts/AppLayout.vue`
- Modify: `resources/js/features/todo/components/TodoWorkspace.vue`

- [ ] **Step 1: Create the reusable application frame**

```vue
<template>
    <main class="min-h-screen bg-slate-100 px-6 py-16 text-slate-900">
        <slot />
    </main>
</template>
```

- [ ] **Step 2: Use the layout and Todo options in the workspace**

Replace `TodoWorkspace.vue` with:

```vue
<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import {
    TODO_PRIORITIES,
    TODO_STATUSES,
} from '@/features/todo/constants/todo-options';
</script>

<template>
    <AppLayout>
        <section class="mx-auto max-w-3xl rounded-3xl bg-white p-10 shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">
                Fondasi proyek siap
            </p>
            <h1 class="mt-3 text-4xl font-bold tracking-tight">
                To Do List KAI
            </h1>
            <p class="mt-4 max-w-2xl text-lg leading-8 text-slate-600">
                Struktur feature-first sudah siap untuk pengembangan UI
                shadcn-vue dan CRUD melalui Inertia.
            </p>

            <div class="mt-8 grid gap-6 md:grid-cols-2">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Status tugas</h2>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span
                            v-for="status in TODO_STATUSES"
                            :key="status.value"
                            class="rounded-full bg-slate-100 px-3 py-1 text-sm text-slate-700"
                        >
                            {{ status.label }}
                        </span>
                    </div>
                </div>

                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Prioritas</h2>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span
                            v-for="priority in TODO_PRIORITIES"
                            :key="priority.value"
                            class="rounded-full bg-blue-50 px-3 py-1 text-sm text-blue-700"
                        >
                            {{ priority.label }}
                        </span>
                    </div>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
```

### Task 4: Verify and record the structure

**Files:**
- Verify: `app/Domain/Todo/Enums/TodoStatus.php`
- Verify: `app/Domain/Todo/Enums/TodoPriority.php`
- Verify: `resources/js/Pages/Todo/Index.vue`
- Verify: `resources/js/features/todo/components/TodoWorkspace.vue`
- Verify: `resources/js/features/todo/constants/todo-options.js`
- Verify: `resources/js/layouts/AppLayout.vue`

- [ ] **Step 1: Verify source paths**

Run:

```powershell
rg --files app\Domain\Todo resources\js
```

Expected: all six architecture files appear and no duplicate Todo Page exists.

- [ ] **Step 2: Build the frontend**

Run:

```powershell
npm.cmd run build
```

Expected: Vite completes without import-resolution or Vue compilation errors.

- [ ] **Step 3: Check whitespace and repository state**

Run:

```powershell
git diff --check
git status -sb
```

Expected: no whitespace errors; only the planned architecture files are
modified or untracked.

- [ ] **Step 4: Commit the implementation**

```powershell
git add app/Domain/Todo/Enums resources/js/features/todo resources/js/layouts docs/superpowers/plans/2026-07-29-todo-clean-architecture.md
git commit -m "refactor: establish Todo feature architecture"
```
