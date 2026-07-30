<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
});
</script>

<template>
    <main class="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10 text-slate-900">
        <section class="w-full max-w-md rounded-2xl bg-white p-8 shadow-sm">
            <p class="text-sm font-semibold text-blue-700">To Do List KAI</p>
            <h1 class="mt-2 text-2xl font-bold">{{ title }}</h1>
            <p v-if="description" class="mt-2 text-sm text-slate-600">{{ description }}</p>
            <div class="mt-6">
                <slot />
            </div>
        </section>
    </main>
</template>
