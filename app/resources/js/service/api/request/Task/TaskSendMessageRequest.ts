import { Method, type ApiInterface, type Request } from '@/service/api/Api';

export interface TaskSendMessagePayload {
    message: string;
}

export interface TaskSendMessageResponse {
    success: boolean;
    message: string;
    chat?: {
        id: number;
        messages: any[];
    };
}

export class TaskSendMessageRequest implements Request<TaskSendMessageResponse> {
    public readonly method: Method;
    public readonly url: string;
    public readonly body: any;

    constructor(taskId: number, payload: TaskSendMessagePayload) {
        this.method = Method.CREATE;
        // @ts-ignore
        this.url = typeof route === 'function' ? route('tasks.send-message', taskId) : `/tasks/${taskId}/send-message`;
        this.body = payload;
    }

    public async call(api: ApiInterface): Promise<TaskSendMessageResponse> {
        return api.execute<TaskSendMessageResponse>(this);
    }
}


