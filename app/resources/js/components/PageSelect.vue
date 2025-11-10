<template>
  <div class="relative">
    <div class="flex items-center gap-2">
      <input
        :placeholder="placeholder || 'Поиск страницы'"
        v-model="query"
        @input="onInput"
        class="w-full border rounded px-2 py-1"
        :aria-invalid="!!error"
      />
      <button v-if="selected" type="button" @click="clear" class="text-sm text-muted-foreground">Сброс</button>
    </div>

    <ul v-if="showList" class="absolute z-50 bg-white border rounded mt-1 w-full max-h-60 overflow-auto">
      <li v-if="loading" class="p-2 text-sm text-muted-foreground">Загрузка...</li>
      <li v-for="item in results" :key="item.id" class="p-2 hover:bg-gray-100 cursor-pointer" @click="select(item)">
        <div class="font-medium">{{ item.title }}</div>
        <div class="text-xs text-muted-foreground">#{{ item.id }} {{ item.path || '' }}</div>
      </li>
      <li v-if="!loading && results.length === 0" class="p-2 text-sm text-muted-foreground">Ничего не найдено</li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

interface PageItem { id: number; title: string; path?: string }

const props = defineProps<{ modelValue: number | null; placeholder?: string; projectId?: number | null }>();
const emits = defineEmits(['update:modelValue']);

const query = ref('');
const results = ref<PageItem[]>([]);
const loading = ref(false);
const showList = ref(false);
const selected = ref<PageItem | null>(null);
let debounceTimer: number | null = null;
const error = ref<string | null>(null);

const buildUrl = (params: Record<string, any>, defaultPath = '/pages') => {
  if (typeof route === 'function') {
    try {
      return route('pages.index', params);
    } catch (err) {
      console.warn('PageSelect.buildUrl: Ziggy route error for pages.index, falling back to manual URL', err);
    }
  }
  const sp = new URLSearchParams();
  for (const [k, v] of Object.entries(params)) {
    if (v === undefined || v === null) continue;
    sp.append(k, String(v));
  }
  const q = sp.toString();
  return q ? `${defaultPath}?${q}` : defaultPath;
};

// При наличии modelValue подгружаем одну страницу для отображения
const fetchById = async (id: number) => {
  try {
    loading.value = true;
    // Попробуем получить страницу через pages.index?id — адаптируйте если есть отдельный show route
    // Используем глобальную функцию route(...) (Ziggy) если доступна
    const url = buildUrl({ id, per_page: 1 });
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    const json = await res.json();
    const item = Array.isArray(json.data) && json.data.length ? json.data[0] : json.data || null;
    if (item) {
      selected.value = { id: item.id, title: item.title, path: item.path || '' } as PageItem;
      query.value = selected.value.title;
    }
  } catch (err) {
    console.error('PageSelect.fetchById error', err);
  } finally {
    loading.value = false;
  }
};

watch(() => props.modelValue, (v) => {
  if (v) fetchById(v);
  else {
    selected.value = null;
    query.value = '';
  }
});

const onInput = () => {
  if (debounceTimer) clearTimeout(debounceTimer);
  debounceTimer = window.setTimeout(async () => {
    if (!query.value || query.value.length < 1) {
      results.value = [];
      showList.value = false;
      return;
    }

    loading.value = true;
    try {
      const params: any = { search: query.value, per_page: 10 };
      if (props.projectId) params.project_id = props.projectId;
      const url = buildUrl(params);
      const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
      const json = await res.json();
      results.value = Array.isArray(json.data) ? json.data.map((p: any) => ({ id: p.id, title: p.title, path: p.path || '' })) : [];
      showList.value = true;
    } catch (err) {
      console.error('PageSelect search error', err);
      error.value = 'Ошибка при поиске';
    } finally {
      loading.value = false;
    }
  }, 300);
};

const select = (item: PageItem) => {
  selected.value = item;
  query.value = item.title;
  showList.value = false;
  emits('update:modelValue', item.id);
};

const clear = () => {
  selected.value = null;
  query.value = '';
  results.value = [];
  showList.value = false;
  emits('update:modelValue', null);
};
</script>
