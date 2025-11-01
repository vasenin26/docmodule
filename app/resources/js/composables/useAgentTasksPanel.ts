import {ref, onMounted, onBeforeUnmount} from 'vue';
import {createApi} from '@/services/api/Api';
import {AgentTasksCheckRequest} from '@/services/api/request/Task/AgentTasksCheckRequest';

const POLL_INTERVAL = 5000;
const LOCAL_STORE_KEY = 'agent_tasks_list'

const api = createApi();

export type Status = 'processing' | 'completed' | 'await' | 'viewed';

export type TaskItem = {
    chat_id: number
    task_id: number
    type: string
    status: Status
    hidden: boolean
}

type Record = {
    items?: TaskItem[]
    lastUpdateTime?: number
}

class TaskLocalStorage {
    getChatIds(): number[] {
        return this.read().items.map((item: TaskItem) => item.task_id)
    }

    pushItem(chatId: number, taskId: number, status: string, type: string): void {
        const items = this.read().items
        const item = this.searchByChatId(chatId, items)

        if (item === null) {
            items.push({
                chat_id: chatId,
                task_id: taskId,
                status: this.defineStatus(status, null),
                type: type,
                hidden: false
            })
        } else {
            let hidden = item.hidden;
            const newStatus = this.defineStatus(status, item);

            if (newStatus === 'await') {
                hidden = false;
            }
            if(newStatus) {
                item.status = newStatus
            }

            item.task_id = taskId
            item.hidden = hidden
            item.type = type
        }

        this.store(items)
    }

    hideItem(chatId: number): never {
        const items = this.read().items
        const item = this.searchByChatId(chatId, items)

        if (item) {
            item.hidden = true
            this.store(items)
        }
    }

    markItem(chatId: number): never {
        const items = this.read().items
        const item = this.searchByChatId(chatId, items)

        if (item) {
            item.status = 'viewed'
            this.store(items)
        }
    }

    getLastUpdateTime(): number {
        return this.read().lastUpdateTime || 0
    }

    getItems(): TaskItem[] {
        return this.read().items || []
    }

    private defineStatus(newStatus: string, item: TaskItem | null): Status | null {
        if (item === null) {
            switch (newStatus) {
                case 'completed':
                    return 'completed'
                case 'processing':
                default:
                    return 'processing'
            }
        } else {
            switch (newStatus) {
                case 'wait':
                case 'processing':
                    return 'processing'
                case 'success':
                    if (item.status === 'processing')
                        return 'await'
                    if (item.status === 'viewed')
                        return 'viewed'
                    if (item.status === 'await')
                        return null
                    return 'completed'
            }
        }

        return item?.status || 'processing'
    }

    private searchByChatId(chatId: number, items: TaskItem[]): TaskItem | null {
        for (const item of items) {
            if (item.chat_id === chatId) return item;
        }

        return null;
    }

    private read(): Record {
        const record = localStorage.getItem(LOCAL_STORE_KEY)

        if (record === null) {
            return {
                items: [],
                lastUpdateTime: 0
            }
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

            if ((new Date()).getTime() - lastUpdateTime > POLL_INTERVAL - 1000) {
                const ids = taskStorage.getChatIds();
                const req = new AgentTasksCheckRequest(ids);
                const remoteItems = await req.call(api);

                for (const item of remoteItems) {
                    taskStorage.pushItem(
                        item.chat_id,
                        item.id,
                        item.status,
                        item.type
                    );
                }
            }

            items.value = taskStorage.getItems()
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

    function hideItem(chatId: number): never {
        taskStorage.hideItem(chatId)

        items.value = taskStorage.getItems()
    }

    function markItem(chatId: number): never {
        taskStorage.markItem(chatId)

        items.value = taskStorage.getItems()
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
        hideItem,
        markItem
    };
}
