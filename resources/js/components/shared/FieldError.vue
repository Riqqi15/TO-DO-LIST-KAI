<script setup>
defineProps({ message: { type: String, default: '' } });
</script>

<template>
    <p v-if="message" class="mt-1.5 text-xs font-medium text-destructive" role="alert">{{ message }}</p>
</template>
