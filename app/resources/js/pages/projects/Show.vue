<template>
  <AppLayout :title="project.title">
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <Heading>{{ project.title }}</Heading>
          <p class="text-muted-foreground mt-1">
            Проект #{{ project.id }} • Создан {{ formatDate(project.created_at) }}
          </p>
        </div>
        <div class="flex items-center gap-2">
          <Button 
            variant="outline" 
            size="sm"
            @click="$inertia.visit(route('projects.pages.create', project.id))"
          >
            <Icon name="plus" class="mr-2 h-4 w-4" />
            Новая страница
          </Button>
          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <Button variant="outline" size="sm">
                <Icon name="more-horizontal" class="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem as-child>
                <Link 
                  :href="route('projects.edit', project.id)"
                  class="flex items-center"
                >
                  <Icon name="edit" class="mr-2 h-4 w-4" />
                  Редактировать
                </Link>
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem 
                class="text-destructive flex items-center"
                @click="deleteProject"
              >
                <Icon name="trash-2" class="mr-2 h-4 w-4" />
                Удалить проект
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader>
            <CardTitle class="text-sm font-medium">Всего страниц</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">
              {{ project.pages ? project.pages.length : 0 }}
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle class="text-sm font-medium">Репозитории</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">
              {{ project.repositories ? project.repositories.length : 0 }}
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle class="text-sm font-medium">Владелец</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-sm">
              {{ project.owner.name }}
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle class="text-sm font-medium">Дата создания</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-sm">
              {{ formatDate(project.created_at) }}
            </div>
          </CardContent>
        </Card>
      </div>

      <div>
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold">Страницы</h3>
          <Button 
            size="sm"
            @click="$inertia.visit(route('projects.pages.create', project.id))"
          >
            <Icon name="plus" class="mr-2 h-4 w-4" />
            Добавить страницу
          </Button>
        </div>

        <div v-if="!project.pages || project.pages.length === 0">
          <Card>
            <CardContent class="text-center py-12">
              <div class="mx-auto mb-4 h-12 w-12 text-muted-foreground">
                <Icon name="file-text" class="h-full w-full" />
              </div>
              <h4 class="mb-2 text-lg font-semibold">Нет страниц</h4>
              <p class="mb-4 text-muted-foreground">
                Создайте первую страницу для этого проекта
              </p>
              <Button @click="$inertia.visit(route('projects.pages.create', project.id))">
                <Icon name="plus" class="mr-2 h-4 w-4" />
                Создать страницу
              </Button>
            </CardContent>
          </Card>
        </div>

        <PageList
          v-else
          :pages="pagesData"
          :project="project"
          :filters="{ search: '', parent_id: undefined }"
          :show-create-button="false"
        />
      </div>

      <!-- Репозитории -->
      <div>
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold">Репозитории</h3>
          <Button 
            size="sm"
            @click="$inertia.visit(route('projects.edit', project.id))"
          >
            <Icon name="plus" class="mr-2 h-4 w-4" />
            Управление репозиториями
          </Button>
        </div>

        <div v-if="!project.repositories || project.repositories.length === 0">
          <Card>
            <CardContent class="text-center py-12">
              <div class="mx-auto mb-4 h-12 w-12 text-muted-foreground">
                <Icon name="git-branch" class="h-full w-full" />
              </div>
              <h4 class="mb-2 text-lg font-semibold">Нет репозиториев</h4>
              <p class="mb-4 text-muted-foreground">
                Добавьте репозитории для этого проекта
              </p>
              <Button @click="$inertia.visit(route('projects.edit', project.id))">
                <Icon name="plus" class="mr-2 h-4 w-4" />
                Добавить репозиторий
              </Button>
            </CardContent>
          </Card>
        </div>

        <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          <Card 
            v-for="repository in project.repositories" 
            :key="repository.id"
            class="hover:shadow-md transition-shadow"
          >
            <CardHeader>
              <CardTitle class="text-base flex items-center">
                <Icon name="git-branch" class="mr-2 h-4 w-4 text-muted-foreground" />
                Репозиторий
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-2">
                <a 
                  :href="repository.url" 
                  target="_blank" 
                  rel="noopener noreferrer"
                  class="text-primary hover:underline text-sm break-all flex items-center"
                >
                  {{ repository.url }}
                  <Icon name="external-link" class="ml-1 h-3 w-3 flex-shrink-0" />
                </a>
                <div class="text-xs text-muted-foreground">
                  Добавлен {{ formatDate(repository.created_at) }}
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import Heading from '@/components/Heading.vue'
import Icon from '@/components/Icon.vue'
import PageList from '@/components/PageList.vue'
import type { Project, PagesData } from '@/types'

const props = defineProps<{
  project: Project
}>()

// Преобразуем страницы проекта в формат PagesData для компонента PageList
const pagesData = computed<PagesData>(() => ({
  data: props.project.pages || [],
  links: [] // В контексте проекта пагинация не используется
}))

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('ru-RU', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

const deleteProject = () => {
  if (confirm(`Вы уверены, что хотите удалить проект? Все страницы проекта также будут удалены.`)) {
    router.delete(route('projects.destroy', props.project.id))
  }
}
</script>
