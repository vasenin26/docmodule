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

    <div class="max-w-4xl space-y-6">
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
              v-if="task.content" 
              :content="task.content" 
            />
            <div v-else class="text-muted-foreground italic">
              Описание задачи еще не сгенерировано
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
            <div class="mt-2 space-y-2">
              <div class="p-2 bg-red-50 border border-red-200 rounded max-h-40 overflow-y-auto">
                <span class="text-xs text-red-600 font-medium">Предыдущая версия:</span>
                <div class="prose prose-sm mt-1">
                  <MarkdownRenderer :content="task.page.previous_version.content" />
                </div>
              </div>
              <div class="p-2 bg-green-50 border border-green-200 rounded max-h-40 overflow-y-auto">
                <span class="text-xs text-green-600 font-medium">Текущая версия:</span>
                <div class="prose prose-sm mt-1">
                  <MarkdownRenderer :content="task.page.content" />
                </div>
              </div>
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
  </AppLayout>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import MarkdownRenderer from '@/components/MarkdownRenderer.vue'
import TaskExportButton from '@/components/TaskExportButton.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Label } from '@/components/ui/label'

interface TaskData {
  id: number
  content: string
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
}

defineProps<{
  task: TaskData
}>()

const formatDate = (date: string) => {
  return new Date(date).toLocaleString('ru-RU', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>
