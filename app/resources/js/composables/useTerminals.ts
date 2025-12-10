import { ref } from 'vue';
import { TerminalCreateRequest } from '@/services/api/request/Terminal/TerminalCreateRequest';
import { TerminalListRequest } from '@/services/api/request/Terminal/TerminalListRequest';
import { createApi } from '@/services/api/Api';

export function useTerminals() {
  const terminals = ref<any[]>([]);
  const activeId = ref<string | null>(null);
  const loading = ref(false);

  async function load() {
    loading.value = true;
    try {
      const api = createApi();
      const req = new TerminalListRequest();
      const list = await req.call(api);
      terminals.value = list || [];
      if (!activeId.value && terminals.value.length) {
        activeId.value = String(terminals.value[0].id);
      }
    } catch (e) {
      console.error('Ошибка при загрузке терминалов:', e);
      terminals.value = [];
    } finally {
      loading.value = false;
    }
  }

  async function create(name?: string) {
    try {
      const api = createApi();
      const req = new TerminalCreateRequest({ name: name || 'term' });
      const t = await req.call(api);
      terminals.value.push(t);
      activeId.value = String(t.id);
      return t;
    } catch (e) {
      console.error('Ошибка при создании терминала:', e);
      throw e;
    }
  }

  function setActive(id: string) {
    activeId.value = id;
  }

  async function remove(id: string) {
    // TODO: Реализовать удаление терминала через API
    // Пока просто удаляем из локального состояния
    terminals.value = terminals.value.filter(t => String(t.id) !== String(id));
    if (activeId.value === String(id)) {
      activeId.value = terminals.value.length ? String(terminals.value[0].id) : null;
    }
  }

  return { terminals, activeId, loading, load, create, setActive, remove };
}
