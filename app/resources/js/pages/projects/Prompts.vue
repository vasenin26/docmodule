<template>
    <AppLayout :title="`Промпты проекта ${project.title}`">
        <template #context-actions>
            <ProjectDropdownMenu :project-id="project.id" :project-title="project.title" />
        </template>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <Heading>Промпты проекта</Heading>
                    <p class="mt-1 text-muted-foreground">
                        {{ project.title }} • Настройка промптов для генерации задач
                    </p>
                </div>
                <Button variant="outline" as-child>
                    <Link :href="route('projects.show', project.id)">
                        <Icon name="arrow-left" class="mr-2 h-4 w-4" />
                        К проекту
                    </Link>
                </Button>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Панель выбора и редактирования -->
                <Card>
                    <CardHeader>
                        <CardTitle>Редактирование промпта</CardTitle>
                        <CardDescription>
                            Выберите тип промпта и отредактируйте его содержимое
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Выбор типа промпта -->
                        <div class="space-y-2">
                            <Label for="prompt-type">Тип промпта</Label>
                            <select
                                id="prompt-type"
                                v-model="selectedType"
                                @change="loadPrompt"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                            >
                                <option value="">Выберите тип промпта</option>
                                <option 
                                    v-for="type in promptTypes" 
                                    :key="type.value" 
                                    :value="type.value"
                                >
                                    {{ type.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Редактор промпта -->
                        <div v-if="selectedType" class="space-y-2">
                            <div class="flex items-center justify-between">
                                <Label for="prompt-content">Содержимое промпта</Label>
                                <div class="flex items-center gap-2">
                                    <span v-if="currentPrompt && currentPrompt.is_default" class="inline-flex items-center px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">
                                        По умолчанию
                                    </span>
                                    <span v-else-if="currentPrompt && !currentPrompt.is_default" class="inline-flex items-center px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                        Настроенный
                                    </span>
                                </div>
                            </div>
                            <textarea
                                id="prompt-content"
                                v-model="promptContent"
                                :placeholder="`Введите содержимое для ${getCurrentTypeLabel()}`"
                                rows="12"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none font-mono"
                                @input="debouncedPreview"
                            />
                            <div class="text-xs text-muted-foreground">
                                Используйте синтаксис Mustache для шаблонов: {{ mustacheExample }}
                            </div>
                        </div>

                        <!-- Кнопки действий -->
                        <div v-if="selectedType" class="flex items-center gap-2">
                            <Button 
                                @click="savePrompt" 
                                :disabled="saving || !hasChanges"
                                :loading="saving"
                            >
                                <Icon name="save" class="mr-2 h-4 w-4" />
                                Сохранить
                            </Button>
                            <Button 
                                v-if="currentPrompt && currentPrompt.can_reset"
                                variant="outline" 
                                @click="showResetDialog = true"
                                :disabled="saving"
                            >
                                <Icon name="rotate-ccw" class="mr-2 h-4 w-4" />
                                Сбросить
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Панель предварительного просмотра -->
                <Card>
                    <CardHeader>
                        <CardTitle>Предварительный просмотр</CardTitle>
                        <CardDescription>
                            Результат рендеринга промпта с тестовыми данными
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="!selectedType" class="py-8 text-center text-muted-foreground">
                            Выберите тип промпта для предварительного просмотра
                        </div>
                        <div v-else-if="previewLoading" class="py-8 text-center">
                            <div class="animate-spin h-6 w-6 border-2 border-primary border-t-transparent rounded-full mx-auto mb-2"></div>
                            <p class="text-sm text-muted-foreground">Обновление предварительного просмотра...</p>
                        </div>
                        <div v-else-if="previewError" class="py-4">
                            <div class="rounded-md bg-red-50 p-4 border border-red-200">
                                <div class="flex">
                                    <Icon name="alert-circle" class="h-5 w-5 text-red-400" />
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-red-800">Ошибка рендеринга</h3>
                                        <p class="mt-1 text-sm text-red-700">{{ previewError }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="space-y-4">
                            <div class="bg-muted p-4 rounded-md">
                                <pre class="whitespace-pre-wrap text-sm">{{ previewContent }}</pre>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Диалог подтверждения сброса -->
        <ConfirmDialog
            v-model:open="showResetDialog"
            title="Сбросить промпт?"
            description="Настроенный промпт будет удален, и будет использоваться промпт по умолчанию. Это действие нельзя отменить."
            confirm-text="Сбросить"
            cancel-text="Отмена"
            confirm-variant="destructive"
            @confirm="resetPrompt"
        />
    </AppLayout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import Icon from '@/components/Icon.vue';
import InputError from '@/components/InputError.vue';
import ProjectDropdownMenu from '@/components/ProjectDropdownMenu.vue';
import Button from '@/components/ui/button/Button.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import Label from '@/components/ui/label/Label.vue';
import ConfirmDialog from '@/components/ui/dialog/ConfirmDialog.vue';

interface Project {
    id: number;
    title: string;
}

interface PromptType {
    value: string;
    label: string;
}

interface Prompt {
    type: string;
    label: string;
    content: string;
    is_default: boolean;
    can_reset: boolean;
}

const props = defineProps<{
    project: Project;
    prompts: Prompt[];
    prompt_types: PromptType[];
}>();

const selectedType = ref<string>('');
const promptContent = ref<string>('');
const currentPrompt = ref<Prompt | null>(null);
const originalContent = ref<string>('');
const saving = ref(false);
const showResetDialog = ref(false);
const previewContent = ref<string>('');
const previewLoading = ref(false);
const previewError = ref<string>('');

const promptTypes = computed(() => props.prompt_types);
const mustacheExample = '{{variable}}';

const hasChanges = computed(() => {
    return promptContent.value !== originalContent.value;
});

const getCurrentTypeLabel = () => {
    const type = promptTypes.value.find(t => t.value === selectedType.value);
    return type?.label || '';
};

const loadPrompt = async () => {
    if (!selectedType.value) return;

    try {
        const response = await fetch(route('projects.prompts.show', {
            project: props.project.id,
            type: selectedType.value
        }));
        
        const data = await response.json();
        currentPrompt.value = data;
        promptContent.value = data.content;
        originalContent.value = data.content;
        
        await updatePreview();
    } catch (error) {
        console.error('Ошибка загрузки промпта:', error);
    }
};

const savePrompt = async () => {
    if (!selectedType.value) return;

    saving.value = true;
    
    try {
        const response = await fetch(route('projects.prompts.store', props.project.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                type: selectedType.value,
                content: promptContent.value,
            }),
        });

        const data = await response.json();
        
        if (data.success) {
            originalContent.value = promptContent.value;
            currentPrompt.value = { ...currentPrompt.value!, ...data.prompt };
            // Показать уведомление об успехе
        }
    } catch (error) {
        console.error('Ошибка сохранения промпта:', error);
        // Показать уведомление об ошибке
    } finally {
        saving.value = false;
    }
};

const resetPrompt = async () => {
    if (!selectedType.value) return;

    try {
        const response = await fetch(route('projects.prompts.destroy', {
            project: props.project.id,
            type: selectedType.value
        }), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        const data = await response.json();
        
        if (data.success) {
            showResetDialog.value = false;
            await loadPrompt(); // Перезагрузить промпт
        }
    } catch (error) {
        console.error('Ошибка сброса промпта:', error);
    }
};

const updatePreview = async () => {
    if (!selectedType.value || !promptContent.value.trim()) {
        previewContent.value = '';
        return;
    }

    previewLoading.value = true;
    previewError.value = '';

    try {
        const response = await fetch(route('projects.prompts.preview', props.project.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                type: selectedType.value,
                content: promptContent.value,
            }),
        });

        const data = await response.json();
        
        if (data.success) {
            previewContent.value = data.rendered_content;
            previewError.value = '';
        } else {
            previewError.value = data.error || 'Ошибка предварительного просмотра';
            previewContent.value = '';
        }
    } catch (error) {
        console.error('Ошибка предварительного просмотра:', error);
        previewError.value = 'Ошибка сети при загрузке предварительного просмотра';
    } finally {
        previewLoading.value = false;
    }
};

// Простая функция debounce
const debounce = (func: Function, delay: number) => {
    let timeoutId: ReturnType<typeof setTimeout>;
    return (...args: any[]) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func.apply(null, args), delay);
    };
};

const debouncedPreview = debounce(updatePreview, 500);

// Автоматически выбрать первый тип промпта при загрузке
if (promptTypes.value.length > 0) {
    selectedType.value = promptTypes.value[0].value;
    loadPrompt();
}
</script>
