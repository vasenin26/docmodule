<script setup lang="ts">
import { ref } from 'vue';
const emit = defineEmits(['submit']);
const input = ref('');

function onKeyDown(e: KeyboardEvent) {
  const target = e.target as HTMLTextAreaElement;
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    if (input.value.trim()) {
      emit('submit', input.value.trim());
      input.value = '';
      if (target) target.blur();
    }
  }
}
</script>

<template>
  <div class="h-14 border-t p-2 bg-gray-50">
    <textarea v-model="input" @keydown="onKeyDown" class="w-full h-full resize-none p-2" placeholder="Введите команду и нажмите Enter"></textarea>
  </div>
</template>
