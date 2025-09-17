export function detachPage(taskId: number, pageVersionId: number, opts?: any) {
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	const { router }: any = (window as any).Inertia || require('@inertiajs/vue3');
	return router.delete(route('tasks.attachments.destroy', { task: taskId, pageVersion: pageVersionId }), opts);
}
