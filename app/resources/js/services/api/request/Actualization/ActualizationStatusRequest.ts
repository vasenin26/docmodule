import { Method, type ApiInterface, type Request } from '@/services/api/Api';

export interface ActualizationStatusResponse {
    success: boolean;
    data: {
        id: number;
        status: string;
        content: string;
        chat_id: int|null;
    };
}

export class ActualizationStatusRequest implements Request<ActualizationStatusResponse> {
    public readonly method: Method;
    public readonly url: string;
    public readonly body: any;

    constructor(actualizationId: number) {
        this.method = Method.GET;
        this.url = route('actualization.status', actualizationId);
        this.body = null;
    }

    public async call(api: ApiInterface): Promise<ActualizationStatusResponse> {
        return api.execute<ActualizationStatusResponse>(this);
    }
}


