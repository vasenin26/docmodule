<script setup lang="ts">
import type { TreeNode } from '@/types';
import { Link } from '@inertiajs/vue3';

defineOptions({ name: 'ProjectPagesSubtree' });

const { nodes } = defineProps<{ nodes: TreeNode[] }>();

function pageTitle(node: TreeNode): string {
    return node.title_current_version || `Страница #${node.id}`;
}
</script>

<template>
    <ul class="tree">
        <li v-for="node in nodes" :key="node.id">
            <div class="node">
                <Link :href="route('pages.show', node.id)">{{ pageTitle(node) }}</Link>
            </div>
            <ProjectPagesSubtree
                v-if="node.children && node.children.length > 0"
                :nodes="node.children"
            />
        </li>
    </ul>
</template>

<style scoped lang="scss">
.tree { list-style: none; padding-left: 12px; margin: 6px 0; }
.node { padding: 2px 0; }
</style>


