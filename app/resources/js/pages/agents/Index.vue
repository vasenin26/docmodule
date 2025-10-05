<template>
  <AppLayout :title="`Агенты - ${project.title}`">
    <div class="space-y-6">
      <!-- Заголовок и действия -->
      <div class="flex items-center justify-between">
        <div>
          <Heading>Агенты проекта</Heading>
          <p class="mt-1 text-muted-foreground">{{ project.title }} • {{ agents.data?.length || 0 }} агентов</p>
        </div>
        <Button as-child>
          <Link :href="route('projects.agents.create', project.id)">
            <Icon name="plus" class="mr-2 h-4 w-4" />
            Создать агента
          </Link>
        </Button>
      </div>

      <!-- Статистика -->
      <div class="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader>
            <CardTitle class="text-sm font-medium">Всего агентов</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ agents.data?.length || 0 }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle class="text-sm font-medium">Активных</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ agents.data?.length || 0 }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle class="text-sm font-medium">Задачи агентов</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-sm space-y-1">
              <div><span class="text-muted-foreground">В ожидании:</span> {{ taskSummary?.waiting ?? 0 }}</div>
              <div><span class="text-muted-foreground">В работе:</span> {{ taskSummary?.processing ?? 0 }}</div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Список агентов -->
      <div>
        <div v-if="agents.data && agents.data.length > 0">
          <Card>
            <CardContent class="p-0">
              <div class="overflow-x-auto">
                <table class="w-full">
                  <thead class="border-b bg-muted/50">
                    <tr>
                      <th class="p-4 text-left font-medium">Агент</th>
                      <th class="p-4 text-left font-medium">Статус</th>
                      <th class="p-4 text-left font-medium">Создан</th>
                      <th class="p-4 text-left font-medium">Действия</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="agent in agents.data" :key="agent.id" class="border-b hover:bg-muted/50">
                      <td class="p-4">
                        <div class="flex items-center gap-3">
                          <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                            <Icon name="bot" class="h-4 w-4 text-blue-600" />
                          </div>
                          <div>
                            <div class="font-medium">{{ agent.name }}</div>
                            <div class="text-sm text-muted-foreground">ID: {{ agent.id }}</div>
                          </div>
                        </div>
                      </td>
                      <td class="p-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                          <Icon name="check-circle" class="h-3 w-3 mr-1" />
                          Активен
                        </span>
                      </td>
                      <td class="p-4 text-sm text-muted-foreground">
                        {{ formatDate(agent.created_at) }}
                      </td>
                      <td class="p-4">
                        <div class="flex items-center gap-2">
                          <Button as-child variant="outline" size="sm">
                            <Link :href="route('projects.agents.edit', [project.id, agent.id])" class="flex items-center">
                              <Icon name="edit" class="mr-2 h-4 w-4" />
                              Редактировать
                            </Link>
                          </Button>
                          <Button variant="destructive" size="sm" @click="deleteAgent(agent)">
                            <Icon name="trash-2" class="mr-2 h-4 w-4" />
                            Удалить
                          </Button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>

          <!-- Пагинация -->
          <div v-if="agents.links && agents.links.length > 3" class="mt-4 flex justify-center">
            <nav class="flex items-center gap-1">
              <Link
                v-for="link in agents.links"
                :key="link.label"
                :href="link.url"
                :class="[
                  'rounded-md px-3 py-2 text-sm',
                  link.active ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:text-foreground',
                ]"
                v-html="link.label"
              />
            </nav>
          </div>
        </div>

        <!-- Пустое состояние -->
        <div v-else>
          <Card>
            <CardContent class="py-12 text-center">
              <div class="mx-auto mb-4 h-12 w-12 text-muted-foreground">
                <Icon name="bot" class="h-full w-full" />
              </div>
              <h4 class="mb-2 text-lg font-semibold">Нет агентов</h4>
              <p class="mb-4 text-muted-foreground">Создайте первого агента для этого проекта</p>
              <Button @click="$inertia.visit(route('projects.agents.create', project.id))">
                <Icon name="plus" class="mr-2 h-4 w-4" />
                Создать агента
              </Button>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import Heading from '@/components/Heading.vue'
import Icon from '@/components/Icon.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
// import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import AppLayout from '@/layouts/AppLayout.vue'

interface Agent {
  id: number
  name: string
  created_at: string
  updated_at: string
}

interface Project {
  id: number
  title: string
}

interface AgentsData {
  data: Agent[]
  links: any[]
}

interface TaskSummary {
  total: number
  waiting: number
  processing: number
  success: number
  failed: number
  lastCreatedAt: string | null
}

interface Props {
  project: Project
  agents: AgentsData
  taskSummary: TaskSummary
}

const props = defineProps<Props>()

const deleteAgent = (agent: Agent) => {
  if (confirm(`Вы уверены, что хотите удалить агента "${agent.name}"?`)) {
    router.delete(route('projects.agents.destroy', [props.project.id, agent.id]))
  }
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