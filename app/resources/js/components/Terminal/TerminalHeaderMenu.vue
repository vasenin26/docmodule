<script setup lang="ts">
const emit = defineEmits(['create']);
</script>

<template>
  <div>
    <button @click="$emit('create')" class="px-3 py-1 bg-blue-600 text-white rounded">Создать терминал</button>
  </div>
</template>
