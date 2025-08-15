<template>
  <AppLayout :title="project ? `Страницы проекта ${project.title}` : 'Страницы документации'">
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <Heading :title="project ? `Страницы проекта ${project.title}` : 'Страницы документации'" />
          <p v-if="project" class="text-sm text-muted-foreground mt-1">
            Проект #{{ project.id }}
          </p>
        </div>
        <div class="flex items-center gap-2">
          <Button v-if="project" as-child variant="outline">
            <Link :href="route('projects.show', project.id)">
              К проекту
            </Link>
          </Button>
          <Button as-child>
            <Link :href="project ? route('projects.pages.create', project.id) : route('pages.create')">
              Создать страницу
            </Link>
          </Button>
        </div>
      </div>
    </template>

    <PageList
      :pages="pages"
      :project="project"
      :filters="filters"
      :show-create-button="false"
    />
  </AppLayout>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import Button from '@/components/ui/button/Button.vue'
import PageList from '@/components/PageList.vue'
import type { Project, PagesData } from '@/types'

defineProps<{
  pages: PagesData
  project?: Project
  filters: {
    search?: string
    parent_id?: number
  }
}>()
</script>
