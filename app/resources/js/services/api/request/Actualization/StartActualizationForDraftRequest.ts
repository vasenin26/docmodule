import { Method, type ApiInterface, type Request } from '@/services/api/Api';

export interface StartActualizationResponse {
    success: boolean;
    message: string;
    data?: any;
}

export class StartActualizationForDraftRequest implements Request<StartActualizationResponse> {
    public readonly method: Method;
    public readonly url: string;
    public readonly body: any;

    constructor(draftId: number) {
        this.method = Method.CREATE;
        // @ts-ignore
        this.url = typeof route === 'function' ? route('drafts.actualize', draftId) : `/drafts/${draftId}/actualize`;
        this.body = {};
    }

    public async call(api: ApiInterface): Promise<StartActualizationResponse> {
        return api.execute<StartActualizationResponse>(this);
    }
}


