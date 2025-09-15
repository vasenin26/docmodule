<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium">Создать дочернюю страницу</h3>
            <Button type="button" variant="outline" size="sm" @click="showForm = !showForm">
                {{ showForm ? 'Отмена' : 'Добавить дочернюю страницу' }}
            </Button>
        </div>

        <div v-if="showForm" class="rounded-lg border bg-gray-50 p-4">
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <Label for="child-title">Название дочерней страницы *</Label>
                    <Input
                        id="child-title"
                        v-model="form.title"
                        placeholder="Введите название дочерней страницы"
                        :class="{ 'border-destructive': errors.title }"
                    />
                    <InputError v-if="errors.title" :message="errors.title" />
                </div>

                <div>
                    <Label for="child-content">Содержимое</Label>
                    <textarea
                        id="child-content"
                        v-model="form.content"
                        rows="8"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        placeholder="Введите содержимое дочерней страницы в формате Markdown..."
                        :class="{ 'border-destructive': errors.content }"
                    />
                    <InputError v-if="errors.content" :message="errors.content" />
                    <p class="text-xs text-muted-foreground">Поддерживается формат Markdown</p>
                </div>

                <div class="flex items-center gap-2">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Создание...' : 'Создать дочернюю страницу' }}
                    </Button>
                    <Button type="button" variant="outline" @click="cancel"> Отмена </Button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Props {
    parentId: number;
    projectId?: number | null;
    errors?: Record<string, string>;
}

const props = withDefaults(defineProps<Props>(), {
    errors: () => ({}),
});

const showForm = ref(false);
const processing = ref(false);

const form = useForm({
    title: '',
    content: '',
    parent_id: props.parentId,
    project_id: props.projectId ?? null,
});

const submit = () => {
    processing.value = true;
    form.post(route('pages.store'), {
        onSuccess: () => {
            processing.value = false;
            showForm.value = false;
            form.reset();
        },
        onError: () => {
            processing.value = false;
        },
    });
};

const cancel = () => {
    showForm.value = false;
    form.reset();
};
</script>
