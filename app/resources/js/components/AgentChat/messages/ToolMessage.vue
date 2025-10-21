<script setup lang="ts">
import type { LLMMessage } from '@/types';
import ToolGeneric from '@/components/AgentChat/messages/tools/ToolGeneric.vue';
// Tasks tools
import ToolTasksComplete from '@/components/AgentChat/messages/tools/Tasks/ToolTasksComplete.vue';
import ToolGetTaskList from '@/components/AgentChat/messages/tools/Tasks/ToolGetTaskList.vue';
import ToolTasksAdd from '@/components/AgentChat/messages/tools/Tasks/ToolTasksAdd.vue';

// Editor tools
import ToolEditorEditFile from '@/components/AgentChat/messages/tools/Editor/ToolEditorEditFile.vue';

// Git tools
import ToolGitAnalyzeStructure from '@/components/AgentChat/messages/tools/Git/ToolGitAnalyzeStructure.vue';
import ToolGitReadDir from '@/components/AgentChat/messages/tools/Git/ToolGitReadDir.vue';
import ToolGitReadFile from '@/components/AgentChat/messages/tools/Git/ToolGitReadFile.vue';
import ToolGitFindConfigFiles from '@/components/AgentChat/messages/tools/Git/ToolGitFindConfigFiles.vue';
import ToolGitSearchFileByName from '@/components/AgentChat/messages/tools/Git/ToolGitSearchFileByName.vue';
import ToolGitAnalyzeClasses from '@/components/AgentChat/messages/tools/Git/ToolGitAnalyzeClasses.vue';
import ToolGitGetDependencies from '@/components/AgentChat/messages/tools/Git/ToolGitGetDependencies.vue';
import ToolGitGrepFile from '@/components/AgentChat/messages/tools/Git/ToolGitGrepFile.vue';
import ToolGitReadFileLines from '@/components/AgentChat/messages/tools/Git/ToolGitReadFileLines.vue';
import ToolGitSearchPattern from '@/components/AgentChat/messages/tools/Git/ToolGitSearchPattern.vue';

// Basic tools
import ToolCatchContent from '@/components/AgentChat/messages/tools/Basic/ToolCatchContent.vue';
import ToolCurrentTime from '@/components/AgentChat/messages/tools/Basic/ToolCurrentTime.vue';
import ToolSendResult from '@/components/AgentChat/messages/tools/Basic/ToolSendResult.vue';

// Editor tools
import ToolEditorInsertOrReplace from '@/components/AgentChat/messages/tools/Editor/ToolEditorInsertOrReplace.vue';
import ToolEditorReplaceInFile from '@/components/AgentChat/messages/tools/Editor/ToolEditorReplaceInFile.vue';

// Git RepoManagement tools
import ToolGitAddFile from '@/components/AgentChat/messages/tools/Git/RepoManagement/ToolGitAddFile.vue';
import ToolGitCommit from '@/components/AgentChat/messages/tools/Git/RepoManagement/ToolGitCommit.vue';
import ToolGitGetCurrentBranch from '@/components/AgentChat/messages/tools/Git/RepoManagement/ToolGitGetCurrentBranch.vue';
import ToolGitGetStatus from '@/components/AgentChat/messages/tools/Git/RepoManagement/ToolGitGetStatus.vue';
import ToolGitPull from '@/components/AgentChat/messages/tools/Git/RepoManagement/ToolGitPull.vue';
import ToolGitPush from '@/components/AgentChat/messages/tools/Git/RepoManagement/ToolGitPush.vue';
import ToolGitResetHard from '@/components/AgentChat/messages/tools/Git/RepoManagement/ToolGitResetHard.vue';
import ToolGitUnstageFile from '@/components/AgentChat/messages/tools/Git/RepoManagement/ToolGitUnstageFile.vue';

// Page tools
import ToolPageFindRelatedPages from '@/components/AgentChat/messages/tools/Page/ToolPageFindRelatedPages.vue';
import ToolPageGetActualizationInfo from '@/components/AgentChat/messages/tools/Page/ToolPageGetActualizationInfo.vue';
import ToolPageGetAttachedFiles from '@/components/AgentChat/messages/tools/Page/ToolPageGetAttachedFiles.vue';
import ToolPageGetHierarchyTree from '@/components/AgentChat/messages/tools/Page/ToolPageGetHierarchyTree.vue';
import ToolPageGetInfo from '@/components/AgentChat/messages/tools/Page/ToolPageGetInfo.vue';
import ToolPageGetProjectPages from '@/components/AgentChat/messages/tools/Page/ToolPageGetProjectPages.vue';
import ToolPageGetTaskHistory from '@/components/AgentChat/messages/tools/Page/ToolPageGetTaskHistory.vue';

// Utils tools
import ToolUtilsAddFileToList from '@/components/AgentChat/messages/tools/Utils/ToolUtilsAddFileToList.vue';
import ToolUtilsUpdateArticle from '@/components/AgentChat/messages/tools/Utils/ToolUtilsUpdateArticle.vue';

const props = defineProps<{
    message: LLMMessage;
    index: number;
}>();

function getToolName(): string | undefined {
    const msg: any = props.message.message;
    return msg?.name || msg?.tool_name;
}

const toolComponentsMap: Record<string, any> = {
    // Existing tools
    'tasks-complete': ToolTasksComplete,
    'get-task-list': ToolGetTaskList,
    'editor-edit-file': ToolEditorEditFile,
    'tasks-add': ToolTasksAdd,
    'git-analyze-structure': ToolGitAnalyzeStructure,
    'git-read-dir': ToolGitReadDir,
    'git-readFile': ToolGitReadFile,
    'git-readDir': ToolGitReadDir,
    'git-read-file': ToolGitReadFile,
    'git-find-config-files': ToolGitFindConfigFiles,
    'git-search-file-by-name': ToolGitSearchFileByName,
    'git-analyze-classes': ToolGitAnalyzeClasses,
    'git-get-dependencies': ToolGitGetDependencies,
    'git-grep-file': ToolGitGrepFile,
    'git-read-file-lines': ToolGitReadFileLines,
    'git-search-pattern': ToolGitSearchPattern,
    
    // Basic tools
    'catch-content': ToolCatchContent,
    'current-time': ToolCurrentTime,
    'send-result': ToolSendResult,
    
    // Editor tools
    'editor-insert-or-replace': ToolEditorInsertOrReplace,
    'editor-replace-in-file': ToolEditorReplaceInFile,
    
    // Git RepoManagement tools
    'git-add-file': ToolGitAddFile,
    'git-commit': ToolGitCommit,
    'git-get-current-branch': ToolGitGetCurrentBranch,
    'git-get-status': ToolGitGetStatus,
    'git-pull': ToolGitPull,
    'git-push': ToolGitPush,
    'git-reset-hard': ToolGitResetHard,
    'git-unstage-file': ToolGitUnstageFile,
    
    // Page tools
    'page-find-related-pages': ToolPageFindRelatedPages,
    'page-get-actualization-info': ToolPageGetActualizationInfo,
    'page-get-attached-files': ToolPageGetAttachedFiles,
    'page-get-hierarchy-tree': ToolPageGetHierarchyTree,
    'page-get-info': ToolPageGetInfo,
    'page-get-project-pages': ToolPageGetProjectPages,
    'page-get-task-history': ToolPageGetTaskHistory,
    
    // Utils tools
    'utils-add-file-to-list': ToolUtilsAddFileToList,
    'utils-update-article': ToolUtilsUpdateArticle,
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
