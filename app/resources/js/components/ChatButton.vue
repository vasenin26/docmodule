<template>
    <Button
        v-if="showCondition"
        @click="$emit('click')"
        :variant="variant"
        :size="size || 'sm'"
        class="flex items-center gap-2 bg-purple-600"
    >
        <Bot class="h-4 w-4" />
        Агент
    </Button>
</template>

<script setup lang="ts">
import { Bot } from 'lucide-vue-next';
import Button from '@/components/ui/button/Button.vue';

interface Props {
    showCondition?: boolean;
    variant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link';
    size?: 'default' | 'sm' | 'lg' | 'icon';
}

defineProps<Props>();

defineEmits<{
    click: [];
}>();
</script>
