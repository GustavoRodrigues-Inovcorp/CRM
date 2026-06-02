<script setup>
/**
 * Reports/Index.vue
 * Relatórios — estatísticas de produtos nos negócios.
 * Mostra quais os produtos mais presentes no pipeline.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { Button } from '@/Components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import {
    Table, TableBody, TableCell,
    TableHead, TableHeader, TableRow,
} from '@/Components/ui/table'
import { Badge } from '@/Components/ui/badge'
import { Input } from '@/Components/ui/input'
import {
    Download, TrendingUp, Package,
    Briefcase, BarChart2, Filter,
} from 'lucide-vue-next'

/* ─── Props vindas do ReportController ─── */
const props = defineProps({
    productStats:   Array,
    totalPipeline:  Number,
    totalDeals:     Number,
    conversionRate: Number,
    dealsByStage:   Array,
    filters:        Object,
})

/* ─── Filtros locais ─── */
const dateFrom = ref(props.filters?.date_from ?? '')
const dateTo   = ref(props.filters?.date_to   ?? '')
const stage    = ref(props.filters?.stage     ?? '')

/* ─── Estágios disponíveis ─── */
const stages = {
    lead:        'Lead',
    proposal:    'Proposta',
    negotiation: 'Negociação',
    follow_up:   'Follow Up',
    won:         'Ganho',
    lost:        'Perdido',
}

/* ─── Cores por estágio ─── */
const stageColors = {
    lead:        '#6366f1',
    proposal:    '#f59e0b',
    negotiation: '#8b5cf6',
    follow_up:   '#3b82f6',
    won:         '#10b981',
    lost:        '#ef4444',
}

/* ─── Aplica filtros ─── */
function applyFilters() {
    router.get(route('reports.index'), {
        date_from: dateFrom.value || undefined,
        date_to:   dateTo.value   || undefined,
        stage:     stage.value    || undefined,
    }, { preserveState: true })
}

/* ─── Limpa filtros ─── */
function clearFilters() {
    dateFrom.value = ''
    dateTo.value   = ''
    stage.value    = ''
    router.get(route('reports.index'))
}

/* ─── Exporta CSV ─── */
function exportCsv() {
    window.location.href = route('reports.export')
}

/* ─── Formata valor em euros ─── */
function formatEuro(value) {
    return new Intl.NumberFormat('pt-PT', {
        style: 'currency', currency: 'EUR', maximumFractionDigits: 0,
    }).format(value || 0)
}

/* ─── Valor máximo para a barra de progresso ─── */
const maxValue = computed(() =>
    Math.max(...props.productStats.map(p => p.total_value), 1)
)
</script>

