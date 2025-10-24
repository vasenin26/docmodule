import { Method, type ApiInterface, type Request } from '@/service/api/Api';

export interface TaskStatusResponse {
    status: string;
    title?: string | null;
    content?: string | null;
    updated_at?: string;
    chat?: {
        id: number;
        messages: any[];
    } | null;
}

export class TaskStatusRequest implements Request<TaskStatusResponse> {
    public readonly method: Method;
    public readonly url: string;
    public readonly body: any;

    constructor(taskId: number) {
        this.method = Method.GET;
        // Use ziggy route helper if available; fallback to REST path
        // @ts-ignore
        this.url = typeof route === 'function' ? route('tasks.status', taskId) : `/tasks/${taskId}/status`;
        this.body = null;
    }

    public async call(api: ApiInterface): Promise<TaskStatusResponse> {
        return api.execute<TaskStatusResponse>(this);
    }
}


