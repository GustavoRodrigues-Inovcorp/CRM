<script setup>
/**
 * Dashboard.vue
 * Página principal após login.
 * Mostra KPIs, pipeline resumido e atividade recente.
 * Os dados virão do backend via props quando os módulos estiverem completos.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { TrendingUp, TrendingDown, Briefcase, Building2, Users, Activity } from 'lucide-vue-next'

/* ─── KPIs estáticos — serão dinâmicos quando o backend estiver pronto ─── */
const kpis = [
    {
        label: 'Pipeline Total',
        value: '€0',
        change: '+0%',
        up: true,
        icon: Briefcase,
        accent: 'text-blue-400',
        bg: 'bg-blue-500/10',
    },
    {
        label: 'Negócios Ganhos',
        value: '0',
        change: '+0%',
        up: true,
        icon: TrendingUp,
        accent: 'text-emerald-400',
        bg: 'bg-emerald-500/10',
    },
    {
        label: 'Taxa de Conversão',
        value: '0%',
        change: '+0%',
        up: true,
        icon: Activity,
        accent: 'text-amber-400',
        bg: 'bg-amber-500/10',
    },
    {
        label: 'Novas Entidades',
        value: '0',
        change: '+0',
        up: true,
        icon: Building2,
        accent: 'text-blue-400',
        bg: 'bg-blue-500/10',
    },
]
</script>

<template>
    <Head title="Dashboard" />

    <!-- Sem botão "Novo" no dashboard — não contextual aqui -->
    <AuthenticatedLayout>
        <template #title>Dashboard</template>

        <div class="p-6 space-y-5">

            <!-- ── KPIs ── -->
            <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
                <Card
                    v-for="kpi in kpis"
                    :key="kpi.label"
                    class="border-border bg-card"
                >
                    <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                        <CardTitle class="text-[11px] font-medium text-muted-foreground uppercase tracking-wide">
                            {{ kpi.label }}
                        </CardTitle>
                        <!-- Ícone colorido por categoria -->
                        <div :class="['w-7 h-7 rounded-lg flex items-center justify-center', kpi.bg]">
                            <component :is="kpi.icon" :class="['w-3.5 h-3.5', kpi.accent]" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold text-foreground tracking-tight leading-none mb-1.5">
                            {{ kpi.value }}
                        </p>
                        <!-- Indicador de tendência -->
                        <div class="flex items-center gap-1">
                            <TrendingUp v-if="kpi.up"   class="w-3 h-3 text-emerald-400" />
                            <TrendingDown v-else         class="w-3 h-3 text-red-400" />
                            <span class="text-[11px]" :class="kpi.up ? 'text-emerald-400' : 'text-red-400'">
                                {{ kpi.change }} este mês
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- ── Painéis inferiores ── -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

                <!-- Pipeline resumido -->
                <Card class="xl:col-span-2 border-border bg-card">
                    <CardHeader class="flex flex-row items-center justify-between pb-3">
                        <CardTitle class="text-sm font-semibold">Pipeline de Negócios</CardTitle>
                        <Link
                            :href="route('deals.index')"
                            class="text-[11px] text-primary hover:text-primary/80 transition-colors"
                        >
                            Ver tudo →
                        </Link>
                    </CardHeader>
                    <CardContent>
                        <!-- Placeholder até o módulo Negócios estar completo -->
                        <div class="flex items-center justify-center py-12 rounded-lg border border-dashed border-border">
                            <div class="text-center">
                                <Briefcase class="w-8 h-8 text-muted-foreground/30 mx-auto mb-2" />
                                <p class="text-[12px] text-muted-foreground">Nenhum negócio ainda</p>
                                <Link
                                    :href="route('deals.index')"
                                    class="text-[11px] text-primary hover:underline mt-1 inline-block"
                                >
                                    Criar primeiro negócio →
                                </Link>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Atividade recente -->
                <Card class="border-border bg-card">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-semibold">Atividade Recente</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <!-- Placeholder até o módulo de atividades estar completo -->
                        <div class="flex items-center justify-center py-8 rounded-lg border border-dashed border-border">
                            <div class="text-center">
                                <Activity class="w-6 h-6 text-muted-foreground/30 mx-auto mb-2" />
                                <p class="text-[11px] text-muted-foreground">Sem atividade recente</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

        </div>
    </AuthenticatedLayout>
</template>