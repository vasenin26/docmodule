import { Method, type ApiInterface, type Request } from '@/services/api/Api';

export type UpdateResponse = {
    success: boolean;
};

export class UpdatePageContent implements Request<UpdateResponse> {
    public readonly method: Method = Method.UPDATE;
    public readonly url: string;
    public readonly body: any = null;

    constructor(page: number, version: number, content: string) {
        this.url = route('pages.versions.update', { page, version });
        this.body = { content, content_format: 'markdown' };
    }

    public async call(api: ApiInterface): Promise<UpdateResponse> {
        return api.execute<UpdateResponse>(this);
    }
}
