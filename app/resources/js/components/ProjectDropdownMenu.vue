<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="outline" size="sm">
                <Icon name="MoreHorizontal" class="h-4 w-4" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuItem as-child>
                <Link :href="route('projects.edit', projectId)" class="flex items-center">
                    <Icon name="edit" class="mr-2 h-4 w-4" />
                    Редактировать
                </Link>
            </DropdownMenuItem>
            <DropdownMenuItem as-child>
                <Link :href="route('projects.prompts.index', projectId)" class="flex items-center">
                    <Icon name="MessageSquare" class="mr-2 h-4 w-4" />
                    Настроить промпты
                </Link>
            </DropdownMenuItem>
            <DropdownMenuItem as-child>
                <Link :href="route('projects.agents.index', projectId)" class="flex items-center">
                    <Icon name="bot" class="mr-2 h-4 w-4" />
                    Агенты
                </Link>
            </DropdownMenuItem>
            <DropdownMenuItem as-child>
                <Link :href="route('terminals.index')" class="flex items-center">
                    <Icon name="Terminal" class="mr-2 h-4 w-4" />
                    Терминалы
                </Link>
            </DropdownMenuItem>
            <DropdownMenuItem as-child>
                <Link :href="route('projects.agent-tasks.index', projectId)" class="flex items-center">
                    <Icon name="play" class="mr-2 h-4 w-4" />
                    Задачи агентов
                </Link>
            </DropdownMenuItem>
            <DropdownMenuItem as-child>
                <Link :href="route('projects.generation-models.index', projectId)" class="flex items-center">
                    <Icon name="Settings" class="mr-2 h-4 w-4" />
                    Настройки генерации
                </Link>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import Icon from '@/components/Icon.vue';

interface Props {
    projectId: number;
    projectTitle: string;
}

const props = defineProps<Props>();

const deleteProject = () => {
    if (confirm(`Вы уверены, что хотите удалить проект "${props.projectTitle}"? Все страницы проекта также будут удалены.`)) {
        router.delete(route('projects.destroy', props.projectId));
    }
};
</script>
