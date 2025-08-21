<template>
    <Card>
        <CardHeader>
            <CardTitle>Дочерние страницы</CardTitle>
            <CardDescription> Страницы, связанные с текущей </CardDescription>
        </CardHeader>
        <CardContent>
            <div v-if="children && children.length > 0" class="mb-4 space-y-2">
                <div
                    v-for="child in children"
                    :key="child.id"
                    class="flex items-center justify-between rounded-lg border p-3 hover:bg-muted/50"
                >
                    <div>
                        <Link :href="route('pages.show', child.id)" class="font-medium hover:underline">
                            {{ child.current_version.title }}
                        </Link>
                        <p class="text-sm text-muted-foreground">Создано {{ formatDate(child.created_at) }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button as-child size="sm" variant="outline">
                            <Link :href="route('pages.show', child.id)"> Просмотр </Link>
                        </Button>
                        <Button as-child size="sm" variant="outline">
                            <Link :href="route('pages.edit', child.id)"> Редактировать </Link>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Форма создания дочерней страницы -->
            <CreateChildPage :parent-id="parentId" />
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
import CreateChildPage from '@/components/CreateChildPage.vue';
import { Link } from '@inertiajs/vue3';

interface Creator {
    name: string;
}

export interface ChildPage {
    id: number;
    current_version: {
        id: number
        title: string
        content: string
    }
    created_at: string;
    creator?: Creator;
}

interface Props {
    children?: ChildPage[];
    parentId: number;
}

withDefaults(defineProps<Props>(), {
    children: () => [],
});

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>
