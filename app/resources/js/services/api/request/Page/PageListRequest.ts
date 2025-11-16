import { Method, type ApiInterface, type Request } from '@/services/api/Api';

export class PageListRequest implements Request<any> {
    public readonly method: Method = Method.LIST;
    public readonly url: string;
    public readonly body: any = null;

    constructor(params: Record<string, any> = {}, projectId?: number | null) {
        if (typeof route !== 'function') {
            throw new Error('Ziggy route is required');
        }

        if (projectId) {
            this.url = route('projects.pages.index', { project: projectId, ...params });
        } else {
            this.url = route('pages.index', params);
        }
    }

    public async call(api: ApiInterface): Promise<any> {
        return api.execute<any>(this);
    }
}
