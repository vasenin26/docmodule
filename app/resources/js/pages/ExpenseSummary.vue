<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto p-6">
      <div class="mb-6">
        <h1 class="text-3xl font-bold">Сводка расходов</h1>
        <p class="text-gray-600 mt-2">
          Анализ расходов по задачам с возможностью фильтрации
        </p>
      </div>
      
      <ExpenseSummary
        :projects="projects"
        :task-types="taskTypes"
        :models="models"
      />
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import ExpenseSummary from '@/components/ExpenseSummary/ExpenseSummary.vue';
import type { BreadcrumbItem, Project, ExpenseTaskType } from '@/types';

interface Props {
  projects: Project[];
  taskTypes: ExpenseTaskType[];
  models: string[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Dashboard',
    href: '/dashboard',
  },
  {
    title: 'Сводка расходов',
    href: '/expense-summary',
  },
];
</script>
