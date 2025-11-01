<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import type { TreeNode } from '@/types';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ name: 'ProjectPagesSubtree' });

const { nodes } = defineProps<{ nodes: TreeNode[] }>();

// Локальное состояние раскрытых узлов в рамках одного экземпляра компонента
const expandedIds = ref(new Set<number>());

function pageTitle(node: TreeNode): string {
    return node.title_current_version || `Страница #${node.id}`;
}

function isExpanded(id: number): boolean {
    return expandedIds.value.has(id);
}

function toggle(id: number): void {
    const s = expandedIds.value;
    if (s.has(id)) {
        s.delete(id);
    } else {
        s.add(id);
    }
    // Reassign to trigger реактивность при необходимости (Set мутабельный)
    expandedIds.value = new Set(s);
}
</script>

<template>
    <div class="menu-list">
        <ul class="tree">
            <li v-for="node in nodes" :key="node.id">
                <div class="node">
                    <div class="node-left">
                        <!-- Кнопка разворачивания/иконка листа -->
                        <template v-if="node.children && node.children.length > 0">
                            <button type="button" class="expand-btn" role="button" :aria-expanded="isExpanded(node.id)" @click.stop="toggle(node.id)">
                                <Icon :name="isExpanded(node.id) ? 'chevronUp' : 'chevronDown'" class="icon" size="16" />
                            </button>
                        </template>
                        <template v-else>
                            <span class="leaf-icon">
                                <Icon name="fileText" class="icon" size="12" />
                            </span>
                        </template>

                        <!-- Ссылка на страницу — должна быть кликабельна отдельно от кнопки -->
                        <Link :href="route('pages.show', node.id)" class="node-link">{{ pageTitle(node) }}</Link>
                    </div>
                </div>

                <!-- Дочерний уровень рендерится только при раскрытом состоянии текущего узла -->
                <ProjectPagesSubtree v-if="node.children && node.children.length > 0 && isExpanded(node.id)" :nodes="node.children" />
            </li>
        </ul>
    </div>
</template>

<style scoped lang="scss"></style>
