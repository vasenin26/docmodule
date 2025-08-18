<template>
    <Card v-if="files && files.length > 0">
        <CardHeader>
            <CardTitle>Прикрепленные файлы</CardTitle>
            <CardDescription> Файлы, связанные с данной страницей документации </CardDescription>
        </CardHeader>
        <CardContent>
            <div class="space-y-2">
                <div v-for="(file, index) in files" :key="index" class="flex items-center gap-3 rounded-lg border p-3 hover:bg-muted/50">
                    <FileIcon class="h-5 w-5 text-muted-foreground" />
                    <div class="flex-1">
                        <p class="font-mono text-sm break-all">{{ getFileName(file) }}</p>
                        <p class="text-xs break-all text-muted-foreground">{{ file }}</p>
                    </div>
                    <Button as-child variant="outline" size="sm" v-if="isValidRepositoryUrl(file)">
                        <a :href="file" target="_blank" rel="noopener noreferrer"> Открыть файл </a>
                    </Button>
                </div>
            </div>
        </CardContent>
    </Card>
</template>

<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import { FileIcon } from 'lucide-vue-next';

interface Props {
    files?: string[];
}

const props = withDefaults(defineProps<Props>(), {
    files: () => [],
});

const isValidRepositoryUrl = (url: string): boolean => {
    try {
        new URL(url);
        return true;
    } catch {
        return false;
    }
};

const getFileName = (url: string): string => {
    try {
        const urlObj = new URL(url);
        const pathParts = urlObj.pathname.split('/');
        return pathParts[pathParts.length - 1] || 'Файл';
    } catch {
        return 'Файл';
    }
};
</script>
