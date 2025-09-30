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

function parseArgs(): { url?: string } | undefined {
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
        const parsed = JSON.parse(raw);
        // Новый формат ToolResult: { message, payload }
        if (parsed && parsed.payload) return parsed.payload;
        return parsed;
    } catch {
        return undefined;
    }
}

function containerClass(): string {
    return getSuccess() ? 'bg-orange-50 border border-orange-200' : 'bg-red-50 border border-red-200';
}
</script>

<template>
    <div class="rounded-lg p-3" :class="containerClass()">
        <ToolHeaderStatus :title="'зависимости проекта'" :subtitle="parseArgs()?.url" :isError="!getSuccess()" />

        <div class="text-sm space-y-4" v-if="parseResult()">
            <div class="space-y-1">
                <div class="text-xs font-medium text-gray-600">PHP (composer.json):</div>
                <div class="bg-white p-2 rounded border">
                    <div class="text-xs text-gray-600">require:</div>
                    <ul class="list-disc pl-5 text-xs space-y-1">
                        <li v-for="(ver, pkg) in parseResult()?.php_dependencies?.require" :key="'php-req-'+pkg" class="font-mono truncate">{{ pkg }}: {{ ver }}</li>
                    </ul>
                    <div class="mt-2 text-xs text-gray-600">require-dev:</div>
                    <ul class="list-disc pl-5 text-xs space-y-1">
                        <li v-for="(ver, pkg) in parseResult()?.php_dependencies?.require_dev || parseResult()?.php_dependencies?.['require-dev']" :key="'php-dev-'+pkg" class="font-mono truncate">{{ pkg }}: {{ ver }}</li>
                    </ul>
                </div>
            </div>

            <div class="space-y-1">
                <div class="text-xs font-medium text-gray-600">JavaScript (package.json):</div>
                <div class="bg-white p-2 rounded border">
                    <div class="text-xs text-gray-600">dependencies:</div>
                    <ul class="list-disc pl-5 text-xs space-y-1">
                        <li v-for="(ver, pkg) in parseResult()?.js_dependencies?.dependencies" :key="'js-dep-'+pkg" class="font-mono truncate">{{ pkg }}: {{ ver }}</li>
                    </ul>
                    <div class="mt-2 text-xs text-gray-600">devDependencies:</div>
                    <ul class="list-disc pl-5 text-xs space-y-1">
                        <li v-for="(ver, pkg) in parseResult()?.js_dependencies?.devDependencies" :key="'js-dev-'+pkg" class="font-mono truncate">{{ pkg }}: {{ ver }}</li>
                    </ul>
                    <div v-if="parseResult()?.js_dependencies?.peerDependencies && Object.keys(parseResult()?.js_dependencies?.peerDependencies).length" class="mt-2 text-xs text-gray-600">peerDependencies:</div>
                    <ul v-if="parseResult()?.js_dependencies?.peerDependencies && Object.keys(parseResult()?.js_dependencies?.peerDependencies).length" class="list-disc pl-5 text-xs space-y-1">
                        <li v-for="(ver, pkg) in parseResult()?.js_dependencies?.peerDependencies" :key="'js-peer-'+pkg" class="font-mono truncate">{{ pkg }}: {{ ver }}</li>
                    </ul>
                </div>
            </div>

            <div class="space-y-1">
                <div class="text-xs font-medium text-gray-600">Python:</div>
                <div class="bg-white p-2 rounded border">
                    <ul class="list-disc pl-5 text-xs space-y-1">
                        <li v-for="line in parseResult()?.python_dependencies" :key="line" class="font-mono truncate">{{ line }}</li>
                    </ul>
                </div>
            </div>

            <div class="space-y-1">
                <div class="text-xs font-medium text-gray-600">Конфликты версий:</div>
                <ul v-if="Array.isArray(parseResult()?.conflicts) && parseResult()?.conflicts.length" class="list-disc pl-5 text-xs space-y-1">
                    <li v-for="c in parseResult()?.conflicts" :key="c" class="bg-white p-2 rounded border">{{ c }}</li>
                </ul>
                <div v-else class="text-gray-500 italic text-xs">не обнаружены</div>
            </div>
        </div>

        <div v-else class="text-gray-500 italic text-sm">Нет данных для отображения</div>
    </div>
</template>
