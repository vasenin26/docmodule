<script setup lang="ts">

import { usePage } from '@inertiajs/vue3';
import type { User } from '@/types';

const page = usePage();
const user = page.props.auth.user as User;
</script>

<template>
    <div class="user-profile-info">
        {{user.name}}
    </div>
</template>

<style scoped>

</style>
