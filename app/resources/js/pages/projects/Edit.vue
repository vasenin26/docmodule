<template>
  <AppLayout :title="`Редактировать ${project.title}`">
    <div class="max-w-2xl mx-auto">
      <div class="mb-6">
        <Heading>Редактировать проект</Heading>
        <p class="text-muted-foreground mt-2">
          Измените информацию о проекте
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Информация о проекте</CardTitle>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="updateProject" class="space-y-4">
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
              <Button
                type="button"
                variant="outline"
                @click="$inertia.visit(route('projects.show', project.id))"
              >
                Отмена
              </Button>
              <div class="flex items-center gap-2">
                <Button
                  type="button"
                  variant="destructive"
                  @click="deleteProject"
                >
                  Удалить
                </Button>
                <Button 
                  type="submit" 
                  :disabled="form.processing"
                >
                  <Icon 
                    v-if="form.processing" 
                    name="loader-2" 
                    class="mr-2 h-4 w-4 animate-spin" 
                  />
                  {{ form.processing ? 'Сохранение...' : 'Сохранить' }}
                </Button>
              </div>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import Heading from '@/components/Heading.vue'
import Icon from '@/components/Icon.vue'
import InputError from '@/components/InputError.vue'

interface User {
  id: number
  name: string
  email: string
}

interface Project {
  id: number
  title: string
  owner_id: number
  owner: User
  created_at: string
  updated_at: string
}

const props = defineProps<{
  project: Project
}>()

const form = useForm({
  title: props.project.title,
})

const updateProject = () => {
  form.put(route('projects.update', props.project.id))
}

const deleteProject = () => {
  if (confirm(`Вы уверены, что хотите удалить проект "${props.project.title}"? Все страницы проекта также будут удалены.`)) {
    router.delete(route('projects.destroy', props.project.id))
  }
}
</script>
