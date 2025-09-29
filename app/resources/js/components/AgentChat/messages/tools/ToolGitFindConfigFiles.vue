<script setup lang="ts">
import type { LLMMessage } from '@/types';
import { ref, computed } from 'vue';
import ToolHeaderStatus from '@/components/AgentChat/chunks/ToolHeaderStatus.vue';

const props = defineProps<{
    message: LLMMessage;
    index: number;
}>();

function getSuccess(): boolean {
    const m: any = props.message.message;
    return (m?.success !== undefined ? m.success : m?.tool_success) === true;
}

function parseArgs(): { url?: string; patterns?: string[] } | undefined {
    const m: any = props.message.message;
    const raw: string | undefined = m?.args || m?.tool_args;
    if (!raw) return undefined;
    try {
        return JSON.parse(raw);
    } catch {
        return undefined;
    }
}

type ConfigFile = { file: string; type?: string; size?: number; description?: string };

function parseResult(): { success?: boolean; message?: string; data?: { config_files?: ConfigFile[]; total_found?: number; search_patterns?: string[] } } | undefined {
    const m: any = props.message.message;
    const raw: string | undefined = m?.result || m?.tool_result;
    if (!raw) return undefined;
    try {
        return JSON.parse(raw);
    } catch {
        return undefined;
    }
}

function files(): ConfigFile[] | undefined {
    return parseResult()?.data?.config_files;
}

const showAll = ref(false);
const visibleFiles = computed(() => {
    const list = files();
    if (!list) return undefined;
    return showAll.value ? list : list.slice(0, 3);
});

function containerClass(): string {
    return getSuccess() ? 'bg-orange-50 border border-orange-200' : 'bg-red-50 border border-red-200';
}
</script>

<template>
    <div class="rounded-lg p-3" :class="containerClass()">
        <ToolHeaderStatus :title="'конфигурационные файлы'" :subtitle="parseArgs()?.url" :isError="!getSuccess()" />

        <div class="text-sm space-y-3">
            <div v-if="parseResult()?.message" class="space-y-1">
                <div class="text-xs font-medium text-gray-600">Сообщение:</div>
                <div class="text-sm bg-white p-2 rounded border overflow-x-auto whitespace-pre-wrap">{{ parseResult()?.message }}</div>
            </div>

            <div v-if="visibleFiles && visibleFiles!.length > 0" class="space-y-2">
                <div class="text-xs font-medium text-gray-600">Найдено файлов ({{ parseResult()?.data?.total_found ?? files()!.length }}):</div>
                <ul class="space-y-1">
                    <li v-for="cf in visibleFiles" :key="cf.file" class="bg-white p-2 rounded border">
                        <div class="flex items-center justify-between gap-2">
                            <div class="font-mono text-xs truncate">{{ cf.file }}</div>
                            <div class="text-xs text-gray-600 whitespace-nowrap" v-if="cf.size !== undefined">{{ cf.size }} B</div>
                        </div>
                        <div class="text-xs text-gray-700" v-if="cf.type">{{ cf.type }}</div>
                        <div class="text-xs text-gray-500" v-if="cf.description">{{ cf.description }}</div>
                    </li>
                </ul>
                <div v-if="files() && files()!.length > 3" class="flex justify-center">
                    <button @click="showAll = !showAll" class="text-xs font-medium text-blue-600 hover:text-blue-800">
                        {{ showAll ? 'скрыть' : 'показать ещё' }}
                    </button>
                </div>
            </div>
            <div v-else class="text-gray-500 italic">Ничего не найдено</div>
        </div>
    </div>
</template>
