import { Method, Request, ApiInterface } from '@/services/api/Api';

export interface SendImplementationMessagePayload {
    message: string;
}

export interface SendImplementationMessageResponse {
    success: boolean;
    message: string;
}

export class SendImplementationMessageRequest implements Request<SendImplementationMessageResponse> {
    public readonly method: Method;
    public readonly url: string;
    public readonly body: any;

    constructor(implementationId: number, payload: SendImplementationMessagePayload) {
        this.method = Method.CREATE;
        // @ts-ignore
        this.url = typeof route === 'function' ? route('implementations.send-message', implementationId) : `/implementations/${implementationId}/send-message`;
        this.body = payload;
    }

    public async call(api: ApiInterface): Promise<SendImplementationMessageResponse> {
        return api.execute<SendImplementationMessageResponse>(this);
    }
}
