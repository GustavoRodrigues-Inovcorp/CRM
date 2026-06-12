<script setup>
/**
 * Deals/Show.vue
 * Detalhe completo de um negócio.
 * Inclui: cronologia, atividades rápidas, produtos e proposta.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, router, Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import { Badge } from '@/Components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import {
    Dialog, DialogContent, DialogHeader,
    DialogTitle, DialogFooter,
} from '@/Components/ui/dialog'
import {
    ArrowLeft, Phone, Users, Mail, FileText,
    CheckCircle2, Circle, Plus, Upload, Send,
    Building2, User, Calendar, Euro, Package,
    Clock, Edit, Trash2, ChevronRight,
} from 'lucide-vue-next'

const props = defineProps({
    deal:     Object,
    timeline: Array,
    entities: Array,
    people:   Array,
    products: Array,
    stages:   Object,
    followUp: Object,
})

/* ─── Ícones por tipo de atividade ─── */
const activityIcons = {
    call:    Phone,
    meeting: Users,
    email:   Mail,
    task:    CheckCircle2,
    note:    FileText,
    proposal: FileText,
}

/* ─── Cores por tipo ─── */
const activityColors = {
    call:     'bg-emerald-500/10 text-emerald-500',
    meeting:  'bg-blue-500/10 text-blue-500',
    email:    'bg-amber-500/10 text-amber-500',
    task:     'bg-violet-500/10 text-violet-500',
    note:     'bg-gray-500/10 text-gray-500',
    proposal: 'bg-primary/10 text-primary',
}

/* ─── Formulário de atividade rápida ─── */
const activityForm = useForm({
    type:         'call',
    title:        '',
    description:  '',
    scheduled_at: '',
})

const showActivityForm = ref(false)

function submitActivity() {
    activityForm.post(route('deals.activities.store', props.deal.id), {
        onSuccess: () => {
            activityForm.reset()
            showActivityForm.value = false
        },
    })
}

/* ─── Upload de proposta ─── */
const proposalFile     = ref(null)
const showSendModal    = ref(false)
const selectedProposal = ref(null)

const sendForm = useForm({
    email:   props.deal.person?.email ?? props.deal.entity?.email ?? '',
    subject: `Proposta Comercial — ${props.deal.title}`,
    body:    `Exmo(a) Senhor(a),\n\nSegue em anexo a proposta comercial referente a "${props.deal.title}".\n\nFicamos ao dispor para qualquer esclarecimento.\n\nCom os melhores cumprimentos`,
})

function handleFileUpload(event) {
    const file = event.target.files[0]
    if (!file) return

    const formData = new FormData()
    formData.append('file', file)

    router.post(route('deals.proposals.upload', props.deal.id), formData, {
        forceFormData: true,
    })
}

function openSendModal(proposal) {
    selectedProposal.value = proposal
    showSendModal.value    = true
}

function sendProposal() {
    sendForm.post(route('deals.proposals.send', {
        deal:     props.deal.id,
        proposal: selectedProposal.value.id,
    }), {
        onSuccess: () => { showSendModal.value = false },
    })
}

/* ─── Adicionar produto ─── */
const showProductModal = ref(false)
const productForm = useForm({
    product_id: null,
    quantity:   1,
    unit_price: '',
})

function openProductModal() {
    productForm.reset()
    productForm.quantity = 1
    showProductModal.value = true
}

function onProductSelect() {
    const product = props.products.find(p => p.id == productForm.product_id)
    if (product) productForm.unit_price = product.price
}

function addProduct() {
    productForm.post(route('deals.addProduct', props.deal.id), {
        onSuccess: () => { showProductModal.value = false },
    })
}

function removeProduct(productId) {
    if (confirm('Remover produto do negócio?'))
        router.delete(route('deals.removeProduct', { deal: props.deal.id, product: productId }))
}

/* ─── Concluir atividade ─── */
function toggleActivity(activityId) {
    router.patch(route('deals.activities.complete', {
        deal:     props.deal.id,
        activity: activityId,
    }))
}

/* ─── Utilitários ─── */
function formatEuro(v) {
    return new Intl.NumberFormat('pt-PT', {
        style: 'currency', currency: 'EUR',
    }).format(v || 0)
}

