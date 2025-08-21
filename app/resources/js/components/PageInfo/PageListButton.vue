<script setup lang="ts">

import Button from '../ui/button/Button.vue';
import { Link } from '@inertiajs/vue3';
import { Page } from '@/types';

defineProps<{
    page: Page
}>()

</script>

<template>

    <Button as-child variant="outline">
        <Link :href="page.project ? route('projects.show', page.project.id) : route('pages.index')">
            {{ page.project ? 'Назад к проекту' : 'Назад к списку' }}
        </Link>
    </Button>
</template>

<style scoped>

</style>
