import { Method, type ApiInterface, type Request } from '@/services/api/Api';

export interface TaskRestartResponse {
    success: boolean;
}

export class TaskRestartGenerationRequest implements Request<TaskRestartResponse> {
    public readonly method: Method;
    public readonly url: string;
    public readonly body: any;

    constructor(taskId: number) {
        this.method = Method.CREATE;
        // @ts-ignore
        this.url = typeof route === 'function' ? route('tasks.restart-generation', taskId) : `/tasks/${taskId}/restart-generation`;
        this.body = null;
    }

    public async call(api: ApiInterface): Promise<TaskRestartResponse> {
        return api.execute<TaskRestartResponse>(this);
    }
}


