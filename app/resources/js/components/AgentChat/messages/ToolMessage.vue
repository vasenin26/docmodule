<script setup lang="ts">
import type { LLMMessage } from '@/types';
import ToolGeneric from '@/components/AgentChat/messages/tools/ToolGeneric.vue';
import ToolTasksComplete from '@/components/AgentChat/messages/tools/ToolTasksComplete.vue';
import ToolGetTaskList from '@/components/AgentChat/messages/tools/ToolGetTaskList.vue';
import ToolEditorEditFile from '@/components/AgentChat/messages/tools/ToolEditorEditFile.vue';
import ToolTasksAdd from '@/components/AgentChat/messages/tools/ToolTasksAdd.vue';
import ToolGitAnalyzeStructure from '@/components/AgentChat/messages/tools/ToolGitAnalyzeStructure.vue';
import ToolGitReadDir from '@/components/AgentChat/messages/tools/ToolGitReadDir.vue';
import ToolGitReadFile from '@/components/AgentChat/messages/tools/ToolGitReadFile.vue';
import ToolGitFindConfigFiles from '@/components/AgentChat/messages/tools/ToolGitFindConfigFiles.vue';

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
    'tasks-add': ToolTasksAdd,
    'git-analyze-structure': ToolGitAnalyzeStructure,
    'git-read-dir': ToolGitReadDir,
    'git-read-file': ToolGitReadFile,
    'git-find-config-files': ToolGitFindConfigFiles,
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
