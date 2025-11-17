import { Method, type ApiInterface, type Request } from '@/services/api/Api';

export interface PageListParams {
    id?: number;
    search?: string;
    per_page?: number;
    page?: number;
}

export class PageListRequest implements Request<any> {
    public readonly method: Method = Method.LIST;
    public readonly url: string;
    public readonly body: any = null;

    constructor(params: PageListParams = {}, projectId?: number | null) {
        if (typeof route !== 'function') {
            throw new Error('Ziggy route is required');
        }

        // Build params object only with provided keys
        const routeParams: Record<string, any> = {};
        if (params.id !== undefined) routeParams.id = params.id;
        if (params.search !== undefined) routeParams.search = params.search;
        if (params.per_page !== undefined) routeParams.per_page = params.per_page;
        if (params.page !== undefined) routeParams.page = params.page;

        // Use project-scoped search route when projectId provided
        if (projectId) {
            this.url = route('projects.pages.search', { project: projectId, ...routeParams });
        } else {
            this.url = route('pages.index', routeParams);
        }
    }

    public async call(api: ApiInterface): Promise<any> {
        return api.execute<any>(this);
    }
}
