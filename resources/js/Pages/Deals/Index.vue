<script setup>
/**
 * Deals/Index.vue
 * Kanban board de negócios com drag-and-drop entre colunas.
 * Pipeline: Lead → Proposta → Negociação → Follow Up → Ganho/Perdido
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, router, Link } from '@inertiajs/vue3'
import { ref, watch, computed } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import {
    Dialog, DialogContent, DialogHeader,
    DialogTitle, DialogFooter,
} from '@/Components/ui/dialog'
import {
    Plus, Building2, User, Calendar,
    Euro, Pencil, Trash2, GripVertical, TrendingUp,
} from 'lucide-vue-next'

/* ─── Props vindas do DealController ─── */
const props = defineProps({
    dealsByStage: Object,
    stages:       Object,
    entities:     Array,
    people:       Array,
})

/* ─── Filtros do Kanban ─── */
const filterStage  = ref('')
const filterSearch = ref('')

/* ─── Colunas filtradas ─── */
const filteredColumns = computed(() => {
    return columns.value.map(col => ({
        ...col,
        deals: col.deals.filter(deal => {
            const matchSearch = !filterSearch.value ||
                deal.title.toLowerCase().includes(filterSearch.value.toLowerCase()) ||
                deal.entity?.name?.toLowerCase().includes(filterSearch.value.toLowerCase())
            return matchSearch
        }),
    })).filter(col => !filterStage.value || col.key === filterStage.value)
})

/* ─── Constrói colunas a partir das props ─── */
function buildColumns() {
    return Object.entries(props.stages).map(([key, config]) => ({
        key,
        label: config.label,
        color: config.color,
        deals: [...(props.dealsByStage[key] ?? [])],
    }))
}

const columns = ref(buildColumns())

/* ─── Modal ─── */
const showModal   = ref(false)
const editingDeal = ref(null)

/* ─── Probabilidade padrão por estágio ─── */
const stageProbability = {
    lead: 10, proposal: 30, negotiation: 60,
    follow_up: 40, won: 100, lost: 0,
}

/* ─── Formulário ─── */
const form = useForm({
    title: '', value: '', stage: 'lead',
    probability: 10, expected_close_date: '',
    entity_id: null, person_id: null, notes: '',
})

/* ─── Flag para bloquear o watch durante drag ─── */
const blockWatch = ref(false)

/* ─── Atualiza colunas quando as props mudam — exceto durante drag ─── */
watch(() => props.dealsByStage, () => {
    if (!blockWatch.value) {
        columns.value = buildColumns()
    }
}, { deep: true })

function openCreate(stage = 'lead') {
    editingDeal.value = null
    form.reset()
    form.stage = stage
    form.probability = stageProbability[stage] ?? 0
    showModal.value = true
}

function openEdit(deal) {
    editingDeal.value = deal
    form.title               = deal.title
    form.value               = deal.value
    form.stage               = deal.stage
    form.probability         = deal.probability
    form.expected_close_date = deal.expected_close_date ?? ''
    form.entity_id           = deal.entity_id ?? null
    form.person_id           = deal.person_id ?? null
    form.notes               = deal.notes ?? ''
    showModal.value = true
}

function submit() {
    const opts = { onSuccess: () => { showModal.value = false } }
    editingDeal.value
        ? form.put(route('deals.update', editingDeal.value.id), opts)
        : form.post(route('deals.store'), opts)
}

function destroy(deal) {
    if (confirm(`Eliminar "${deal.title}"?`))
        router.delete(route('deals.destroy', deal.id))
}

/* ─── Drag: atualiza local + servidor ─── */
async function onDealAdded(evt, targetStage) {
    const id = parseInt(evt.item?.dataset?.id)
    if (!id) return

    blockWatch.value = true

    /* Encontra o deal em todas as colunas originais */
    let movedDeal = null
    for (const col of columns.value) {
        const idx = col.deals.findIndex(d => d.id === id)
        if (idx !== -1) {
            movedDeal = col.deals[idx]
            col.deals.splice(idx, 1)
            break
        }
    }

    /* Adiciona na coluna destino */
    if (movedDeal) {
        movedDeal.stage = targetStage
        const targetCol = columns.value.find(c => c.key === targetStage)
        if (targetCol && !targetCol.deals.find(d => d.id === id)) {
            targetCol.deals.push(movedDeal)
        }
    }

    try {
        await window.axios.patch(
            route('deals.updateStage', id),
            { stage: targetStage }
        )
    } catch (e) {
        console.error('Erro:', e.response?.data)
        columns.value = buildColumns()
    } finally {
        setTimeout(() => { blockWatch.value = false }, 500)
    }
}

function onStageChange() {
    form.probability = stageProbability[form.stage] ?? 0
}

/* ─── Utilitários ─── */
function formatEuro(v) {
    return new Intl.NumberFormat('pt-PT', {
        style: 'currency', currency: 'EUR', maximumFractionDigits: 0,
    }).format(v || 0)
}

