import { Method, type ApiInterface, type Request } from '@/services/api/Api';

export interface TerminalSendCommandPayload {
    message: string;
}

export interface TerminalSendCommandResponse {
    success: boolean;
    message: string;
    chat?: {
        id: number;
        messages: any[];
    };
}

export class TerminalSendCommandRequest implements Request<TerminalSendCommandResponse> {
    public readonly method: Method;
    public readonly url: string;
    public readonly body: any;

    constructor(terminalId: number, payload: TerminalSendCommandPayload) {
        this.method = Method.CREATE;
        this.url = typeof route === 'function' 
            ? route('terminals.command', terminalId) 
            : `/terminals/${terminalId}/command`;
        this.body = payload;
    }

    public async call(api: ApiInterface): Promise<TerminalSendCommandResponse> {
        return api.execute<TerminalSendCommandResponse>(this);
    }
}

