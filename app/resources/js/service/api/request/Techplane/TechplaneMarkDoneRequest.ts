import { Method, type ApiInterface, type Request } from '@/service/api/Api';

export interface TechplaneMarkDoneResponse {
    id: number;
    status: string;
    generation_status: string;
    solutions: Array<{
        id: number;
        content: string | null;
        mergeRequests: Array<{ id: number; url: string; created_by: number; created_at: string | null }>;
    }>;
}

export class TechplaneMarkDoneRequest implements Request<TechplaneMarkDoneResponse> {
    public readonly method: Method;
    public readonly url: string;
    public readonly body: any;

    constructor(techplaneId: number, mergeRequestUrl: string) {
        this.method = Method.CREATE;
        this.url = `/techplanes/${techplaneId}/done`;
        this.body = { mergeRequestUrl };
    }

    public async call(api: ApiInterface): Promise<TechplaneMarkDoneResponse> {
        return api.execute<TechplaneMarkDoneResponse>(this);
    }
}
