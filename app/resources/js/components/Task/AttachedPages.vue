<template>
  <Card>
    <CardHeader>
      <CardTitle>Привязанные страницы</CardTitle>
    </CardHeader>
    <CardContent>
      <ul class="space-y-2">
        <li v-for="item in items" :key="item.id" class="flex items-center justify-between">
          <div>
            <div class="font-medium">{{ item.title }}</div>
            <div class="text-xs text-muted-foreground">Версия: {{ item.version ?? '—' }}</div>
          </div>
          <Button size="sm" variant="destructive" type="button" @click="onDetach(item.id)">Удалить</Button>
        </li>
      </ul>
      <div class="mt-4">
        <Button type="button" @click="openModal">Привязать страницу</Button>
      </div>
    </CardContent>
  </Card>
  <AttachPageModal v-if="show" :task-id="taskId" @attached="onAttached" @close="show=false" />
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AttachPageModal from './AttachPageModal.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{ taskId: number; items: { id:number; title:string; version:number|null }[] }>();
const emit = defineEmits<{ (e:'update:items', items:any[]):void }>();
const show = ref(false);
const openModal = () => show.value = true;
const onAttached = (item:any) => { emit('update:items', [...props.items, item]); show.value=false; };
const onDetach = (id:number) => {
  router.delete(route('tasks.attachments.destroy', { task: props.taskId, pageVersion: id }), {
    onSuccess: () => emit('update:items', props.items.filter(i => i.id !== id))
  });
};
</script>


