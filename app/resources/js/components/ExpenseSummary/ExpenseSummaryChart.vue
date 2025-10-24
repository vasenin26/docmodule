<template>
  <div class="h-96 w-full">
    <Bar
      v-if="chartData"
      :data="chartData"
      :options="chartOptions"
    />
    <div v-else class="flex h-full items-center justify-center text-gray-500">
      Нет данных для отображения
    </div>
  </div>
</template>

<script setup lang="ts">
import { Bar } from 'vue-chartjs';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale
} from 'chart.js';
import type { ExpenseSummaryData } from '@/types/expenseSummary';
import { computed } from 'vue';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

interface Props {
  data: ExpenseSummaryData[];
  loading?: boolean;
}

const props = defineProps<Props>();

const chartData = computed(() => {
  if (!props.data || props.data.length === 0) {
    return null;
  }

  return {
    labels: props.data.map(item => item.period_label),
    datasets: [
      {
        label: 'Расходы (₽)',
        data: props.data.map(item => item.total_cost),
        backgroundColor: 'rgba(59, 130, 246, 0.5)',
        borderColor: 'rgba(59, 130, 246, 1)',
        borderWidth: 1,
      }
    ]
  };
});

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  indexAxis: 'y' as const, // Горизонтальный график
  plugins: {
    title: {
      display: true,
      text: 'Сводка расходов'
    },
    legend: {
      display: false
    },
    tooltip: {
      callbacks: {
        label: function(context: any) {
          return `Расходы: ${context.parsed.x.toFixed(2)} ₽`;
        }
      }
    }
  },
  scales: {
    x: {
      beginAtZero: true,
      ticks: {
        callback: function(value: any) {
          return `${value.toFixed(2)} ₽`;
        }
      }
    }
  }
};
</script>
