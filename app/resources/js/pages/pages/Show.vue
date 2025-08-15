<template>
  <AppLayout :title="page.title">
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <Heading :title="page.title" />
          <p class="text-sm text-muted-foreground mt-1">
            Создано {{ formatDate(page.created_at) }} пользователем {{ page.creator?.name }}
          </p>
        </div>
        <div class="flex items-center gap-2">
          <Button 
            v-if="canCreateTask" 
            @click="createTask" 
            variant="default"
          >
            Создать задачу
          </Button>
          <Button as-child>
            <Link :href="route('pages.edit', page.id)">
              Редактировать
            </Link>
          </Button>
          <Button as-child variant="outline">
            <Link :href="route('pages.versions', page.id)">
              Версии
            </Link>
          </Button>
          <Button as-child variant="outline">
            <Link :href="page.project ? route('projects.show', page.project.id) : route('pages.index')">
              {{ page.project ? 'Назад к проекту' : 'Назад к списку' }}
            </Link>
          </Button>
        </div>
      </div>
    </template>

    <div class="max-w-4xl space-y-6">
      <!-- Информация о черновике -->
      <div v-if="page.currentDraft" class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-yellow-800">
              <strong>Активный черновик:</strong> 
              Создан {{ formatDate(page.currentDraft.created_at) }}
            </p>
            <p class="text-xs text-yellow-600 mt-1">
              Последнее обновление: {{ formatDate(page.currentDraft.updated_at) }}
            </p>
          </div>
          <div class="flex gap-2">
            <Button as-child variant="outline" size="sm">
              <Link :href="route('pages.edit', page.id)">
                Продолжить редактирование
              </Link>
            </Button>
            <Button @click="approveDraft" variant="default" size="sm">
              Утвердить
            </Button>
          </div>
        </div>
      </div>

      <!-- Родительская страница -->
      <div v-if="page.parent" class="p-4 bg-muted/50 rounded-lg">
        <p class="text-sm text-muted-foreground mb-2">Родительская страница:</p>
        <Link :href="route('pages.show', page.parent.id)" class="font-medium hover:underline">
          {{ page.parent.title }}
        </Link>
      </div>

      <!-- Информация о проекте -->
      <Card v-if="page.project">
        <CardHeader>
          <CardTitle class="text-lg">Проект</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex items-center justify-between">
            <div>
              <p class="font-medium">{{ page.project.title }}</p>
              <p class="text-sm text-muted-foreground">ID: {{ page.project.id }}</p>
            </div>
            <Button as-child variant="outline" size="sm">
              <Link :href="route('projects.show', page.project.id)">
                Перейти к проекту
              </Link>
            </Button>
          </div>
        </CardContent>
      </Card>

      <!-- Содержимое страницы -->
      <Card>
        <CardContent class="p-6">
          <div v-if="page.content">
            <MarkdownRenderer :content="page.content" />
          </div>
          <div v-else class="text-center text-muted-foreground py-8">
            Содержимое страницы отсутствует
          </div>
        </CardContent>
      </Card>

      <!-- Прикрепленные файлы -->
      <Card v-if="page.files && page.files.length > 0">
        <CardHeader>
          <CardTitle>Прикрепленные файлы</CardTitle>
          <CardDescription>
            Файлы, связанные с данной страницей документации
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-2">
            <div
              v-for="(file, index) in page.files"
              :key="index"
              class="flex items-center gap-3 p-3 border rounded-lg hover:bg-muted/50"
            >
              <FileIcon class="w-5 h-5 text-muted-foreground" />
              <div class="flex-1">
                <p class="font-mono text-sm break-all">{{ getFileName(file) }}</p>
                <p class="text-xs text-muted-foreground break-all">{{ file }}</p>
              </div>
              <Button 
                as-child 
                variant="outline" 
                size="sm"
                v-if="isValidRepositoryUrl(file)"
              >
                <a 
                  :href="file" 
                  target="_blank" 
                  rel="noopener noreferrer"
                >
                  Открыть файл
                </a>
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Описания задач -->
      <Card v-if="page.diff_descriptions && page.diff_descriptions.length > 0">
        <CardHeader>
          <CardTitle>Связанные задачи</CardTitle>
          <CardDescription>
            Задачи, созданные на основе изменений в данной версии страницы
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div
              v-for="taskDescription in page.diff_descriptions"
              :key="taskDescription.id"
              class="p-4 border rounded-lg"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <p class="text-sm text-muted-foreground mb-2">
                    Создано {{ formatDate(taskDescription.created_at) }}
                    <span v-if="taskDescription.creator">
                      пользователем {{ taskDescription.creator.name }}
                    </span>
                  </p>
                  <div class="prose prose-sm max-w-none">
                    <MarkdownRenderer :content="taskDescription.content" />
                  </div>
                </div>
                <Button as-child variant="outline" size="sm" class="ml-4">
                  <Link :href="route('tasks.show', taskDescription.id)">
                    Перейти к задаче
                  </Link>
                </Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Дочерние страницы -->
      <Card>
        <CardHeader>
          <CardTitle>Дочерние страницы</CardTitle>
          <CardDescription>
            Страницы, связанные с текущей
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="page.children && page.children.length > 0" class="space-y-2 mb-4">
            <div
              v-for="child in page.children"
              :key="child.id"
              class="flex items-center justify-between p-3 border rounded-lg hover:bg-muted/50"
            >
              <div>
                <Link :href="route('pages.show', child.id)" class="font-medium hover:underline">
                  {{ child.title }}
                </Link>
                <p class="text-sm text-muted-foreground">
                  Создано {{ formatDate(child.created_at) }}
                </p>
              </div>
              <div class="flex items-center gap-2">
                <Button as-child size="sm" variant="outline">
                  <Link :href="route('pages.show', child.id)">
                    Просмотр
                  </Link>
                </Button>
                <Button as-child size="sm" variant="outline">
                  <Link :href="route('pages.edit', child.id)">
                    Редактировать
                  </Link>
                </Button>
              </div>
            </div>
          </div>
          
          <!-- Форма создания дочерней страницы -->
          <CreateChildPage :parent-id="page.id" />
        </CardContent>
      </Card>

      <!-- Информация о версиях -->
      <Card>
        <CardHeader>
          <CardTitle>Информация о версиях</CardTitle>
          <CardDescription>
            Детали версионирования страницы
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
              <span class="font-medium">Base ID:</span>
              <span class="text-muted-foreground ml-2">{{ page.base_id || 'Первая версия' }}</span>
            </div>
            <div>
              <span class="font-medium">Previous Version ID:</span>
              <span class="text-muted-foreground ml-2">{{ page.previous_version_id || 'Первая версия' }}</span>
            </div>
            <div>
              <span class="font-medium">Текущая версия:</span>
              <span class="text-muted-foreground ml-2">{{ page.current ? 'Да' : 'Нет' }}</span>
            </div>
            <div>
              <span class="font-medium">Статус:</span>
              <span class="text-muted-foreground ml-2">
                <span v-if="page.current" class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">
                  Текущая
                </span>
                <span v-else class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded">
                  Архивная
                </span>
              </span>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Информация о странице -->
      <Card>
        <CardHeader>
          <CardTitle>Информация о странице</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
              <span class="font-medium">ID:</span>
              <span class="text-muted-foreground ml-2">{{ page.id }}</span>
            </div>
            <div>
              <span class="font-medium">Создатель:</span>
              <span class="text-muted-foreground ml-2">{{ page.creator?.name }}</span>
            </div>
            <div>
              <span class="font-medium">Дата создания:</span>
              <span class="text-muted-foreground ml-2">{{ formatDate(page.created_at) }}</span>
            </div>
            <div>
              <span class="font-medium">Последнее обновление:</span>
              <span class="text-muted-foreground ml-2">{{ formatDate(page.updated_at) }}</span>
            </div>
            <div v-if="page.children && page.children.length > 0">
              <span class="font-medium">Дочерних страниц:</span>
              <span class="text-muted-foreground ml-2">{{ page.children.length }}</span>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import Button from '@/components/ui/button/Button.vue'
