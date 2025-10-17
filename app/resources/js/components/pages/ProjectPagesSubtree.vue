<script setup lang="ts">
import type { Page } from '@/types';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ name: 'ProjectPagesSubtree' });

const { nodes } = defineProps<{ nodes: Page[] }>();

const filteredNodes = computed(() => (nodes || []).filter((n) => !!n.current_version));

function pageTitle(p: Page): string {
    return p.current_version?.title || (p as any).title || `Страница #${p.id}`;
}
</script>

<template>
    <ul class="tree">
        <li v-for="node in filteredNodes" :key="node.id">
            <div class="node">
                <Link :href="route('pages.show', node.id)">{{ pageTitle(node) }}</Link>
            </div>
            <ProjectPagesSubtree
                v-if="node.children && node.children.length"
                :nodes="(node.children || []).filter((c:any) => !!c.current_version)"
            />
        </li>
    </ul>
    
</template>

<style scoped lang="scss">
.tree { list-style: none; padding-left: 12px; margin: 6px 0; }
.node { padding: 2px 0; }
</style>


