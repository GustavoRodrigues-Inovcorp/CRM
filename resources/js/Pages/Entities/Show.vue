<script setup>
/**
 * Entities/Show.vue
 * Detalhe de uma entidade — informações, pessoas associadas e histórico de negócios.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Badge } from '@/Components/ui/badge'
import { Button } from '@/Components/ui/button'
import {
    ArrowLeft, Building2, Mail, Phone,
    Globe, MapPin, Users, Briefcase,
    User, Euro, Calendar,
} from 'lucide-vue-next'

const props = defineProps({
    entity: Object,
})

const stageConfig = {
    lead:        { label: 'Lead',       color: '#6366f1' },
    proposal:    { label: 'Proposta',   color: '#f59e0b' },
    negotiation: { label: 'Negociação', color: '#8b5cf6' },
    follow_up:   { label: 'Follow Up',  color: '#3b82f6' },
    won:         { label: 'Ganho',      color: '#10b981' },
    lost:        { label: 'Perdido',    color: '#ef4444' },
}

const statusConfig = {
    prospect: { label: 'Prospeto', class: 'bg-amber-500/10 text-amber-500 border-amber-500/20 rounded-md' },
    active:   { label: 'Ativo',    class: 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20 rounded-md' },
    inactive: { label: 'Inativo',  class: 'bg-muted text-muted-foreground border-muted-foreground/20 rounded-md' },
}

function formatEuro(v) {
    return new Intl.NumberFormat('pt-PT', {
        style: 'currency', currency: 'EUR', maximumFractionDigits: 0,
    }).format(v || 0)
}

function getInitials(name) {
    const parts = name?.trim().split(' ') ?? []
    return parts.length >= 2
        ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
        : (parts[0]?.[0] ?? '?').toUpperCase()
}
</script>

<template>
    <Head :title="entity.name" />
    <AuthenticatedLayout>
        <template #title>
            <div class="flex items-center gap-2">
                <Link :href="route('entities.index')" class="text-muted-foreground hover:text-foreground transition-colors">
                    <ArrowLeft class="w-4 h-4" />
                </Link>
                <span>{{ entity.name }}</span>
                <Badge variant="outline" class="text-xs ml-1" :class="statusConfig[entity.status]?.class">
                    {{ statusConfig[entity.status]?.label }}
                </Badge>
            </div>
        </template>

        <div class="p-6 grid grid-cols-1 xl:grid-cols-3 gap-5">

            <!-- ── Coluna esquerda: Info ── -->
            <div class="space-y-4">
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-semibold flex items-center gap-2">
                            <Building2 class="w-4 h-4 text-primary" />
                            Informações
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div v-if="entity.email" class="flex items-center gap-2">
                            <Mail class="w-3.5 h-3.5 text-muted-foreground/50 flex-shrink-0" />
                            <span class="text-sm text-foreground">{{ entity.email }}</span>
                        </div>
                        <div v-if="entity.phone" class="flex items-center gap-2">
                            <Phone class="w-3.5 h-3.5 text-muted-foreground/50 flex-shrink-0" />
                            <span class="text-sm text-foreground">{{ entity.phone }}</span>
                        </div>
                        <div v-if="entity.address" class="flex items-center gap-2">
                            <MapPin class="w-3.5 h-3.5 text-muted-foreground/50 flex-shrink-0" />
                            <span class="text-sm text-foreground">{{ entity.address }}</span>
                        </div>
                        <div v-if="entity.vat" class="flex items-center justify-between">
                            <span class="text-xs text-muted-foreground">NIF</span>
                            <span class="text-sm font-mono text-foreground">{{ entity.vat }}</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Resumo -->
                <div class="grid grid-cols-2 gap-3">
                    <Card>
                        <CardContent class="pt-4 text-center">
                            <p class="text-2xl font-bold text-foreground">{{ entity.people?.length ?? 0 }}</p>
                            <p class="text-xs text-muted-foreground">Pessoas</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="pt-4 text-center">
                            <p class="text-2xl font-bold text-foreground">{{ entity.deals?.length ?? 0 }}</p>
                            <p class="text-xs text-muted-foreground">Negócios</p>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- ── Coluna direita: Pessoas + Negócios ── -->
            <div class="xl:col-span-2 space-y-4">

                <!-- Pessoas associadas -->
                <Card>
                    <CardHeader class="pb-3 flex flex-row items-center justify-between">
                        <CardTitle class="text-sm font-semibold flex items-center gap-2">
                            <Users class="w-4 h-4 text-primary" />
                            Pessoas ({{ entity.people?.length ?? 0 }})
                        </CardTitle>
                        <Link :href="route('people.index')" class="text-xs text-primary hover:underline">
                            Ver todas →
                        </Link>
                    </CardHeader>
                    <CardContent>
                        <div v-if="!entity.people?.length" class="text-center py-6">
                            <Users class="w-8 h-8 text-muted-foreground/20 mx-auto mb-2" />
                            <p class="text-xs text-muted-foreground">Sem pessoas associadas</p>
                        </div>
                        <div v-else class="space-y-2">
                            <div
                                v-for="person in entity.people"
                                :key="person.id"
                                class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-accent/40 transition-colors"
                            >
                                <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-[11px] font-bold text-primary flex-shrink-0">
                                    {{ getInitials(person.name) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-foreground">{{ person.name }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ person.position || person.email || '—' }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-3 text-xs text-muted-foreground">
                                    <span v-if="person.phone">{{ person.phone }}</span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Histórico de negócios -->
                <Card>
                    <CardHeader class="pb-3 flex flex-row items-center justify-between">
                        <CardTitle class="text-sm font-semibold flex items-center gap-2">
                            <Briefcase class="w-4 h-4 text-primary" />
                            Negócios ({{ entity.deals?.length ?? 0 }})
                        </CardTitle>
                        <Link :href="route('deals.index')" class="text-xs text-primary hover:underline">
                            Ver todos →
                        </Link>
                    </CardHeader>
                    <CardContent>
                        <div v-if="!entity.deals?.length" class="text-center py-6">
                            <Briefcase class="w-8 h-8 text-muted-foreground/20 mx-auto mb-2" />
                            <p class="text-xs text-muted-foreground">Sem negócios associados</p>
                        </div>
                        <div v-else class="space-y-2">
                            <Link
                                v-for="deal in entity.deals"
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
                                            {{ deal.user?.name }} ·
                                            {{ deal.expected_close_date
                                                ? new Date(deal.expected_close_date).toLocaleDateString('pt-PT')
                                                : 'Sem data' }}
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
                    </CardContent>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>