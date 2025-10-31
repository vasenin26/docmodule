import { Method, type ApiInterface, type Request } from '@/services/api/Api';
import type { Project } from '@/types';

export class ProjectListRequest implements Request<Project[]> {
    public readonly method: Method;
    public readonly url: string;
    public readonly body: any;

    constructor() {
        this.method = Method.LIST;
        this.url = '/projects';
        this.body = null;
    }

    public async call(api: ApiInterface): Promise<Project[]> {
        return api.execute<Project[]>(this);
    }
}


