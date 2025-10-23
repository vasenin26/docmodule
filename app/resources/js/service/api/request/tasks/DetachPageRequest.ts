export function detachPage(taskId: number, pageVersionId: number, opts?: any) {
	 
	const { router }: any = (window as any).Inertia || require('@inertiajs/vue3');
	return router.delete(route('tasks.attachments.destroy', { task: taskId, pageVersion: pageVersionId }), opts);
}
