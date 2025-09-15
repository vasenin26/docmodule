import { ref } from 'vue';

export function useTextExpansion() {
    const expandedTexts = ref(new Set<string>());

    // Функции для работы с длинными текстами
    function isLongText(text: string | undefined, maxLength: number = 100): boolean {
        return text !== undefined && text.length > maxLength;
    }

    function getTruncatedText(text: string | undefined, maxLength: number = 100): string {
        if (!text) return '';
        return text.substring(0, maxLength) + '...';
    }

    function toggleTextExpansion(textId: string): void {
        if (expandedTexts.value.has(textId)) {
            expandedTexts.value.delete(textId);
        } else {
            expandedTexts.value.add(textId);
        }
    }

    function isTextExpanded(textId: string): boolean {
        return expandedTexts.value.has(textId);
    }

    return {
        expandedTexts,
        isLongText,
        getTruncatedText,
        toggleTextExpansion,
        isTextExpanded,
    };
}
