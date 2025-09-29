<script setup lang="ts">
import type { LLMMessage } from '@/types';
import ToolGeneric from '@/components/AgentChat/messages/tools/ToolGeneric.vue';
import ToolTasksComplete from '@/components/AgentChat/messages/tools/ToolTasksComplete.vue';
import ToolGetTaskList from '@/components/AgentChat/messages/tools/ToolGetTaskList.vue';
import ToolEditorEditFile from '@/components/AgentChat/messages/tools/ToolEditorEditFile.vue';

const props = defineProps<{
    message: LLMMessage;
    index: number;
}>();

function getToolName(): string | undefined {
    const msg: any = props.message.message;
    return msg?.name || msg?.tool_name;
}

const toolComponentsMap: Record<string, any> = {
    'tasks-complete': ToolTasksComplete,
    'get-task-list': ToolGetTaskList,
    'editor-edit-file': ToolEditorEditFile,
};

function getComponentName(): any {
    const name = getToolName();
    if (name && toolComponentsMap[name]) {
        return toolComponentsMap[name];
    }
    return ToolGeneric;
}
</script>

<template>
    <component :is="getComponentName()" :message="props.message" :index="props.index" />
</template>
