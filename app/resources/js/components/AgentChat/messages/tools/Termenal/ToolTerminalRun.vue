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

function parseResult(): any | undefined {
    const m: any = props.message.message;
    const raw: string | undefined = m?.result || m?.tool_result;
    if (!raw) return undefined;
    try {
        const parsed = JSON.parse(raw);
        // Новый формат ToolResult: { message: string, payload: any }
        if (parsed && parsed.payload) return parsed.payload;
        return parsed;
    } catch {
        return undefined;
    }
}

function getCommand(): string | undefined {
    return parseResult()?.command;
}

function getCwd(): string | undefined {
    return parseResult()?.cwd;
}

function getExitCode(): number | undefined {
    return parseResult()?.exit_code;
}

function getStdout(): string | undefined {
    const stdout = parseResult()?.stdout;
    return stdout !== undefined && stdout !== null ? String(stdout) : undefined;
}

function getStderr(): string | undefined {
    const stderr = parseResult()?.stderr;
    return stderr !== undefined && stderr !== null ? String(stderr) : undefined;
}

function getElapsed(): number | undefined {
    return parseResult()?.elapsed;
}

function hasOutput(): boolean {
    const stdout = getStdout();
    const stderr = getStderr();
    return (stdout !== undefined && stdout.trim() !== '') || (stderr !== undefined && stderr.trim() !== '');
}

function containerClass(): string {
    const exitCode = getExitCode();
    const success = getSuccess();
    // Если exit_code = 0 или success = true, то успех
    if ((exitCode !== undefined && exitCode === 0) || success) {
        return 'bg-orange-50 border border-orange-200';
    }
    return 'bg-red-50 border border-red-200';
}

function isError(): boolean {
    const exitCode = getExitCode();
    const success = getSuccess();
    // Если exit_code != 0 или success = false, то ошибка
    return (exitCode !== undefined && exitCode !== 0) || success === false;
}
</script>

<template>
    <div class="rounded-lg p-3" :class="containerClass()">
        <ToolHeaderStatus :title="'выполнение команды'" :isError="isError()" />

        <div class="text-sm space-y-3">
            <div v-if="getCommand()" class="space-y-1">
                <div class="text-xs font-medium text-gray-600">Команда:</div>
                <div class="text-sm font-mono bg-gray-100 p-2 rounded border overflow-x-auto">
                    {{ getCommand() }}
                </div>
            </div>

            <div v-if="hasOutput()" class="space-y-2">
                <div v-if="getStdout() && getStdout()!.trim() !== ''" class="space-y-1">
                    <div class="text-xs font-medium text-gray-600">Stdout:</div>
                    <div class="text-sm font-mono bg-white p-2 rounded border overflow-auto whitespace-pre-wrap">
                        {{ getStdout() }}
                    </div>
                </div>

                <div v-if="getStderr() && getStderr()!.trim() !== ''" class="space-y-1">
                    <div class="text-xs font-medium text-red-600">Stderr:</div>
                    <div class="text-sm font-mono bg-red-50 p-2 rounded border border-red-200 overflow-auto whitespace-pre-wrap text-red-800">
                        {{ getStderr() }}
                    </div>
                </div>
            </div>

            <div v-if="!getCommand() && !hasOutput()" class="text-gray-500 italic text-sm">
                Нет данных для отображения
            </div>
        </div>
    </div>
</template>

