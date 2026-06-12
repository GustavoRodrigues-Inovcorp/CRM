<script setup>
/**
 * Dashboard.vue
 * Página principal — KPIs reais, pipeline, atividade recente e próximos eventos.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Badge } from '@/Components/ui/badge'
import {
    TrendingUp, TrendingDown, Briefcase,
    Building2, Activity, Phone, Users,
    Mail, CheckCircle2, FileText, Calendar,
    MapPin, Clock, Sparkles, AlertTriangle,
    Info, ArrowRight,
} from 'lucide-vue-next'

/* ─── Props vindas do DashboardController ─── */
const props = defineProps({
    stats:          Object,
    dealsByStage:   Array,
    recentDeals:    Array,
    recentActivity: Array,
    upcomingEvents: Array,
    aiSuggestions:  Array,
})

/* ─── Configuração dos estágios ─── */
const stageConfig = {
    lead:        { label: 'Lead',        color: '#6366f1' },
    proposal:    { label: 'Proposta',    color: '#f59e0b' },
    negotiation: { label: 'Negociação',  color: '#8b5cf6' },
    follow_up:   { label: 'Follow Up',   color: '#3b82f6' },
    won:         { label: 'Ganho',       color: '#10b981' },
    lost:        { label: 'Perdido',     color: '#ef4444' },
}

/* ─── Ícones por tipo de atividade ─── */
const activityIcons = {
    call:    Phone,
    meeting: Users,
    email:   Mail,
    task:    CheckCircle2,
    note:    FileText,
}

const activityColors = {
    call:    'bg-emerald-500/10 text-emerald-500',
    meeting: 'bg-blue-500/10 text-blue-500',
    email:   'bg-amber-500/10 text-amber-500',
    task:    'bg-violet-500/10 text-violet-500',
    note:    'bg-gray-500/10 text-gray-400',
}

/* ─── Formata valor em euros ─── */
function formatEuro(v) {
    return new Intl.NumberFormat('pt-PT', {
        style: 'currency', currency: 'EUR', maximumFractionDigits: 0,
    }).format(v || 0)
}

/* ─── Formata data relativa ─── */
function formatRelative(d) {
    const diff = Math.floor((Date.now() - new Date(d)) / 60000)
    if (diff < 60)  return `há ${diff}min`
    if (diff < 1440) return `há ${Math.floor(diff / 60)}h`
    return `há ${Math.floor(diff / 1440)}d`
}

function formatEventDate(d) {
    return new Date(d).toLocaleDateString('pt-PT', {
        weekday: 'short', day: '2-digit',
        month: 'short', hour: '2-digit', minute: '2-digit',
    })
}

