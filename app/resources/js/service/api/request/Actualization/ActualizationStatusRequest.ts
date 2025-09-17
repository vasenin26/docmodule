import { Method, type ApiInterface, type Request } from '@/service/api/Api';

export interface ActualizationStatusResponse {
    success: boolean;
    data: {
        id: number;
        status: string;
        content: string;
        chat: {
            id: number;
            messages: any[];
        } | null;
        created_at: string;
        updated_at: string;
        created_by: string;
    };
}

export class ActualizationStatusRequest implements Request<ActualizationStatusResponse> {
    public readonly method: Method;
    public readonly url: string;
    public readonly body: any;

    constructor(actualizationId: number) {
        this.method = Method.GET;
        // @ts-ignore
        this.url = typeof route === 'function' ? route('actualizations.status-with-chat', actualizationId) : `/actualizations/${actualizationId}/status-with-chat`;
        this.body = null;
    }

    public async call(api: ApiInterface): Promise<ActualizationStatusResponse> {
        return api.execute<ActualizationStatusResponse>(this);
    }
}


