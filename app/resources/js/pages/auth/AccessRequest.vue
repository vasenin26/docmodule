<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const form = useForm({
    full_name: '',
    contact: '',
    organization: '',
    message: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('message'),
    });
};
</script>

<template>
    <AuthBase title="Request access" description="Fill the form and admins will contact you">
        <Head title="Request access" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="full_name">Full name</Label>
                    <Input id="full_name" type="text" required autofocus :tabindex="1" autocomplete="name" v-model="form.full_name" placeholder="Full name" />
                    <InputError :message="form.errors.full_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="contact">Контактные данные</Label>
                    <Input id="contact" type="text" required :tabindex="2" v-model="form.contact" placeholder="Email или телефон" />
                    <InputError :message="form.errors.contact" />
                </div>

                <div class="grid gap-2">
                    <Label for="organization">Organization</Label>
                    <Input id="organization" type="text" :tabindex="3" v-model="form.organization" placeholder="Organization" />
                    <InputError :message="form.errors.organization" />
                </div>

                <div class="grid gap-2">
                    <Label for="message">Message</Label>
                    <textarea id="message" v-model="form.message" rows="6" class="mt-1 block w-full border rounded p-2" placeholder="Tell us about your needs"></textarea>
                    <InputError :message="form.errors.message" />
                </div>

                <Button type="submit" class="mt-2 w-full" tabindex="5" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Send request
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink :href="route('login')" class="underline underline-offset-4" :tabindex="6">Log in</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
