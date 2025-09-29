<script setup lang="ts">
const props = defineProps<{
    title: string;
    subtitle?: string;
    status?: 'success' | 'error' | 'processing' | 'wait';
    isError?: boolean; // backward compatibility
    theme?: 'orange' | 'indigo' | 'red' | 'blue' | 'green';
    badgeLetter?: string;
}>();

function effectiveStatus(): 'success' | 'error' | 'processing' | 'wait' {
    if (props.status) return props.status;
    return props.isError ? 'error' : 'success';
}

function themeCircleClass(): string {
    const theme = props.theme || 'orange';
    const status = effectiveStatus();
    // If error, prefer red unless theme explicitly indigo (service styling keeps indigo)
    if (status === 'error' && theme !== 'indigo') return 'bg-red-500';
    switch (theme) {
        case 'indigo': return 'bg-indigo-500';
        case 'red': return 'bg-red-500';
        case 'blue': return 'bg-blue-500';
        case 'green': return 'bg-green-500';
        default: return 'bg-orange-500';
    }
}

function titleColorClass(): string {
    const status = effectiveStatus();
    const theme = props.theme || 'orange';
    if (status === 'error') return 'text-red-700';
    switch (theme) {
        case 'indigo': return 'text-indigo-700';
        case 'red': return 'text-red-700';
        case 'blue': return 'text-blue-700';
        case 'green': return 'text-green-700';
        default: return 'text-orange-700';
    }
}
</script>

<template>
    <div class="mb-2 flex items-center justify-between">
        <div class="flex items-center space-x-2 min-w-0">
            <div class="flex h-5 w-5 items-center justify-center rounded-full" :class="themeCircleClass()">
                <span class="text-xs font-medium text-white">{{ badgeLetter || 'T' }}</span>
            </div>
            <span class="text-xs font-medium" :class="titleColorClass()">{{ title }}</span>
            <span v-if="subtitle" class="text-xs text-gray-600 truncate max-w-[28ch]">{{ subtitle }}</span>
            <span v-if="effectiveStatus() === 'error'" class="inline-flex items-center" title="Ошибка">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-600" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-5a1 1 0 112 0 1 1 0 01-2 0zm1-8a1 1 0 00-1 1v5a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </span>
            <span v-else-if="effectiveStatus() === 'success'" class="inline-flex items-center" title="Успех">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.707a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            </span>
            <span v-else class="inline-flex items-center" title="Выполняется">
                <svg class="h-4 w-4 text-indigo-500 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
            </span>
        </div>
    </div>
</template>
