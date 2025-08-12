<template>
  <AppLayout title="Редактировать страницу">
    <template #header>
      <div class="flex items-center justify-between">
        <Heading title="Редактировать страницу" />
        <div class="flex items-center gap-2">
          <Button as-child variant="outline">
            <Link :href="route('pages.show', page?.id)">
              Просмотр
            </Link>
          </Button>
          <Button as-child variant="outline">
            <Link :href="route('pages.index')">
              Назад к списку
            </Link>
          </Button>
        </div>
      </div>
    </template>

    <div class="max-w-4xl">
      <!-- Предупреждение о существующем черновике -->
      <!-- <div v-if="hasActiveDraft" class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg mb-6">
        <p class="text-sm text-yellow-800">
          <strong>Внимание:</strong> У этой страницы есть активный черновик. 
          Вы можете продолжить редактирование черновика или создать новый.
        </p>
        <div class="mt-2 flex gap-2">
          <Button @click="continueDraft" variant="outline" size="sm">
            Продолжить черновик
          </Button>
          <Button @click="createNewDraft" variant="outline" size="sm">
            Создать новый черновик
          </Button>
        </div>
      </div> -->

      <!-- Информация о черновике -->
      <div v-if="currentDraft" class="p-4 bg-blue-50 border border-blue-200 rounded-lg mb-6">
        <p class="text-sm text-blue-800">
          <strong>Редактирование черновика:</strong> 
          Создан {{ formatDate(currentDraft.created_at) }}
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{{ page?.title || 'Загрузка...' }}</CardTitle>
          <CardDescription v-if="!currentDraft">
            Редактирование страницы документации. При сохранении будет создан черновик.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-6">
            <!-- Название -->
            <div class="space-y-2">
              <Label for="title">Название страницы *</Label>
              <Input
                id="title"
                v-model="form.title"
                placeholder="Введите название страницы"
                :class="{ 'border-destructive': errors?.title }"
              />
              <InputError v-if="errors?.title" :message="errors.title" />
            </div>

            <!-- Содержимое -->
            <div class="space-y-2">
              <Label for="content">Содержимое</Label>
              <textarea
                id="content"
                v-model="form.content"
                rows="15"
                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                placeholder="Введите содержимое страницы в формате Markdown..."
                :class="{ 'border-destructive': errors?.content }"
              />
                          <InputError v-if="errors?.content" :message="errors.content" />
            <p class="text-xs text-muted-foreground">
              Поддерживается формат Markdown
            </p>
            
            <!-- Предварительный просмотр -->
            <MarkdownPreview :content="form.content" />
            </div>

            <!-- Кнопки -->
            <div class="flex items-center gap-4">
              <Button type="submit" :disabled="processing">
                {{ processing ? 'Сохранение...' : (currentDraft ? 'Обновить черновик' : 'Создать черновик') }}
              </Button>
              <Button v-if="currentDraft" type="button" @click="approveDraft" variant="default">
                Утвердить черновик
              </Button>
              <Button type="button" variant="outline" @click="cancel">
                Отмена
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import Button from '@/components/ui/button/Button.vue'
import Card from '@/components/ui/card/Card.vue'
import CardContent from '@/components/ui/card/CardContent.vue'
import CardHeader from '@/components/ui/card/CardHeader.vue'
import CardTitle from '@/components/ui/card/CardTitle.vue'
import CardDescription from '@/components/ui/card/CardDescription.vue'
import Input from '@/components/ui/input/Input.vue'
import Label from '@/components/ui/label/Label.vue'
import InputError from '@/components/InputError.vue'
import MarkdownPreview from '@/components/MarkdownPreview.vue'

interface Page {
  id: number
  title: string
  content: string
  current: boolean
}

interface Draft {
  id: number
  title: string
  content: string
  created_at: string
  updated_at: string
}

const props = withDefaults(defineProps<{
  page: Page
  currentDraft?: Draft
  hasActiveDraft?: boolean
  errors?: Record<string, string>
}>(), {
  errors: () => ({}),
  hasActiveDraft: false,
})

const form = useForm({
  title: props.currentDraft?.title || props.page?.title || '',
  content: props.currentDraft?.content || props.page?.content || '',
})

const processing = ref(false)

const submit = () => {
  processing.value = true
  form.put(route('pages.update', props.page?.id), {
    onSuccess: () => {
      processing.value = false
    },
    onError: () => {
      processing.value = false
    },
  })
}

const continueDraft = () => {
  if (props.currentDraft) {
    form.title = props.currentDraft.title
    form.content = props.currentDraft.content
  }
}

const createNewDraft = () => {
  form.title = props.page.title
  form.content = props.page.content
}

const approveDraft = () => {
  if (props.currentDraft) {
    router.post(route('pages.draft.approve', props.currentDraft.id))
  }
}

const cancel = () => {
  router.visit(route('pages.show', props.page?.id))
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleString('ru-RU')
}
</script>
