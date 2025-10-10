import { Method, type ApiInterface, type Request } from '@/service/api/Api';

export interface TechplaneStopGeneratingResponse {
    success: boolean;
    message: string;
    techplane_id: number;
    agent_task_id: number[];
    chat_id: number;
}

export class TechplaneStopGeneratingRequest implements Request<TechplaneStopGeneratingResponse> {
    public readonly method: Method;
    public readonly url: string;
    public readonly body: any;

    constructor(techplaneId: number) {
        this.method = Method.UPDATE;
        this.url = route('techplanes.stop-generating', techplaneId);
        this.body = null;
    }

    public async call(api: ApiInterface): Promise<TechplaneStopGeneratingResponse> {
        return api.execute<TechplaneStopGeneratingResponse>(this);
    }
}


