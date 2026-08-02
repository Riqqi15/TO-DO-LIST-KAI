<script setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { GripVertical, Pencil, Pin, PinOff, Trash2 } from '@lucide/vue';

defineProps({
    note: { type: Object, required: true },
    colorClass: { type: String, required: true },
    draggable: { type: Boolean, default: false },
    pinBusy: { type: Boolean, default: false },
});
const emit = defineEmits(['edit', 'pin', 'delete']);
</script>

<template>
    <Card
        :data-note-id="note.id"
        class="group relative flex min-h-56 min-w-0 flex-col justify-between overflow-hidden border p-4 transition hover:-translate-y-1 hover:rotate-0 hover:shadow-lg sm:p-5"
        :class="colorClass"
        :style="{ '--note-rotate': note.id % 2 === 0 ? '-0.35deg' : '0.35deg' }"
    >
        <div>
            <div class="flex items-start justify-between gap-3">
                <Badge v-if="note.pinned_at" variant="outline" class="border-primary/25 bg-white/60 text-primary">
                    <Pin class="size-3" />Disematkan
                </Badge>
                <span v-else class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Catatan</span>
                <Button
                    v-if="draggable"
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    class="pin-drag-handle -mr-2 -mt-2 cursor-grab bg-white/30 active:cursor-grabbing"
                    aria-label="Geser urutan sticky note"
                    title="Geser urutan"
                >
                    <GripVertical class="size-4" />
                </Button>
            </div>

            <p class="mt-3 line-clamp-6 whitespace-pre-line break-words [overflow-wrap:anywhere] text-sm font-semibold leading-6 text-slate-800">{{ note.content }}</p>
        </div>

        <div class="mt-4 flex items-center justify-between gap-3 border-t border-slate-900/10 pt-3">
            <span class="truncate text-[10px] font-medium text-slate-500">{{ note.creator?.name ?? 'Pengguna' }}</span>
            <div class="flex gap-1">
                <Button type="button" variant="ghost" size="icon-xs" :disabled="pinBusy" :title="note.pinned_at ? 'Lepas pin' : 'Pin catatan'" @click="emit('pin', note)">
                    <PinOff v-if="note.pinned_at" class="size-3.5" />
                    <Pin v-else class="size-3.5" />
                </Button>
                <Button type="button" variant="ghost" size="icon-xs" title="Edit" @click="emit('edit', note)"><Pencil class="size-3.5" /></Button>
                <Button type="button" variant="ghost" size="icon-xs" class="text-destructive" title="Hapus" @click="emit('delete', note)"><Trash2 class="size-3.5" /></Button>
            </div>
        </div>
    </Card>
</template>
