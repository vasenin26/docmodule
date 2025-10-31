import { Method, type ApiInterface, type Request } from '@/service/api/Api';

export interface AgentTasksCheckResponseItem {
    id: string;
    chat_id: number | null;
    agent_task_id: number;
    url?: string | null;
    raw_status?: string | null;
    updated_at?: string | null;
}

export class AgentTasksCheckRequest implements Request<AgentTasksCheckResponseItem[]> {
    public readonly method = Method.CREATE;
    public readonly url: string;
    public readonly body: any;

    constructor(ids: Array<string | number>) {
        // @ts-ignore
        this.url = typeof route === 'function' ? route('agent-tasks.check') : '/agent-tasks/check';
        this.body = { ids };
    }

    public async call(api: ApiInterface): Promise<AgentTasksCheckResponseItem[]> {
        return api.execute<AgentTasksCheckResponseItem[]>(this);
    }
}
