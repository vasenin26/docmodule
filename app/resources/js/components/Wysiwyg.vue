<template>
  <div>
    <!-- Thin wrapper around vue-wysiwyg. Exposes v-model which returns HTML string. -->
    <vue-wysiwyg v-model="internalValue" :placeholder="placeholder" />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import VueWysiwyg from 'vue-wysiwyg';

// Register locally
const components = { VueWysiwyg };

interface Props {
  modelValue?: string | null;
  placeholder?: string;
}
const props = defineProps<Props>();
const emit = defineEmits(['update:modelValue']);

const internalValue = computed<string>({
  get: () => props.modelValue ?? '',
  set: (v: string) => emit('update:modelValue', v),
});
</script>

<style scoped>
/* Add any minimal styling if needed */
</style>
