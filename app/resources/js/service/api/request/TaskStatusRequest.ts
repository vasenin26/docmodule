import { Method, type ApiInterface, type Request } from '@/service/api/Api';

export interface TaskStatusResponse {
    status: string;
    content?: string | null;
}

export class TaskStatusRequest implements Request<TaskStatusResponse> {
    public readonly method: Method;
    public readonly url: string;
    public readonly body: any;

    constructor(url: string) {
        this.method = Method.GET;
        this.url = url;
        this.body = null;
    }

    public async call(api: ApiInterface): Promise<TaskStatusResponse> {
        return api.execute<TaskStatusResponse>(this);
    }
}


