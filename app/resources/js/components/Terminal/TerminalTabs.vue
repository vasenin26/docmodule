<script setup lang="ts">

const props = defineProps({ terminals: { type: Array, default: () => [] }, activeId: [String, Number] });
const emit = defineEmits(['select']);

function onClick(t: any) { emit('select', String(t.id)); }
</script>

<template>
  <div class="flex space-x-2 overflow-x-auto">
    <button v-for="t in props.terminals" :key="t.id" @click="onClick(t)"
            :class="['px-3 py-1 rounded', String(t.id) === String(activeId) ? 'bg-gray-200' : 'bg-white']">
      {{ t.name || ('terminal-' + t.id) }}
    </button>
  </div>
</template>
