<script setup lang="ts">
import type { LLMMessage } from '@/types';
import ToolHeaderStatus from '@/components/AgentChat/chunks/ToolHeaderStatus.vue';

const props = defineProps<{
    message: LLMMessage;
    index: number;
}>();

function getSuccess(): boolean {
    const m: any = props.message.message;
    return (m?.success !== undefined ? m.success : m?.tool_success) === true;
}

function parseArgs(): { url?: string; needle?: string } | undefined {
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

function parseResult(): any | undefined {
    const raw = getResultRaw();
    if (!raw) return undefined;
    try {
        const parsed = JSON.parse(raw);
        if (parsed && parsed.payload) return parsed.payload;
        return parsed;
    } catch {
        return undefined;
    }
}

function isErrorText(): boolean {
    if (!getSuccess()) return true;
    const raw = getResultRaw();
    const pr = parseResult();
    if (pr && typeof pr.message === 'string' && !getSuccess()) return true;
    if (typeof raw !== 'string') return false;
    const t = raw.trim();
    if (/^error/i.test(t)) return true;
    if (/no\s+files\s+found/i.test(t)) return true;
    return false;
}

function getPaths(): string[] | undefined {
    const pr = parseResult();
    if (pr && Array.isArray(pr.paths)) return pr.paths as string[];
    if (pr && Array.isArray(pr.entries)) return pr.entries as string[];
    const raw = getResultRaw();
    if (typeof raw !== 'string') return undefined;
    if (isErrorText()) return [];
    const lines = raw.split(/\r?\n/).map(s => s.trim()).filter(Boolean);
    return lines;
}

function containerClass(): string {
    return getSuccess() && !isErrorText() ? 'bg-orange-50 border border-orange-200' : 'bg-red-50 border border-red-200';
}

function subtitle(): string | undefined {
    const a: any = parseArgs();
    if (!a) return undefined;
    return a.url ? (a.needle ? `${a.url} • ${a.needle}` : a.url) : a.needle;
}
</script>

<template>
    <div class="rounded-lg p-3" :class="containerClass()">
        <ToolHeaderStatus :title="'поиск файла по имени'" :subtitle="subtitle()" :isError="isErrorText()" />

        <div class="text-sm space-y-3">
            <template v-if="!isErrorText()">
                <div v-if="getPaths() && getPaths()!.length > 0" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Найденные пути ({{ getPaths()!.length }}):</div>
                    <ul class="space-y-1">
                        <li v-for="p in getPaths()" :key="p" class="bg-white p-2 rounded border font-mono text-xs truncate">{{ p }}</li>
                    </ul>
                </div>
                <div v-else class="text-gray-500 italic">Ничего не найдено</div>
            </template>
            <template v-else>
                <div class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Сообщение:</div>
                    <div class="text-sm bg-white p-2 rounded border overflow-x-auto whitespace-pre-wrap">{{ getResultRaw() }}</div>
                </div>
            </template>
        </div>
    </div>
</template>
