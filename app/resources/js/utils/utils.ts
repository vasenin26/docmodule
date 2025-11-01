export async function sleep(delay: number) {
    return new Promise((r) => setTimeout(r, delay));
}



export async function navigateToTargetResource(taskId: number): void{
    try {
        const response = await fetch(`/agent-tasks/${taskId}/target-resource`);
        const data = await response.json();

        if (response.ok && data.url) {
            // Перенаправляем на целевую страницу
            window.location.href = data.url;
        } else {
            // Показываем ошибку
            alert(data.error || 'Ресурс не найден');
        }
    } catch (error) {
        console.error('Error fetching target resource:', error);
        alert('Ошибка при определении целевого ресурса');
    } finally {
        isLoadingTargetResource.value = false;
    }
};
