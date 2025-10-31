import { Method, type ApiInterface, type Request } from '@/services/api/Api';

export interface TechplaneStatusResponse {
    status: string;
    content: string | null;
    updated_at: string;
    chat?: {
        id: number;
        messages: any[];
    };
}

export class TechplaneStatusRequest implements Request<TechplaneStatusResponse> {
    public readonly method: Method;
    public readonly url: string;

    constructor(techplaneId: number) {
        this.method = Method.GET;
        this.url = typeof route === 'function' ? route('techplanes.check-generation-status', techplaneId) : `/techplanes/${techplaneId}/check-generation-status`;
    }

    public async call(api: ApiInterface): Promise<TechplaneStatusResponse> {
        return api.execute<TechplaneStatusResponse>(this);
    }
}
