<template>
  <AppLayout :title="`Актуализация: ${page.title}`">
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <Heading :title="`Актуализация: ${page.title}`" />
          <p class="text-sm text-muted-foreground mt-1">
            Запущена {{ formatDate(actualization.created_at) }} пользователем {{ actualization.created_by.name }}
          </p>
        </div>
        <div class="flex items-center gap-2">
          <Button as-child variant="outline">
            <Link :href="route('pages.show', page.id)">
              Назад к странице
            </Link>
          </Button>
        </div>
      </div>
    </template>

    <div class="max-w-4xl space-y-6">
      <!-- Статус актуализации -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <RefreshCw :class="{ 'animate-spin': actualization.status === 'processing' }" class="h-5 w-5" />
            Статус актуализации
          </CardTitle>
          <CardDescription>
            Информация о процессе актуализации документации
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm font-medium">Статус</p>
              <div class="flex items-center gap-2 mt-1">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="{
                        'bg-blue-100 text-blue-800': actualization.status === 'pending',
                        'bg-yellow-100 text-yellow-800': actualization.status === 'processing',
                        'bg-green-100 text-green-800': actualization.status === 'completed',
                        'bg-red-100 text-red-800': actualization.status === 'failed'
                      }">
                  {{ getStatusText(actualization.status) }}
                </span>
              </div>
            </div>
            <div>
              <p class="text-sm font-medium">Запущена</p>
              <p class="text-sm text-muted-foreground mt-1">
                {{ formatDate(actualization.created_at) }}
              </p>
            </div>
            <div>
              <p class="text-sm font-medium">Обновлена</p>
              <p class="text-sm text-muted-foreground mt-1">
                {{ formatDate(actualization.updated_at) }}
              </p>
            </div>
            <div>
              <p class="text-sm font-medium">Инициатор</p>
              <p class="text-sm text-muted-foreground mt-1">
                {{ actualization.created_by.name }}
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Обновленное содержимое -->
      <Card>
        <CardHeader>
          <CardTitle>Обновленное содержимое</CardTitle>
          <CardDescription>
            Результат актуализации документации на основе прикрепленных файлов
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="prose max-w-none">
            <MarkdownRenderer :content="page.content" />
          </div>
        </CardContent>
      </Card>

      <!-- Прикрепленные файлы -->
      <Card v-if="page.files && page.files.length > 0">
        <CardHeader>
          <CardTitle>Проанализированные файлы</CardTitle>
          <CardDescription>
            Файлы, которые были использованы для актуализации документации
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-2">
            <div 
              v-for="(file, index) in page.files" 
              :key="index"
              class="flex items-center gap-3 p-3 border rounded-lg"
            >
              <FileIcon class="h-4 w-4 text-muted-foreground flex-shrink-0" />
              <div class="flex-1 min-w-0">
                <a 
                  :href="file" 
                  target="_blank" 
                  rel="noopener noreferrer"
                  class="text-sm font-medium hover:underline truncate block"
                >
                  {{ getFileName(file) }}
                </a>
                <p class="text-xs text-muted-foreground truncate">
                  {{ file }}
                </p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- LLM чат (если есть) -->
      <Card v-if="chat">
        <CardHeader>
          <CardTitle>Детали обработки</CardTitle>
          <CardDescription>
            Информация о работе ИИ при актуализации
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-3 gap-4 mb-4">
            <div class="text-center p-4 bg-muted/50 rounded-lg">
              <p class="text-2xl font-bold">{{ chat.prompt_tokens || 0 }}</p>
              <p class="text-sm text-muted-foreground">Промпт токены</p>
            </div>
            <div class="text-center p-4 bg-muted/50 rounded-lg">
              <p class="text-2xl font-bold">{{ chat.completion_tokens || 0 }}</p>
              <p class="text-sm text-muted-foreground">Ответ токены</p>
            </div>
            <div class="text-center p-4 bg-muted/50 rounded-lg">
              <p class="text-2xl font-bold">{{ chat.total_tokens || 0 }}</p>
              <p class="text-sm text-muted-foreground">Всего токенов</p>
            </div>
          </div>
          
          <!-- Показываем сообщения, если они есть -->
          <div v-if="chat.messages && chat.messages.length > 0" class="space-y-4">
            <h4 class="text-lg font-semibold">Диалог с ИИ</h4>
            <div 
              v-for="(message, index) in chat.messages" 
              :key="index"
              class="p-4 rounded-lg"
              :class="{
                'bg-blue-50 border-l-4 border-blue-500': message.role === 'user',
                'bg-green-50 border-l-4 border-green-500': message.role === 'assistant',
                'bg-gray-50 border-l-4 border-gray-500': message.role === 'system'
              }"
            >
              <div class="flex items-center gap-2 mb-2">
                <span class="text-xs font-medium uppercase tracking-wide"
                      :class="{
                        'text-blue-600': message.role === 'user',
                        'text-green-600': message.role === 'assistant', 
                        'text-gray-600': message.role === 'system'
                      }">
                  {{ getRoleText(message.role) }}
                </span>
              </div>
              <div class="prose prose-sm max-w-none">
                <pre class="whitespace-pre-wrap text-sm">{{ message.content }}</pre>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import Button from '@/components/ui/button/Button.vue'
import Card from '@/components/ui/card/Card.vue'
import CardContent from '@/components/ui/card/CardContent.vue'
import CardHeader from '@/components/ui/card/CardHeader.vue'
import CardTitle from '@/components/ui/card/CardTitle.vue'
import CardDescription from '@/components/ui/card/CardDescription.vue'
import MarkdownRenderer from '@/components/MarkdownRenderer.vue'
import { FileIcon, RefreshCw } from 'lucide-vue-next'

interface User {
  id: number
  name: string
  email: string
}

interface Page {
  id: number
  title: string
  content: string
  files?: string[]
}

interface ChatMessage {
  role: 'user' | 'assistant' | 'system'
  content: string
}

interface Chat {
  id: number
  messages: ChatMessage[]
  prompt_tokens: number
  completion_tokens: number
  total_tokens: number
}

interface Actualization {
  id: number
  status: 'pending' | 'processing' | 'completed' | 'failed'
  created_at: string
  updated_at: string
  created_by: User
}

const props = defineProps<{
  actualization: Actualization
  page: Page
  chat?: Chat
}>()

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('ru-RU', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const getStatusText = (status: string) => {
  const statusMap = {
    pending: 'Ожидает обработки',
    processing: 'Обрабатывается',
    completed: 'Завершена',
    failed: 'Ошибка'
  }
  return statusMap[status] || status
}

const getRoleText = (role: string) => {
  const roleMap = {
    user: 'Пользователь',
    assistant: 'ИИ-Ассистент',
    system: 'Система'
  }
  return roleMap[role] || role
}

const getFileName = (url: string): string => {
  try {
    const urlObj = new URL(url)
    const pathParts = urlObj.pathname.split('/')
    return pathParts[pathParts.length - 1] || 'Файл'
  } catch {
    return 'Файл'
  }
}
</script>
