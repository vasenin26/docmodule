import { ref, onMounted, onBeforeUnmount } from 'vue';
import { createApi } from '@/services/api/Api';
import {AgentTasksCheckRequest, AgentTasksCheckResponseItem} from '@/services/api/request/Task/AgentTasksCheckRequest';

const POLL_INTERVAL = 5000;
const MAX_IDS = 50;

const api = createApi();
export function useAgentTasksPanel() {
    const stopped = ref(true);
    const isRunning = ref(false);
    const items = ref<AgentTasksCheckResponseItem[]>([])
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
            const req = new AgentTasksCheckRequest(ids);
            const data = await req.call(api);

            // normalize items into panel shape
            const normalized = data.map((it) => ({
                chat_id: it.chat_id,
                agent_task_id: it.agent_task_id,
                raw_status: it.raw_status,
                hidden: !!localStorage.getItem('agent_tasks_panel_hidden_' + String(it.chat_id)),
            }));

            const byChat = new Map();

            normalized.forEach((it) => {
                const chatId = String(it.chat_id ?? '');
                if (!byChat.has(chatId)) byChat.set(chatId, []);
                byChat.get(chatId).push(it);
            });

            const merged: any[] = [];

            byChat.forEach((list) => {
                // if any processing/wait -> processing
                const hasActive = list.some(l => ['processing', 'wait'].includes(l.raw_status));
                const finalStatus = hasActive ? 'processing' : 'completed';
                // choose representative task: newest by updated_at
                list.sort((a,b) => (b.updated_at || '') .localeCompare(a.updated_at || ''));
                const rep = { ...list[0], status: finalStatus };
                merged.push(rep);
            });

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
        start();
    });

    onBeforeUnmount(() => {
        stop();
    });

    return {
        items,
        isRunning,
    };
}
