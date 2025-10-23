<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

defineProps<{
    cost: number;
}>();

const formatNumber = (num: number): string => new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 0 }).format(num);
</script>

<template>
    <Card class="h-full">
        <CardHeader>
            <CardTitle class="flex items-center gap-2">
                Расходы
            </CardTitle>
        </CardHeader>
        <CardContent>
            <div class="flex items-center justify-between p-3">
                <span class="text-sm font-medium text-gray-700">Общие расходы</span>
                <span class="text-xl font-bold text-blue-600">{{ formatNumber(cost) }}</span>
            </div>
        </CardContent>
    </Card>
</template>
