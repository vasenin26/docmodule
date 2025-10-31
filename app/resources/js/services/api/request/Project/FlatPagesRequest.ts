import { Method, type ApiInterface, type Request } from '@/services/api/Api';
import type { FlatPage } from '@/types';

export class FlatPagesRequest implements Request<{ pages: FlatPage[] }> {
    public readonly method: Method;
    public readonly url: string;
    public readonly body: any;

    constructor(private readonly projectId: number) {
        this.method = Method.GET;
        this.url = `/projects/${projectId}/flat-pages`;
        this.body = null;
    }

    public async call(api: ApiInterface): Promise<{ pages: FlatPage[] }> {
        return api.execute<{ pages: FlatPage[] }>(this);
    }
}