import Card from '@/components/ui/card/Card.vue'
import CardContent from '@/components/ui/card/CardContent.vue'
import CardHeader from '@/components/ui/card/CardHeader.vue'
import CardTitle from '@/components/ui/card/CardTitle.vue'
import CardDescription from '@/components/ui/card/CardDescription.vue'
import MarkdownRenderer from '@/components/MarkdownRenderer.vue'
import CreateChildPage from '@/components/CreateChildPage.vue'
import { FileIcon } from 'lucide-vue-next'



interface Creator {
  name: string
}

interface TaskDescription {
  id: number
  content: string
  created_at: string
  creator?: Creator
}

interface Draft {
  id: number
  title: string
  content: string
  created_at: string
  updated_at: string
}

interface Page {
  id: number
  title: string
  content: string
  files?: string[]
  created_at: string
  updated_at: string
  creator: Creator
  parent?: Page
  children: Page[]
  base_id?: number
  previous_version_id?: number
  current: boolean
  currentDraft?: Draft
  diff_descriptions?: TaskDescription[]
}

const props = defineProps<{
  page: Page
}>()

const canCreateTask = computed(() => {
  return props.page.current && 
         (!props.page.diff_descriptions || props.page.diff_descriptions.length === 0) &&
         !props.page.currentDraft &&
         props.page.previous_version_id !== null
})

const createTask = () => {
  router.post(route('pages.create-task', props.page.id))
}

const approveDraft = () => {
  if (props.page.currentDraft) {
    router.post(route('pages.draft.approve', props.page.currentDraft.id))
  }
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('ru-RU', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

// Методы для работы с файлами
const isValidRepositoryUrl = (url: string): boolean => {
  try {
    new URL(url)
    return true
  } catch {
    return false
  }
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
