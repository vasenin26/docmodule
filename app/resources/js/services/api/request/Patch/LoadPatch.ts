import { type ApiInterface, Method, Request } from '@/services/api/Api';

export type PatchDetails = {
    id: number
    title: string
    content: string
}

export class LoadPatchRequest implements Request<PatchDetails> {

    public readonly method: Method;
    public readonly url: string;
    public readonly body: any;

    constructor(patchId: number) {
        this.method = Method.GET;
        this.url = route('patches.get', patchId);
        this.body = null;
    }

    public async call(api: ApiInterface): Promise<PatchDetails> {
        return api.execute<PatchDetails>(this);
    }
}
