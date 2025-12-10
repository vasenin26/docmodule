import { Method, type ApiInterface, type Request } from '@/services/api/Api';
import { LLMMessage } from '@/types';

export interface TerminalChatStateResponse {
    messages: LLMMessage[];
    totalTokens: number;
    contextFill: number;
    status: string;
}

export class TerminalGetChatStateRequest implements Request<TerminalChatStateResponse> {
    public readonly method: Method;
    public readonly url: string;
    public readonly body: any = {};

    constructor(terminalId: number) {
        this.method = Method.GET;
        this.url = typeof route === 'function' 
            ? route('terminals.chat', terminalId) 
            : `/terminals/${terminalId}/chat`;
    }

    public async call(api: ApiInterface): Promise<TerminalChatStateResponse> {
        return api.execute<TerminalChatStateResponse>(this);
    }
}

