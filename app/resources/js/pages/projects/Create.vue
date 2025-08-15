<template>
    <AppLayout title="Создать проект">
        <div class="mx-auto max-w-2xl">
            <div class="mb-6">
                <Heading>Создать новый проект</Heading>
                <p class="mt-2 text-muted-foreground">Создайте проект для организации вашей документации</p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Информация о проекте</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="createProject" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="title">Название проекта</Label>
                            <Input
                                id="title"
                                v-model="form.title"
                                type="text"
                                placeholder="Введите название проекта"
                                :class="{ 'border-destructive': form.errors.title }"
                                required
                            />
                            <InputError :message="form.errors.title" />
                        </div>

                        <div class="flex items-center justify-between pt-4">
                            <Button type="button" variant="outline" @click="$inertia.visit(route('projects.index'))"> Отмена </Button>
                            <Button type="submit" :disabled="form.processing">
                                <Icon v-if="form.processing" name="loader-2" class="mr-2 h-4 w-4 animate-spin" />
                                {{ form.processing ? 'Создание...' : 'Создать проект' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import Icon from '@/components/Icon.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    title: '',
});

const createProject = () => {
    form.post(route('projects.store'));
};
</script>
