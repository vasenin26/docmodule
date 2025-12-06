import { ref } from 'vue';
import axios from 'axios';

export function useTerminals() {
  const terminals = ref<any[]>([]);
  const activeId = ref<string | null>(null);
  const loading = ref(false);

  async function load() {
    loading.value = true;
    try {
      const res = await axios.get('/api/terminals');
      terminals.value = res.data || [];
      if (!activeId.value && terminals.value.length) {
        activeId.value = String(terminals.value[0].id);
      }
    } catch (e) {
      terminals.value = [];
    } finally {
      loading.value = false;
    }
  }

  async function create(name?: string) {
    try {
      const res = await axios.post('/api/terminals', { name: name || 'term' });
      const t = res.data;
      terminals.value.push(t);
      activeId.value = String(t.id);
      return t;
    } catch (e) {
      const tmp = { id: Date.now(), name: name || 'term', created_at: new Date() };
      terminals.value.push(tmp);
      activeId.value = String(tmp.id);
      return tmp;
    }
  }

  function setActive(id: string) {
    activeId.value = id;
  }

  async function remove(id: string) {
    try {
      await axios.delete(`/api/terminals/${id}`);
      terminals.value = terminals.value.filter(t => String(t.id) !== String(id));
      if (activeId.value === String(id)) {
        activeId.value = terminals.value.length ? String(terminals.value[0].id) : null;
      }
    } catch (e) {
      terminals.value = terminals.value.filter(t => String(t.id) !== String(id));
      if (activeId.value === String(id)) {
        activeId.value = terminals.value.length ? String(terminals.value[0].id) : null;
      }
    }
  }

  return { terminals, activeId, loading, load, create, setActive, remove };
}
