<template>
    <div class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle>{{ cardTitle }}</CardTitle>
                <CardDescription>{{ description }}</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div v-if="showTitle">
                    <Label for="task-title" class="text-sm font-medium">Заголовок задачи</Label>
                    <input
                        id="task-title"
                        :value="title"
                        @input="onTitleInput"
                        placeholder="Введите заголовок задачи (необязательно)..."
                        class="mt-2 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        :class="{ 'border-red-500': errors?.title }"
                    />
                    <p v-if="errors?.title" class="mt-1 text-sm text-red-600">{{ errors.title }}</p>
                </div>
                <div>
                    <Label for="task-content" class="text-sm font-medium">Описание задачи</Label>
                    <textarea
                        id="task-content"
                        :value="content"
                        @input="onInput"
                        placeholder="Введите описание задачи..."
                        rows="15"
                        class="mt-2 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        :class="{ 'border-red-500': errors?.content || errors?.description }"
                    />
                    <p v-if="errors?.content" class="mt-1 text-sm text-red-600">{{ errors.content }}</p>
                    <p v-else-if="errors?.description" class="mt-1 text-sm text-red-600">{{ errors.description }}</p>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardContent class="pt-6">
                <div class="flex items-center justify-between">
                    <Button type="button" variant="outline" as-child>
                        <Link :href="cancelHref">{{ cancelText }}</Link>
                    </Button>
                    <Button type="submit" :disabled="submitting">
                        <span v-if="submitting">{{ submittingText }}</span>
                        <span v-else>{{ submitText }}</span>
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Link } from '@inertiajs/vue3';

const props = withDefaults(defineProps<{
    content: string;
    title?: string;
    errors?: Record<string, string>;
    submitting: boolean;
    cancelHref: string;
    submitText?: string;
    submittingText?: string;
    cancelText?: string;
    cardTitle?: string;
    description?: string;
    showTitle?: boolean;
}>(), {
    errors: () => ({}),
    submitText: 'Сохранить изменения',
    submittingText: 'Сохранение...',
    cancelText: 'Отмена',
    cardTitle: 'Редактирование описания задачи',
    description: 'Измените описание задачи. При сохранении техплан будет очищен.',
    showTitle: false,
});

const emit = defineEmits<{
    (e: 'update:content', value: string): void
    (e: 'update:title', value: string): void
}>();

const onInput = (event: Event) => {
    const target = event.target as HTMLTextAreaElement;
    emit('update:content', target.value);
};

const onTitleInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    emit('update:title', target.value);
};
</script>


