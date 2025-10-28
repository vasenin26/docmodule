<template>
    <div>
        <ckeditor
            v-model="internalValue"
            :editor="ClassicEditor"
            :config="config"
        />
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { ClassicEditor, Essentials, Paragraph, Strikethrough, BlockQuote, CodeBlock, Bold, Italic, Heading, List, Link } from 'ckeditor5';
import { Ckeditor } from '@ckeditor/ckeditor5-vue';

import 'ckeditor5/ckeditor5.css';
interface Props {
    modelValue?: string | null;
    placeholder?: string;
}
const props = defineProps<Props>();
const emit = defineEmits(['update:modelValue']);
const internalValue = computed<string>({
    get: () => props.modelValue ?? '',
    set: (v: string) => emit('update:modelValue', v),
});



const config = computed( () => {
    return {
        licenseKey: 'GPL', // Or 'GPL'.
        plugins: [ Essentials, Paragraph, Bold, Italic, Heading, List, Strikethrough, BlockQuote, CodeBlock, Link ],
        toolbar: {
            items: [
                'undo', 'redo',
                '|',
                'heading',
                '|',
                'bold', 'italic', 'strikethrough',
                '|',
                'link', 'blockQuote', 'codeBlock',
                '|',
                'bulletedList', 'numberedList'
            ],
            shouldNotGroupWhenFull: false
        }

    };
} );
</script>

<style scoped>
/* Add any minimal styling if needed */
</style>
