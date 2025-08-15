<template>
  <Card>
    <CardHeader>
      <CardTitle>Технический план</CardTitle>
    </CardHeader>
    <CardContent>
      <div v-if="techplane">
        <!-- Содержимое техплана -->
        <div v-if="techplane.content" class="mb-4">
          <div class="prose prose-sm max-w-none">
            <MarkdownRenderer :content="techplane.content" />
          </div>
        </div>
        <div v-else class="text-muted-foreground italic mb-4">
          Техплан создан, но содержимое еще не сгенерировано
        </div>
        
        <!-- Кнопки действий -->
        <div class="flex gap-3">
          <Button 
            v-if="techplane.generation_status !== 'generating'"
            @click="restartGeneration" 
            :disabled="isRestartingGeneration" 
            variant="outline"
          >
            <span v-if="isRestartingGeneration">Перезапуск...</span>
            <span v-else>Сгенерировать заново</span>
          </Button>
          <Button
            as-child
            variant="default"
          >
            <Link :href="route('techplanes.show', techplane.id)">
              Открыть техплан
            </Link>
          </Button>
        </div>
      </div>
      <div v-else>
        <!-- Кнопка создания техплана -->
        <Button
          as-child
          variant="default"
        >
          <Link 
            :href="route('tasks.create-techplane', taskId)" 
            method="post"
            as="button"
          >
            Создать технический план
          </Link>
        </Button>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import MarkdownRenderer from '@/components/MarkdownRenderer.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

interface TechplaneData {
  id: number;
  content: string | null;
  generation_status: string;
  created_at: string;
  creator: {
    id: number;
    name: string;
  };
}

interface Props {
  techplane?: TechplaneData | null;
  taskId: number;
}

const props = defineProps<Props>();

const isRestartingGeneration = ref<boolean>(false);

// Функция перезапуска генерации техплана
const restartGeneration = async () => {
  if (!props.techplane || isRestartingGeneration.value) {
    return;
  }

  isRestartingGeneration.value = true;

  try {
    const response = await fetch(route('techplanes.restart-generation', props.techplane.id), {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      credentials: 'same-origin',
    });

    if (response.ok) {
      const data = await response.json();
      if (data.success) {
        // Можно показать уведомление или обновить состояние
        console.log('Генерация техплана перезапущена');
        // Перезагрузить страницу чтобы увидеть обновленный статус
        window.location.reload();
      }
    } else {
      console.error('Ошибка при перезапуске генерации техплана:', response.status, response.statusText);
    }
  } catch (error) {
    console.error('Ошибка при перезапуске генерации техплана:', error);
  } finally {
    isRestartingGeneration.value = false;
  }
};
</script>
