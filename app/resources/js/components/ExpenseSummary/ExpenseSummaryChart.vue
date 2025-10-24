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
import type { ExpenseSummaryData } from '@/types';
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
        labels: props.data.map(item => item.periodLabel),
    datasets: [
      {
        label: 'Расходы (₽)',
        data: props.data.map(item => item.totalCost),
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
          return `Расходы: ${context.parsed.y.toFixed(2)} ₽`;
        }
      }
    }
  },
  scales: {
    x: {
      title: {
        display: true,
        text: 'Дата'
      }
    },
    y: {
      beginAtZero: true,
      title: {
        display: true,
        text: 'Стоимость (₽)'
      },
      ticks: {
        callback: function(value: any) {
          return `${value.toFixed(2)} ₽`;
        }
      }
    }
  }
};
</script>
