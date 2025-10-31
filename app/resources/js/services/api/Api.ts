export enum Method {
    LIST = 'LIST',
    GET = 'GET',
    CREATE = 'POST',
    UPDATE = 'PUT',
    DELETE = 'DELETE',
}

export interface ApiInterface {
    execute<T>(request: Request<T>): Promise<T>;
}

export interface Request<T> {
    method: Method;
    url: string;
    body: any;

    call(api: ApiInterface): Promise<T>
}

class Api implements ApiInterface {

    private baseUrl:string;

    constructor(baseUrl: string, private readonly csrfToken: string | null) {
        this.baseUrl = baseUrl.replace(/\/+$/, '');
    }

    public async execute<T>(request: Request<T>): Promise<T> {
        const isAbsoluteUrl = /^https?:\/\//i.test(request.url);
        const url = isAbsoluteUrl ? request.url : this.baseUrl + request.url;

        const method = this.getMethod(request)

        const hasBodyMethod = method !== Method.GET && method !== Method.LIST && method !== Method.DELETE;

        const isFormData = typeof FormData !== 'undefined' && request.body instanceof FormData;

        const headers: Record<string, string> = {};
        headers['Accept'] = 'application/json';
        headers['X-Requested-With'] = 'XMLHttpRequest';
        if (this.csrfToken) {
            headers['X-CSRF-TOKEN'] = this.csrfToken;
        }
        if (hasBodyMethod && request.body != null && !isFormData) {
            headers['Content-Type'] = 'application/json';
        }

        const response = await fetch(url, {
            method,
            headers,
            credentials: 'same-origin',
            body: hasBodyMethod && request.body != null
                ? (isFormData ? request.body : JSON.stringify(request.body))
                : undefined,
        });

        if (!response.ok) {
            let errorPayload: unknown = undefined;
            const contentType = response.headers.get('content-type') || '';
            try {
                if (contentType.includes('application/json')) {
                    errorPayload = await response.json();
                } else {
                    errorPayload = await response.text();
                }
            } catch (_) {
                // ignore parse errors
            }
            const errorWithPayload = new Error(`HTTP ${response.status} ${response.statusText}`) as Error & { payload?: unknown };
            errorWithPayload.payload = errorPayload;
            throw errorWithPayload;
        }

        if (response.status === 204) {
            return undefined as unknown as T;
        }

        const contentType = response.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            return (await response.json()) as T;
        }

        // Fallback to text or empty
        try {
            const text = await response.text();
            return (text as unknown) as T;
        } catch (_) {
            return undefined as unknown as T;
        }
    }

    private getMethod(request: Request<any>): string
    {
        if(request.method as string === 'LIST') return 'GET';
        return request.method as string;
    }
}

let cachedApi: Api | null = null;

export function createApi(): Api {
    if (cachedApi) {
        return cachedApi;
    }

    // Get API URL from meta tag
    const apiUrl = typeof document !== 'undefined'
        ? (document.querySelector('meta[name="api-base-url"]')?.getAttribute('content') || 'http://localhost/api')
        : 'http://localhost/api';

    const csrfToken = typeof document !== 'undefined'
        ? (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || null)
        : null;
    cachedApi = new Api(apiUrl, csrfToken);
    return cachedApi;
}

let cachedWebApi: Api | null = null;

/**
 * @deprecated использовать createApi
 */
export function createWebApi(): Api {
    if (cachedWebApi) {
        return cachedWebApi;
    }

    // Get base URL for web routes (without /api prefix)
    const baseUrl = typeof document !== 'undefined'
        ? (document.querySelector('meta[name="app-url"]')?.getAttribute('content') || window.location.origin)
        : 'http://localhost';

    const csrfToken = typeof document !== 'undefined'
        ? (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || null)
        : null;
    cachedWebApi = new Api(baseUrl, csrfToken);
    return cachedWebApi;
}
