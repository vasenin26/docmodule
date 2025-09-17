<template>
  <Dialog :open="true">
    <DialogContent>
      <DialogHeader><DialogTitle>Привязать страницу</DialogTitle></DialogHeader>
      <input v-model="query" class="input w-full border rounded p-2" placeholder="Поиск..." @input="debouncedSearch" />
      <ul class="mt-4 space-y-2">
        <li v-for="r in results" :key="r.id" class="flex items-center justify-between">
          <span>{{ r.title }}</span>
          <Button size="sm" type="button" @click="attach(r.id)">Выбрать</Button>
        </li>
      </ul>
      <DialogFooter>
        <Button variant="outline" type="button" @click="$emit('close')">Закрыть</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { ref } from 'vue';

const props = defineProps<{ taskId:number }>();
const emit = defineEmits<{ (e:'attached', item:any):void; (e:'close'):void }>();
const query = ref('');
const results = ref<any[]>([]);

let timer: any = null;
const search = async () => {
  if (!query.value) { results.value = []; return; }
  const res = await fetch(route('pages.search', { query: query.value }));
  const data = await res.json();
  results.value = data.data || [];
};
const debouncedSearch = () => { clearTimeout(timer); timer = setTimeout(search, 400); };
const attach = (pageVersionId:number) => {
  const item = results.value.find(r => r.id === pageVersionId);
  if (item) emit('attached', item);
};
</script>


