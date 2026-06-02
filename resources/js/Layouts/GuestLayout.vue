<script setup>
import { computed, defineProps } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'
import { Sun, Moon } from 'lucide-vue-next'

/* Props: permite desativar o cartão envolvente (ex.: Login) */
const props = defineProps({
    noCard: { type: Boolean, default: false },
})

/* ─── Tema atual para escolher o logo correto ─── */
const { isDark, toggleTheme } = useTheme()

/* ─── Logo alterna entre versão clara e escura ─── */
const guestLogo = computed(() => '/inovcorp_dark.png')
</script>

<template>
    <div class="h-screen bg-background text-foreground lg:grid lg:grid-cols-[1.12fr_0.88fr] overflow-hidden">

        <!-- Theme toggle fixed top-right -->
        <button
            type="button"
            @click="toggleTheme"
            class="fixed top-4 right-4 z-50 rounded-lg border border-border bg-background p-2 text-muted-foreground shadow-sm hover:bg-accent"
            :title="isDark ? 'Mudar para claro' : 'Mudar para escuro'"
        >
            <Sun v-if="!isDark" class="h-4 w-4" />
            <Moon v-else class="h-4 w-4" />
        </button>
        <aside class="relative hidden overflow-hidden bg-slate-950 text-white lg:flex lg:flex-col">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(59,130,246,0.38),transparent_32%),radial-gradient(circle_at_bottom_left,rgba(15,23,42,0.85),transparent_40%),linear-gradient(135deg,#07111f_0%,#0b1728_50%,#08101c_100%)]" />
            <div class="absolute inset-0 opacity-[0.18] bg-[linear-gradient(rgba(255,255,255,0.08)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.08)_1px,transparent_1px)] bg-[size:28px_28px]" />

            <div class="relative flex h-full flex-col p-10 xl:p-12">
                <div class="flex items-center gap-3">
                    <Link href="/" class="inline-flex items-center gap-3">
                        <img :src="guestLogo" alt="Inovcorp CRM" class="h-8 w-8 object-contain" />
                        <div>
                            <p class="text-[11px] uppercase tracking-[0.35em] text-white/55">Inovcorp CRM</p>
                            <p class="text-sm text-white/80">Gestão comercial</p>
                        </div>
                    </Link>
                </div>

                <div class="flex flex-1 items-center">
                    <div class="max-w-xl space-y-5">
                        <p class="text-sm font-medium uppercase tracking-[0.3em] text-blue-200/70">Hub comercial</p>
                        <h1 class="text-4xl font-semibold tracking-tight text-white xl:text-5xl">
                            Organiza clientes, oportunidades e equipa num só lugar.
                        </h1>
                        <p class="max-w-lg text-base leading-7 text-white/70">
                            Um CRM limpo, rápido e profissional para acompanhar o pipeline com clareza.
                        </p>
                    </div>
                </div>
            </div>
        </aside>

        <main :class="props.noCard ? 'flex h-screen items-stretch justify-start px-8 py-8 sm:px-10 lg:px-12' : 'flex h-screen items-center justify-center px-4 py-10 sm:px-6 lg:px-10'">
            <div class="w-full max-w-md">
                <div class="mb-6 flex items-center gap-3 lg:hidden">
                    <img :src="guestLogo" alt="Inovcorp CRM" class="h-8 w-8 object-contain" />
                    <div>
                        <p class="text-[11px] uppercase tracking-[0.3em] text-muted-foreground">Inovcorp CRM</p>
                        <p class="text-sm font-medium text-foreground">Gestão comercial</p>
                    </div>
                </div>

                <template v-if="props.noCard">
                    <slot />
                </template>
                <template v-else>
                    <div class="rounded-3xl border border-border/80 bg-card p-6 shadow-[0_20px_80px_rgba(15,23,42,0.12)] sm:p-8">
                        <slot />
                    </div>
                </template>
            </div>
        </main>
        </div>
</template>
