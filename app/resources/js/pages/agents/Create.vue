<template>
  <AppLayout :title="`Создать агента - ${project.title}`">
    <div class="mx-auto max-w-2xl">
      <div class="mb-6">
        <Heading>Создать агента</Heading>
        <p class="mt-2 text-muted-foreground">Добавьте нового агента для проекта {{ project.title }}</p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Информация об агенте</CardTitle>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="createAgent" class="space-y-4">
            <div class="space-y-2">
              <Label for="name">Название агента</Label>
              <Input
                id="name"
                v-model="form.name"
                type="text"
                placeholder="Введите название агента"
                :class="{ 'border-destructive': form.errors.name }"
                required
              />
              <InputError :message="form.errors.name" />
              <p class="text-sm text-muted-foreground">
                Предложенное название: <span class="font-medium">{{ suggestedName }}</span>
              </p>
            </div>

            <div class="flex items-center justify-between pt-4">
              <Button type="button" variant="outline" @click="$inertia.visit(route('projects.agents.index', project.id))">
                Отмена
              </Button>
              <Button type="submit" :disabled="form.processing">
                <Icon v-if="form.processing" name="loader-2" class="mr-2 h-4 w-4 animate-spin" />
                {{ form.processing ? 'Создание...' : 'Создать агента' }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>

      <!-- Информация о токене -->
      <Card class="mt-6">
        <CardHeader>
          <CardTitle>JWT Токен</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div class="rounded-lg bg-muted p-4">
              <div class="flex items-center gap-2 mb-2">
                <Icon name="info" class="h-4 w-4 text-blue-600" />
                <span class="text-sm font-medium">После создания агента</span>
              </div>
              <p class="text-sm text-muted-foreground">
                Агенту будет автоматически сгенерирован JWT токен для аутентификации в API. 
                Токен будет отображен на странице редактирования агента.
              </p>
            </div>
            
            <div class="rounded-lg bg-muted p-4">
              <div class="flex items-center gap-2 mb-2">
                <Icon name="shield" class="h-4 w-4 text-green-600" />
                <span class="text-sm font-medium">Безопасность</span>
              </div>
              <p class="text-sm text-muted-foreground">
                Токен обеспечивает безопасную аутентификацию агента при работе с API. 
                Сохраните токен в безопасном месте - он не будет показан повторно.
              </p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import Heading from '@/components/Heading.vue'
import Icon from '@/components/Icon.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'

interface Project {
  id: number
  title: string
}

interface Props {
  project: Project
  suggestedName: string
}

const props = defineProps<Props>()

const form = useForm({
  name: props.suggestedName,
})

const createAgent = () => {
  form.post(route('projects.agents.store', props.project.id), {
    onSuccess: () => {
      // Перенаправление произойдет автоматически
    }
  })
}
</script>