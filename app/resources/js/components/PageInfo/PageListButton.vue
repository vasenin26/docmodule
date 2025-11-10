<script setup lang="ts">

import Button from '../ui/button/Button.vue';
import { Link } from '@inertiajs/vue3';
import { Page } from '@/types';

defineProps<{
    page: Page
}>()

// small helper
const pagesIndexHref = () => {
  if (typeof route === 'function') {
    try { return route('pages.index'); } catch (e) { console.warn('PageListButton: Ziggy missing pages.index', e); }
  }
  return '/pages';
};

</script>

<template>

    <Button as-child variant="outline">
        <Link :href="page.project ? route('projects.show', page.project.id) : pagesIndexHref()">
            {{ page.project ? 'Назад к проекту' : 'Назад к списку' }}
        </Link>
    </Button>
</template>

<style scoped>

</style>
