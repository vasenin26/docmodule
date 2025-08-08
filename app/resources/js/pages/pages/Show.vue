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
            <Link :href="route('pages.index')">
              Назад к списку
            </Link>
          </Button>
        </div>
      </div>
    </template>

    <div class="max-w-4xl space-y-6">
      <!-- Родительская страница -->
      <div v-if="page.parent" class="p-4 bg-muted/50 rounded-lg">
        <p class="text-sm text-muted-foreground mb-2">Родительская страница:</p>
        <Link :href="route('pages.show', page.parent.id)" class="font-medium hover:underline">
          {{ page.parent.title }}
        </Link>
      </div>

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
import CreateChildPage from '@/components/CreateChildPage.vue'

interface Creator {
  name: string
}

interface Page {
  id: number
  title: string
  content: string
  created_at: string
  updated_at: string
  creator: Creator
  parent?: Page
  children: Page[]
  base_id?: number
  previous_version_id?: number
  current: boolean
}

const props = defineProps<{
  page: Page
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
</script>
