<script setup lang="ts">
import ProjectPagesTree from '@/components/pages/ProjectPagesTree.vue';
</script>

<template>
    <div class="pages-sidebar">
        <div class="mt-3">
            <ProjectPagesTree />
        </div>
    </div>
</template>

<style scoped lang="scss">
    .pages-sidebar {
        .sidebar-card {
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--card);
            padding: 12px;
        }
        .sidebar-title {
            font-weight: 600;
            margin-bottom: 8px;
        }
        .sidebar-list {
            margin: 0;
            padding-left: 18px;
        }
    }
</style>


