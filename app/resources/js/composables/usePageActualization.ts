import { router } from '@inertiajs/vue3';
import { computed, onUnmounted, ref } from 'vue';
import { createApi } from '@/services/api/Api';
import { CancelActualizationRequest, GetActualizationHistoryRequest, GetActualizationStatusRequest, StartVersionActualizationRequest } from '@/services/api/request/Page/PageActualizationRequests';

export interface ActualizationStatus {
    id: number;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    created_at: string;
    updated_at: string;
    created_by: string;
    has_chat: boolean;
}

export function usePageActualization(pageId: number) {
    const isActualizing = ref(false);
    const actualizationStatus = ref<ActualizationStatus | null>(null);
    const statusCheckInterval = ref<number | null>(null);
    const api = createApi();

    /**
     * Запустить процесс актуализации
     */
    const startActualization = async (versionId: int): Promise<number|null> => {
        if (isActualizing.value || hasActiveActualization.value) {
            return;
        }

        isActualizing.value = true;

        try {
            const result = await new StartVersionActualizationRequest(versionId).call(api);

            if (result.success) {
                return result.data.id
            } else {
                throw new Error(result.message || 'Ошибка при запуске актуализации');
            }
        } catch (error: any) {
            console.error('Error starting actualization:', error);

            let errorMessage = 'Ошибка при запуске актуализации';
            if (error.message) {
                errorMessage = error.message;
            }

            throw new Error(errorMessage);
        } finally {
            isActualizing.value = false;
        }
    };

    /**
     * Проверить текущий статус актуализации
     */
    const checkStatus = async (): Promise<void> => {
        try {
            const result = await new GetActualizationStatusRequest(pageId).call(api);
            if (result.success) {
                actualizationStatus.value = result.data;
            }
        } catch (error) {
            console.error('Error checking actualization status:', error);
        }
    };

    /**
     * Начать периодическую проверку статуса
     */
    const startStatusChecking = (): void => {
        if (statusCheckInterval.value) {
            clearInterval(statusCheckInterval.value);
        }

        statusCheckInterval.value = setInterval(async () => {
            await checkStatus();

            // Останавливаем проверку если актуализация завершилась
            if (actualizationStatus.value && ['completed', 'failed'].includes(actualizationStatus.value.status)) {
                stopStatusChecking();

                // Если актуализация завершилась успешно, перезагружаем страницу
                if (actualizationStatus.value.status === 'completed') {
                    router.reload();
                }
            }
        }, 5000); // Проверяем каждые 5 секунд
    };

    /**
     * Остановить периодическую проверку статуса
     */
    const stopStatusChecking = (): void => {
        if (statusCheckInterval.value) {
            clearInterval(statusCheckInterval.value);
            statusCheckInterval.value = null;
        }
    };

    /**
     * Отменить актуализацию
     */
    const cancelActualization = async (): Promise<void> => {
        if (!actualizationStatus.value) {
            return;
        }

        try {
            const result = await new CancelActualizationRequest(actualizationStatus.value.id).call(api);
            if (result.success) {
                await checkStatus();
                stopStatusChecking();
            } else {
                throw new Error(result.message || 'Ошибка при отмене актуализации');
            }
        } catch (error: any) {
            console.error('Error canceling actualization:', error);

            let errorMessage = 'Ошибка при отмене актуализации';
            if (error.message) {
                errorMessage = error.message;
            }

            throw new Error(errorMessage);
        }
    };

    /**
     * Получить список всех актуализаций для страницы
     */
    const getActualizationHistory = async (): Promise<ActualizationStatus[]> => {
        try {
            const result = await new GetActualizationHistoryRequest(pageId).call(api);
            if (result.success) {
                return result.data;
            } else {
                throw new Error('Ошибка при получении истории актуализаций');
            }
        } catch (error) {
            console.error('Error fetching actualization history:', error);
            throw error;
        }
    };

    /**
     * Есть ли активная актуализация
     */
    const hasActiveActualization = computed(() => {
        return actualizationStatus.value && ['pending', 'processing'].includes(actualizationStatus.value.status);
    });

    /**
     * Текст статуса для отображения
     */
    const statusText = computed(() => {
        if (!actualizationStatus.value) {
            return null;
        }

        const statusMap = {
            pending: 'Ожидает обработки',
            processing: 'Обрабатывается',
            completed: 'Завершена',
            failed: 'Ошибка',
        };

        return statusMap[actualizationStatus.value.status] || actualizationStatus.value.status;
    });

    /**
     * Цвет статуса для UI
     */
    const statusColor = computed(() => {
        if (!actualizationStatus.value) {
            return 'gray';
        }

        const colorMap = {
            pending: 'blue',
            processing: 'yellow',
            completed: 'green',
            failed: 'red',
        };

        return colorMap[actualizationStatus.value.status] || 'gray';
    });

    /**
     * Можно ли запустить актуализацию
     */
    const canStartActualization = computed(() => {
        return !isActualizing.value && !hasActiveActualization.value;
    });

    /**
     * Можно ли отменить актуализацию
     */
    const canCancelActualization = computed(() => {
        return actualizationStatus.value && ['pending', 'processing'].includes(actualizationStatus.value.status);
    });

    // Автоматически начинаем проверку статуса если есть активная актуализация
    if (hasActiveActualization.value) {
        startStatusChecking();
    }

    // Очищаем интервал при размонтировании компонента
    onUnmounted(() => {
        stopStatusChecking();
    });

    return {
        // Состояние
        isActualizing,
        actualizationStatus,
        hasActiveActualization,
        statusText,
        statusColor,
        canStartActualization,
        canCancelActualization,

        // Методы
        startActualization,
        cancelActualization,
        checkStatus,
        getActualizationHistory,
        startStatusChecking,
        stopStatusChecking,
    };
}