function formatDate(d) {
    if (!d) return ''
    return new Date(d).toLocaleDateString('pt-PT', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    })
}

function cancelFollowUp() {
    if (confirm('Cancelar o follow-up automático?')) {
        router.patch(route('deals.followup.cancel', props.deal.id))
    }
}

const stageColor = computed(() => props.stages[props.deal.stage]?.color ?? '#6b7280')
const stageLabel = computed(() => props.stages[props.deal.stage]?.label ?? props.deal.stage)
</script>

<template>
    <Head :title="deal.title" />

    <AuthenticatedLayout>
        <template #title>
            <div class="flex items-center gap-2">
                <Link :href="route('deals.index')" class="text-muted-foreground hover:text-foreground transition-colors">
                    <ArrowLeft class="w-4 h-4" />
                </Link>
                <span>{{ deal.title }}</span>
                <span
                    class="text-[11px] font-medium px-2 py-0.5 rounded-full ml-1"
                    :style="{ background: stageColor + '20', color: stageColor }"
                >
                    {{ stageLabel }}
                </span>
            </div>
        </template>

        <div class="p-6 grid grid-cols-1 xl:grid-cols-3 gap-5">

            <!-- ══ COLUNA ESQUERDA — Info + Produtos + Proposta ══ -->
            <div class="space-y-4">

                <!-- Info do negócio -->
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-semibold">Informações</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-muted-foreground">Valor</span>
                            <span class="text-sm font-bold text-foreground">{{ formatEuro(deal.value) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-muted-foreground">Probabilidade</span>
                            <div class="flex items-center gap-2">
                                <div class="w-16 h-1.5 bg-muted rounded-full overflow-hidden">
                                    <div
                                        class="h-full rounded-full"
                                        :style="{ width: deal.probability + '%', background: stageColor }"
                                    />
                                </div>
                                <span class="text-xs font-semibold text-foreground">{{ deal.probability }}%</span>
                            </div>
                        </div>
                        <div v-if="deal.expected_close_date" class="flex items-center justify-between">
                            <span class="text-xs text-muted-foreground">Fecho previsto</span>
                            <span class="text-xs text-foreground">
                                {{ new Date(deal.expected_close_date).toLocaleDateString('pt-PT') }}
                            </span>
                        </div>
                        <div v-if="deal.entity" class="flex items-center justify-between">
                            <span class="text-xs text-muted-foreground">Empresa</span>
                            <div class="flex items-center gap-1.5">
                                <Building2 class="w-3 h-3 text-muted-foreground/50" />
                                <span class="text-xs text-foreground">{{ deal.entity.name }}</span>
                            </div>
                        </div>
                        <div v-if="deal.person" class="flex items-center justify-between">
                            <span class="text-xs text-muted-foreground">Contacto</span>
                            <div class="flex items-center gap-1.5">
                                <User class="w-3 h-3 text-muted-foreground/50" />
                                <span class="text-xs text-foreground">{{ deal.person.name }}</span>
                            </div>
                        </div>
                        <div v-if="deal.notes" class="pt-2 border-t border-border">
                            <p class="text-xs text-muted-foreground mb-1">Notas</p>
                            <p class="text-xs text-foreground">{{ deal.notes }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Produtos -->
                <Card>
                    <CardHeader class="pb-3 flex flex-row items-center justify-between">
                        <CardTitle class="text-sm font-semibold">Produtos</CardTitle>
                        <Button variant="ghost" size="sm" class="h-7 w-7 p-0" @click="openProductModal">
                            <Plus class="w-3.5 h-3.5" />
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <div v-if="deal.products.length === 0" class="text-center py-4">
                            <Package class="w-6 h-6 text-muted-foreground/20 mx-auto mb-1.5" />
                            <p class="text-xs text-muted-foreground">Sem produtos</p>
                        </div>
                        <div v-else class="space-y-2">
                            <div
                                v-for="product in deal.products"
                                :key="product.id"
                                class="flex items-center justify-between group"
                            >
                                <div>
                                    <p class="text-xs font-medium text-foreground">{{ product.name }}</p>
                                    <p class="text-[10px] text-muted-foreground">
                                        {{ product.pivot.quantity }}x {{ formatEuro(product.pivot.unit_price) }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-semibold text-foreground">
                                        {{ formatEuro(product.pivot.total) }}
                                    </span>
                                    <button
                                        @click="removeProduct(product.id)"
                                        class="opacity-0 group-hover:opacity-100 transition-opacity"
                                    >
                                        <Trash2 class="w-3 h-3 text-destructive/60" />
                                    </button>
                                </div>
                            </div>
                            <div class="pt-2 border-t border-border flex justify-between">
                                <span class="text-xs text-muted-foreground">Total</span>
                                <span class="text-xs font-bold text-foreground">{{ formatEuro(deal.value) }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Proposta -->
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-semibold">Proposta</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <!-- Propostas existentes -->
                        <div
                            v-for="proposal in deal.proposals"
                            :key="proposal.id"
                            class="flex items-center justify-between p-2.5 rounded-lg border border-border bg-muted/30"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <FileText class="w-4 h-4 text-primary flex-shrink-0" />
                                <div class="min-w-0">
                                    <p class="text-xs font-medium text-foreground truncate">{{ proposal.file_name }}</p>
                                    <p class="text-[10px] text-muted-foreground">
                                        {{ proposal.sent_at ? '✓ Enviada ' + formatDate(proposal.sent_at) : 'Não enviada' }}
                                    </p>
                                </div>
                            </div>
                            <Button
                                size="sm"
                                variant="outline"
                                class="h-7 text-xs gap-1 flex-shrink-0 ml-2 rounded-md"
                                @click="openSendModal(proposal)"
                            >
                                <Send class="w-3 h-3" />
                                Enviar
                            </Button>
                        </div>

                        <!-- Upload novo ficheiro -->
                        <label class="flex items-center justify-center gap-2 w-full py-2.5 rounded-lg border border-dashed border-border hover:border-primary/40 hover:bg-accent/30 transition-all cursor-pointer text-xs text-muted-foreground hover:text-foreground">
                            <Upload class="w-3.5 h-3.5" />
                            Carregar proposta (PDF/Word)
                            <input type="file" class="hidden" accept=".pdf,.doc,.docx" @change="handleFileUpload" />
                        </label>
                    </CardContent>
                </Card>

                <!-- Follow-up automático -->
                <Card v-if="followUp || deal.stage === 'follow_up'">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-semibold flex items-center gap-2">
                            <Mail class="w-4 h-4 text-primary" />
                            Follow-up Automático
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <!-- Ativo -->
                        <div v-if="followUp && followUp.active" class="space-y-3">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
                                <span class="text-xs text-emerald-500 font-medium">Ativo</span>
                            </div>
                            <div class="space-y-1.5 text-xs text-muted-foreground">
                                <div class="flex justify-between">
                                    <span>Emails enviados</span>
                                    <span class="font-medium text-foreground">{{ followUp.emails_sent }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Próximo envio</span>
                                    <span class="font-medium text-foreground">
                                        {{ followUp.next_send_at ? new Date(followUp.next_send_at).toLocaleDateString('pt-PT') : '—' }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Destinatário</span>
                                    <span class="font-medium text-foreground truncate ml-2">{{ followUp.email }}</span>
                                </div>
                            </div>
                            <Button
                                variant="outline"
                                size="sm"
                                class="w-full text-xs text-destructive rounded-lg border-destructive/30 hover:bg-destructive/10"
                                @click="cancelFollowUp"
                            >
                                Cancelar follow-up
                            </Button>
                        </div>

                        <!-- Inativo ou cancelado -->
                        <div v-else-if="followUp && !followUp.active" class="text-center py-3">
                            <p class="text-xs text-muted-foreground">Follow-up cancelado</p>
                            <p class="text-[10px] text-muted-foreground/60">{{ followUp.emails_sent }} email(s) enviado(s)</p>
                        </div>

                        <!-- Sem email configurado -->
                        <div v-else class="text-center py-3">
                            <p class="text-xs text-muted-foreground">Sem email de contacto</p>
                            <p class="text-[10px] text-muted-foreground/60">Associa uma pessoa ou empresa com email</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- ══ COLUNA DIREITA — Cronologia + Atividades ══ -->
            <div class="xl:col-span-2 space-y-4">

                <!-- Criação rápida de atividade -->
                <Card>
                    <CardContent class="pt-4">
                        <div v-if="!showActivityForm" class="flex gap-2 flex-wrap">
                            <button
                                v-for="type in ['call', 'meeting', 'task', 'email', 'note']"
                                :key="type"
                                @click="activityForm.type = type; showActivityForm = true"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-border hover:border-primary/30 hover:bg-accent transition-all text-xs text-muted-foreground hover:text-foreground"
                            >
                                <component :is="activityIcons[type]" class="w-3.5 h-3.5" />
                                {{ { call: 'Chamada', meeting: 'Reunião', task: 'Tarefa', email: 'Email', note: 'Nota' }[type] }}
                            </button>
                        </div>

                        <div v-else class="space-y-3">
                            <div class="flex items-center gap-2">
                                <div class="flex gap-1.5 flex-wrap">
                                    <button
                                        v-for="type in ['call', 'meeting', 'task', 'email', 'note']"
                                        :key="type"
                                        @click="activityForm.type = type"
                                        class="px-2.5 py-1 rounded-md text-xs font-medium border transition-all"
                                        :class="activityForm.type === type
                                            ? 'bg-primary text-primary-foreground border-primary'
                                            : 'border-border text-muted-foreground hover:border-primary/30'"
                                    >
                                        {{ { call: 'Chamada', meeting: 'Reunião', task: 'Tarefa', email: 'Email', note: 'Nota' }[type] }}
                                    </button>
                                </div>
                                <button @click="showActivityForm = false" class="ml-auto text-muted-foreground hover:text-foreground">
                                    ✕
                                </button>
                            </div>
                            <Input
                                v-model="activityForm.title"
                                :placeholder="{ call: 'Ex: Chamada de acompanhamento', meeting: 'Ex: Reunião de proposta', task: 'Ex: Enviar documentação', email: 'Ex: Email de follow-up', note: 'Ex: Cliente pediu desconto' }[activityForm.type]"
                                class="h-8 text-sm"
                                autofocus
                            />
                            <div class="grid grid-cols-2 gap-2">
                                <Input v-model="activityForm.scheduled_at" type="datetime-local" class="h-8 text-xs" />
                                <textarea
                                    v-model="activityForm.description"
                                    rows="1"
                                    placeholder="Descrição (opcional)"
                                    class="rounded-md border border-input bg-background px-3 py-1.5 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring resize-none"
                                />
                            </div>
                            <div class="flex gap-2 justify-end">
                                <Button variant="outline" size="sm" @click="showActivityForm = false" class="gap-1.5 rounded-md">Cancelar</Button>
                                <Button size="sm" @click="submitActivity" :disabled="!activityForm.title || activityForm.processing" class="gap-1.5 rounded-md">
                                    Guardar
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Cronologia -->
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-semibold">Cronologia</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <!-- Estado vazio -->
                        <div v-if="timeline.length === 0" class="text-center py-10">
                            <Clock class="w-8 h-8 text-muted-foreground/20 mx-auto mb-2" />
                            <p class="text-sm text-muted-foreground">Sem atividade ainda</p>
                            <p class="text-xs text-muted-foreground/60">Usa os botões acima para registar a primeira interação</p>
                        </div>

                        <!-- Lista de eventos -->
                        <div v-else class="relative">
                            <!-- Linha vertical da cronologia -->
                            <div class="absolute left-[18px] top-0 bottom-0 w-px bg-border" />

                            <div class="space-y-4">
                                <div
                                    v-for="item in timeline"
                                    :key="item.type + item.id"
                                    class="flex gap-3 relative"
                                >
                                    <!-- Ícone do tipo -->
                                    <div
                                        class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 z-10 border-2 border-background"
                                        :class="activityColors[item.subtype] ?? 'bg-muted text-muted-foreground'"
                                    >
                                        <component
                                            :is="activityIcons[item.subtype] ?? Clock"
                                            class="w-3.5 h-3.5"
                                        />
                                    </div>

                                    <!-- Conteúdo -->
                                    <div class="flex-1 min-w-0 pb-4">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <p
                                                    class="text-sm font-medium"
                                                    :class="item.completed ? 'text-muted-foreground line-through' : 'text-foreground'"
                                                >
                                                    {{ item.title }}
                                                </p>
                                                <!-- Botão concluir para atividades -->
                                                <button
                                                    v-if="item.type === 'activity'"
                                                    @click="toggleActivity(item.id)"
                                                    class="text-muted-foreground hover:text-foreground transition-colors"
                                                >
                                                    <CheckCircle2
                                                        class="w-4 h-4"
                                                        :class="item.completed ? 'text-emerald-500' : ''"
                                                    />
                                                </button>
                                            </div>
                                            <span class="text-[10px] text-muted-foreground whitespace-nowrap flex-shrink-0">
                                                {{ formatDate(item.date) }}
                                            </span>
                                        </div>
                                        <p v-if="item.description" class="text-xs text-muted-foreground mt-0.5">
                                            {{ item.description }}
                                        </p>
                                        <p class="text-[10px] text-muted-foreground/60 mt-1">
                                            por {{ item.user }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- MODAL — Enviar Proposta -->
        <Dialog v-model:open="showSendModal">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>Enviar Proposta ao Cliente</DialogTitle>
                </DialogHeader>
                <div class="space-y-4 py-2">
                    <div class="flex items-center gap-2 p-3 rounded-lg bg-muted/30 border border-border">
                        <FileText class="w-4 h-4 text-primary flex-shrink-0" />
                        <span class="text-sm text-foreground">{{ selectedProposal?.file_name }}</span>
                    </div>
                    <div class="space-y-1.5">
                        <Label>Para <span class="text-destructive">*</span></Label>
                        <Input v-model="sendForm.email" type="email" placeholder="email@cliente.pt" />
                        <p v-if="sendForm.errors.email" class="text-xs text-destructive">{{ sendForm.errors.email }}</p>
                    </div>
                    <div class="space-y-1.5">
                        <Label>Assunto</Label>
                        <Input v-model="sendForm.subject" />
                    </div>
                    <div class="space-y-1.5">
                        <Label>Mensagem</Label>
                        <textarea
                            v-model="sendForm.body"
                            rows="6"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring resize-none"
                        />
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showSendModal = false">Cancelar</Button>
                    <Button @click="sendProposal" :disabled="sendForm.processing" class="gap-1.5 rounded-md">
                        <Send class="w-3.5 h-3.5" />
                        Enviar proposta
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- ══ MODAL — Adicionar Produto ══ -->
        <Dialog v-model:open="showProductModal">
            <DialogContent class="max-w-sm">
                <DialogHeader>
                    <DialogTitle>Adicionar Produto</DialogTitle>
                </DialogHeader>
                <div class="space-y-4 py-2">
                    <div class="space-y-1.5">
                        <Label>Produto</Label>
                        <select
                            v-model="productForm.product_id"
                            @change="onProductSelect"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                        >
                            <option :value="null">Selecionar produto...</option>
                            <option v-for="p in products" :key="p.id" :value="p.id">
                                {{ p.name }} — {{ formatEuro(p.price) }}
                            </option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label>Quantidade</Label>
                            <Input v-model="productForm.quantity" type="number" min="1" />
                        </div>
                        <div class="space-y-1.5">
                            <Label>Preço unitário (€)</Label>
                            <Input v-model="productForm.unit_price" type="number" min="0" step="0.01" />
                        </div>
                    </div>
                    <div v-if="productForm.product_id && productForm.quantity && productForm.unit_price"
                        class="p-3 rounded-lg bg-muted/30 border border-border">
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Total</span>
                            <span class="font-bold text-foreground">
                                {{ formatEuro(productForm.quantity * productForm.unit_price) }}
                            </span>
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showProductModal = false">Cancelar</Button>
                    <Button @click="addProduct" :disabled="!productForm.product_id || productForm.processing">
                        Adicionar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </AuthenticatedLayout>
</template>