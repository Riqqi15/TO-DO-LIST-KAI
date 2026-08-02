<script setup>
import kaiLogo from '@/assets/kai-logo.svg';
import { BadgeCheck, BellRing, CalendarDays, CheckCheck } from '@lucide/vue';

defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
});
</script>

<template>
    <main class="grid min-h-screen bg-background text-foreground lg:grid-cols-[1.05fr_0.95fr]">
        <section class="relative hidden overflow-hidden bg-[#172033] p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="absolute -right-28 -top-24 size-96 rounded-full bg-primary/30 blur-3xl" />
            <div class="absolute -bottom-36 left-20 size-96 rounded-full bg-[#12806a]/20 blur-3xl" />

            <div class="relative flex items-center gap-3">
                <img :src="kaiLogo" alt="Logo KAI" class="h-8 w-auto object-contain shrink-0" />
                <div>
                    <p class="font-extrabold tracking-[-0.02em]">To Do List KAI</p>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-white/55">Kerja lebih terarah</p>
                </div>
            </div>

            <div class="relative max-w-xl">
                <p class="mb-5 font-mono text-xs font-semibold uppercase tracking-[0.18em] text-blue-200">Satu ruang kerja. Semua tetap jelas.</p>
                <h2 class="text-balance text-4xl font-extrabold leading-[1.15] tracking-[-0.04em] xl:text-5xl">
                    Rencanakan hari ini tanpa kehilangan gambaran besar.
                </h2>
                <div class="mt-10 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                        <BadgeCheck class="mb-4 size-5 text-blue-300" />
                        <p class="text-sm font-bold">Status yang jelas</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                        <CalendarDays class="mb-4 size-5 text-blue-300" />
                        <p class="text-sm font-bold">Deadline terpantau</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                        <BellRing class="mb-4 size-5 text-blue-300" />
                        <p class="text-sm font-bold">Reminder tepat waktu</p>
                    </div>
                </div>
            </div>

            <p class="relative text-xs text-white/45">Personal dan kolaborasi tim dalam satu aplikasi.</p>
        </section>

        <section class="flex items-center justify-center px-5 py-10 sm:px-10">
            <div class="w-full max-w-md">
                <div class="mb-10 flex items-center gap-3 lg:hidden">
                    <img :src="kaiLogo" alt="Logo KAI" class="h-7 w-auto object-contain shrink-0" />
                    <p class="font-extrabold">To Do List KAI</p>
                </div>

                <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary">Selamat datang</p>
                <h1 class="mt-3 text-3xl font-extrabold tracking-[-0.035em]">{{ title }}</h1>
                <p v-if="description" class="mt-3 text-sm leading-6 text-muted-foreground">{{ description }}</p>
                <div class="mt-8">
                    <slot />
                </div>
            </div>
        </section>
    </main>
</template>
