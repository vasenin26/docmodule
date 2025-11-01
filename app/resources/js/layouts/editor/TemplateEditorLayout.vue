<script setup lang="ts">
import FullScreenLayout from '@/layouts/fullscreen/FullScreenLayout.vue';

interface Props {
    sidebarWidth?: string;
    assistantWidth?: string;
}

const props = withDefaults(defineProps<Props>(), {
    sidebarWidth: '280px',
    assistantWidth: '660px'
});

const cssVars = {
    '--sidebar-width': props.sidebarWidth,
    '--assistant-width': props.assistantWidth
} as Record<string, string>;
</script>

<template>
    <FullScreenLayout>
        <template #context-actions>
            <slot name="context-actions" />
        </template>

        <div class="template-editor-layout" :style="cssVars">
            <aside class="tpl-sidebar" aria-label="Template sidebar">
                <div class="tpl-slot-inner">
                    <slot name="sidebar" />
                </div>
            </aside>

            <main class="tpl-editor" aria-label="Template editor">
                <div class="tpl-slot-inner">
                    <slot />
                </div>
            </main>

            <aside class="tpl-assistant" aria-label="Template assistant">
                <div class="tpl-slot-inner">
                    <slot name="assistant" />
                </div>
            </aside>
        </div>
    </FullScreenLayout>
</template>

<style scoped lang="scss">
.template-editor-layout {
    display: grid;
    grid-template-columns: var(--sidebar-width) 1fr var(--assistant-width);
    gap: 16px;
    align-items: stretch;
}

.tpl-sidebar,
.tpl-editor,
.tpl-assistant {
    min-width: 0;
}

/* Sticky sidebars: mirror behaviour from PagesLayout */
.tpl-sidebar {
    top: 64px;
    align-self: start;
    height: calc(100vh - 78px);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: sticky;
}

.tpl-assistant {
    top: 64px;
    align-self: start;
    height: calc(100vh - 78px);
    display: flex;
    flex-direction: column;
    position: sticky;
}

.tpl-slot-inner {
    box-sizing: border-box;
    overflow: auto;
    -webkit-overflow-scrolling: touch;
    padding: 0;
}
</style>
