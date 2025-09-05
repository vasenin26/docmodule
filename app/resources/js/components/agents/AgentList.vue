<template>
  <div class="space-y-6">
    <!-- Поиск -->
    <Card>
      <CardContent class="p-4">
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <Icon name="search" class="h-5 w-5 text-gray-400" />
          </div>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Поиск агентов..."
            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            @input="onSearch"
          />
        </div>
      </CardContent>
    </Card>

    <!-- Список агентов -->
    <div v-if="filteredAgents.length > 0">
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
                <tr v-for="agent in filteredAgents" :key="agent.id" class="border-b hover:bg-muted/50">
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
                        <Link :href="route('projects.agents.edit', [projectId, agent.id])" class="flex items-center">
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
      <div v-if="pagination && pagination.links && pagination.links.length > 3" class="mt-4 flex justify-center">
        <nav class="flex items-center gap-1">
          <Link
            v-for="link in pagination.links"
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
          <h4 class="mb-2 text-lg font-semibold">
            {{ searchQuery ? 'Агенты не найдены' : 'Нет агентов' }}
          </h4>
          <p class="mb-4 text-muted-foreground">
            {{ searchQuery ? 'Попробуйте изменить поисковый запрос' : 'Начните с создания первого агента' }}
          </p>
          <Button v-if="!searchQuery" @click="$inertia.visit(route('projects.agents.create', projectId))">
            <Icon name="plus" class="mr-2 h-4 w-4" />
            Создать агента
          </Button>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import Icon from '@/components/Icon.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
// import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'

interface Agent {
  id: number
  name: string
  created_at: string
  updated_at: string
}

interface PaginationData {
  links: any[]
}

interface Props {
  agents: Agent[]
  projectId: number
  pagination?: PaginationData
}

const props = defineProps<Props>()

const searchQuery = ref('')

const filteredAgents = computed(() => {
  if (!searchQuery.value) {
    return props.agents
  }
  
  const query = searchQuery.value.toLowerCase()
  return props.agents.filter(agent => 
    agent.name.toLowerCase().includes(query) ||
    agent.id.toString().includes(query)
  )
})

const onSearch = () => {
  // Поиск происходит реактивно через computed свойство
}

const deleteAgent = (agent: Agent) => {
  if (confirm(`Вы уверены, что хотите удалить агента "${agent.name}"?`)) {
    router.delete(route('projects.agents.destroy', [props.projectId, agent.id]))
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