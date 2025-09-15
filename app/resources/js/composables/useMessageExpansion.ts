import { ref } from 'vue';

export function useMessageExpansion() {
    const expandedMessages = ref(new Set<number>());

    // Константы
    const MAX_MESSAGE_LENGTH = 200;

    // Функции для работы с контентом
    function isLongMessage(content: string | null | undefined): boolean {
        return content !== undefined && content !== null && content.length > MAX_MESSAGE_LENGTH;
    }

    function getTruncatedContent(content: string | null | undefined): string {
        if(content === undefined) return '';
        if (!content) return '';
        return content.substring(0, MAX_MESSAGE_LENGTH) + '...';
    }

    function toggleMessageExpansion(index: number): void {
        if (expandedMessages.value.has(index)) {
            expandedMessages.value.delete(index);
        } else {
            expandedMessages.value.add(index);
        }
    }

    return {
        expandedMessages,
        isLongMessage,
        getTruncatedContent,
        toggleMessageExpansion,
    };
}
