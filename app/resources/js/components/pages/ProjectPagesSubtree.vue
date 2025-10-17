<script setup lang="ts">
import type { Page } from '@/types';
import { Link } from '@inertiajs/vue3';

defineOptions({ name: 'ProjectPagesSubtree' });

const props = defineProps<{ nodes: Page[] }>();

function pageTitle(p: Page): string {
    return p.current_version?.title || `Страница #${p.id}`;
}
</script>

<template>
    <ul class="tree">
        <li v-for="child in props.nodes" :key="child.id">
            <div class="node">
                <Link :href="route('pages.show', child.id)">{{ pageTitle(child) }}</Link>
            </div>
            <ProjectPagesSubtree
                v-if="child.children && child.children.length"
                :nodes="child.children"
            />
        </li>
    </ul>
</template>

<style scoped lang="scss">
.tree { list-style: none; padding-left: 12px; margin: 6px 0; }
.node { padding: 2px 0; }
</style>


