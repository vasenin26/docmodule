import { Method, type ApiInterface, type Request } from '@/services/api/Api';

export interface AgentTasksCheckResponseItem {
    chat_id: number | null;
    id: number;
    status?: string | null;
    type: string
}

export class AgentTasksCheckRequest implements Request<AgentTasksCheckResponseItem[]> {
    public readonly method = Method.CREATE;
    public readonly url: string;
    public readonly body: any;

    constructor(ids: Array<string | number>) {
        this.url = route('agent-tasks.check');
        this.body = { ids };
    }

    public async call(api: ApiInterface): Promise<AgentTasksCheckResponseItem[]> {
        const response = await api.execute<AgentTasksCheckResponseItem[]>(this);

        return response.data as AgentTasksCheckResponseItem[];
    }
}
