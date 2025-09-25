import { getStupidStore } from '@/lib/utils';

export function useToolCallCache() {
    const functions: {[key: string]: string} = getStupidStore('functions');

    function registerFunctionName(toolCall: {
        id: string;
        function?: { name: string };
        name?: string;
    }): string {
        const name = toolCall.function?.name || toolCall.name || '';
        if (toolCall.id && name) {
            functions[toolCall.id] = name;
        }
        return name;
    }

    function getFunctionName(id: string): string {
        return functions[id] || '';
    }

    return {
        registerFunctionName,
        getFunctionName,
    };
}
