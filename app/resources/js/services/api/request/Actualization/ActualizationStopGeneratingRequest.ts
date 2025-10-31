import { Method, type ApiInterface, type Request } from '@/services/api/Api';

export interface ActualizationStopGeneratingResponse {
    success: boolean;
    message: string;
    actualization_id: number;
    agent_task_id: number[];
    chat_id: number;
}

export class ActualizationStopGeneratingRequest implements Request<ActualizationStopGeneratingResponse> {
    public readonly method: Method;
    public readonly url: string;
    public readonly body: any;

    constructor(actualizationId: number) {
        this.method = Method.UPDATE;
        this.url = route('actualizations.stop-generating', actualizationId);
        this.body = null;
    }

    public async call(api: ApiInterface): Promise<ActualizationStopGeneratingResponse> {
        return api.execute<ActualizationStopGeneratingResponse>(this);
    }
}
