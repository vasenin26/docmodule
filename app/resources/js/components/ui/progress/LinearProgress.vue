<template>
    <div class="w-full" :style="{ height: heightPx + 'px' }">
        <div class="relative h-full w-full overflow-hidden rounded bg-gray-200">
            <div class="h-full bg-blue-500 transition-all" :style="{ width: Math.round(safeValue * 100) + '%' }"></div>
        </div>
    </div>
    
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    value: number;
    heightPx?: number;
}

const props = withDefaults(defineProps<Props>(), {
    heightPx: 5,
});

const safeValue = computed(() => {
    const v = Number(props.value);
    if (!isFinite(v)) return 0;
    if (v < 0) return 0;
    if (v > 1) return 1;
    return v;
});
</script>


