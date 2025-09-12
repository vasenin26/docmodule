import { Method, type ApiInterface, type Request } from '@/service/api/Api';

export interface TaskRestartResponse {
    success: boolean;
}

export class TaskRestartGenerationRequest implements Request<TaskRestartResponse> {
    public readonly method: Method;
    public readonly url: string;
    public readonly body: any;

    constructor(url: string) {
        this.method = Method.CREATE;
        this.url = url;
        this.body = null;
    }

    public async call(api: ApiInterface): Promise<TaskRestartResponse> {
        return api.execute<TaskRestartResponse>(this);
    }
}


