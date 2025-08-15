<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Activity, MessageCircle, Zap } from 'lucide-vue-next'
import type { TokenStatistics } from '@/types'

defineProps<{
    statistics: TokenStatistics
}>()

const formatNumber = (num: number): string => {
    return new Intl.NumberFormat('ru-RU').format(num)
}
</script>

<template>
    <Card class="h-full">
        <CardHeader>
            <CardTitle class="flex items-center gap-2">
                <Activity class="w-5 h-5 text-blue-600" />
                Расходы на токены
            </CardTitle>
        </CardHeader>
        <CardContent>
            <div class="space-y-4">
                <!-- Переданные токены (prompt) -->
                <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-950/20 rounded-lg">
                    <div class="flex items-center gap-2">
                        <MessageCircle class="w-4 h-4 text-blue-600" />
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Переданные
                        </span>
                    </div>
                    <span class="text-lg font-bold text-blue-600">
                        {{ formatNumber(statistics.prompt_tokens) }}
                    </span>
                </div>

                <!-- Генерированные токены (completion) -->
                <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-950/20 rounded-lg">
                    <div class="flex items-center gap-2">
                        <Zap class="w-4 h-4 text-green-600" />
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Генерированные
                        </span>
                    </div>
                    <span class="text-lg font-bold text-green-600">
                        {{ formatNumber(statistics.completion_tokens) }}
                    </span>
                </div>

                <!-- Общие токены -->
                <div class="flex items-center justify-between p-3 bg-purple-50 dark:bg-purple-950/20 rounded-lg border-2 border-purple-200 dark:border-purple-800">
                    <div class="flex items-center gap-2">
                        <Activity class="w-4 h-4 text-purple-600" />
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Общие расходы
                        </span>
                    </div>
                    <span class="text-xl font-bold text-purple-600">
                        {{ formatNumber(statistics.total_tokens) }}
                    </span>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
