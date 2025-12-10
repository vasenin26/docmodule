import { Method, type ApiInterface, type Request } from '@/services/api/Api';

export interface TerminalCreatePayload {
    name?: string;
}

export interface TerminalCreateResponse {
    id: number;
    project_id: number;
    chat_id: number;
    created_by: number;
    created_at: string;
    updated_at: string;
    llm_chat?: {
        id: number;
        messages: any[];
    };
}

export class TerminalCreateRequest implements Request<TerminalCreateResponse> {
    public readonly method: Method;
    public readonly url: string;
    public readonly body: any;

    constructor(payload: TerminalCreatePayload = {}) {
        this.method = Method.CREATE;
        this.url = typeof route === 'function' 
            ? route('terminals.store') 
            : '/terminals';
        this.body = payload;
    }

    public async call(api: ApiInterface): Promise<TerminalCreateResponse> {
        return api.execute<TerminalCreateResponse>(this);
    }
}

