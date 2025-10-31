import { defineStore } from 'pinia';
import { ref } from 'vue';
import { startPoller, stopPoller } from '@/services/agentTasksPoller';

export interface AgentTasksPanelItem {
    id: string;
    chat_id?: number | null;
    agent_task_id?: number;
    url?: string | null;
    raw_status?: string | null;
    updated_at?: string | null;
    hidden?: boolean;
}

export const useAgentTasksPanelStore = defineStore('agentTasksPanel', () => {
    const items = ref<AgentTasksPanelItem[]>([]);
    const initialized = ref(false);

    function mergeItems(newItems: AgentTasksPanelItem[]) {
        const map = new Map(items.value.map((i) => [i.id, i]));
        newItems.forEach((ni) => {
            const existing = map.get(ni.id) ?? {};
            // preserve hidden flag if exists on existing
            const hidden = existing.hidden ?? ni.hidden ?? false;
            map.set(ni.id, { ...existing, ...ni, hidden });
        });
        items.value = Array.from(map.values());
    }

    function init() {
        if (initialized.value) return;
        initialized.value = true;
        try {
            startPoller();
        } catch (e) {
            console.error('agentTasksPanelStore: failed to start poller', e);
        }
    }

    function dispose() {
        if (!initialized.value) return;
        initialized.value = false;
        try {
            stopPoller();
        } catch (e) {
            console.error('agentTasksPanelStore: failed to stop poller', e);
        }
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
        mergeItems,
    };
});
