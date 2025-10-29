<script setup lang="ts">

import ProfileInfo from '@/components/ProfileInfo.vue';
import ProjectToggle from '@/components/ProjectToggle.vue';
import ProjectMenu from '@/components/ProjectMenu.vue';
</script>

<template>
    <div class="app-fullscreen">
        <div class="header bg-card shadow-sm gap-2">
            <div class="title">
                <ProjectToggle/>
            </div>
            <div class="project-menu">
                <ProjectMenu/>
            </div>
            <div class="context-actions flex items-center gap-2">
                <slot name="context-actions" />
            </div>
            <div class="profile">
                <ProfileInfo />
            </div>
        </div>
        <div class="body">
            <slot></slot>
        </div>
    </div>
</template>

<style scoped lang="scss">

    $header-height: 46px;
    $main-padding: 15px;

    .header {
        height: $header-height;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        position: fixed;
        left: 0;
        top: 0;
        right: 0;
        padding-right: $main-padding;
        padding-left: $main-padding;
        z-index: 100;
        .profile {
            flex-grow: 1;
            display: flex;
            justify-content: end;
        }
    }

    .app-fullscreen {
        padding-top: $header-height;
    }

    .body {
        padding: 15px;
    }
</style>
