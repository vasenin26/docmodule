import { defineStore } from 'pinia';
import { ref } from 'vue';
import { startPoller, stopPoller, isPollerRunning } from '@/services/agentTasksPoller';

export interface AgentTasksPanelItem {
    id: string;
    chat_id?: number | null;
    agent_task_id?: number;
    url?: string | null;
    raw_status?: string | null;
    updated_at?: string | null;
}

export const useAgentTasksPanelStore = defineStore('agentTasksPanel', () => {
    const items = ref<AgentTasksPanelItem[]>([]);
    const initialized = ref(false);

    function mergeItems(newItems: AgentTasksPanelItem[]) {
        const map = new Map(items.value.map((i) => [i.id, i]));
        newItems.forEach((ni) => {
            map.set(ni.id, { ...(map.get(ni.id) ?? {}), ...ni });
        });
        items.value = Array.from(map.values());
    }

    function onWindowUpdated(e: any) {
        try {
            const payload = e?.detail?.items ?? e?.detail ?? null;
            if (!payload) return;
            mergeItems(payload as AgentTasksPanelItem[]);
        } catch (err) {
            // ignore
            // eslint-disable-next-line no-console
            console.error('agentTasksPanelStore: failed to handle update event', err);
        }
    }

    function init() {
        if (initialized.value) return;
        initialized.value = true;

        // listen to poller updates
        window.addEventListener('agent_tasks.updated', onWindowUpdated as EventListener);

        // start poller
        startPoller();
    }

    function dispose() {
        if (!initialized.value) return;
        initialized.value = false;

        window.removeEventListener('agent_tasks.updated', onWindowUpdated as EventListener);
        stopPoller();
    }

    function clear() {
        items.value = [];
    }

    function getItems(): AgentTasksPanelItem[] {
        return items.value;
    }

    function addLocalId(id: string | number) {
        try {
            const raw = localStorage.getItem('agent_tasks_panel_ids');
            let arr = Array.isArray(raw ? JSON.parse(raw) : []) ? JSON.parse(raw ?? '[]') : [];
            const stringId = String(id);
            if (!arr.some((it: any) => String(it?.id ?? it) === stringId)) {
                arr.unshift({ id: stringId });
                localStorage.setItem('agent_tasks_panel_ids', JSON.stringify(arr));
            }
        } catch (e) {
            console.error('agentTasksPanelStore: failed to add local id', e);
        }
    }

    function removeLocalId(id: string | number) {
        try {
            const raw = localStorage.getItem('agent_tasks_panel_ids');
            let arr = Array.isArray(raw ? JSON.parse(raw) : []) ? JSON.parse(raw ?? '[]') : [];
            const stringId = String(id);
            arr = arr.filter((it: any) => String(it?.id ?? it) !== stringId);
            localStorage.setItem('agent_tasks_panel_ids', JSON.stringify(arr));
        } catch (e) {
            console.error('agentTasksPanelStore: failed to remove local id', e);
        }
    }

    return {
        init,
        dispose,
        clear,
        getItems,
        items,
        addLocalId,
        removeLocalId,
        isPollerRunning,
    };
});
