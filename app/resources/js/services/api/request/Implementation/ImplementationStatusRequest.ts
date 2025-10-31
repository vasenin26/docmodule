import { Method, type ApiInterface, type Request } from '@/services/api/Api';

export interface ImplementationStatusResponse {
    status: string;
    content?: string | null;
    updated_at?: string;
    chat?: {
        id: number;
        messages: any[];
    } | null;
}

export class ImplementationStatusRequest implements Request<ImplementationStatusResponse> {
    public readonly method: Method;
    public readonly url: string;
    public readonly body: any;

    constructor(implementationId: number) {
        this.method = Method.GET;
        // @ts-ignore
        this.url = typeof route === 'function' ? route('implementations.check-status', implementationId) : `/implementations/${implementationId}/check-status`;
        this.body = null;
    }

    public async call(api: ApiInterface): Promise<ImplementationStatusResponse> {
        return api.execute<ImplementationStatusResponse>(this);
    }
}
