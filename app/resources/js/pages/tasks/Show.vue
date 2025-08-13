<template>
  <AppLayout :title="`Задача: ${task.page.title}`">
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <Heading :title="`Задача для страницы: ${task.page.title}`" />
          <p class="text-sm text-muted-foreground mt-1">
            Создана {{ formatDate(task.created_at) }} пользователем {{ task.creator?.name }}
          </p>
        </div>
        <div class="flex items-center gap-2">
          <TaskExportButton />
          <Button as-child variant="outline">
            <Link :href="route('pages.show', task.page.id)">
              К странице
            </Link>
          </Button>
        </div>
      </div>
    </template>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-7xl">
      <!-- Основное содержимое -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Информация о странице -->
        <Card>
        <CardHeader>
          <CardTitle>Информация о странице</CardTitle>
          <CardDescription>
            Детали страницы, для которой создана задача
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label class="text-sm font-medium">Заголовок</Label>
              <p class="text-sm text-muted-foreground mt-1">{{ task.page.title }}</p>
            </div>
            <div>
              <Label class="text-sm font-medium">Автор</Label>
              <p class="text-sm text-muted-foreground mt-1">{{ task.page.creator.name }}</p>
            </div>
            <div>
              <Label class="text-sm font-medium">Дата создания</Label>
              <p class="text-sm text-muted-foreground mt-1">{{ formatDate(task.page.created_at) }}</p>
            </div>
            <div v-if="task.page.previous_version">
              <Label class="text-sm font-medium">Предыдущая версия</Label>
              <p class="text-sm text-muted-foreground mt-1">{{ task.page.previous_version.title }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Описание задачи -->
      <Card>
        <CardHeader>
          <CardTitle>Описание задачи</CardTitle>
          <CardDescription>
            Автоматически сгенерированное описание задачи на основе изменений в документации
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="prose prose-sm max-w-none">
            <MarkdownRenderer
              v-if="taskContent"
              :content="taskContent"
            />
            <div v-else class="text-muted-foreground italic">
              <div class="flex items-center gap-2">
                <div v-if="isPolling" class="w-4 h-4 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                <span>{{ getStatusMessage() }}</span>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Сравнение версий -->
      <Card v-if="task.page.previous_version">
        <CardHeader>
          <CardTitle>Сравнение версий</CardTitle>
          <CardDescription>
            Изменения между предыдущей и текущей версией страницы
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <!-- Сравнение заголовков -->
          <div v-if="task.page.title !== task.page.previous_version.title">
            <Label class="text-sm font-medium">Изменение заголовка</Label>
            <div class="mt-2 space-y-2">
              <div class="p-2 bg-red-50 border border-red-200 rounded">
                <span class="text-xs text-red-600 font-medium">Было:</span>
                <p class="text-sm">{{ task.page.previous_version.title }}</p>
              </div>
              <div class="p-2 bg-green-50 border border-green-200 rounded">
                <span class="text-xs text-green-600 font-medium">Стало:</span>
                <p class="text-sm">{{ task.page.title }}</p>
              </div>
            </div>
          </div>

          <!-- Сравнение содержимого -->
          <div v-if="task.page.content !== task.page.previous_version.content">
            <Label class="text-sm font-medium">Изменение содержимого</Label>
            <div class="mt-2">
              <DiffViewer
                :old-content="task.page.previous_version.content"
                :new-content="task.page.content"
              />
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Метаинформация -->
      <Card>
        <CardHeader>
          <CardTitle>Метаинформация</CardTitle>
        </CardHeader>
        <CardContent class="space-y-2">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
              <Label class="font-medium">ID задачи</Label>
              <p class="text-muted-foreground">{{ task.id }}</p>
            </div>
            <div>
              <Label class="font-medium">ID страницы</Label>
              <p class="text-muted-foreground">{{ task.page.id }}</p>
            </div>
            <div>
              <Label class="font-medium">Создатель задачи</Label>
              <p class="text-muted-foreground">{{ task.creator.name }}</p>
            </div>
            <div>
              <Label class="font-medium">Дата создания задачи</Label>
              <p class="text-muted-foreground">{{ formatDate(task.created_at) }}</p>
            </div>
          </div>
        </CardContent>
      </Card>
      </div>

      <!-- Боковая панель с чатом -->
      <div class="lg:col-span-1">
        <Card v-if="task.llm_chat" class="h-fit">
          <CardHeader>
            <CardTitle class="text-lg">История LLM</CardTitle>
            <CardDescription>
              Процесс генерации описания задачи
            </CardDescription>
          </CardHeader>
          <CardContent class="p-0">
            <AgentChat
              :messages="task.llm_chat.messages"
              :loading="isPolling && generationStatus === 'generating'"
            />
          </CardContent>
        </Card>

        <!-- Заглушка для отсутствующего чата -->
        <Card v-else class="h-fit">
          <CardHeader>
            <CardTitle class="text-lg">История LLM</CardTitle>
            <CardDescription>
              Процесс генерации описания задачи
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div class="flex items-center justify-center py-8 text-center">
              <div class="text-gray-500">
                <div class="text-sm">История LLM недоступна</div>
                <div class="text-xs mt-1">Чат будет доступен после окончания генерации</div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import MarkdownRenderer from '@/components/MarkdownRenderer.vue'
import TaskExportButton from '@/components/TaskExportButton.vue'
import DiffViewer from '@/components/DiffViewer.vue'
import AgentChat from '@/components/AgentChat.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import type { LLMChat } from '@/types'

interface TaskData {
  id: number
  content: string
  generation_status: string
  created_at: string
  updated_at: string
  page: {
    id: number
    title: string
    content: string
    created_at: string
    creator: {
      id: number
      name: string
      email: string
    }
    previous_version?: {
      id: number
      title: string
      content: string
    }
  }
  creator: {
    id: number
    name: string
    email: string
  }
  llm_chat?: LLMChat | null
}

const props = defineProps<{
  task: TaskData
}>()

// Реактивные переменные для отслеживания статуса
const generationStatus = ref<string>(props.task.generation_status || 'unknown')
const taskContent = ref<string>(props.task.content || '')
const isPolling = ref<boolean>(false)
const pollInterval = ref<number | null>(null)

// Функция проверки статуса генерации
const checkGenerationStatus = async () => {
  try {
    const response = await fetch(route('tasks.status', props.task.id), {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      credentials: 'same-origin'
    })

    if (response.ok) {
      const data = await response.json()
      generationStatus.value = data.status
      taskContent.value = data.content || taskContent.value

      // Останавливаем опрос если генерация завершена или завершилась с ошибкой
      if (generationStatus.value === 'completed' || generationStatus.value === 'failed') {
        stopPolling()
      }
    } else {
      console.error('Ошибка HTTP:', response.status, response.statusText)
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

// Lifecycle hooks
onMounted(() => {
  // Начинаем опрос если содержимое пустое или статус не завершен
  if (!taskContent.value || (generationStatus.value !== 'completed' && generationStatus.value !== 'failed')) {
    startPolling()
  }
})

onUnmounted(() => {
  stopPolling()
})

const formatDate = (date: string) => {
  return new Date(date).toLocaleString('ru-RU', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Функция для получения сообщения о статусе
const getStatusMessage = () => {
  switch (generationStatus.value) {
    case 'pending':
      return 'Описание задачи ожидает генерации...'
    case 'generating':
      return 'Описание задачи генерируется...'
    case 'failed':
      return 'Ошибка при генерации описания задачи'
    default:
      return 'Описание задачи еще не сгенерировано'
  }
}
</script>
