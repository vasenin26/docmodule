<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { Primitive, type PrimitiveProps } from 'reka-ui';
import { type ButtonVariants, buttonVariants } from '.';
import { router } from '@inertiajs/vue3';

interface Props extends PrimitiveProps {
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
    class?: HTMLAttributes['class'];
    link?: string;
}

const props = withDefaults(defineProps<Props>(), {
    as: 'button'
});

function onClick() {
    if (props.link) {
        router.visit(props.link)
    }
}
</script>

<template>
    <Primitive
        data-slot="button"
        :as="as"
        :as-child="asChild"
        :class="cn(buttonVariants({ variant, size }), props.class)"
        @click="onClick"
    >
        <slot />
    </Primitive>
</template>
