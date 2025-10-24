<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Coins, ArrowRight } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';

defineProps<{
    cost: {
        total: number;
        daily: number;
        monthly: number;
    };
}>();

const formatCost = (cost: number): string => {
    // Делим на 1000, так как cost хранится как RUB * 1000
    const costInRubles = cost / 1000;
    return new Intl.NumberFormat('ru-RU', { 
        minimumFractionDigits: 2,
        maximumFractionDigits: 2 
    }).format(costInRubles);
};
</script>

<template>
    <Card class="h-full">
        <CardHeader>
            <CardTitle class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Coins class="h-5 w-5 text-green-600" />
                    Расходы
                </div>
                <Link 
                    href="/expense-summary" 
                    class="flex items-center gap-1 text-sm text-green-600 hover:text-green-800 transition-colors"
                >
                    <span>Сводка</span>
                    <ArrowRight class="h-4 w-4" />
                </Link>
            </CardTitle>
        </CardHeader>
        <CardContent>
            <div class="space-y-4">
                <!-- Расходы за день -->
                <div class="flex items-center justify-between rounded-lg bg-blue-50 p-3 dark:bg-blue-950/20">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">За день</span>
                    <span class="text-lg font-bold text-blue-600">{{ formatCost(cost.daily) }} ₽</span>
                </div>
                <!-- Расходы за месяц -->
                <div class="flex items-center justify-between rounded-lg bg-purple-50 p-3 dark:bg-purple-950/20">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">За месяц</span>
                    <span class="text-lg font-bold text-purple-600">{{ formatCost(cost.monthly) }} ₽</span>
                </div>
                <!-- Общие расходы -->
                <div class="flex items-center justify-between rounded-lg bg-green-50 p-3 dark:bg-green-950/20">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Общие расходы</span>
                    <span class="text-xl font-bold text-green-600">{{ formatCost(cost.total) }} ₽</span>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
