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
