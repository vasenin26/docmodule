<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

interface BlogPost {
    slug: string;
    title: string;
    description: string;
    preview: string;
    keywords: string[];
    updated_at: string;
    updated_at_human: string;
    published_at: string | null;
    published_at_human: string | null;
}

defineProps<{
    posts: BlogPost[];
}>();
</script>

<template>
    <Head title="Blog" />

    <main class="min-h-screen bg-background">
        <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
            <div class="mb-8 flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Blog</h1>
                    <p class="mt-2 text-muted-foreground">Публичные заметки и обновления проекта.</p>
                </div>
                <Link href="/" class="text-sm text-primary hover:underline">На главную</Link>
            </div>

            <div v-if="posts.length === 0" class="rounded-lg border border-dashed p-8 text-center text-muted-foreground">
                Пока нет публикаций. Добавьте `.md` файлы в `storage/app/blog`.
            </div>

            <div v-else class="space-y-4">
                <article
                    v-for="post in posts"
                    :key="post.slug"
                    class="rounded-xl border bg-card p-5 shadow-sm transition hover:shadow"
                >
                    <div class="mb-2 text-xs uppercase text-muted-foreground">
                        <span v-if="post.published_at_human">Опубликовано: {{ post.published_at_human }} · </span>
                        {{ post.updated_at_human }}
                    </div>
                    <h2 class="text-xl font-semibold">
                        <Link :href="route('blog.show', { slug: post.slug })" class="hover:underline">
                            {{ post.title }}
                        </Link>
                    </h2>
                    <p class="mt-2 text-sm text-muted-foreground">
                        {{ post.preview }}
                    </p>
                </article>
            </div>
        </div>
    </main>
</template>
