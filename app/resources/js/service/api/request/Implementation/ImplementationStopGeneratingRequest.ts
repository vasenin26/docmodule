import { Method, type ApiInterface, type Request } from '@/service/api/Api';

export interface ImplementationStopGeneratingResponse {
    success: boolean;
    message: string;
    implementation_id: number;
    agent_task_id: number[];
    chat_id: number;
}

export class ImplementationStopGeneratingRequest implements Request<ImplementationStopGeneratingResponse> {
    public readonly method: Method;
    public readonly url: string;
    public readonly body: any;

    constructor(implementationId: number) {
        this.method = Method.UPDATE;
        this.url = route('implementations.stop-generating', implementationId);
        this.body = null;
    }

    public async call(api: ApiInterface): Promise<ImplementationStopGeneratingResponse> {
        return api.execute<ImplementationStopGeneratingResponse>(this);
    }
}


