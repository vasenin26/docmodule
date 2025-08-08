<template>
  <AppLayout title="Версии страницы">
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <Heading>Версии страницы</Heading>
          <p class="text-sm text-muted-foreground mt-1">
            {{ page.title }}
          </p>
        </div>
        <div class="flex items-center gap-2">
          <Button as-child variant="outline">
            <Link :href="route('pages.show', page.id)">
              Просмотр страницы
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

    <div class="max-w-4xl space-y-6">
      <!-- Текущая версия -->
      <Card>
        <CardHeader>
          <CardTitle>Текущая версия</CardTitle>
          <CardDescription>
            Активная версия страницы
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div
              v-for="version in currentVersions"
              :key="version.id"
              class="p-4 border rounded-lg bg-green-50 border-green-200"
            >
              <div class="flex items-center justify-between">
                <div>
                  <h3 class="font-medium">{{ version.title }}</h3>
                  <p class="text-sm text-muted-foreground">
                    Создано {{ formatDate(version.created_at) }} пользователем {{ version.creator?.name }}
                  </p>
                </div>
                <div class="flex items-center gap-2">
                  <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">
                    Текущая
                  </span>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- История версий -->
      <Card>
        <CardHeader>
          <CardTitle>История версий</CardTitle>
          <CardDescription>
            Все версии страницы в хронологическом порядке
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div
              v-for="version in versions"
              :key="version.id"
              class="p-4 border rounded-lg hover:bg-muted/50"
            >
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <h3 class="font-medium">{{ version.title }}</h3>
                  <p class="text-sm text-muted-foreground">
                    Создано {{ formatDate(version.created_at) }} пользователем {{ version.creator?.name }}
                  </p>
                  <div v-if="version.content" class="mt-2 text-sm text-muted-foreground">
                    {{ truncateContent(version.content) }}
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <Button
                    size="sm"
                    variant="outline"
                    @click="restoreVersion(version.id)"
                    :disabled="version.current"
                  >
                    {{ version.current ? 'Текущая' : 'Восстановить' }}
                  </Button>
                  <Button as-child size="sm" variant="outline">
                    <Link :href="route('pages.show', version.id)">
                      Просмотр
                    </Link>
                  </Button>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Статистика -->
      <Card>
        <CardHeader>
          <CardTitle>Статистика версий</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-3 gap-4 text-sm">
            <div>
              <span class="font-medium">Всего версий:</span>
              <span class="text-muted-foreground ml-2">{{ versions.length }}</span>
            </div>
            <div>
              <span class="font-medium">Первая версия:</span>
              <span class="text-muted-foreground ml-2">{{ formatDate(oldestVersion?.created_at) }}</span>
            </div>
            <div>
              <span class="font-medium">Последняя версия:</span>
              <span class="text-muted-foreground ml-2">{{ formatDate(newestVersion?.created_at) }}</span>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import Button from '@/components/ui/button/Button.vue'
import Card from '@/components/ui/card/Card.vue'
import CardContent from '@/components/ui/card/CardContent.vue'
import CardHeader from '@/components/ui/card/CardHeader.vue'
import CardTitle from '@/components/ui/card/CardTitle.vue'
import CardDescription from '@/components/ui/card/CardDescription.vue'

interface Creator {
  name: string
}

interface Version {
  id: number
  title: string
  content: string
  created_at: string
  current: boolean
  creator: Creator
}

interface Page {
  id: number
  title: string
}

const props = defineProps<{
  page: Page
  versions: Version[]
}>()

const currentVersions = computed(() => {
  return props.versions.filter(version => version.current)
})

const oldestVersion = computed(() => {
  return props.versions.reduce((oldest, current) => {
    return new Date(current.created_at) < new Date(oldest.created_at) ? current : oldest
  })
})

const newestVersion = computed(() => {
  return props.versions.reduce((newest, current) => {
    return new Date(current.created_at) > new Date(newest.created_at) ? current : newest
  })
})

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('ru-RU', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const truncateContent = (content: string) => {
  return content.length > 150 ? content.substring(0, 150) + '...' : content
}

const restoreVersion = (versionId: number) => {
  if (confirm('Вы уверены, что хотите восстановить эту версию? Это создаст новую версию на основе выбранной.')) {
    router.post(route('pages.restore', [props.page.id, versionId]))
  }
}
</script>
