<template>
  <div class="space-y-6">
    <!-- Фильтры -->
    <ExpenseSummaryFiltersComponent
      :projects="projects"
      :task-types="taskTypes"
      :models="models"
      :loading="loading"
      @filters-changed="handleFiltersChanged"
    />

    <!-- График -->
    <Card>
      <CardHeader>
        <CardTitle>График расходов</CardTitle>
      </CardHeader>
      <CardContent>
        <ExpenseSummaryChart
          :data="chartData"
          :loading="loading"
        />
      </CardContent>
    </Card>

    <!-- Статистика -->
    <div v-if="chartData && chartData.length > 0" class="grid grid-cols-3 gap-4">
      <Card>
        <CardContent class="p-4">
          <div class="text-sm text-gray-600">Общая сумма</div>
          <div class="text-2xl font-bold">
            {{ totalCost.toFixed(2) }} ₽
          </div>
        </CardContent>
      </Card>
      <Card>
        <CardContent class="p-4">
          <div class="text-sm text-gray-600">Количество задач</div>
          <div class="text-2xl font-bold">{{ totalTasks }}</div>
        </CardContent>
      </Card>
      <Card>
        <CardContent class="p-4">
          <div class="text-sm text-gray-600">Средняя стоимость</div>
          <div class="text-2xl font-bold">
            {{ averageCost.toFixed(2) }} ₽
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import ExpenseSummaryFiltersComponent from './ExpenseSummaryFilters.vue';
import ExpenseSummaryChart from './ExpenseSummaryChart.vue';
import { createWebApi } from '@/service/api/Api';
import { ExpenseSummaryDataRequest } from '@/service/api/request/ExpenseSummary/ExpenseSummaryDataRequest';
import type { ExpenseSummaryData, ExpenseSummaryFilters, ExpenseTaskType, Project, RawApiItem } from '@/types';
import { mergeApiDataWithGenerated, type PeriodType } from '@/utils/expensePeriods';

interface Props {
  projects: Project[];
  taskTypes: ExpenseTaskType[];
  models?: string[];
}

defineProps<Props>();

const loading = ref(false);
const chartData = ref<ExpenseSummaryData[]>([]);

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

const currentFilters = ref<ExpenseSummaryFilters>({
  period: 'day',
  date_from: defaultRange.from,
  date_to: defaultRange.to,
  task_type: '',
  project_id: null
});

const api = createWebApi();

const totalCost = computed(() => {
  return chartData.value.reduce((sum, item) => sum + (item.totalCost || 0), 0);
});

const totalTasks = computed(() => {
  return chartData.value.reduce((sum, item) => sum + (item.taskCount || 0), 0);
});

const averageCost = computed(() => {
  return totalTasks.value > 0 ? totalCost.value / totalTasks.value : 0;
});

const handleFiltersChanged = async (filters: ExpenseSummaryFilters) => {
  currentFilters.value = filters;
  await loadData();
};

const loadData = async () => {
  loading.value = true;

  try {
    const request = new ExpenseSummaryDataRequest(currentFilters.value);
    const response = await request.call(api);

    if (response.success) {
      const apiData = (response.data ?? []) as RawApiItem[];
      const period = currentFilters.value.period as PeriodType;
      const merged = mergeApiDataWithGenerated(period, currentFilters.value.date_from, currentFilters.value.date_to, apiData);
      chartData.value = merged as unknown as ExpenseSummaryData[];
    }
  } catch (error) {
    console.error('Ошибка загрузки данных:', error);
    chartData.value = [];
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadData();
});
</script>
