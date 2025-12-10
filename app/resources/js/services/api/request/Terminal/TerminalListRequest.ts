import { Method, type ApiInterface, type Request } from '@/services/api/Api';

export interface TerminalListItem {
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

export class TerminalListRequest implements Request<TerminalListItem[]> {
    public readonly method: Method;
    public readonly url: string;
    public readonly body: any = {};

    constructor() {
        this.method = Method.GET;
        this.url = typeof route === 'function' 
            ? route('terminals.list') 
            : '/terminals/list';
    }

    public async call(api: ApiInterface): Promise<TerminalListItem[]> {
        return api.execute<TerminalListItem[]>(this);
    }
}

