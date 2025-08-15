<template>
  <AppLayout>
    <div class="container mx-auto py-8 px-4">
      <!-- Заголовок -->
      <div class="flex justify-between items-start mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">
            Технический план
          </h1>
          <p class="text-gray-600 mt-2">
            Создан: {{ formatDate(techplane.created_at) }} •
            Автор: {{ techplane.creator.name }}
          </p>
        </div>
        <div class="flex gap-3">
          <!-- Кнопка перегенерации -->
          <Button v-if="canRestartGeneration" @click="restartGeneration"
                  :disabled="isRestartingGeneration" variant="outline" size="sm">
            <span v-if="isRestartingGeneration">Перезапуск...</span>
            <span v-else>Перезапустить генерацию</span>
          </Button>
          <!-- Кнопка экспорта (заглушка) -->
          <Button variant="outline" disabled>
            Экспортировать
          </Button>
          <!-- Кнопка чата (если есть) -->
          <Button
            v-if="techplane.llm_chat"
            @click="openChatModal"
            variant="default"
          >
            Чат
          </Button>
          <!-- Кнопка возврата к задаче -->
          <Button as-child variant="outline">
            <Link :href="route('tasks.show', techplane.task.id)">
              К задаче
            </Link>
          </Button>
        </div>
      </div>

      <!-- Метаинформация -->
      <Card class="mb-6">
        <CardHeader>
          <CardTitle>Информация о техплане</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label class="text-sm font-medium text-gray-500">ID техплана</Label>
              <p class="text-sm">{{ techplane.id }}</p>
            </div>
            <div>
              <Label class="text-sm font-medium text-gray-500">Связанная задача</Label>
              <p class="text-sm">
                <Link :href="route('tasks.show', techplane.task.id)" class="text-blue-600 hover:underline">
                  Задача #{{ techplane.task.id }}
                </Link>
              </p>
            </div>
            <div>
              <Label class="text-sm font-medium text-gray-500">Страница</Label>
              <p class="text-sm">{{ techplane.task.page.title }}</p>
            </div>
            <div>
              <Label class="text-sm font-medium text-gray-500">Статус</Label>
              <p class="text-sm">{{ techplane.generation_status }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Содержимое техплана -->
      <Card>
        <CardHeader>
          <CardTitle>Описание техплана</CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="techplaneContent && techplaneContent.trim().length > 0" class="prose max-w-none">
            <MarkdownRenderer :content="techplaneContent" />
          </div>
          <div v-else class="text-muted-foreground italic">
            <div class="flex items-center gap-2">
              <div v-if="isPolling"
                   class="h-4 w-4 animate-spin rounded-full border-2 border-blue-500 border-t-transparent">
              </div>
              <span>{{ getStatusMessage() }}</span>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Модальное окно чата -->
      <Dialog v-model:open="showChatModal">
        <DialogContent class="max-w-5xl max-h-[80vh]">
          <DialogHeader>
            <DialogTitle>Чат LLM</DialogTitle>
          </DialogHeader>
          <div class="overflow-hidden">
            <AgentChat
              v-if="techplane.llm_chat"
              :messages="techplane.llm_chat.messages"
              :loading="isPolling && generationStatus === 'generating'"
            />
          </div>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import MarkdownRenderer from '@/components/MarkdownRenderer.vue'
import AgentChat from '@/components/AgentChat/AgentChat.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps(['techplane'])

const showChatModal = ref(false)

// Реактивные данные для отслеживания состояния генерации
const generationStatus = ref(props.techplane.generation_status)
const techplaneContent = ref(props.techplane.content)
const isPolling = ref(false)
const pollInterval = ref(null)
const isRestartingGeneration = ref(false)

// Вычисляемые свойства
const canRestartGeneration = computed(() => {
    return generationStatus.value !== 'generating'
})

// Функция для проверки статуса генерации
const checkGenerationStatus = async () => {
    try {
        const response = await fetch(route('techplanes.check-generation-status', props.techplane.id), {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })

        if (response.ok) {
            const data = await response.json()
            generationStatus.value = data.status
            techplaneContent.value = data.content

            // Остановить опрос если генерация завершена
            if (data.status === 'completed' || data.status === 'failed') {
                stopPolling()
            }
        }
    } catch (error) {
        console.error('Ошибка при запросе статуса:', error)
    }
}

// Функция для запуска автоматического опроса
const startPolling = () => {
    if (!isPolling.value) {
        isPolling.value = true
        pollInterval.value = setInterval(checkGenerationStatus, 3000) // каждые 3 секунды
    }
}

// Функция для остановки опроса
const stopPolling = () => {
    if (pollInterval.value) {
        clearInterval(pollInterval.value)
        pollInterval.value = null
        isPolling.value = false
    }
}

// Функция перезапуска генерации
const restartGeneration = async () => {
    if (!canRestartGeneration.value || isRestartingGeneration.value) {
        return
    }

    isRestartingGeneration.value = true

    try {
        const response = await fetch(route('techplanes.restart-generation', props.techplane.id), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            credentials: 'same-origin',
        })

        if (response.ok) {
            const data = await response.json()
            if (data.success) {
                // Сбросить состояние и начать опрос заново
                generationStatus.value = 'pending'
                techplaneContent.value = null
                startPolling()
            }
        } else {
            console.error('Ошибка при перезапуске генерации:', response.status, response.statusText)
        }
    } catch (error) {
        console.error('Ошибка при перезапуске генерации:', error)
    } finally {
        isRestartingGeneration.value = false
    }
}

// Lifecycle hooks
onMounted(() => {
    // Начинаем опрос если содержимое пустое или статус не завершен
    if (!techplaneContent.value || (generationStatus.value !== 'completed' && generationStatus.value !== 'failed')) {
        startPolling()
    }
})

onUnmounted(() => {
    stopPolling()
})

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleString('ru-RU', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const openChatModal = () => {
  showChatModal.value = true
}

// Функция для получения сообщения о статусе
const getStatusMessage = () => {
    switch (generationStatus.value) {
        case 'pending':
            return 'Техплан ожидает генерации...'
        case 'generating':
            return 'Техплан генерируется...'
        case 'failed':
            return 'Ошибка при генерации техплана'
        default:
            return 'Техплан еще не сгенерирован'
    }
}
</script>
