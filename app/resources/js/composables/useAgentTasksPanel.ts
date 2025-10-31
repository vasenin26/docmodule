import { ref, onMounted, onBeforeUnmount } from 'vue';
import { createApi } from '@/service/api/Api';
import { AgentTasksCheckRequest } from '@/service/api/request/Task/AgentTasksCheckRequest';
import { useAgentTasksPanelStore } from '@/stores/agentTasksPanelStore';

const POLL_INTERVAL = 5000;
const MAX_IDS = 50;

export function useAgentTasksPanel() {
    const store = useAgentTasksPanelStore();
    const stopped = ref(true);
    const isRunning = ref(false);
    let timeoutHandle: any = null;

    function readLocalIds(): Array<string> {
        try {
            const raw = localStorage.getItem('agent_tasks_panel_ids');
            if (!raw) return [];
            const parsed = JSON.parse(raw);
            if (!Array.isArray(parsed)) return [];
            return parsed.map((it) => String(it.id ?? it));
        } catch (e) {
            console.error('useAgentTasksPanel: failed to read local ids', e);
            return [];
        }
    }

    async function fetchOnce(): Promise<void> {
        if (stopped.value) return;
        if (isRunning.value) {
            timeoutHandle = setTimeout(fetchOnce, POLL_INTERVAL);
            return;
        }
        isRunning.value = true;

        try {
            const ids = readLocalIds().slice(0, MAX_IDS);
            const api = createApi();
            const req = new AgentTasksCheckRequest(ids);
            const data = await req.call(api);

            // normalize items into panel shape
            const normalized = data.map((it) => ({
                id: String(it.id),
                chat_id: it.chat_id,
                agent_task_id: it.agent_task_id,
                url: it.url,
                raw_status: it.raw_status,
                updated_at: it.updated_at,
                hidden: !!localStorage.getItem('agent_tasks_panel_hidden_' + String(it.chat_id)),
            }));

            // apply status rules per chat
            const byChat = new Map();
            normalized.forEach((it) => {
                const chatId = String(it.chat_id ?? '');
                if (!byChat.has(chatId)) byChat.set(chatId, []);
                byChat.get(chatId).push(it);
            });

            const merged: any[] = [];
            byChat.forEach((list, chatId) => {
                // if any processing/wait -> processing
                const hasActive = list.some(l => ['processing', 'wait'].includes(l.raw_status));
                const finalStatus = hasActive ? 'processing' : 'completed';
                // choose representative task: newest by updated_at
                list.sort((a,b) => (b.updated_at || '') .localeCompare(a.updated_at || ''));
                const rep = { ...list[0], status: finalStatus };
                merged.push(rep);
            });

            // update store
            store.mergeItems(merged);
            window.dispatchEvent(new CustomEvent('agent_tasks_panel.updated', { detail: merged }));
        } catch (e) {
            console.error('useAgentTasksPanel: fetch error', e);
        } finally {
            isRunning.value = false;
            if (!stopped.value) {
                timeoutHandle = setTimeout(fetchOnce, POLL_INTERVAL);
            }
        }
    }

    function start() {
        if (!stopped.value) return;
        stopped.value = false;
        fetchOnce();
    }

    function stop() {
        stopped.value = true;
        if (timeoutHandle) {
            clearTimeout(timeoutHandle);
            timeoutHandle = null;
        }
    }

    onMounted(() => {
        // lazy init store
        store.init();
        start();
    });

    onBeforeUnmount(() => {
        stop();
        store.dispose();
    });

    return {
        start,
        stop,
        isRunning,
    };
}
