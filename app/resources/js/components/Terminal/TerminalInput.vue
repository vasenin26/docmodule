<script setup lang="ts">
import { ref, nextTick } from 'vue';

const props = defineProps<{
    disabled?: boolean;
}>();

const emit = defineEmits(['submit']);
const input = ref('');
const textareaRef = ref<HTMLTextAreaElement | null>(null);

async function onKeyDown(e: KeyboardEvent) {
  const target = e.target as HTMLTextAreaElement;
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    if (input.value.trim() && !props.disabled) {
      emit('submit', input.value.trim());
      input.value = '';
      await nextTick();
      if (textareaRef.value) {
        textareaRef.value.focus();
      }
    }
  }
}
</script>

<template>
  <div class="h-14 border-t p-2 bg-gray-50">
    <textarea 
        ref="textareaRef" 
        v-model="input" 
        @keydown="onKeyDown" 
        :disabled="disabled"
        class="w-full h-full resize-none p-2" 
        placeholder="Введите команду и нажмите Enter"
    />
  </div>
</template>
