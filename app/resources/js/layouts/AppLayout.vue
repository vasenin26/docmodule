<script setup lang="ts">
import AppLayout from '@/layouts/fullscreen/FullScreenLayout.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <template #context-actions>
            <slot name="context-actions" />
        </template>
        <slot />
    </AppLayout>
</template>
