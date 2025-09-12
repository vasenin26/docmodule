import { Method, type ApiInterface, type Request } from '@/service/api/Api';

export interface TechplaneSendMessagePayload {
    message: string;
}

export interface TechplaneSendMessageResponse {
    success: boolean;
    message: string;
    chat?: {
        id: number;
        messages: any[];
    };
}

export class TechplaneSendMessageRequest implements Request<TechplaneSendMessageResponse> {
    public readonly method: Method;
    public readonly url: string;
    public readonly body: any;

    constructor(techplaneId: number, payload: TechplaneSendMessagePayload) {
        this.method = Method.CREATE;
        this.url = typeof route === 'function' ? route('techplanes.send-message', techplaneId) : `/techplanes/${techplaneId}/send-message`;
        this.body = payload;
    }

    public async call(api: ApiInterface): Promise<TechplaneSendMessageResponse> {
        return api.execute<TechplaneSendMessageResponse>(this);
    }
}
