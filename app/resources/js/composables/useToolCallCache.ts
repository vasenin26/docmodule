import { getStupidStore } from '@/lib/utils';

export function useToolCallCache() {
    const functions: {[key: string]: string} = getStupidStore('functions');

    function registerFunctionName(toolCall: {
        id: string;
        function: {
            name: string;
        };
    }): string {
        functions[toolCall.id] = toolCall.function.name;
        return toolCall.function.name;
    }

    function getFunctionName(id: string): string {
        return functions[id] || '';
    }

    return {
        registerFunctionName,
        getFunctionName,
    };
}
