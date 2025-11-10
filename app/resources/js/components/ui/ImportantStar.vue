<script setup lang="ts">
import Icon from '@/components/Icon.vue';

const props = withDefaults(defineProps<{
  modelValue?: boolean;
  size?: number | string;
}>(), {
  modelValue: false,
  size: 18,
});

const emits = defineEmits<{ 'update:modelValue': (value: boolean) => void }>();

const toggle = () => {
  emits('update:modelValue', !props.modelValue);
};

const onKeydown = (e: KeyboardEvent) => {
  if (e.key === 'Enter' || e.key === ' ') {
    e.preventDefault();
    toggle();
  }
};
</script>

<template>
  <button
    type="button"
    class="inline-flex items-center justify-center p-1 rounded focus:outline-none focus:ring-2 focus:ring-offset-2"
    :class="props.modelValue ? 'focus:ring-yellow-400' : 'focus:ring-primary'"
    :aria-pressed="String(props.modelValue)"
    :aria-label="props.modelValue ? 'Убрать из важных' : 'Отметить как важную'"
    @click="toggle"
    @keydown="onKeydown"
  >
    <Icon :name="'star'" :size="props.size" :class="props.modelValue ? 'text-yellow-400' : 'text-muted-foreground'" />
  </button>
</template>
