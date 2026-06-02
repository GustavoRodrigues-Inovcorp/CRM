<script setup>
import Checkbox from '@/Components/Checkbox.vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'
import { Sun, Moon } from 'lucide-vue-next'

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

/* Tema */
const { isDark, toggleTheme } = useTheme()

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout :noCard="true">
        <Head title="Iniciar sessão" />

        <div class="relative ml-auto w-full max-w-[460px] pr-2 sm:pr-4 lg:pr-6 h-full flex flex-col">
            <div class="space-y-8">
                <div class="space-y-3 pt-10 sm:pt-14">
                    <h1 class="text-[34px] font-semibold uppercase tracking-tight text-foreground sm:text-[38px] pb-16">
                        Inovcorp CRM
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Insira os seus dados para iniciar sessão
                    </p>
                </div>

                <div v-if="status" class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300">
                    {{ status }}
                </div>

                <form class="space-y-6" @submit.prevent="submit">
                    <div class="space-y-2">
                        <InputLabel for="email" />

                        <input
                            id="email"
                            type="email"
                            class="block w-full border-b border-base bg-transparent px-0 pb-2 text-sm text-primary focus:outline-none placeholder:text-primary/70"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="Email"
                        />

                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div class="space-y-2">
                        <InputLabel for="password" />

                        <input
                            id="password"
                            type="password"
                            class="block w-full border-b border-base bg-transparent px-0 pb-2 text-sm text-primary focus:outline-none placeholder:text-primary/70"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            placeholder="Palavra-passe"
                        />

                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Checkbox name="remember" v-model:checked="form.remember" />
                            <span>Lembrar-me</span>
                        </label>

                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="ml-auto text-sm text-muted-foreground hover:text-primary"
                        >
                            Esqueci-me da palavra-passe
                        </Link>
                    </div>

                    <PrimaryButton
                        class="w-full justify-center rounded-xl py-3 text-sm font-semibold tracking-wide"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Entrar
                    </PrimaryButton>
                </form>
            </div>

            <!-- Footer específico da página de login -->
            <footer class="mt-auto text-xs text-muted-foreground">
                <p>Copyright © Inovcorp CRM {{ new Date().getFullYear() }}</p>
            </footer>
        </div>
    </GuestLayout>
</template>
