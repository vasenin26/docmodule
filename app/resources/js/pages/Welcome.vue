<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';

const features = [
  {
    title: 'Версионирование страниц',
    description: 'Управляйте документацией без страха потерять важное',
  },
  {
    title: 'Актуализация документации',
    description: 'Держите документацию в актуальном состоянии',
  },
  {
    title: 'Задачи на основе изменений',
    description: 'Формируйте задачу как только готово описание',
  },
  {
    title: 'Техплан для проведения работ',
    description: 'Генерируйте техплан отражающий требования проекта',
  },
  {
    title: 'Актуализация кодовой базы для соответствия документации',
    description: 'Вносите изменения в проект нажатием одной кнопки',
  },
];
</script>

<template>
  <Head title="Welcome">
    <link rel="preconnect" href="https://rsms.me/" />
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
  </Head>

  <div class="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a]">
    <!-- Header: сохраняем текущую логику -->
    <header class="w-full px-6 pt-6 pb-4 lg:px-8">
      <nav class="max-w-4xl mx-auto flex items-center justify-end gap-4 text-sm">
        <Link
          v-if="$page.props.auth.user"
          :href="route('dashboard')"
          class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
        >
          Dashboard
        </Link>
        <template v-else>
          <Link
            :href="route('login')"
            class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
          >
            Log in
          </Link>
          <Link
            :href="route('register')"
            class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
          >
            Register
          </Link>
        </template>
      </nav>
    </header>

    <!-- Hero -->
    <section
      class="relative flex items-center justify-center w-full bg-cover bg-center"
      :style="{ backgroundImage: `url('/images/welcome-hero-placeholder.jpg')` }"
    >
      <div class="absolute inset-0 bg-gradient-to-b from-black/50 via-black/30 to-transparent" />
      <div class="relative z-10 max-w-4xl w-full px-6 py-28 text-center lg:py-40">
        <h1 class="text-white font-extrabold text-3xl sm:text-4xl md:text-5xl lg:text-6xl leading-tight">
          Новая эра управления проектами.
        </h1>
      </div>
      <!-- ensure hero occupies most of viewport on large screens -->
      <div style="height: calc(100vh - 88px);" class="hidden lg:block" />
    </section>

    <!-- Features -->
    <section class="w-full max-w-6xl mx-auto px-6 py-12 lg:py-20">
      <div class="mx-auto text-center mb-8">
        <h2 class="text-2xl font-semibold">Особенности</h2>
        <p class="text-muted-foreground mt-2">Краткий набор возможностей системы</p>
      </div>

      <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
        <Card v-for="(f, i) in features" :key="i" class="min-h-[120px]">
          <CardHeader>
            <CardTitle class="text-base">{{ f.title }}</CardTitle>
          </CardHeader>
          <CardDescription class="px-6 pb-6">{{ f.description }}</CardDescription>
        </Card>
      </div>

      <div class="mt-8 flex justify-center">
        <Link
          href="#"
          class="inline-flex items-center justify-center rounded-md bg-primary px-6 py-3 text-white font-semibold shadow-sm hover:brightness-95"
        >
          погнали!
        </Link>
      </div>
    </section>
  </div>
</template>

<style scoped>
/* Scoped styles can be added if necessary, but prefer Tailwind classes. */
</style>
