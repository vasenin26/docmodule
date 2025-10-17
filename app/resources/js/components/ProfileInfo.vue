<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { DropdownMenu, DropdownMenuContent, DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';
import { computed } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import { LogOut, Settings } from 'lucide-vue-next';

const page = usePage();
const user = page.props.auth.user as User;

const { getInitials } = useInitials();

// Compute whether we should show the avatar image
const showAvatar = computed(() => user.avatar && user.avatar !== '');

const handleLogout = () => {
    router.flushAll();
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <div class="flex items-center gap-2 cursor-pointer hover:bg-accent rounded-md p-1">
                <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
                    <AvatarImage v-if="showAvatar" :src="user.avatar!" :alt="user.name" />
                    <AvatarFallback class="rounded-lg text-black dark:text-white">
                        {{ getInitials(user.name) }}
                    </AvatarFallback>
                </Avatar>
                <div class="grid flex-1 text-left text-sm leading-tight">
                    <span class="truncate font-medium">{{ user.name }}</span>
                    <span class="truncate text-xs text-muted-foreground">{{ user.email }}</span>
                </div>
            </div>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-56">
            <DropdownMenuLabel class="p-0 font-normal">
                <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                    <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
                        <AvatarImage v-if="showAvatar" :src="user.avatar!" :alt="user.name" />
                        <AvatarFallback class="rounded-lg text-black dark:text-white">
                            {{ getInitials(user.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="grid flex-1 text-left text-sm leading-tight">
                        <span class="truncate font-medium">{{ user.name }}</span>
                        <span class="truncate text-xs text-muted-foreground">{{ user.email }}</span>
                    </div>
                </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
                <DropdownMenuItem :as-child="true">
                    <Link class="block w-full" :href="route('profile.edit')" prefetch as="button">
                        <Settings class="mr-2 h-4 w-4" />
                        Настройки
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <DropdownMenuItem :as-child="true">
                <Link class="block w-full" method="post" :href="route('logout')" @click="handleLogout" as="button">
                    <LogOut class="mr-2 h-4 w-4" />
                    Выйти
                </Link>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

<style scoped>

</style>
