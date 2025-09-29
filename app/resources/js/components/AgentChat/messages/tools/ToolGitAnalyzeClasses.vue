<script setup lang="ts">
import type { LLMMessage } from '@/types';
import { ref } from 'vue';
import ToolHeaderStatus from '@/components/AgentChat/chunks/ToolHeaderStatus.vue';

const props = defineProps<{
    message: LLMMessage;
    index: number;
}>();

function getSuccess(): boolean {
    const m: any = props.message.message;
    return (m?.success !== undefined ? m.success : m?.tool_success) === true;
}

function parseArgs(): { url?: string; namespace?: string } | undefined {
    const m: any = props.message.message;
    const raw: string | undefined = m?.args || m?.tool_args;
    if (!raw) return undefined;
    try {
        return JSON.parse(raw);
    } catch {
        return undefined;
    }
}

function parseResult(): any | undefined {
    const m: any = props.message.message;
    const raw: string | undefined = m?.result || m?.tool_result;
    if (!raw) return undefined;
    try {
        return JSON.parse(raw);
    } catch {
        return undefined;
    }
}

const showClasses = ref(false);
const showInterfaces = ref(false);
const showTraits = ref(false);
const showNamespaces = ref(false);

function containerClass(): string {
    return getSuccess() ? 'bg-orange-50 border border-orange-200' : 'bg-red-50 border border-red-200';
}
</script>

<template>
    <div class="rounded-lg p-3" :class="containerClass()">
        <ToolHeaderStatus :title="'анализ классов'" :subtitle="parseArgs()?.namespace ? (parseArgs()?.url ? (parseArgs()?.url + ' • ' + parseArgs()?.namespace) : parseArgs()?.namespace) : parseArgs()?.url" :status="getSuccess() ? 'success' : 'error'" />

        <div class="text-sm space-y-3" v-if="parseResult()?.data">
            <div class="grid grid-cols-2 gap-2">
                <div class="bg-white p-2 rounded border text-xs">PHP files: {{ parseResult()?.data?.total_php_files }}</div>
                <div class="bg-white p-2 rounded border text-xs">Classes: {{ parseResult()?.data?.classes_count }}</div>
                <div class="bg-white p-2 rounded border text-xs">Interfaces: {{ parseResult()?.data?.interfaces_count }}</div>
                <div class="bg-white p-2 rounded border text-xs">Traits: {{ parseResult()?.data?.traits_count }}</div>
                <div class="bg-white p-2 rounded border text-xs">Namespaces: {{ parseResult()?.data?.namespaces_count }}</div>
            </div>

            <div class="space-y-1">
                <div class="text-xs font-medium text-gray-600">Паттерны:</div>
                <ul v-if="Array.isArray(parseResult()?.data?.architectural_patterns) && parseResult()?.data?.architectural_patterns.length" class="list-disc pl-5 text-xs space-y-1">
                    <li v-for="p in parseResult()?.data?.architectural_patterns" :key="p">{{ p }}</li>
                </ul>
                <div v-else class="text-gray-500 italic text-xs">не найдены</div>
            </div>

            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <div class="text-xs font-medium text-gray-600">Классы (до 50):</div>
                    <button @click="showClasses = !showClasses" class="text-xs font-medium text-blue-600 hover:text-blue-800">{{ showClasses ? 'скрыть' : 'показать' }}</button>
                </div>
                <ul v-if="showClasses" class="list-disc pl-5 text-xs space-y-1">
                    <li v-for="c in parseResult()?.data?.classes" :key="c.full_name || c.name" class="bg-white p-2 rounded border">
                        <div class="font-mono truncate">{{ c.full_name || c.name }}</div>
                        <div v-if="c.extends" class="text-gray-600">extends: {{ c.extends }}</div>
                        <div v-if="Array.isArray(c.implements) && c.implements.length" class="text-gray-600">implements: {{ c.implements.join(', ') }}</div>
                    </li>
                </ul>
            </div>

            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <div class="text-xs font-medium text-gray-600">Интерфейсы (до 20):</div>
                    <button @click="showInterfaces = !showInterfaces" class="text-xs font-medium text-blue-600 hover:text-blue-800">{{ showInterfaces ? 'скрыть' : 'показать' }}</button>
                </div>
                <ul v-if="showInterfaces" class="list-disc pl-5 text-xs space-y-1">
                    <li v-for="i in parseResult()?.data?.interfaces" :key="i.full_name || i.name" class="bg-white p-2 rounded border">
                        <div class="font-mono truncate">{{ i.full_name || i.name }}</div>
                        <div v-if="Array.isArray(i.extends) && i.extends.length" class="text-gray-600">extends: {{ i.extends.join(', ') }}</div>
                    </li>
                </ul>
            </div>

            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <div class="text-xs font-medium text-gray-600">Трейты (до 20):</div>
                    <button @click="showTraits = !showTraits" class="text-xs font-medium text-blue-600 hover:text-blue-800">{{ showTraits ? 'скрыть' : 'показать' }}</button>
                </div>
                <ul v-if="showTraits" class="list-disc pl-5 text-xs space-y-1">
                    <li v-for="t in parseResult()?.data?.traits" :key="t.full_name || t.name" class="bg-white p-2 rounded border">
                        <div class="font-mono truncate">{{ t.full_name || t.name }}</div>
                    </li>
                </ul>
            </div>

            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <div class="text-xs font-medium text-gray-600">Namespaces:</div>
                    <button @click="showNamespaces = !showNamespaces" class="text-xs font-medium text-blue-600 hover:text-blue-800">{{ showNamespaces ? 'скрыть' : 'показать' }}</button>
                </div>
                <ul v-if="showNamespaces" class="list-disc pl-5 text-xs space-y-1">
                    <li v-for="ns in parseResult()?.data?.namespaces" :key="ns" class="bg-white p-2 rounded border">
                        <div class="font-mono truncate">{{ ns }}</div>
                    </li>
                </ul>
            </div>
        </div>

        <div v-else class="text-gray-500 italic text-sm">Нет данных для отображения</div>
    </div>
</template>
