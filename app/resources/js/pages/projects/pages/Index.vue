<template>
  <AppLayout :title="`Страницы проекта ${project.title}`">
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <Heading :title="project ? `Страницы проекта ${project.title}` : 'Страницы документации'" />
          <p v-if="project" class="mt-1 text-sm text-muted-foreground">Проект #{{ project.id }}</p>
        </div>
        <div class="flex items-center gap-2">
          <Button v-if="project" as-child variant="outline">
            <Link :href="route('projects.show', project.id)"> К проекту </Link>
          </Button>
          <Button as-child>
            <Link :href="project ? route('projects.pages.create', project.id) : route('pages.create')"> Создать страницу </Link>
          </Button>
        </div>
      </div>
    </template>

    <PageList :pages="pages" :project="project" :filters="filters" :show-create-button="false" />
  </AppLayout>
</template>

<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import PageList from '@/components/PageList.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { PagesData, Project } from '@/types';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    project: Project;
    pages: PagesData;
    filters?: {
        search?: string;
        parent_id?: number;
    };
}>();

// Используем фильтры из пропсов или пустые по умолчанию
const filters = {
    search: props.filters?.search || '',
    parent_id: props.filters?.parent_id || undefined,
};
</script>
