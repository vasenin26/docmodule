<script setup lang="ts">
import AppLayout from '@/layouts/fullscreen/FullScreenLayout.vue';

</script>

<template>
    <AppLayout>
        <template #context-actions>
            <slot name="context-actions" />
        </template>
        <slot />
    </AppLayout>
</template>
