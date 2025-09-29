<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import { useTextExpansion } from '@/composables/useTextExpansion';

const props = defineProps<{
    id: number | string;
    title: string;
    done: boolean;
    expansionKeyPrefix?: string;
}>();

const { toggleTextExpansion, isTextExpanded } = useTextExpansion();

function keyId(): string {
    return (props.expansionKeyPrefix || 'task-title-') + props.id;
}

const titleEl = ref<HTMLElement | null>(null);
const isOverflowing = ref(false);

function checkOverflow() {
    const el = titleEl.value;
    if (!el) {
        isOverflowing.value = false;
        return;
    }
    // Measure overflow in compact state (when not expanded)
    if (!isTextExpanded(keyId())) {
        isOverflowing.value = el.scrollWidth > el.clientWidth;
    } else {
        // While expanded, keep button visible to allow collapse
        isOverflowing.value = true;
    }
}

function onResize() {
    // Defer to ensure layout settled
    nextTick(() => checkOverflow());
}

onMounted(() => {
    window.addEventListener('resize', onResize);
    nextTick(() => checkOverflow());
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', onResize);
});

watch(() => props.title, () => nextTick(() => checkOverflow()));
watch(() => isTextExpanded(keyId()), () => nextTick(() => checkOverflow()));
</script>

<template>
    <li class="flex items-start space-x-2">
        <span style="padding-top: 8px;">
            <span class="mt-1 inline-flex items-center" :title="props.done ? 'Выполнено' : 'Не выполнено'">
                <svg v-if="props.done" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.707a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor">
                    <circle cx="12" cy="12" r="8" />
                </svg>
            </span>
        </span>
        <div class="flex-1 min-w-0">
            <div class="space-y-1">
                <div class="relative min-w-0">
                    <div ref="titleEl" class="text-sm bg-white p-2 rounded border" :class="isTextExpanded(keyId()) ? 'whitespace-pre-wrap' : 'truncate pr-8'" :title="isTextExpanded(keyId()) ? undefined : props.title">{{ props.title }}</div>
                    <button v-if="isOverflowing" @click="toggleTextExpansion(keyId())" class="absolute bottom-1 right-1 p-1 text-gray-500 hover:text-gray-700" :title="isTextExpanded(keyId()) ? 'Свернуть' : 'Показать полностью'" aria-label="Переключить разворачивание">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <circle cx="4" cy="10" r="1.5" />
                            <circle cx="10" cy="10" r="1.5" />
                            <circle cx="16" cy="10" r="1.5" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </li>
</template>
