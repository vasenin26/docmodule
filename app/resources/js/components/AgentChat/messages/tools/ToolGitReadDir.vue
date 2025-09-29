<script setup lang="ts">
import type { LLMMessage } from '@/types';
import { ref, computed } from 'vue';

const props = defineProps<{
    message: LLMMessage;
    index: number;
}>();

function getSuccess(): boolean {
    const m: any = props.message.message;
    return (m?.success !== undefined ? m.success : m?.tool_success) === true;
}

function getArgs(): { url?: string; path?: string } | undefined {
    const m: any = props.message.message;
    const raw: string | undefined = m?.args || m?.tool_args;
    if (!raw) return undefined;
    try {
        return JSON.parse(raw);
    } catch {
        return undefined;
    }
}

function getResultRaw(): string | undefined {
    const m: any = props.message.message;
    return m?.result || m?.tool_result;
}

function isErrorText(): boolean {
    // Treat as error only if success flag is false or known error phrases detected
    if (!getSuccess()) return true;
    const raw = getResultRaw();
    if (typeof raw !== 'string') return false;
    const t = raw.trim();
    if (/^directory\s+not\s+found[:]?/i.test(t)) return true;
    if (/no\s+such\s+file\s+or\s+directory/i.test(t)) return true;
    if (/^error[:]/i.test(t)) return true;
    return false;
}

function getEntries(): string[] | undefined {
    const raw = getResultRaw();
    if (typeof raw !== 'string') return undefined;
    if (isErrorText()) return [];
    const lines = raw.split(/\r?\n/).map(s => s.trim()).filter(Boolean);
    return lines;
}

const showAll = ref(false);

const visibleEntries = computed(() => {
    const entries = getEntries();
    if (!entries) return undefined;
    if (showAll.value) return entries;
    return entries.slice(0, 3);
});

function isDir(name: string): boolean {
    // naive heuristic: names without a dot likely directories
    return !/\.[^./\\\s]+$/.test(name);
}

function containerClass(): string {
    return getSuccess() && !isErrorText() ? 'bg-orange-50 border border-orange-200' : 'bg-red-50 border border-red-200';
}
</script>

<template>
    <div class="rounded-lg p-3" :class="containerClass()">
        <div class="mb-2 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <div class="flex h-5 w-5 items-center justify-center rounded-full" :class="getSuccess() && !isErrorText() ? 'bg-orange-500' : 'bg-red-500'">
                    <span class="text-xs font-medium text-white">T</span>
                </div>
                <span class="text-xs font-medium" :class="getSuccess() && !isErrorText() ? 'text-orange-700' : 'text-red-700'">список папок</span>
                <span class="px-2 py-1 rounded text-xs font-medium" :class="getSuccess() && !isErrorText() ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                    {{ getSuccess() && !isErrorText() ? 'Успешно' : 'Ошибка' }}
                </span>
            </div>
        </div>

        <div class="text-sm space-y-3">
            <div v-if="getArgs()?.path" class="space-y-1">
                <div class="text-xs font-medium text-gray-600">Путь:</div>
                <div class="text-sm bg-white p-2 rounded border overflow-x-auto">{{ getArgs()?.path }}</div>
            </div>

            <template v-if="!isErrorText()">
                <div v-if="visibleEntries && visibleEntries!.length > 0" class="space-y-2">
                    <div class="text-xs font-medium text-gray-600">Элементы ({{ getEntries()!.length }}):</div>
                    <ul class="space-y-1">
                        <li v-for="name in visibleEntries" :key="name" class="flex items-center gap-2 bg-white p-2 rounded border">
                            <svg v-if="isDir(name)" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-yellow-600" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2 6a2 2 0 012-2h3l2 2h7a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M4 2a2 2 0 00-2 2v12a2 2 0 002 2h8l4-4V4a2 2 0 00-2-2H4z" />
                            </svg>
                            <span class="truncate">{{ name }}</span>
                        </li>
                    </ul>
                    <div v-if="getEntries() && getEntries()!.length > 3" class="flex justify-center">
                        <button @click="showAll = !showAll" class="text-xs font-medium text-blue-600 hover:text-blue-800">
                            {{ showAll ? 'скрыть' : 'показать все' }}
                        </button>
                    </div>
                </div>
                <div v-else class="text-gray-500 italic">Каталог пуст или данные отсутствуют</div>
            </template>
            <template v-else>
                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Сообщение об ошибке:</div>
                    <div class="text-sm bg-white p-2 rounded border overflow-x-auto whitespace-pre-wrap">{{ getResultRaw() }}</div>
                </div>
            </template>
        </div>
    </div>
</template>
