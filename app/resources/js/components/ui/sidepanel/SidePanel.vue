<script setup lang="ts">
import {Button} from "@/components/ui/button";

const model = defineModel<boolean>('open');
</script>

<template>
    <Teleport to="body">
    <div class="panel-wrapper"
         :class="{
        closed: !model
         }"
    >
        <div class="panel-header">
            <span>Чат</span>
            <Button
                  @click="model = false"
            >Закрыть</Button>
        </div>
        <div class="pane-body">
            <slot></slot>
        </div>
    </div>
    </Teleport>
</template>

<style scoped lang="scss">
    $width: 700px;
    .panel-wrapper {
        position: fixed;
        right: 0;
        top: 0;
        bottom: 0;
        width: $width;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 0 15px rgba(0, 0, 0, .6);
        display: flex;
        flex-direction: column;
        flex-wrap: nowrap;
        transition: ease-in 200ms;
        justify-content: stretch;
        &.closed {
            width: 0;
        }
    }

    .panel-header {
        padding: 10px;
        display: flex;
        width: $width;
        justify-content: space-between;
        align-items: center;
        span {
            font-size: 24px;
        }
    }
    .pane-body {
        flex: 1;
        min-height: 0;
        padding: 10px;
        width: $width;
    }
</style>
