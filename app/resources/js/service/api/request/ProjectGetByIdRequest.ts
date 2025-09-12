import { Method, type ApiInterface, type Request } from '@/service/api/Api';
import type { Project } from '@/types';

export class ProjectGetByIdRequest implements Request<Project> {
    public readonly method: Method;
    public readonly url: string;
    public readonly body: any;

    constructor(projectId: number) {
        this.method = Method.GET;
        this.url = `/api/projects/${projectId}`;
        this.body = null;
    }

    public async call(api: ApiInterface): Promise<Project> {
        return api.execute<Project>(this);
    }
}


