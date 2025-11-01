<script setup lang="ts">
import { ref, computed } from 'vue';
import { createApi } from '@/services/api/Api';
import { TechplaneMarkDoneRequest, type TechplaneMarkDoneResponse } from '@/services/api/request/Techplane/TechplaneMarkDoneRequest';
import { Dialog, DialogFooter, DialogHeader, DialogContent, DialogClose, DialogDescription, DialogTitle } from '@/components/ui/dialog';

const props = defineProps<{
  techplaneId: number;
  modelValue: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'applied', payload: TechplaneMarkDoneResponse): void;
}>();

const open = computed({
  get: () => props.modelValue,
  set: (v: boolean) => emit('update:modelValue', v),
});

const url = ref('');
const loading = ref(false);
const error = ref<string | null>(null);

async function apply() {
  error.value = null;
  const trimmed = url.value.trim();
  const isValid = /^(https?:)\/\//i.test(trimmed);
  if (!isValid) {
    error.value = 'Укажите корректный URL (http/https)';
    return;
  }
  loading.value = true;
  try {
    const api = createApi();
    const res = await api.execute(new TechplaneMarkDoneRequest(props.techplaneId, trimmed));
    emit('applied', res);
    open.value = false;
    url.value = '';
  } catch (e: any) {
    error.value = e?.message || 'Ошибка';
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <Dialog v-model:open="open">
    <DialogContent>
      <DialogHeader>
        <DialogTitle>Отметить техплан как выполненный</DialogTitle>
        <DialogDescription>Ссылка на Merge Request</DialogDescription>
      </DialogHeader>
      <div class="space-y-2">
        <input v-model="url" type="url" placeholder="https://git.example.com/..." class="w-full border rounded px-3 py-2" />
        <p v-if="error" class="text-red-600 text-sm">{{ error }}</p>
      </div>
      <DialogFooter>
        <button class="btn" :disabled="loading" @click="apply">Применить</button>
        <DialogClose as-child>
          <button class="btn btn-ghost" :disabled="loading">Отмена</button>
        </DialogClose>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
