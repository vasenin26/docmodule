export async function searchPages(query: string, limit = 20) {
	const res = await fetch(route('pages.search', { query, limit }));
	return res.json();
}
