<script setup>
/**
 * People/Show.vue
 * Detalhe de uma pessoa — informações, empresa, negócios e eventos.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Badge } from '@/Components/ui/badge'
import {
    ArrowLeft, User, Mail, Phone,
    Building2, Briefcase, Calendar,
    CheckCircle2, Circle,
} from 'lucide-vue-next'

const props = defineProps({
    person: Object,
    events: Array,
})

const stageConfig = {
    lead:        { label: 'Lead',       color: '#6366f1' },
    proposal:    { label: 'Proposta',   color: '#f59e0b' },
    negotiation: { label: 'Negociação', color: '#8b5cf6' },
    follow_up:   { label: 'Follow Up',  color: '#3b82f6' },
    won:         { label: 'Ganho',      color: '#10b981' },
    lost:        { label: 'Perdido',    color: '#ef4444' },
}

const eventTypeConfig = {
    meeting: { label: 'Reunião',  color: 'bg-blue-500/10 text-blue-500' },
    call:    { label: 'Chamada',  color: 'bg-emerald-500/10 text-emerald-500' },
    task:    { label: 'Tarefa',   color: 'bg-violet-500/10 text-violet-500' },
    email:   { label: 'Email',    color: 'bg-amber-500/10 text-amber-500' },
}

function formatEuro(v) {
    return new Intl.NumberFormat('pt-PT', {
        style: 'currency', currency: 'EUR', maximumFractionDigits: 0,
    }).format(v || 0)
}

function formatDate(d) {
    return new Date(d).toLocaleDateString('pt-PT', {
        day: '2-digit', month: 'short', year: 'numeric',
    })
}
</script>

<template>
    <Head :title="person.name" />
    <AuthenticatedLayout>
        <template #title>
            <div class="flex items-center gap-2">
                <Link :href="route('people.index')" class="text-muted-foreground hover:text-foreground transition-colors">
                    <ArrowLeft class="w-4 h-4" />
                </Link>
                <span>{{ person.name }}</span>
                <Badge
                    variant="outline"
                    class="text-xs ml-1"
                    :class="person.status === 'active'
                        ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20 rounded-md'
                        : ''"
                >
                    {{ person.status === 'active' ? 'Ativo' : 'Inativo' }}
                </Badge>
            </div>
        </template>

        <div class="p-6 grid grid-cols-1 xl:grid-cols-3 gap-5">

            <!-- ── Coluna esquerda: Info ── -->
            <div class="space-y-4">
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-semibold flex items-center gap-2">
                            <User class="w-4 h-4 text-primary" />
                            Informações
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div v-if="person.position" class="flex items-center justify-between">
                            <span class="text-xs text-muted-foreground">Cargo</span>
                            <span class="text-sm text-foreground">{{ person.position }}</span>
                        </div>
                        <div v-if="person.email" class="flex items-center gap-2">
                            <Mail class="w-3.5 h-3.5 text-muted-foreground/50" />
                            <span class="text-sm text-foreground">{{ person.email }}</span>
                        </div>
                        <div v-if="person.phone" class="flex items-center gap-2">
                            <Phone class="w-3.5 h-3.5 text-muted-foreground/50" />
                            <span class="text-sm text-foreground">{{ person.phone }}</span>
                        </div>
                        <div v-if="person.mobile" class="flex items-center gap-2">
                            <Phone class="w-3.5 h-3.5 text-muted-foreground/50" />
                            <span class="text-sm text-foreground">{{ person.mobile }} <span class="text-xs text-muted-foreground">(móvel)</span></span>
                        </div>
                        <div v-if="person.entity" class="flex items-center gap-2 pt-2 border-t border-border">
                            <Building2 class="w-3.5 h-3.5 text-muted-foreground/50" />
                            <Link
                                :href="route('entities.show', person.entity.id)"
                                class="text-sm text-primary hover:underline"
                            >
                                {{ person.entity.name }}
                            </Link>
                        </div>
                        <div v-if="person.notes" class="pt-2 border-t border-border">
                            <p class="text-xs text-muted-foreground mb-1">Notas</p>
                            <p class="text-sm text-foreground">{{ person.notes }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Resumo -->
                <div class="grid grid-cols-2 gap-3">
                    <Card>
                        <CardContent class="pt-4 text-center">
                            <p class="text-2xl font-bold text-foreground">{{ person.deals?.length ?? 0 }}</p>
                            <p class="text-xs text-muted-foreground">Negócios</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="pt-4 text-center">
                            <p class="text-2xl font-bold text-foreground">{{ events?.length ?? 0 }}</p>
                            <p class="text-xs text-muted-foreground">Eventos</p>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- ── Coluna direita: Negócios + Eventos ── -->
            <div class="xl:col-span-2 space-y-4">

                <!-- Negócios -->
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-semibold flex items-center gap-2">
                            <Briefcase class="w-4 h-4 text-primary" />
                            Negócios ({{ person.deals?.length ?? 0 }})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="!person.deals?.length" class="text-center py-6">
                            <Briefcase class="w-8 h-8 text-muted-foreground/20 mx-auto mb-2" />
                            <p class="text-xs text-muted-foreground">Sem negócios associados</p>
                        </div>
                        <div v-else class="space-y-2">
                            <Link
                                v-for="deal in person.deals"
                                :key="deal.id"
                                :href="route('deals.show', deal.id)"
                                class="flex items-center justify-between p-3 rounded-lg border border-border hover:bg-accent/40 hover:border-primary/30 transition-all group"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <div
                                        class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                                        :style="{ background: stageConfig[deal.stage]?.color ?? '#6b7280' }"
                                    />
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-foreground group-hover:text-primary transition-colors truncate">
                                            {{ deal.title }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ deal.user?.name }} · {{ deal.expected_close_date ? formatDate(deal.expected_close_date) : 'Sem data' }}
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
                                    <span class="text-sm font-semibold text-foreground">{{ formatEuro(deal.value) }}</span>
                                </div>
                            </Link>
                        </div>
                    </CardContent>
                </Card>

                <!-- Eventos do calendário -->
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-semibold flex items-center gap-2">
                            <Calendar class="w-4 h-4 text-primary" />
                            Eventos ({{ events?.length ?? 0 }})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="!events?.length" class="text-center py-6">
                            <Calendar class="w-8 h-8 text-muted-foreground/20 mx-auto mb-2" />
                            <p class="text-xs text-muted-foreground">Sem eventos associados</p>
                        </div>
                        <div v-else class="space-y-2">
                            <div
                                v-for="event in events"
                                :key="event.id"
                                class="flex items-center gap-3 p-2.5 rounded-lg border border-border"
                            >
                                <component
                                    :is="event.completed ? CheckCircle2 : Circle"
                                    class="w-4 h-4 flex-shrink-0"
                                    :class="event.completed ? 'text-emerald-500' : 'text-muted-foreground/40'"
                                />
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-foreground truncate" :class="event.completed ? 'line-through text-muted-foreground' : ''">
                                        {{ event.title }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">{{ formatDate(event.start_at) }}</p>
                                </div>
                                <Badge
                                    variant="outline"
                                    class="text-xs flex-shrink-0"
                                    :class="eventTypeConfig[event.type]?.color"
                                >
                                    {{ eventTypeConfig[event.type]?.label ?? event.type }}
                                </Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>