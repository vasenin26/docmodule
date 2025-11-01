<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <Label>Прикрепленные файлы</Label>
            <Button @click.prevent="addFile" variant="outline" size="sm">
                <Plus class="mr-2 h-4 w-4" />
                Добавить файл
            </Button>
        </div>

        <div v-if="files.length > 0" class="space-y-2">
            <div v-for="(file, index) in files" :key="index" class="flex items-center gap-2 rounded-lg border p-3">
                <Input
                    v-model="files[index]"
                    placeholder="https://github.com/user/repo/blob/main/src/file.ext"
                    class="flex-1"
                    @blur="validateFile(index)"
                />
                <Button @click="removeFile(index)" variant="outline" size="sm">
                    <Trash2 class="h-4 w-4" />
                </Button>
            </div>
        </div>

        <p v-else class="text-sm text-muted-foreground">Файлы не прикреплены</p>

        <p class="text-xs text-muted-foreground">Добавьте прямые ссылки на файлы в git репозиториях (GitHub, GitLab, Bitbucket)</p>
    </div>
</template>

<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import { Plus, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps<{
    modelValue: string[];
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string[]];
}>();

const files = ref<string[]>([...props.modelValue]);

watch(
    files,
    (newFiles) => {
        emit('update:modelValue', newFiles);
    },
    { deep: true },
);

const addFile = () => {
    files.value.push('');
};

const removeFile = (index: number) => {
    files.value.splice(index, 1);
};

const validateFile = (index: number) => {
    const file = files.value[index];

    // Валидация URL
    if (file && !isValidUrl(file)) {
        console.warn('Ссылка должна быть корректным URL');
    }

    // Проверка на git хостинг
    if (file && !isGitRepositoryUrl(file)) {
        console.warn('Ссылка должна вести на файл в git репозитории (GitHub, GitLab, Bitbucket)');
    }
};

const isValidUrl = (url: string): boolean => {
    try {
        new URL(url);
        return true;
    } catch {
        return false;
    }
};

const isGitRepositoryUrl = (url: string): boolean => {
    const gitHosts = ['github.com', 'gitlab.com', 'bitbucket.org'];
    try {
        const parsedUrl = new URL(url);
        return gitHosts.some((host) => parsedUrl.host.includes(host));
    } catch {
        return false;
    }
};
</script>
