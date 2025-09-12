import { Method, Request, ApiInterface } from '@/service/api/Api';

export interface TechplaneExecuteResponse {
    success: boolean;
    message: string;
    redirect_url: string;
}

export class TechplaneExecuteRequest implements Request<TechplaneExecuteResponse> {
    public readonly method: Method;
    public readonly url: string;
    public readonly body: any;

    constructor(techplaneId: number) {
        this.method = Method.CREATE;
        // @ts-ignore
        this.url = typeof route === 'function' ? route('techplanes.execute', techplaneId) : `/techplanes/${techplaneId}/execute`;
        this.body = {};
    }

    public async call(api: ApiInterface): Promise<TechplaneExecuteResponse> {
        return api.execute<TechplaneExecuteResponse>(this);
    }
}