/* ─── KPIs com dados reais ─── */
const kpis = computed(() => [
    {
        label:  'Pipeline Total',
        value:  formatEuro(props.stats.pipeline),
        icon:   Briefcase,
        accent: 'text-primary',
        bg:     'bg-primary/10',
    },
    {
        label:  'Negócios Ganhos',
        value:  props.stats.wonThisMonth,
        sub:    'este mês',
        icon:   TrendingUp,
        accent: 'text-emerald-400',
        bg:     'bg-emerald-500/10',
    },
    {
        label:  'Taxa de Conversão',
        value:  props.stats.convRate + '%',
        icon:   Activity,
        accent: 'text-amber-400',
        bg:     'bg-amber-500/10',
    },
    {
        label:  'Novas Entidades',
        value:  props.stats.newEntities,
        sub:    'este mês',
        icon:   Building2,
        accent: 'text-blue-400',
        bg:     'bg-blue-500/10',
    },
])
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #title>Dashboard</template>

        <div class="p-6 space-y-5">

            <!-- ── KPIs ── -->
            <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
                <Card v-for="kpi in kpis" :key="kpi.label" class="border-border bg-card">
                    <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                        <CardTitle class="text-[11px] font-medium text-muted-foreground uppercase tracking-wide">
                            {{ kpi.label }}
                        </CardTitle>
                        <div :class="['w-7 h-7 rounded-lg flex items-center justify-center', kpi.bg]">
                            <component :is="kpi.icon" :class="['w-3.5 h-3.5', kpi.accent]" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold text-foreground tracking-tight leading-none mb-1">
                            {{ kpi.value }}
                        </p>
                        <p v-if="kpi.sub" class="text-[11px] text-muted-foreground">{{ kpi.sub }}</p>
                    </CardContent>
                </Card>
            </div>

            <!-- ── Linha do meio: Pipeline + Atividade ── -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

                <!-- Pipeline por estágio -->
                <Card class="xl:col-span-2 border-border bg-card">
                    <CardHeader class="flex flex-row items-center justify-between pb-3">
                        <CardTitle class="text-sm font-semibold">Pipeline de Negócios</CardTitle>
                        <Link :href="route('deals.index')" class="text-[11px] text-primary hover:underline">
                            Ver tudo →
                        </Link>
                    </CardHeader>
                    <CardContent>

                        <!-- Estado vazio -->
                        <div v-if="recentDeals.length === 0" class="flex flex-col items-center justify-center py-10 gap-2">
                            <Briefcase class="w-8 h-8 text-muted-foreground/20" />
                            <p class="text-xs text-muted-foreground">Sem negócios ainda</p>
                            <Link :href="route('deals.index')" class="text-xs text-primary hover:underline">
                                Criar primeiro negócio →
                            </Link>
                        </div>

                        <!-- Lista de negócios recentes -->
                        <div v-else class="space-y-2">
                            <Link
                                v-for="deal in recentDeals"
                                :key="deal.id"
                                :href="route('deals.show', deal.id)"
                                class="flex items-center justify-between p-3 rounded-lg hover:bg-accent/50 transition-colors group"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <div
                                        class="w-2 h-2 rounded-full flex-shrink-0"
                                        :style="{ background: stageConfig[deal.stage]?.color ?? '#6b7280' }"
                                    />
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-foreground truncate group-hover:text-primary transition-colors">
                                            {{ deal.title }}
                                        </p>
                                        <p v-if="deal.entity" class="text-xs text-muted-foreground truncate">
                                            {{ deal.entity.name }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 flex-shrink-0 ml-3">
                                    <span
                                        class="text-[10px] font-medium px-2 py-0.5 rounded-full"
                                        :style="{
                                            background: (stageConfig[deal.stage]?.color ?? '#6b7280') + '20',
                                            color: stageConfig[deal.stage]?.color ?? '#6b7280',
                                        }"
                                    >
                                        {{ stageConfig[deal.stage]?.label ?? deal.stage }}
                                    </span>
                                    <span class="text-sm font-semibold text-foreground">
                                        {{ formatEuro(deal.value) }}
                                    </span>
                                </div>
                            </Link>
                        </div>

                        <!-- Resumo por estágio -->
                        <div v-if="dealsByStage.length > 0" class="mt-4 pt-4 border-t border-border">
                            <div class="flex gap-3 flex-wrap">
                                <div
                                    v-for="stage in dealsByStage"
                                    :key="stage.stage"
                                    class="flex items-center gap-1.5"
                                >
                                    <div
                                        class="w-2 h-2 rounded-full"
                                        :style="{ background: stageConfig[stage.stage]?.color ?? '#6b7280' }"
                                    />
                                    <span class="text-xs text-muted-foreground">
                                        {{ stageConfig[stage.stage]?.label }} ({{ stage.count }})
                                    </span>
                                </div>
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
                        <div v-if="recentActivity.length === 0" class="flex flex-col items-center justify-center py-8 gap-2">
                            <Activity class="w-6 h-6 text-muted-foreground/20" />
                            <p class="text-xs text-muted-foreground">Sem atividade recente</p>
                        </div>
                        <div v-else class="space-y-3">
                            <div
                                v-for="item in recentActivity"
                                :key="item.id"
                                class="flex items-start gap-2.5"
                            >
                                <div
                                    class="w-6 h-6 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5"
                                    :class="activityColors[item.type] ?? 'bg-muted text-muted-foreground'"
                                >
                                    <component :is="activityIcons[item.type] ?? Activity" class="w-3 h-3" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium text-foreground truncate" :class="item.completed ? 'line-through text-muted-foreground' : ''">
                                        {{ item.title }}
                                    </p>
                                    <p class="text-[10px] text-muted-foreground truncate">
                                        {{ item.deal?.title }}
                                    </p>
                                </div>
                                <span class="text-[10px] text-muted-foreground/60 whitespace-nowrap flex-shrink-0">
                                    {{ formatRelative(item.created_at) }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- ── Agente Comercial AI ── -->
            <Card v-if="aiSuggestions?.length > 0" class="border-primary/20 bg-card">
                <CardHeader class="pb-3">
                    <div class="flex items-center justify-between">
                        <CardTitle class="text-sm font-semibold flex items-center gap-2">
                            <div class="w-6 h-6 rounded-lg bg-primary/10 flex items-center justify-center">
                                <Sparkles class="w-3.5 h-3.5 text-primary" />
                            </div>
                            Agente Comercial AI
                            <span class="text-[10px] text-muted-foreground font-normal">
                                — sugestões baseadas nos teus dados
                            </span>
                        </CardTitle>
                        <Link :href="route('chat.index')" class="text-[11px] text-primary hover:underline flex items-center gap-1">
                            Abrir chat
                            <ArrowRight class="w-3 h-3" />
                        </Link>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-3">
                        <Link
                            v-for="suggestion in aiSuggestions"
                            :key="suggestion.id"
                            :href="suggestion.link"
                            class="flex items-start gap-3 p-3 rounded-lg border transition-all hover:shadow-sm group"
                            :class="{
                                'border-amber-500/20 bg-amber-500/5 hover:bg-amber-500/10': suggestion.type === 'warning',
                                'border-blue-500/20 bg-blue-500/5 hover:bg-blue-500/10':   suggestion.type === 'info',
                                'border-emerald-500/20 bg-emerald-500/5 hover:bg-emerald-500/10': suggestion.type === 'success',
                            }"
                        >
                            <!-- Ícone -->
                            <div
                                class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5"
                                :class="{
                                    'bg-amber-500/20':   suggestion.type === 'warning',
                                    'bg-blue-500/20':    suggestion.type === 'info',
                                    'bg-emerald-500/20': suggestion.type === 'success',
                                }"
                            >
                                <AlertTriangle v-if="suggestion.type === 'warning'" class="w-4 h-4 text-amber-500" />
                                <Info          v-else-if="suggestion.type === 'info'" class="w-4 h-4 text-blue-500" />
                                <TrendingUp    v-else class="w-4 h-4 text-emerald-500" />
                            </div>

                            <!-- Conteúdo -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2 mb-0.5">
                                    <p class="text-xs font-semibold text-foreground">{{ suggestion.title }}</p>
                                    <span
                                        class="text-[9px] font-bold px-1.5 py-0.5 rounded-full flex-shrink-0"
                                        :class="{
                                            'bg-red-500/10 text-red-500':     suggestion.priority === 'high',
                                            'bg-amber-500/10 text-amber-500': suggestion.priority === 'medium',
                                        }"
                                    >
                                        {{ suggestion.priority === 'high' ? 'URGENTE' : 'MÉDIO' }}
                                    </span>
                                </div>
                                <p class="text-xs text-muted-foreground leading-relaxed">{{ suggestion.message }}</p>
                                <span class="text-[10px] text-primary group-hover:underline mt-1 inline-block">
                                    {{ suggestion.action }} →
                                </span>
                            </div>
                        </Link>
                    </div>
                </CardContent>
            </Card>

            <!-- ── Próximos eventos ── -->
            <Card v-if="upcomingEvents.length > 0" class="border-border bg-card">
                <CardHeader class="flex flex-row items-center justify-between pb-3">
                    <CardTitle class="text-sm font-semibold flex items-center gap-2">
                        <Calendar class="w-4 h-4 text-primary" />
                        Próximos Eventos
                    </CardTitle>
                    <Link :href="route('calendar.index')" class="text-[11px] text-primary hover:underline">
                        Ver calendário →
                    </Link>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 xl:grid-cols-3 gap-3">
                        <div
                            v-for="event in upcomingEvents"
                            :key="event.id"
                            class="flex items-start gap-3 p-3 rounded-lg border border-border hover:bg-accent/30 transition-colors"
                        >
                            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                                <Calendar class="w-3.5 h-3.5 text-primary" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-foreground truncate">{{ event.title }}</p>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <Clock class="w-3 h-3 text-muted-foreground/50" />
                                    <span class="text-[10px] text-muted-foreground">{{ formatEventDate(event.start_at) }}</span>
                                </div>
                                <div v-if="event.location" class="flex items-center gap-1.5 mt-0.5">
                                    <MapPin class="w-3 h-3 text-muted-foreground/50" />
                                    <span class="text-[10px] text-muted-foreground truncate">{{ event.location }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

        </div>
    </AuthenticatedLayout>
</template>