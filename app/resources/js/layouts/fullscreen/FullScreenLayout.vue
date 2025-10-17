<script setup lang="ts">

import ProfileInfo from '@/components/ProfileInfo.vue';
import ProjectToggle from '@/components/ProjectToggle.vue';
</script>

<template>
    <div class="app-fullscreen">
        <div class="header bg-card shadow-sm">
            <div class="title">
                <ProjectToggle/>
            </div>
            <div class="context-actions">
                <slot name="header" />
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
    }

    .app-fullscreen {
        padding-top: $header-height;
    }

    .body {
        padding: 15px;
    }
</style>
