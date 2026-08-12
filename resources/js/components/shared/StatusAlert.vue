<script setup>
defineProps({ status: { type: String, default: '' } });
</script>

<template>
    <div v-if="status" class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm font-medium text-emerald-800">{{ status }}</div>
</template>
