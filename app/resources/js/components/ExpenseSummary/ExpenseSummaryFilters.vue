<template>
  <Card>
    <CardHeader>
      <CardTitle>Фильтры</CardTitle>
    </CardHeader>
    <CardContent>
      <form @submit.prevent="applyFilters" class="space-y-4">
        <!-- Период группировки -->
        <div>
          <Label for="period">Период группировки</Label>
          <select
            id="period"
            v-model="filters.period"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
          >
            <option value="day">День</option>
            <option value="week">Неделя</option>
            <option value="month">Месяц</option>
          </select>
        </div>

        <!-- Диапазон дат -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <Label for="date_from">От</Label>
            <Input
              id="date_from"
              v-model="filters.date_from"
              type="date"
            />
          </div>
          <div>
            <Label for="date_to">До</Label>
            <Input
              id="date_to"
              v-model="filters.date_to"
              type="date"
            />
          </div>
        </div>

        <!-- Тип задач -->
        <div>
          <Label for="task_type">Тип задач</Label>
          <select
            id="task_type"
            v-model="filters.task_type"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
          >
            <option value="">Все типы</option>
            <option
              v-for="type in taskTypes"
              :key="type.value"
              :value="type.value"
            >
              {{ type.label }}
            </option>
          </select>
        </div>

        <!-- Проект -->
        <div>
          <Label for="project_id">Проект</Label>
          <select
            id="project_id"
            v-model="filters.project_id"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
          >
            <option :value="null">Все проекты</option>
            <option
              v-for="project in projects"
              :key="project.id"
              :value="project.id"
            >
              {{ project.title }}
            </option>
          </select>
        </div>

        <!-- Кнопки -->
        <div class="flex gap-2">
          <Button type="submit" :disabled="loading">
            {{ loading ? 'Загрузка...' : 'Применить' }}
          </Button>
          <Button type="button" variant="outline" @click="resetFilters">
            Сбросить
          </Button>
        </div>
      </form>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ExpenseSummaryFilters, ExpenseTaskType, Project } from '@/types';

interface Props {
  projects: Project[];
  taskTypes: ExpenseTaskType[];
  loading?: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  filtersChanged: [filters: ExpenseSummaryFilters];
}>();

// Устанавливаем диапазон по умолчанию на месяц
const getDefaultDateRange = () => {
  const today = new Date();
  const monthAgo = new Date();
  monthAgo.setMonth(today.getMonth() - 1);
  
  return {
    from: monthAgo.toISOString().split('T')[0],
    to: today.toISOString().split('T')[0]
  };
};

const defaultRange = getDefaultDateRange();

const filters = reactive<ExpenseSummaryFilters>({
  period: 'day',
  date_from: defaultRange.from,
  date_to: defaultRange.to,
  task_type: '',
  project_id: null
});

const applyFilters = () => {
  emit('filtersChanged', { ...filters });
};

const resetFilters = () => {
  const newRange = getDefaultDateRange();
  Object.assign(filters, {
    period: 'day',
    date_from: newRange.from,
    date_to: newRange.to,
    task_type: '',
    project_id: null
  });
  emit('filtersChanged', { ...filters });
};
</script>
