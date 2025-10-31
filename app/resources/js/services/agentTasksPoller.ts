import { createApi } from '@/service/api/Api';
import { AgentTasksCheckRequest, AgentTasksCheckResponseItem } from '@/service/api/request/Task/AgentTasksCheckRequest';

const POLL_INTERVAL = 5000; // ms
const MAX_IDS = 50;

let stopped = true;
let isRunning = false;
let timeoutHandle: any = null;

function readLocalTasks(): Array<{ id: string }> {
    try {
        const raw = localStorage.getItem('agent_tasks_panel_ids');
        if (!raw) return [];
        const parsed = JSON.parse(raw);
        if (!Array.isArray(parsed)) return [];
        return parsed.map((it) => ({ id: String(it.id ?? it) }));
    } catch (e) {
        console.error('Failed to read agent tasks ids from localStorage', e);
        return [];
    }
}

function mergeServerData(items: AgentTasksCheckResponseItem[]) {
    try {
        window.dispatchEvent(new CustomEvent('agent_tasks.updated', { detail: { items } }));
    } catch (e) {
        console.error('Failed to dispatch agent_tasks.updated event', e);
    }
}

async function tickOnce() {
    if (stopped) return;
    if (isRunning) {
        timeoutHandle = setTimeout(tickOnce, POLL_INTERVAL);
        return;
    }

    isRunning = true;

    try {
        const localList = readLocalTasks();
        const idsToCheck = localList.map((it) => it.id).slice(0, MAX_IDS);

        const api = createApi();
        const req = new AgentTasksCheckRequest(idsToCheck);
        const data = await req.call(api);

        mergeServerData(data);
    } catch (err) {
        console.error('AgentTasks poller error', err);
    } finally {
        isRunning = false;
        if (!stopped) {
            timeoutHandle = setTimeout(tickOnce, POLL_INTERVAL);
        }
    }
}

export function startPoller() {
    if (!stopped) return;
    stopped = false;
    tickOnce();
}

export function stopPoller() {
    stopped = true;
    if (timeoutHandle) {
        clearTimeout(timeoutHandle);
        timeoutHandle = null;
    }
}

export function isPollerRunning(): boolean {
    return !stopped || isRunning;
}

const agentTasksPoller = {
    startPoller,
    stopPoller,
    getPanelItems: readLocalTasks,
    hideChat: (chatId: number | string) => {
        try {
            const key = 'agent_tasks_panel_hidden_' + String(chatId);
            localStorage.setItem(key, '1');
        } catch (e) {
            console.error('agentTasksPoller: hideChat failed', e);
        }
    },
    isPollerRunning,
};

export { agentTasksPoller };
export default agentTasksPoller;
