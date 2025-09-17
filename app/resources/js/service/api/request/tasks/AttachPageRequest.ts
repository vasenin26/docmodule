export function attachPage(taskId: number, pageVersionId: number, opts?: any) {
	// Using Inertia router available globally via Ziggy route helper
	// Consumers should pass opts with onSuccess/onError handlers
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	const { router }: any = (window as any).Inertia || require('@inertiajs/vue3');
	return router.post(route('tasks.attachments.store', { task: taskId }), { page_version_id: pageVersionId }, opts);
}
