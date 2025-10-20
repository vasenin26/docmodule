<template>
    <div class="relative">
        <!-- Trigger Button -->
        <Button
            ref="triggerRef"
            variant="outline"
            role="combobox"
            :aria-expanded="open"
            aria-haspopup="listbox"
            class="w-full justify-between"
            @click="toggle"
        >
            <span class="truncate">
                {{ selectedModel ? formatModelName(selectedModel) : placeholder }}
            </span>
            <Icon 
                :name="open ? 'chevron-up' : 'chevron-down'" 
                class="ml-2 h-4 w-4 shrink-0 opacity-50" 
            />
        </Button>

        <!-- Dropdown Content -->
        <div
            v-if="open"
            class="absolute z-50 w-full mt-1 bg-popover text-popover-foreground border rounded-md shadow-lg"
        >
            <!-- Search Input -->
            <div class="p-2 border-b">
                <Input
                    ref="searchInputRef"
                    v-model="searchQuery"
                    placeholder="Поиск модели..."
                    class="h-8"
                    @keydown.escape="close"
                    @keydown.down.prevent="navigateDown"
                    @keydown.up.prevent="navigateUp"
                    @keydown.enter.prevent="selectHighlighted"
                />
            </div>

            <!-- Options List -->
            <div class="max-h-60 overflow-auto">
                <div
                    v-if="filteredModels.length === 0"
                    class="p-2 text-sm text-muted-foreground text-center"
                >
                    Модели не найдены
                </div>
                <div
                    v-for="(model, index) in filteredModels"
                    :key="model.id"
                    :class="[
                        'flex items-center justify-between p-3 cursor-pointer transition-colors',
                        highlightedIndex === index ? 'bg-accent' : 'hover:bg-accent/50',
                        selectedModel?.id === model.id ? 'bg-accent' : ''
                    ]"
                    @click="selectModel(model)"
                >
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-sm truncate">{{ model.name }}</div>
                        <div class="flex justify-between  mt-1">
                            <div class="text-xs text-muted-foreground">
                                Контекст: {{ formatNumber(model.context_size) }} токенов
                            </div>
                            <div v-if="model.price_in !== null && model.price_out !== null" class="text-xs text-muted-foreground">
                                {{ formatMoney(model.price_in) }}₽ / {{ formatMoney(model.price_out) }}₽
                            </div>
                        </div>
                    </div>
                    <div v-if="selectedModel?.id === model.id" class="ml-2">
                        <Icon name="check" class="h-4 w-4" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import Icon from '@/components/Icon.vue';

interface GenerationModel {
    id: number;
    name: string;
    context_size: number;
    price_in: number | string | null;
    price_out: number | string | null;
}

interface Props {
    models: GenerationModel[];
    selectedModelId: number | null;
    placeholder?: string;
    formatNumbers?: boolean; // форматирование с разделителями тысяч
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Выберите модель...',
    formatNumbers: true,
});

const emit = defineEmits<{
    (e: 'update:selectedModelId', value: number | null): void;
}>();

const open = ref(false);
const searchQuery = ref('');
const highlightedIndex = ref(-1);
const triggerRef = ref<HTMLElement>();
const searchInputRef = ref<HTMLElement>();

// Computed
const selectedModel = computed(() => 
    props.models.find(m => m.id === props.selectedModelId) || null
);

const filteredModels = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.models;
    }
    
    const query = searchQuery.value.toLowerCase();
    return props.models.filter(model => 
        model.name.toLowerCase().includes(query) ||
        model.context_size.toString().includes(query)
    );
});

// Methods
const formatNumber = (num: number | string) => {
    if (!props.formatNumbers) return String(num);
    const value = typeof num === 'string' ? Number.parseFloat(num) : num;
    if (Number.isNaN(value)) return String(num);
    return value.toLocaleString('ru-RU');
};

const formatMoney = (num: number | string) => {
    const value = typeof num === 'string' ? Number.parseFloat(num) : num;
    if (Number.isNaN(value)) return String(num);
    return new Intl.NumberFormat('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value);
};

const formatModelName = (model: GenerationModel) => {
    const priceInfo = model.price_in !== null && model.price_out !== null 
        ? ` • ${formatMoney(model.price_in)}₽/${formatMoney(model.price_out)}₽` 
        : '';
    return `${model.name} (${formatNumber(model.context_size)}${priceInfo})`;
};

const toggle = () => {
    open.value = !open.value;
    if (open.value) {
        nextTick(() => {
            searchInputRef.value?.focus();
        });
    }
};

const close = () => {
    open.value = false;
    searchQuery.value = '';
    highlightedIndex.value = -1;
};

const selectModel = (model: GenerationModel) => {
    emit('update:selectedModelId', model.id);
    close();
};

const selectHighlighted = () => {
    if (highlightedIndex.value >= 0 && highlightedIndex.value < filteredModels.value.length) {
        selectModel(filteredModels.value[highlightedIndex.value]);
    }
};

const navigateDown = () => {
    if (highlightedIndex.value < filteredModels.value.length - 1) {
        highlightedIndex.value++;
    }
};

const navigateUp = () => {
    if (highlightedIndex.value > 0) {
        highlightedIndex.value--;
    }
};

// Click outside to close
const handleClickOutside = (event: MouseEvent) => {
    if (open.value && triggerRef.value && !triggerRef.value.contains(event.target as Node)) {
        close();
    }
};

// Watchers
watch(open, (newOpen) => {
    if (newOpen) {
        document.addEventListener('click', handleClickOutside);
    } else {
        document.removeEventListener('click', handleClickOutside);
    }
});

watch(filteredModels, () => {
    highlightedIndex.value = -1;
});

// Lifecycle
onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>