function columnTotal(deals) {
    return deals.reduce((s, d) => s + parseFloat(d.value || 0), 0)
}

function probabilityColor(p) {
    if (p >= 70) return '#10b981'
    if (p >= 40) return '#f59e0b'
    return '#6b7280'
}
</script>

<template>
    <Head title="Negócios" />
    <AuthenticatedLayout>
        <template #title>Negócios</template>
        <template #action>
            <Button size="sm" class="gap-1.5 rounded-lg pr-3" @click="openCreate()">
                <Plus class="w-3.5 h-3.5" />
                Novo Negócio
            </Button>
        </template>

        <!-- ── Kanban ── -->
        <div class="flex h-full min-h-0 flex-col overflow-hidden">

            <!-- Barra de filtros + totais -->
            <div class="flex items-center gap-4 px-6 py-3 border-b border-border bg-card/50 flex-wrap">
                <!-- Totais por estágio -->
                <div class="flex items-center gap-4 flex-wrap flex-1">
                    <div
                        v-for="col in columns" :key="col.key"
                        class="flex items-center gap-2 cursor-pointer"
                        @click="filterStage = filterStage === col.key ? '' : col.key"
                    >
                        <div class="w-2 h-2 rounded-full" :style="{ background: col.color }" />
                        <span class="text-xs" :class="filterStage === col.key ? 'text-foreground font-semibold' : 'text-muted-foreground'">
                            {{ col.label }}
                        </span>
                        <span class="text-xs font-semibold text-foreground">{{ formatEuro(columnTotal(col.deals)) }}</span>
                    </div>
                </div>

                <!-- Pesquisa no kanban -->
                <div class="relative w-52">
                    <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-muted-foreground pointer-events-none" />
                    <input
                        v-model="filterSearch"
                        placeholder="Filtrar negócios..."
                        class="h-8 w-full rounded-lg border border-input bg-background pl-6 pr-3 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                    />
                </div>

                <!-- Total geral -->
                <div class="flex items-center gap-1.5 ml-2">
                    <TrendingUp class="w-3.5 h-3.5 text-primary" />
                    <span class="text-xs font-semibold text-foreground">
                        Total: {{ formatEuro(columns.reduce((s, c) => s + columnTotal(c.deals), 0)) }}
                    </span>
                </div>
            </div>

            <!-- Colunas do Kanban -->
            <div class="min-h-0 flex-1 flex gap-3 overflow-x-auto overflow-y-hidden p-5">
                <div
                    v-for="column in filteredColumns"
                    :key="column.key"
                    class="flex-shrink-0 w-[270px] flex flex-col"
                >
                    <!-- Header da coluna -->
                    <div
                        class="flex items-center justify-between px-3 py-2.5 rounded-t-xl border border-b-0 border-border"
                        :style="{ borderTopColor: column.color, borderTopWidth: '2px' }"
                        style="background: hsl(var(--card))"
                    >
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-foreground">{{ column.label }}</span>
                            <span
                                class="text-[10px] font-bold px-1.5 py-0.5 rounded-full"
                                :style="{ background: column.color + '20', color: column.color }"
                            >
                                {{ column.deals.length }}
                            </span>
                        </div>
                        <span class="text-[11px] text-muted-foreground font-medium">
                            {{ formatEuro(columnTotal(column.deals)) }}
                        </span>
                    </div>

                    <!-- Área de cards + drag-and-drop -->
                    <VueDraggable
                        :modelValue="columns.find(c => c.key === column.key)?.deals ?? []"
                        @update:modelValue="val => { const c = columns.find(c => c.key === column.key); if(c) c.deals = val }"
                        group="deals"
                        animation="200"
                        ghost-class="opacity-30"
                        class="flex-1 flex flex-col gap-2 p-2 border border-t-0 border-border rounded-b-xl overflow-y-auto"
                        style="background: hsl(var(--muted)/0.3); min-height: 200px;"
                        @add="onDealAdded($event, column.key)"
                    >
                        <!-- Card de negócio -->
                        <div
                            v-for="deal in column.deals"
                            :key="deal.id"
                            :data-id="deal.id"
                            class="bg-card border border-border rounded-lg p-3 cursor-grab active:cursor-grabbing hover:border-primary/40 hover:shadow-md transition-all group select-none"
                        >
                            <!-- Título + ações -->
                            <div class="flex items-start justify-between gap-1.5 mb-2">
                                <div class="flex items-start gap-1.5 flex-1 min-w-0">
                                    <GripVertical class="w-3.5 h-3.5 text-muted-foreground/20 flex-shrink-0 mt-0.5" />
                                    <Link
                                        :href="route('deals.show', deal.id)"
                                        class="text-[13px] font-semibold text-foreground leading-snug hover:text-primary transition-colors"
                                        @click.stop
                                    >
                                        {{ deal.title }}
                                    </Link>
                                </div>
                                <div class="flex gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0">
                                    <button
                                        @click.stop="openEdit(deal)"
                                        class="w-5 h-5 rounded flex items-center justify-center hover:bg-accent"
                                    >
                                        <Pencil class="w-2.5 h-2.5 text-muted-foreground" />
                                    </button>
                                    <button
                                        @click.stop="destroy(deal)"
                                        class="w-5 h-5 rounded flex items-center justify-center hover:bg-destructive/10"
                                    >
                                        <Trash2 class="w-2.5 h-2.5 text-destructive/60" />
                                    </button>
                                </div>
                            </div>

                            <!-- Valor -->
                            <div
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full mb-2.5 text-xs font-bold"
                                :style="{ background: column.color + '15', color: column.color }"
                            >
                                {{ formatEuro(deal.value) }}
                            </div>

                            <!-- Meta: empresa, pessoa, data -->
                            <div class="space-y-1">
                                <div v-if="deal.entity" class="flex items-center gap-1.5">
                                    <Building2 class="w-3 h-3 text-muted-foreground/40 flex-shrink-0" />
                                    <span class="text-[11px] text-muted-foreground truncate">{{ deal.entity.name }}</span>
                                </div>
                                <div v-if="deal.person" class="flex items-center gap-1.5">
                                    <User class="w-3 h-3 text-muted-foreground/40 flex-shrink-0" />
                                    <span class="text-[11px] text-muted-foreground truncate">{{ deal.person.name }}</span>
                                </div>
                                <div v-if="deal.expected_close_date" class="flex items-center gap-1.5">
                                    <Calendar class="w-3 h-3 text-muted-foreground/40 flex-shrink-0" />
                                    <span class="text-[11px] text-muted-foreground">
                                        {{ new Date(deal.expected_close_date).toLocaleDateString('pt-PT') }}
                                    </span>
                                </div>
                            </div>

                            <!-- Barra de probabilidade -->
                            <div class="mt-2.5 pt-2 border-t border-border/40">
                                <div class="flex justify-between mb-1">
                                    <span class="text-[10px] text-muted-foreground/60">Probabilidade</span>
                                    <span
                                        class="text-[10px] font-bold"
                                        :style="{ color: probabilityColor(deal.probability) }"
                                    >
                                        {{ deal.probability }}%
                                    </span>
                                </div>
                                <div class="h-1 bg-muted rounded-full overflow-hidden">
                                    <div
                                        class="h-full rounded-full transition-all duration-500"
                                        :style="{
                                            width: deal.probability + '%',
                                            background: probabilityColor(deal.probability),
                                        }"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Botão adicionar no fundo da coluna -->
                        <button
                            @click="openCreate(column.key)"
                            class="flex items-center justify-center gap-1.5 w-full py-2 rounded-lg text-[11px] text-muted-foreground/50 hover:text-muted-foreground hover:bg-accent/50 transition-all border border-dashed border-transparent hover:border-border mt-1"
                        >
                            <Plus class="w-3 h-3" />
                            Adicionar
                        </button>
                    </VueDraggable>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════
             MODAL — Criar / Editar Negócio
        ══════════════════════════════ -->
        <Dialog v-model:open="showModal">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ editingDeal ? 'Editar Negócio' : 'Novo Negócio' }}</DialogTitle>
                </DialogHeader>

                <div class="space-y-4 py-2">

                    <div class="space-y-1.5">
                        <Label>Título <span class="text-destructive">*</span></Label>
                        <Input v-model="form.title" placeholder="Ex: Website Acme Corp" />
                        <p v-if="form.errors.title" class="text-xs text-destructive">{{ form.errors.title }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label>Valor (€)</Label>
                            <Input v-model="form.value" type="number" min="0" step="0.01" placeholder="0" />
                        </div>
                        <div class="space-y-1.5">
                            <Label>Estágio</Label>
                            <select
                                v-model="form.stage"
                                @change="onStageChange"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                            >
                                <option v-for="(config, key) in stages" :key="key" :value="key">
                                    {{ config.label }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label>Empresa</Label>
                            <select
                                v-model="form.entity_id"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                            >
                                <option :value="null">Nenhuma</option>
                                <option v-for="e in entities" :key="e.id" :value="e.id">{{ e.name }}</option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <Label>Contacto</Label>
                            <select
                                v-model="form.person_id"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                            >
                                <option :value="null">Nenhum</option>
                                <option v-for="p in people" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label>Probabilidade ({{ form.probability }}%)</Label>
                            <input
                                v-model="form.probability"
                                type="range" min="0" max="100" step="5"
                                class="w-full accent-primary"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <Label>Data de fecho prevista</Label>
                            <Input v-model="form.expected_close_date" type="date" />
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label>Notas</Label>
                        <textarea
                            v-model="form.notes"
                            rows="2"
                            placeholder="Notas sobre o negócio..."
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring resize-none"
                        />
                    </div>

                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showModal = false">Cancelar</Button>
                    <Button @click="submit" :disabled="form.processing">
                        {{ editingDeal ? 'Guardar' : 'Criar Negócio' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </AuthenticatedLayout>
</template>