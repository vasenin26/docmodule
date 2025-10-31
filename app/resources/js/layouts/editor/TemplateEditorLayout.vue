<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  /**
   * CSS значение ширины для sidebar. Пример: '280px' или '18rem'.
   * Рекомендуемое значение по умолчанию: '280px'.
   */
  sidebarWidth?: string;
  /**
   * CSS значение ширины для assistant. Пример: '360px'.
   * Рекомендуемое значение по умолчанию: '360px'.
   */
  assistantWidth?: string;
}

const props = withDefaults(defineProps<Props>(), {
  sidebarWidth: '280px',
  assistantWidth: '360px',
});

const cssVars = computed(() => ({
  '--sidebar-width': props.sidebarWidth,
  '--assistant-width': props.assistantWidth,
} as Record<string, string>));
</script>

<template>
  <!--
    TemplateEditorLayout

    Slots:
      - sidebar:   левый фиксированный блок (навигация, инструменты).
      - default:   центральная область редактора (гибкая, занимает оставшееся пространство).
      - assistant: правый фиксированный блок (помощник, настройки).

    Рекомендации:
      - Вложенные компоненты внутри слотов должны заполнять высоту контейнера и сами обеспечивать прокрутку.
        Для этого внутри каждой колонки есть wrapper `.tpl-slot-inner` с `height:100%` и `overflow:auto`.
      - По умолчанию ширины: sidebar 280px, assistant 360px. Может быть переопределено через props.
      - Высота layout рассчитана как min-height: calc(100vh - 46px - 30px) — это учитывает header 46px (FullScreenLayout)
        и body padding 15px сверху/снизу. При изменении header/padding — скорректировать формулу.
  -->

  <div class="template-editor-layout" :style="cssVars">
    <aside class="tpl-sidebar" aria-label="Template sidebar">
      <div class="tpl-slot-inner">
        <slot name="sidebar" />
      </div>
    </aside>

    <main class="tpl-editor" aria-label="Template editor">
      <div class="tpl-slot-inner">
        <slot />
      </div>
    </main>

    <aside class="tpl-assistant" aria-label="Template assistant">
      <div class="tpl-slot-inner">
        <slot name="assistant" />
      </div>
    </aside>
  </div>
</template>

<style scoped lang="scss">
/*
  Рекомендации по ширинам (по умолчанию):
    --sidebar-width: 280px
    --assistant-width: 360px

  Подстраховка: дочерние колонки имеют min-width: 0 чтобы избежать overflow внутри grid.
*/

.template-editor-layout {
  display: grid;
  grid-template-columns: var(--sidebar-width) 1fr var(--assistant-width);
  gap: 16px;
  align-items: stretch;

  /*
    Высота: вычитаем header + body padding (см. FullScreenLayout)
    46px — header height in FullScreenLayout
    30px — body vertical padding (15px top + 15px bottom)
  */
  min-height: calc(100vh - 46px - 30px);
  /* Backup: allow using parent-provided height as well */
  height: 100%;
}

/* Колонки должны позволять внутренним элементам прокручиваться. */
.tpl-sidebar,
.tpl-editor,
.tpl-assistant {
  min-width: 0; /* important for content truncation in grid */
  display: block;
}

/* Внутренний wrapper: предоставляет поведене для прокрутки содержимого */
.tpl-slot-inner {
  height: 100%;
  box-sizing: border-box;
  overflow: auto; /* enable internal scrolling */
  -webkit-overflow-scrolling: touch;
  padding: 0; /* padding по желанию — можно использовать utility-классы */
}

/* Sticky behavior для боковых панелей, если нужно: */
.tpl-sidebar .tpl-slot-inner,
.tpl-assistant .tpl-slot-inner {
  /* Если нужно закрепить содержимое при скролле страницы (вместо внутреннего скролла),
     можно заменить overflow и использовать `position: sticky` на внешнем элементе.
     Здесь по умолчанию — внутренний скролл. */
}

/* Мобильная адаптация: стек колонок вертикально на узких экранах */
@media (max-width: 767px) {
  .template-editor-layout {
    grid-template-columns: 1fr;
    grid-auto-rows: min-content;
  }
}
</style>