<template>
    <Head title="Relatórios" />

    <AuthenticatedLayout>
        <template #title>Relatórios</template>
        <template #action>
            <Button size="sm" variant="outline" class="gap-1.5 rounded-lg" @click="exportCsv">
                <Download class="w-3.5 h-3.5" />
                Exportar CSV
            </Button>
        </template>

        <div class="p-6 space-y-5">

            <!-- ── KPIs ── -->
            <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
                <Card>
                    <CardHeader class="pb-2 flex flex-row items-center justify-between space-y-0">
                        <CardTitle class="text-xs text-muted-foreground uppercase tracking-wide font-medium">
                            Pipeline Total
                        </CardTitle>
                        <div class="w-7 h-7 rounded-lg bg-primary/10 flex items-center justify-center">
                            <TrendingUp class="w-3.5 h-3.5 text-primary" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold tracking-tight">{{ formatEuro(totalPipeline) }}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2 flex flex-row items-center justify-between space-y-0">
                        <CardTitle class="text-xs text-muted-foreground uppercase tracking-wide font-medium">
                            Total de Negócios
                        </CardTitle>
                        <div class="w-7 h-7 rounded-lg bg-amber-500/10 flex items-center justify-center">
                            <Briefcase class="w-3.5 h-3.5 text-amber-500" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold tracking-tight">{{ totalDeals }}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2 flex flex-row items-center justify-between space-y-0">
                        <CardTitle class="text-xs text-muted-foreground uppercase tracking-wide font-medium">
                            Taxa de Conversão
                        </CardTitle>
                        <div class="w-7 h-7 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                            <BarChart2 class="w-3.5 h-3.5 text-emerald-500" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold tracking-tight">{{ conversionRate }}%</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2 flex flex-row items-center justify-between space-y-0">
                        <CardTitle class="text-xs text-muted-foreground uppercase tracking-wide font-medium">
                            Produtos no Pipeline
                        </CardTitle>
                        <div class="w-7 h-7 rounded-lg bg-violet-500/10 flex items-center justify-center">
                            <Package class="w-3.5 h-3.5 text-violet-500" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold tracking-tight">{{ productStats.length }}</p>
                    </CardContent>
                </Card>
            </div>

            <!-- ── Filtros ── -->
            <Card>
                <CardContent class="pt-4">
                    <div class="flex items-center gap-3 flex-wrap">
                        <Filter class="w-4 h-4 text-muted-foreground flex-shrink-0" />
                        <div class="flex items-center gap-2">
                            <label class="text-xs text-muted-foreground">De</label>
                            <Input v-model="dateFrom" type="date" class="h-8 text-xs w-36" />
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-xs text-muted-foreground">Até</label>
                            <Input v-model="dateTo" type="date" class="h-8 text-xs w-36" />
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-xs text-muted-foreground">Estágio</label>
                            <select
                                v-model="stage"
                                class="h-8 rounded-md border border-input bg-background px-2 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                            >
                                <option value="">Todos</option>
                                <option v-for="(label, key) in stages" :key="key" :value="key">
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                        <Button size="sm" class="h-8 text-xs rounded-md" @click="applyFilters">Filtrar</Button>
                        <Button size="sm" variant="ghost" class="h-8 text-xs rounded-md" @click="clearFilters">Limpar</Button>
                    </div>
                </CardContent>
            </Card>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

                <!-- ── Tabela de produtos ── -->
                <div class="xl:col-span-2">
                    <Card>
                        <CardHeader class="pb-3">
                            <CardTitle class="text-sm font-semibold">Produtos no Pipeline</CardTitle>
                        </CardHeader>
                        <CardContent class="p-0">

                            <!-- Estado vazio -->
                            <div v-if="productStats.length === 0" class="flex flex-col items-center justify-center py-16 gap-2">
                                <Package class="w-10 h-10 text-muted-foreground/20" />
                                <p class="text-sm text-muted-foreground">Nenhum produto nos negócios ainda</p>
                                <p class="text-xs text-muted-foreground/60">Adiciona produtos aos negócios para ver estatísticas</p>
                            </div>

                            <Table v-else>
                                <TableHeader>
                                    <TableRow class="hover:bg-transparent">
                                        <TableHead class="pl-5">Produto</TableHead>
                                        <TableHead class="text-right">Qtd.</TableHead>
                                        <TableHead class="text-right">Valor Total</TableHead>
                                        <TableHead class="text-right pr-5">Negócios</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow
                                        v-for="(product, index) in productStats"
                                        :key="product.id"
                                        class="group"
                                    >
                                        <TableCell class="pl-5">
                                            <div class="flex items-center gap-3">
                                                <!-- Posição ranking -->
                                                <span class="text-xs text-muted-foreground/50 w-4 text-center font-mono">
                                                    {{ index + 1 }}
                                                </span>
                                                <div>
                                                    <p class="text-sm font-medium text-foreground">{{ product.name }}</p>
                                                    <!-- Barra de progresso relativa ao maior valor -->
                                                    <div class="w-32 h-1 bg-muted rounded-full mt-1 overflow-hidden">
                                                        <div
                                                            class="h-full bg-primary rounded-full transition-all"
                                                            :style="{ width: (product.total_value / maxValue * 100) + '%' }"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell class="text-right">
                                            <span class="text-sm text-foreground">
                                                {{ product.total_quantity }}
                                                <span v-if="product.unit" class="text-xs text-muted-foreground">{{ product.unit }}</span>
                                            </span>
                                        </TableCell>
                                        <TableCell class="text-right">
                                            <span class="text-sm font-semibold text-foreground">
                                                {{ formatEuro(product.total_value) }}
                                            </span>
                                        </TableCell>
                                        <TableCell class="text-right pr-5">
                                            <Badge variant="secondary" class="text-xs">
                                                {{ product.deal_count }} negócio{{ product.deal_count !== 1 ? 's' : '' }}
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>

                <!-- ── Negócios por estágio ── -->
                <div>
                    <Card>
                        <CardHeader class="pb-3">
                            <CardTitle class="text-sm font-semibold">Negócios por Estágio</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div
                                v-for="item in dealsByStage"
                                :key="item.stage"
                                class="flex items-center gap-3"
                            >
                                <div
                                    class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                                    :style="{ background: stageColors[item.stage] ?? '#6b7280' }"
                                />
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs text-foreground font-medium">
                                            {{ stages[item.stage] ?? item.stage }}
                                        </span>
                                        <span class="text-xs text-muted-foreground">
                                            {{ item.count }} · {{ formatEuro(item.total) }}
                                        </span>
                                    </div>
                                    <div class="h-1.5 bg-muted rounded-full overflow-hidden">
                                        <div
                                            class="h-full rounded-full transition-all"
                                            :style="{
                                                width: totalDeals > 0 ? (item.count / totalDeals * 100) + '%' : '0%',
                                                background: stageColors[item.stage] ?? '#6b7280',
                                            }"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div v-if="dealsByStage.length === 0" class="py-8 text-center">
                                <p class="text-xs text-muted-foreground">Sem negócios ainda</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>