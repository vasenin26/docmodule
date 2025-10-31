export class TaskListRequest {
    constructor(
        public projectId?: number,
        public status?: string,
        public search?: string,
        public page: number = 1,
        public perPage: number = 15
    ) {}

    toParams(): Record<string, any> {
        const params: Record<string, any> = {
            page: this.page,
            per_page: this.perPage,
        };

        if (this.projectId) params.project_id = this.projectId;
        if (this.status) params.status = this.status;
        if (this.search) params.search = this.search;

        return params;
    }
}
