<template>
  <AppLayout :title="`Редактировать агента - ${agent.name}`">
    <div class="mx-auto max-w-2xl">
      <div class="mb-6">
        <Heading>Редактировать агента</Heading>
        <p class="mt-2 text-muted-foreground">Измените информацию об агенте {{ agent.name }}</p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Информация об агенте</CardTitle>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="updateAgent" class="space-y-4">
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
            </div>

            <div class="flex items-center justify-between pt-4">
              <Button type="button" variant="outline" @click="$inertia.visit(route('projects.agents.index', project.id))">
                Отмена
              </Button>
              <div class="flex items-center gap-2">
                <Button type="button" variant="destructive" @click="deleteAgent">
                  Удалить
                </Button>
                <Button type="submit" :disabled="form.processing">
                  <Icon v-if="form.processing" name="loader-2" class="mr-2 h-4 w-4 animate-spin" />
                  {{ form.processing ? 'Сохранение...' : 'Сохранить' }}
                </Button>
              </div>
            </div>
          </form>
        </CardContent>
      </Card>

      <!-- JWT Токен -->
      <Card class="mt-6">
        <CardHeader>
          <CardTitle>JWT Токен</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-2">
            <Label>Токен агента</Label>
            <div class="flex items-center space-x-2">
              <Input
                :value="agent.token || 'Токен не найден'"
                readonly
                class="font-mono text-sm"
                :type="showToken ? 'text' : 'password'"
                :id="`token-${agent.id}`"
              />
              <Button
                type="button"
                variant="outline"
                size="sm"
                @click="toggleTokenVisibility"
              >
                <Icon :name="showToken ? 'eye-off' : 'eye'" class="h-4 w-4" />
              </Button>
              <Button
                type="button"
                variant="outline"
                size="sm"
                @click="copyToken"
                :disabled="!agent.token"
              >
                <Icon name="copy" class="h-4 w-4" />
              </Button>
            </div>
            <p class="text-sm text-muted-foreground">
              {{ agent.token ? 'Используйте этот токен для аутентификации агента в API' : 'Токен не был сгенерирован. Попробуйте регенерировать токен.' }}
            </p>
          </div>

          <div class="border-t pt-4">
            <div class="flex items-center justify-between">
              <div>
                <h4 class="text-sm font-medium">Регенерация токена</h4>
                <p class="text-sm text-muted-foreground">
                  Создайте новый токен, если текущий был скомпрометирован
                </p>
              </div>
              <Button
                type="button"
                variant="destructive"
                size="sm"
                @click="regenerateToken"
                :disabled="isRegenerating"
              >
                <Icon v-if="isRegenerating" name="loader-2" class="mr-2 h-4 w-4 animate-spin" />
                {{ isRegenerating ? 'Регенерация...' : 'Регенерировать' }}
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Публичный ключ -->
      <Card class="mt-6">
        <CardHeader>
          <CardTitle>Публичный ключ</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-2">
            <Label>Публичный ключ агента</Label>
            <div v-if="agent.public_key" class="flex items-center space-x-2">
              <Input
                :value="agent.public_key"
                readonly
                class="font-mono text-sm"
                :type="showPublicKey ? 'text' : 'password'"
                :id="`public-key-${agent.id}`"
              />
              <Button
                type="button"
                variant="outline"
                size="sm"
                @click="togglePublicKeyVisibility"
              >
                <Icon :name="showPublicKey ? 'eye-off' : 'eye'" class="h-4 w-4" />
              </Button>
              <Button
                type="button"
                variant="outline"
                size="sm"
                @click="copyPublicKey"
              >
                <Icon name="copy" class="h-4 w-4" />
              </Button>
            </div>
            <div v-else class="rounded-md border border-dashed p-4 text-center">
              <p class="text-sm text-muted-foreground">
                Публичный ключ еще не получен. Регистрация агента в оркестраторе выполняется в фоновом режиме.
              </p>
              <Button
                type="button"
                variant="outline"
                size="sm"
                class="mt-2"
                @click="refreshPage"
              >
                <Icon name="refresh-cw" class="mr-2 h-4 w-4" />
                Обновить страницу
              </Button>
            </div>
            <p class="text-sm text-muted-foreground">
              Передайте публичный ключ в сторонние сервисы для аутентификации агента.
            </p>
          </div>
        </CardContent>
      </Card>

      <!-- Информация о проекте -->
      <Card class="mt-6">
        <CardHeader>
          <CardTitle>Информация о проекте</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium">Проект</span>
              <Link :href="route('projects.show', project.id)" class="text-sm text-primary hover:underline">
                {{ project.title }}
              </Link>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium">ID агента</span>
              <span class="text-sm text-muted-foreground">{{ agent.id }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium">Создан</span>
              <span class="text-sm text-muted-foreground">{{ formatDate(agent.created_at) }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium">Обновлен</span>
              <span class="text-sm text-muted-foreground">{{ formatDate(agent.updated_at) }}</span>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import Heading from '@/components/Heading.vue'
import Icon from '@/components/Icon.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'

interface Agent {
  id: number
  name: string
  token: string
  public_key: string | null
  created_at: string
  updated_at: string
}

interface Project {
  id: number
  title: string
}

interface Props {
  project: Project
  agent: Agent
}

const props = defineProps<Props>()

const form = useForm({
  name: props.agent.name,
})

const showToken = ref(false)
const isRegenerating = ref(false)
const showPublicKey = ref(false)

const updateAgent = () => {
  form.put(route('projects.agents.update', [props.project.id, props.agent.id]))
}

const deleteAgent = () => {
  if (confirm(`Вы уверены, что хотите удалить агента "${props.agent.name}"?`)) {
    router.delete(route('projects.agents.destroy', [props.project.id, props.agent.id]))
  }
}

const toggleTokenVisibility = () => {
  showToken.value = !showToken.value
}

const copyToken = async () => {
  if (!props.agent.token) {
    alert('Токен не найден')
    return
  }
  
  try {
    await navigator.clipboard.writeText(props.agent.token)
    alert('Токен скопирован в буфер обмена')
  } catch (err) {
    console.error('Ошибка копирования токена:', err)
    alert('Ошибка копирования токена')
  }
}

const regenerateToken = () => {
  if (confirm('Вы уверены, что хотите регенерировать токен? Старый токен станет недействительным.')) {
    isRegenerating.value = true
    
    router.post(route('projects.agents.regenerate-token', [props.project.id, props.agent.id]), {}, {
      onFinish: () => {
        isRegenerating.value = false
      }
    })
  }
}

const togglePublicKeyVisibility = () => {
  showPublicKey.value = !showPublicKey.value
}

const copyPublicKey = async () => {
  if (!props.agent.public_key) {
    alert('Публичный ключ не найден')
    return
  }
  
  try {
    await navigator.clipboard.writeText(props.agent.public_key)
    alert('Публичный ключ скопирован в буфер обмена')
  } catch (err) {
    console.error('Ошибка копирования публичного ключа:', err)
    alert('Ошибка копирования публичного ключа')
  }
}

const refreshPage = () => {
  router.reload()
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('ru-RU', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>