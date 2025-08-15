<template>
  <AppLayout>
    <div class="container mx-auto py-8 px-4">
      <!-- Заголовок -->
      <div class="flex justify-between items-start mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">
            Технический план
          </h1>
          <p class="text-gray-600 mt-2">
            Создан: {{ formatDate(techplane.created_at) }} • 
            Автор: {{ techplane.creator.name }}
          </p>
        </div>
        <div class="flex gap-3">
          <!-- Кнопка экспорта (заглушка) -->
          <Button variant="outline" disabled>
            Экспортировать
          </Button>
          <!-- Кнопка чата (если есть) -->
          <Button
            v-if="techplane.llm_chat"
            @click="openChatModal"
            variant="default"
          >
            Чат
          </Button>
          <!-- Кнопка возврата к задаче -->
          <Button as-child variant="outline">
            <Link :href="route('tasks.show', techplane.task.id)">
              К задаче
            </Link>
          </Button>
        </div>
      </div>

      <!-- Метаинформация -->
      <Card class="mb-6">
        <CardHeader>
          <CardTitle>Информация о техплане</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label class="text-sm font-medium text-gray-500">ID техплана</Label>
              <p class="text-sm">{{ techplane.id }}</p>
            </div>
            <div>
              <Label class="text-sm font-medium text-gray-500">Связанная задача</Label>
              <p class="text-sm">
                <Link :href="route('tasks.show', techplane.task.id)" class="text-blue-600 hover:underline">
                  Задача #{{ techplane.task.id }}
                </Link>
              </p>
            </div>
            <div>
              <Label class="text-sm font-medium text-gray-500">Страница</Label>
              <p class="text-sm">{{ techplane.task.page.title }}</p>
            </div>
            <div>
              <Label class="text-sm font-medium text-gray-500">Статус</Label>
              <p class="text-sm">{{ techplane.generation_status }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Содержимое техплана -->
      <Card>
        <CardHeader>
          <CardTitle>Описание техплана</CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="techplane.content" class="prose max-w-none">
            <MarkdownRenderer :content="techplane.content" />
          </div>
          <div v-else class="text-gray-500 italic">
            Содержимое техплана пока не сгенерировано
          </div>
        </CardContent>
      </Card>

      <!-- Модальное окно чата -->
      <Dialog v-model:open="showChatModal">
        <DialogContent class="max-w-4xl max-h-[80vh]">
          <DialogHeader>
            <DialogTitle>Чат LLM</DialogTitle>
          </DialogHeader>
          <div class="overflow-hidden">
            <AgentChat 
              v-if="techplane.llm_chat"
              :chat="techplane.llm_chat"
              :readonly="true"
            />
          </div>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import MarkdownRenderer from '@/components/MarkdownRenderer.vue'
import AgentChat from '@/components/AgentChat/AgentChat.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps(['techplane'])

const showChatModal = ref(false)

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleString('ru-RU', {
    year: 'numeric',
    month: 'long', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const openChatModal = () => {
  showChatModal.value = true
}
</script>
