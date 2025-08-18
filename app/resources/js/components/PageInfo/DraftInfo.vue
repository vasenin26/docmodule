<template>
    <div v-if="draft" class="rounded-lg border border-yellow-200 bg-yellow-50 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-yellow-800">
                    <strong>Активный черновик:</strong>
                    Создан {{ formatDate(draft.created_at) }}
                </p>
                <p class="mt-1 text-xs text-yellow-600">
                    Последнее обновление: {{ formatDate(draft.updated_at) }}
                </p>
            </div>
            <div class="flex gap-2">
                <Button as-child variant="outline" size="sm">
                    <Link :href="route('pages.versions.edit', [pageId, draft.id])"> 
                        Продолжить редактирование 
                    </Link>
                </Button>
                <Button @click="approveDraft" variant="default" size="sm"> 
                    Утвердить 
                </Button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import { Link, router } from '@inertiajs/vue3';

interface Draft {
    id: number;
    title: string;
    content: string;
    created_at: string;
    updated_at: string;
}

interface Props {
    draft: Draft;
    pageId: number;
}

const props = defineProps<Props>();

const approveDraft = () => {
    router.post(route('pages.draft.approve', props.draft.id));
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>
