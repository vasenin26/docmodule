import { Method, Request } from '@/services/api/Api';

export type StopGenerationResponse = {
    status: string
};

export class StopChatGeneration implements Request<StopGenerationResponse> {
    body: any = {};
    method: Method = Method.DELETE;
    url: string;

    constructor(chatId: int) {
        this.url = route('chat.stop', chatId);
    }

    call(api: ApiInterface): Promise<StopGenerationResponse> {
        return api.execute(this);
    }
}
