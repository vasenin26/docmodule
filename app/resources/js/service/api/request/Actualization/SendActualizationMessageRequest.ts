import { Method, type ApiInterface, type Request } from '@/service/api/Api';

export interface SendActualizationMessagePayload {
    message: string;
}

export interface SendActualizationMessageResponse {
    success: boolean;
    message: string;
    chat?: {
        id: number;
        messages: any[];
    };
}

export class SendActualizationMessageRequest implements Request<SendActualizationMessageResponse> {
    public readonly method: Method;
    public readonly url: string;
    public readonly body: any;

    constructor(actualizationId: number, payload: SendActualizationMessagePayload) {
        this.method = Method.CREATE;
        // @ts-ignore
        this.url = typeof route === 'function' ? route('actualizations.send-message', actualizationId) : `/actualizations/${actualizationId}/send-message`;
        this.body = payload;
    }

    public async call(api: ApiInterface): Promise<SendActualizationMessageResponse> {
        return api.execute<SendActualizationMessageResponse>(this);
    }
}


