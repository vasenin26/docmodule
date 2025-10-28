import { Method, type ApiInterface, type Request } from '@/service/api/Api';

export interface ActualizationStartResponse {
    success: boolean;
    message?: string;
    data: {
        id: number | null;
    };
}

export interface ActualizationStatusData {
    id: number;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    created_at: string;
    updated_at: string;
    created_by: string;
    has_chat: boolean;
}

export interface ActualizationStatusResponse {
    success: boolean;
    data: ActualizationStatusData;
}

export class StartVersionActualizationRequest implements Request<ActualizationStartResponse> {
    public readonly method: Method = Method.CREATE;
    public readonly url: string;
    public readonly body: any = null;

    constructor(versionId: number) {
        this.url = route('pages.versions.actualise', versionId);
    }

    public async call(api: ApiInterface): Promise<ActualizationStartResponse> {
        return api.execute<ActualizationStartResponse>(this);
    }
}

export class GetActualizationStatusRequest implements Request<ActualizationStatusResponse> {
    public readonly method: Method = Method.GET;
    public readonly url: string;
    public readonly body: any = null;

    constructor(pageId: number) {
        this.url = `/pages/${pageId}/actualization/status`;
    }

    public async call(api: ApiInterface): Promise<ActualizationStatusResponse> {
        return api.execute<ActualizationStatusResponse>(this);
    }
}

export class CancelActualizationRequest implements Request<ActualizationStartResponse> {
    public readonly method: Method = Method.DELETE;
    public readonly url: string;
    public readonly body: any = null;

    constructor(actualizationId: number) {
        this.url = `/actualizations/${actualizationId}`;
    }

    public async call(api: ApiInterface): Promise<ActualizationStartResponse> {
        return api.execute<ActualizationStartResponse>(this);
    }
}

export class GetActualizationHistoryRequest implements Request<{ success: boolean; data: ActualizationStatusData[] }> {
    public readonly method: Method = Method.GET;
    public readonly url: string;
    public readonly body: any = null;

    constructor(pageId: number) {
        this.url = `/pages/${pageId}/actualizations`;
    }

    public async call(api: ApiInterface): Promise<{ success: boolean; data: ActualizationStatusData[] }> {
        return api.execute<{ success: boolean; data: ActualizationStatusData[] }>(this);
    }
}
