import { Method, Request } from '@/service/api/Api';
import { LLMMessage } from '@/types';

export type ChatState = {
    messages: LLMMessage[];
    totalTokens: number;
    contextFill: number;
    status: string;
};

export class GetChatState implements Request<ChatState> {
    body: any = {};
    method: Method = Method.CREATE;
    url: string;

    constructor(chatId: int) {
        this.url = route('chat.state', chatId);
    }

    call(api: ApiInterface): Promise<ChatState> {
        return api.execute(this);
    }
}
