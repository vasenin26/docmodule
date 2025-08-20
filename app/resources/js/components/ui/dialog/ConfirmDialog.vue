<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="sm:max-w-[425px]">
      <DialogHeader>
        <DialogTitle>{{ title }}</DialogTitle>
        <DialogDescription v-if="description" class="space-y-2">
          <div v-html="description"></div>
          
          <!-- Слот для дополнительного контента -->
          <slot name="content"></slot>
          
          <!-- Предупреждение, если передано -->
          <div v-if="warning" class="mt-3 p-3 bg-destructive/10 border border-destructive/20 rounded-md">
            <div class="flex items-start space-x-2">
              <svg class="w-5 h-5 text-destructive mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
              <div>
                <p class="font-medium text-destructive">{{ warningTitle || 'Внимание!' }}</p>
                <p class="text-sm text-destructive/80 mt-1" v-html="warning"></p>
              </div>
            </div>
          </div>
        </DialogDescription>
      </DialogHeader>
      
      <DialogFooter class="gap-2">
        <Button 
          variant="outline" 
          @click="handleCancel"
          :disabled="loading"
        >
          {{ cancelText || 'Отмена' }}
        </Button>
        <Button 
          :variant="confirmVariant || 'default'" 
          @click="handleConfirm"
          :disabled="loading"
        >
          <span v-if="loading" class="mr-2">
            <!-- Spinner -->
            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </span>
          {{ confirmText || 'Подтвердить' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

interface Props {
  open: boolean;
  title: string;
  description?: string;
  warning?: string;
  warningTitle?: string;
  confirmText?: string;
  cancelText?: string;
  confirmVariant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link';
  loading?: boolean;
}

interface Emits {
  (e: 'update:open', value: boolean): void;
  (e: 'confirm'): void;
  (e: 'cancel'): void;
}

const props = withDefaults(defineProps<Props>(), {
  confirmText: 'Подтвердить',
  cancelText: 'Отмена',
  confirmVariant: 'default',
  loading: false,
});

const emit = defineEmits<Emits>();

const isOpen = ref(props.open);

// Синхронизация с родительским компонентом
watch(() => props.open, (newValue) => {
  isOpen.value = newValue;
});

watch(isOpen, (newValue) => {
  emit('update:open', newValue);
});

const handleConfirm = () => {
  emit('confirm');
};

const handleCancel = () => {
  isOpen.value = false;
  emit('cancel');
};
</script>
