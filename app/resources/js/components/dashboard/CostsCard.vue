<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Coins } from 'lucide-vue-next';

defineProps<{
    cost: number;
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
            <CardTitle class="flex items-center gap-2">
                <Coins class="h-5 w-5 text-green-600" />
                Расходы
            </CardTitle>
        </CardHeader>
        <CardContent>
            <div class="flex items-center justify-between rounded-lg bg-green-50 p-3 dark:bg-green-950/20">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Общие расходы</span>
                <span class="text-xl font-bold text-green-600">{{ formatCost(cost) }} ₽</span>
            </div>
        </CardContent>
    </Card>
</template>
