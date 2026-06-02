<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'

defineProps({
    status: {
        type: String,
    },
    email: {
        type: String,
    },
})

const { isDark, toggleTheme } = useTheme()

const form = useForm({
    code: '',
})

const submit = () => {
    form.post(route('two-factor.verify'), {
        onFinish: () => form.reset('code'),
    })
}
</script>

<template>
    <GuestLayout :noCard="true">
        <Head title="Verificação 2FA" />

        <div class="relative ml-auto flex h-full w-full max-w-[460px] flex-col pr-2 sm:pr-4 lg:pr-6">
            <div class="space-y-8">
                <div class="space-y-3 pt-10 sm:pt-14">
                    <h1 class="pb-16 text-[34px] font-semibold uppercase tracking-tight text-foreground sm:text-[38px]">
                        Inovcorp CRM
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Introduz o código do teu autenticador para continuar.
                    </p>
                </div>

                <div v-if="status" class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300">
                    {{ status }}
                </div>

                <div class="rounded-2xl border border-base bg-card/70 px-4 py-3 text-sm text-muted-foreground">
                    Conta: <span class="font-medium text-foreground">{{ email }}</span>
                </div>

                <form class="space-y-6" @submit.prevent="submit">
                    <div class="space-y-2">
                        <InputLabel for="code" />

                        <input
                            id="code"
                            type="text"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            maxlength="6"
                            class="block w-full border-b border-base bg-transparent px-0 pb-2 text-sm text-primary focus:outline-none placeholder:text-primary/70"
                            v-model="form.code"
                            required
                            autofocus
                            placeholder="Código"
                        />

                        <InputError class="mt-2" :message="form.errors.code" />
                    </div>

                    <PrimaryButton
                        class="w-full justify-center rounded-xl py-3 text-sm font-semibold tracking-wide"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Verificar
                    </PrimaryButton>
                </form>

                <div class="flex items-center justify-between text-sm text-muted-foreground">
                    <span>Sem acesso ao código?</span>
                    <Link :href="route('logout')" method="post" as="button" class="hover:text-primary">
                        Terminar sessão
                    </Link>
                </div>
            </div>

            <footer class="mt-auto text-xs text-muted-foreground">
                <p>Copyright © Inovcorp CRM {{ new Date().getFullYear() }}</p>
            </footer>
        </div>
    </GuestLayout>
</template>