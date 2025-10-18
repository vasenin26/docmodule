import { Method, type ApiInterface, type Request } from '@/service/api/Api';

export interface AgentTaskChatContentResponse {
    chat_id: number;
    content: string;
}

export class AgentTaskChatContentRequest implements Request<AgentTaskChatContentResponse> {
    public readonly method: Method;
    public readonly url: string;
    public readonly body: any;

    constructor(taskId: number) {
        this.method = Method.GET;
        // @ts-ignore
        this.url = typeof route === 'function' 
            ? route('agent-tasks.chat-content', { id: taskId })
            : `/agent-tasks/${taskId}/chat-content`;
        this.body = null;
    }

    public async call(api: ApiInterface): Promise<AgentTaskChatContentResponse> {
        return api.execute<AgentTaskChatContentResponse>(this);
    }
}
