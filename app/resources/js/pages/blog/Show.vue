<script setup lang="ts">
import MarkdownRenderer from '@/components/MarkdownRenderer.vue';
import { Head, Link } from '@inertiajs/vue3';

interface BlogPost {
    slug: string;
    title: string;
    description: string;
    keywords: string[];
    canonical: string;
    content: string;
    updated_at: string;
    updated_at_human: string;
    published_at: string | null;
    published_at_human: string | null;
}

defineProps<{
    post: BlogPost;
}>();
</script>

<template>
    <Head :title="post.title">
        <meta name="description" :content="post.description" />
        <meta v-if="post.keywords.length > 0" name="keywords" :content="post.keywords.join(', ')" />
        <link v-if="post.canonical" rel="canonical" :href="post.canonical" />
    </Head>

    <main class="min-h-screen bg-background">
        <article class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
            <div class="mb-8">
                <Link :href="route('blog.index')" class="text-sm text-primary hover:underline">
                    ← К списку публикаций
                </Link>
                <h1 class="mt-4 text-3xl font-bold tracking-tight">{{ post.title }}</h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    <span v-if="post.published_at_human">Опубликовано: {{ post.published_at_human }} · </span>
                    Обновлено: {{ post.updated_at_human }}
                </p>
            </div>

            <div class="rounded-xl border bg-card p-6 shadow-sm">
                <MarkdownRenderer :content="post.content" />
            </div>
        </article>
    </main>
</template>
