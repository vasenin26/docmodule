<template>
    <AppLayout :title="`Настройки генерации • ${project.title}`">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <Heading :title="'Настройки генерации'" :description="`${project.title} • Сопоставление типов генерации и моделей`" />
                </div>
                <Button variant="outline" as-child>
                    <Link :href="route('projects.show', project.id)">
                        <Icon name="arrow-left" class="mr-2 h-4 w-4" />
                        К проекту
                    </Link>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Выбор моделей для типов генерации</CardTitle>
                    <CardDescription>
                        Для каждого типа укажите модель, которая будет использоваться при генерации
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div v-for="type in generationTypes" :key="type.value" class="grid gap-3 md:grid-cols-3 items-center">
                        <div class="text-sm font-medium">{{ type.label }}</div>
                        <div class="md:col-span-2 flex items-center gap-2">
                            <select
                                v-model="selected[type.value]"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                            >
                                <option :value="null">— Не выбрано —</option>
                                <option v-for="m in models" :key="m.id" :value="m.id">
                                    {{ m.name }} ({{ m.context_size }})
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center justify-end pt-4">
                        <Button :disabled="savingAll" :loading="savingAll" @click="saveAll">
                            <Icon name="save" class="mr-2 h-4 w-4" />
                            Сохранить
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
    
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue';
import { Link } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import Icon from '@/components/Icon.vue';
import Button from '@/components/ui/button/Button.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';

interface Project { id: number; title: string }
interface GenerationType { value: string; label: string }
interface GenerationModel { id: number; name: string; context_size: number }
interface Mapping { generation_type: string; model_id: number | null }

const props = defineProps<{
    project: Project;
    generation_types: GenerationType[];
    models: GenerationModel[];
    mappings: Mapping[];
}>();

const generationTypes = props.generation_types;
const models = props.models;

const selected: Record<string, number | null> = reactive({});
const savingAll = ref(false);

props.generation_types.forEach(t => {
    const mapping = props.mappings.find(m => m.generation_type === t.value);
    selected[t.value] = mapping ? mapping.model_id : null;
    
});

const saveAll = async () => {
    savingAll.value = true;
    try {
        // Save or delete for each type
        for (const t of generationTypes) {
            const type = t.value;
            const value = selected[type];
            if (value) {
                await fetch(route('projects.generation-models.store', props.project.id), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify({
                        generation_type: type,
                        model_id: value,
                    }),
                });
            } else {
                await fetch(route('projects.generation-models.destroy', { project: props.project.id, type }), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                });
            }
        }
    } finally {
        savingAll.value = false;
    }
};
</script>


