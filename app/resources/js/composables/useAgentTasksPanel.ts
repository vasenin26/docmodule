import {ref, onMounted, onBeforeUnmount} from 'vue';
import {createApi} from '@/services/api/Api';
import {AgentTasksCheckRequest, AgentTasksCheckResponseItem} from '@/services/api/request/Task/AgentTasksCheckRequest';

const POLL_INTERVAL = 5000;
const LOCAL_STORE_KEY = 'agent_tasks_list'

const api = createApi();

export type Status = 'processing' | 'completed' | 'await';

export type TaskItem = {
    chat_id: number
    task_id: number
    status: Status,
    hidden: boolean
}

type Record = {
    items?: TaskItem[]
    lastUpdateTime?: number
}

class TaskLocalStorage {
    getChatIds(): number[] {
        const items = this.read();
        return items.map((item: TaskItem) => item.task_id)
    }

    pushItem(chatId: number, taskId: number, status: string): void {
        const items = this.read().items
        const item = this.searchByChatId(chatId, items)

        if (item === null) {
            items.push({
                chat_id: chatId,
                task_id: taskId,
                status: this.defineStatus(status, null),
                hidden: false
            })
        } else {
            item.status = this.defineStatus(status, item)
            item.task_id = taskId
            iten.hidden = false
        }

        this.store()
    }

    getLastUpdateTime(): number {
        return this.read().lastUpdateTime || 0
    }

    getItems(): TaskItem[] {
        return this.read().items || []
    }

    private defineStatus(current: string, item: TaskItem | null): Status {
        if (item === null) {
            switch (current) {
                case 'completed':
                    return 'completed'
                case 'processing':
                default:
                    return 'processing'
            }
        } else {
            if (item.status === 'completed' && (current === 'processing' || current === 'wait')) {
                return 'processing'
            }

            if (item.status === 'processing' && current === 'completed') {
                return 'await'
            }
        }

        return 'completed'
    }

    private searchByChatId(chatId: number, items: TaskItem): TaskItem | null {
        for (let item of items) {
            if (item.chat_id === chatId) return item;
        }

        return null;
    }

    private read(): Record {
        const record = localStorage.getItem(LOCAL_STORE_KEY)

        if (record === null) {
            return []
        }

        return JSON.parse(record) as Record;
    }

    private store(items: TaskItem[]): void {
        const json = JSON.stringify({
            items,
            lastUpdateTime: (new Date()).getTime()
        })
        localStorage.setItem(LOCAL_STORE_KEY, json)
    }
}

export function useAgentTasksPanel() {
    const stopped = ref(true);
    const isRunning = ref(false);
    const items = ref<TaskItem[]>([])
    let timeoutHandle: any = null;

    const taskStorage = new TaskLocalStorage();

    async function fetchOnce(): Promise<void> {
        if (stopped.value) return;
        if (isRunning.value) return;

        isRunning.value = true;

        try {
            const lastUpdateTime = taskStorage.getLastUpdateTime();

            if ((new Date()).getTime() - lastUpdateTime > POLL_INTERVAL) {
                const ids = taskStorage.getChatIds();
                const req = new AgentTasksCheckRequest(ids);
                const remoteItems = await req.call(api);

                for (let item of remoteItems) {
                    taskStorage.pushItem(
                        item.chat_id,
                        item.id,
                        item.raw_status
                    );
                }
            }

            items.value.set(taskStorage.getItems())
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
