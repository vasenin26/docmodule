<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import Icon from '@/components/Icon.vue';

const emit = defineEmits(['create']);
</script>

<template>
  <Button @click="$emit('create')">
    <Icon name="plus"/>
    Создать терминал
  </Button>
</template>
