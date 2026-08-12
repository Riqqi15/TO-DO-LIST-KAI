<script setup>
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { wibDateTimeParts } from '@/lib/wib';
import { Clock } from '@lucide/vue';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    required: { type: Boolean, default: false },
    ariaInvalid: { type: Boolean, default: false },
    ariaLabel: { type: String, default: null },
    title: { type: String, default: null },
    id: { type: String, default: null },
    class: { type: [String, Array, Object, Boolean, null], required: false, skipCheck: true },
});
const emit = defineEmits(['update:modelValue']);

const popoverOpen = ref(false);
const hourVal = ref(0);
const minuteVal = ref(0);

const datePart = computed({
    get: () => (props.modelValue ? props.modelValue.split('T')[0] : ''),
    set: (v) => {
        emit('update:modelValue', v ? `${v}T${pad(hourVal.value)}:${pad(minuteVal.value)}` : '');
    },
});

const pad = (v) => String(Number(v) || 0).padStart(2, '0');

const syncFromModel = () => {
    if (!props.modelValue) { hourVal.value = 0; minuteVal.value = 0; return; }
    const time = props.modelValue.split('T')[1];
    if (!time) { hourVal.value = 0; minuteVal.value = 0; return; }
    const [h, m] = time.split(':');
    hourVal.value = Number(h) || 0;
    minuteVal.value = Number(m) || 0;
};
syncFromModel();
watch(() => props.modelValue, syncFromModel);

const displayTime = computed(() => {
    if (!props.modelValue || !props.modelValue.includes('T')) return 'Pilih jam';
    return `${pad(hourVal.value)}:${pad(minuteVal.value)}`;
});

const emitUpdate = () => {
    const d = datePart.value;
    if (d) emit('update:modelValue', `${d}T${pad(hourVal.value)}:${pad(minuteVal.value)}`);
};

const incHour = () => { hourVal.value = (hourVal.value + 1) % 24; emitUpdate(); };
const decHour = () => { hourVal.value = (hourVal.value - 1 + 24) % 24; emitUpdate(); };
const incMinute = () => { minuteVal.value = (minuteVal.value + 1) % 60; emitUpdate(); };
const decMinute = () => { minuteVal.value = (minuteVal.value - 1 + 60) % 60; emitUpdate(); };

const setNow = () => {
    const parts = wibDateTimeParts();
    hourVal.value = Number(parts.hour);
    minuteVal.value = Number(parts.minute);
    emitUpdate();
};

const clearTime = () => {
    hourVal.value = 0;
    minuteVal.value = 0;
    emitUpdate();
};

const onHourInput = (e) => {
    let v = Number(e.target.value) || 0;
    if (v > 23) v = 23;
    if (v < 0) v = 0;
    hourVal.value = v;
    emitUpdate();
};

const onMinuteInput = (e) => {
    let v = Number(e.target.value) || 0;
    if (v > 59) v = 59;
    if (v < 0) v = 0;
    minuteVal.value = v;
    emitUpdate();
};

const baseInput = "border-input h-full w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none md:text-sm focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-3 aria-invalid:ring-destructive/20 aria-invalid:border-destructive disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50";
</script>

<template>
    <div :class="cn('flex items-center gap-2', props.class)">
        <!-- Date input -->
        <input
            :id="id"
            v-model="datePart"
            type="date"
            :required="required"
            :aria-invalid="ariaInvalid"
            :aria-label="ariaLabel ? `${ariaLabel} (tanggal)` : undefined"
            :title="title ? `${title} (tanggal)` : undefined"
            :class="cn(baseInput, 'flex-[1.2]')"
        />

        <!-- Time picker popover -->
        <Popover v-model:open="popoverOpen">
            <PopoverTrigger as-child>
                <button
                    type="button"
                    :class="cn(
                        'border-input inline-flex h-full flex-1 items-center justify-between gap-2 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none md:text-sm',
                        'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-3',
                        'hover:bg-accent/50',
                        displayTime === 'Pilih jam' ? 'text-muted-foreground' : '',
                    )"
                >
                    <span class="font-mono">{{ displayTime }}</span>
                    <Clock class="size-3.5 shrink-0 text-muted-foreground" />
                </button>
            </PopoverTrigger>
            <PopoverContent class="w-auto p-4" align="start">
                <p class="mb-3 text-center text-xs font-semibold text-muted-foreground">Pilih waktu</p>

                <div class="flex items-center justify-center gap-1">
                    <!-- Hour spinner -->
                    <div class="flex flex-col items-center gap-1">
                        <button
                            type="button"
                            class="grid size-7 place-items-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                            aria-label="Tambah jam"
                            @click="incHour"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>
                        </button>
                        <input
                            :value="pad(hourVal)"
                            type="text"
                            inputmode="numeric"
                            class="h-14 w-14 rounded-lg border-2 border-primary/30 bg-transparent text-center text-2xl font-bold tabular-nums outline-none transition-colors focus:border-primary focus:ring-2 focus:ring-primary/20"
                            aria-label="Jam"
                            @input="onHourInput"
                            @focus="$event.target.select()"
                        />
                        <button
                            type="button"
                            class="grid size-7 place-items-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                            aria-label="Kurangi jam"
                            @click="decHour"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                    </div>

                    <!-- Separator -->
                    <span class="px-1 text-2xl font-bold text-muted-foreground select-none">:</span>

                    <!-- Minute spinner -->
                    <div class="flex flex-col items-center gap-1">
                        <button
                            type="button"
                            class="grid size-7 place-items-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                            aria-label="Tambah menit"
                            @click="incMinute"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>
                        </button>
                        <input
                            :value="pad(minuteVal)"
                            type="text"
                            inputmode="numeric"
                            class="h-14 w-14 rounded-lg border-2 border-border bg-transparent text-center text-2xl font-bold tabular-nums outline-none transition-colors focus:border-primary focus:ring-2 focus:ring-primary/20"
                            aria-label="Menit"
                            @input="onMinuteInput"
                            @focus="$event.target.select()"
                        />
                        <button
                            type="button"
                            class="grid size-7 place-items-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                            aria-label="Kurangi menit"
                            @click="decMinute"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Now & Clear buttons -->
                <div class="mt-3 flex items-center justify-between border-t pt-3">
                    <button
                        type="button"
                        class="text-xs font-semibold text-primary transition-colors hover:text-primary/80"
                        @click="setNow"
                    >
                        Sekarang
                    </button>
                    <button
                        type="button"
                        class="text-xs font-semibold text-muted-foreground transition-colors hover:text-foreground"
                        @click="clearTime"
                    >
                        Reset
                    </button>
                </div>
            </PopoverContent>
        </Popover>
    </div>
</template>
