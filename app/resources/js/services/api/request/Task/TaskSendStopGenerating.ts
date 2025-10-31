import { Method, type ApiInterface, type Request } from '@/services/api/Api';


export interface TaskSendMessageResponse {
    success: boolean;
    message: string;
}

export class TaskSendStopGenerating implements Request<TaskSendMessageResponse> {
    public readonly method: Method;
    public readonly url: string;
    public readonly body: any;

    constructor(taskId: number) {
        this.method = Method.UPDATE;
        this.url = route('tasks.stop-generating', taskId);
        this.body = null;
    }

    public async call(api: ApiInterface): Promise<TaskSendMessageResponse> {
        return api.execute<TaskSendMessageResponse>(this);
    }
}


