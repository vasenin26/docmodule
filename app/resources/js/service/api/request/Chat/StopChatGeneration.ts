import { Method, Request } from '@/service/api/Api';

export type StopGenerationResponse = {
    status: string
};

export class StopChatGeneration implements Request<StopGenerationResponse> {
    body: any = {};
    method: Method = Method.CREATE;
    url: string;

    constructor(chatId: int) {
        this.url = route('chat.stop', chatId);
    }

    call(api: ApiInterface): Promise<StopGenerationResponse> {
        return api.execute(this);
    }
}
