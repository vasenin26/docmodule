<script setup lang="ts">
export type Patch = {
    id: number;
    title: string;
};

defineProps<{
    items: Patch[]
    selected: number|null
}>();

const emit = defineEmits<{
    (e: 'select', id: number);
}>();

function onSelect(item: Patch): void {
    emit('select', item.id)
}

</script>

<template>
    <div class="flex h-full flex-col flex-nowrap overflow-hidden rounded-lg border bg-white">
        <div class="border-b bg-gray-100 px-4 py-3">
            <h3 class="text-sm font-medium text-gray-800">Доступные патчи</h3>
        </div>
        <div class="menu-list">
            <div class="list">
                <div class="patch node" v-for="item in items" :key="item.id" @click="onSelect(item)"
                     :class="{selected: item.id === selected}"
                     :title="item.title"
                >
                    {{ item.title }}
                </div>
                <div v-if="items.length === 0">
                    Ничего нет
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
.menu-list {
    padding: 20px;
}
.node {
    cursor: pointer;
    width: 100%;
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
    &.selected{
        font-weight: bold;
    }
}
</style>
