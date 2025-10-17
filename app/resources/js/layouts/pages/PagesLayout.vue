<script setup lang="ts">
import FullScreenLayout from '@/layouts/fullscreen/FullScreenLayout.vue';
import PagesSidebar from '@/components/pages/PagesSidebar.vue';
</script>

<template>
    <FullScreenLayout>
        <template #context-actions>
            <slot name="context-actions" />
        </template>

        <div class="pages-layout">
            <aside class="sidebar">
                <PagesSidebar />
            </aside>
            <main class="content">
                <slot />
            </main>
        </div>
    </FullScreenLayout>
    
</template>

<style scoped lang="scss">
    .pages-layout {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 16px;
    }

    .sidebar {
        position: sticky;
        top: 62px; // header height + padding
        align-self: start;
    }

    .content {
        min-width: 0; // prevent overflow in grid
    }
</style>


