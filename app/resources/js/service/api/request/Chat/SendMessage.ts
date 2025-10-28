import { Method, Request } from '@/service/api/Api';

export type SendMessageResponse = {
    status: string
};

export class SendMessage implements Request<SendMessageResponse> {
    body: any = {};
    method: Method = Method.CREATE;
    url: string;

    constructor(chatId: int, message: string) {
        this.url = route('chat.message.send', chatId);
        this.body = {
            message
        }
    }

    call(api: ApiInterface): Promise<SendMessageResponse> {
        return api.execute(this);
    }
}
