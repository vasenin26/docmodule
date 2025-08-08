<template>
  <AppLayout title="Создать страницу">
    <template #header>
      <div class="flex items-center justify-between">
        <Heading title="Создать страницу" />
        <Button as-child variant="outline">
          <Link :href="route('pages.index')">
            Назад к списку
          </Link>
        </Button>
      </div>
    </template>

    <div class="max-w-4xl">
      <Card>
        <CardHeader>
          <CardTitle>Новая страница</CardTitle>
          <CardDescription>
            Создайте новую страницу документации
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-6">
            <!-- Родительская страница -->
            <div v-if="parentPage" class="p-4 bg-muted/50 rounded-lg">
              <p class="text-sm text-muted-foreground mb-2">Родительская страница:</p>
              <p class="font-medium">{{ parentPage.title }}</p>
            </div>

            <!-- Название -->
            <div class="space-y-2">
              <Label for="title">Название страницы *</Label>
              <Input
                id="title"
                v-model="form.title"
                placeholder="Введите название страницы"
                :class="{ 'border-destructive': errors.title }"
              />
              <InputError v-if="errors.title" :message="errors.title" />
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
                :class="{ 'border-destructive': errors.content }"
              />
                          <InputError v-if="errors.content" :message="errors.content" />
            <p class="text-xs text-muted-foreground">
              Поддерживается формат Markdown
            </p>
            
            <!-- Предварительный просмотр -->
            <MarkdownPreview :content="form.content" />
            </div>

            <!-- Скрытое поле для parent_id -->
            <input v-if="parentPage" type="hidden" name="parent_id" :value="parentPage.id" />

            <!-- Кнопки -->
            <div class="flex items-center gap-4">
              <Button type="submit" :disabled="processing">
                {{ processing ? 'Создание...' : 'Создать страницу' }}
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

interface ParentPage {
  id: number
  title: string
}

const props = withDefaults(defineProps<{
  parentPage?: ParentPage
  errors?: Record<string, string>
}>(), {
  errors: () => ({}),
})

const form = useForm({
  title: '',
  content: '',
  parent_id: props.parentPage?.id || null,
})

const processing = ref(false)

const submit = () => {
  processing.value = true
  form.post(route('pages.store'), {
    onSuccess: () => {
      processing.value = false
    },
    onError: () => {
      processing.value = false
    },
  })
}

const cancel = () => {
  router.visit(route('pages.index'))
}
</script>
