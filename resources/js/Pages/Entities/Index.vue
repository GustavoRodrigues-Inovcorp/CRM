<script setup>
/**
 * Entities/Index.vue
 * Módulo de Entidades — listagem com pesquisa, filtros por estado e acesso ao detalhe.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, router, Link } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import { Textarea } from '@/Components/ui/textarea'
import { Badge } from '@/Components/ui/badge'
import {
    Dialog, DialogContent, DialogHeader,
    DialogTitle, DialogFooter,
} from '@/Components/ui/dialog'
import {
    Table, TableBody, TableCell,
    TableHead, TableHeader, TableRow,
} from '@/Components/ui/table'
import {
    Building2, Plus, Search, Mail,
    Phone, Pencil, Trash2, Filter, X,
} from 'lucide-vue-next'

const props = defineProps({
    entities: Array,
    filters:  Object,
})

/* ─── Filtros locais ─── */
const search = ref(props.filters?.search ?? '')
const status = ref(props.filters?.status ?? '')
const showModal     = ref(false)
const editingEntity = ref(null)

/* ─── Aplica filtros com debounce ─── */
let searchTimeout = null
watch(search, (val) => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => applyFilters(), 400)
})

watch(status, () => applyFilters())

function applyFilters() {
    router.get(route('entities.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true, replace: true })
}

function clearFilters() {
    search.value = ''
    status.value = ''
    router.get(route('entities.index'))
}

/* ─── Formulário ─── */
const form = useForm({
    name: '', vat: '', email: '', phone: '',
    address: '', status: 'prospect',
})

const statusConfig = {
    prospect: { label: 'Prospeto', class: 'bg-amber-500/10 text-amber-500 border-amber-500/20 rounded-md' },
    active:   { label: 'Ativo',    class: 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20 rounded-md' },
    inactive: { label: 'Inativo',  class: 'bg-muted text-muted-foreground border-border rounded-md' },
}

function openCreate() {
    editingEntity.value = null
    form.reset()
    form.status = 'prospect'
    showModal.value = true
}

function openEdit(entity) {
    editingEntity.value = entity
    form.name    = entity.name
    form.vat     = entity.vat     ?? ''
    form.email   = entity.email   ?? ''
    form.phone   = entity.phone   ?? ''
    form.address = entity.address ?? ''
    form.status  = entity.status
    showModal.value = true
}

function submit() {
    const opts = { onSuccess: () => { showModal.value = false } }
    editingEntity.value
        ? form.put(route('entities.update', editingEntity.value.id), opts)
        : form.post(route('entities.store'), opts)
}

function destroy(entity) {
    if (confirm(`Eliminar "${entity.name}"?`))
        router.delete(route('entities.destroy', entity.id))
}

function getInitial(name) {
    return name?.charAt(0).toUpperCase() ?? '?'
}
</script>

<template>
    <Head title="Entidades" />
    <AuthenticatedLayout>
        <template #title>Entidades</template>
        <template #action>
            <Button size="sm" class="gap-1.5 rounded-lg pr-3" @click="openCreate">
                <Plus class="w-3.5 h-3.5" />
                Nova Entidade
            </Button>
        </template>

        <div class="p-6 space-y-4">

            <!-- ── Barra de filtros ── -->
            <div class="flex items-center gap-3 flex-wrap">
                <!-- Pesquisa -->
                <div class="relative flex-1 min-w-48 max-w-72">
                    <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-muted-foreground pointer-events-none" />
                    <Input
                        v-model="search"
                        placeholder="Pesquisar por nome, NIF, email..."
                        class="pl-8 h-8 text-sm"
                    />
                </div>

                <!-- Filtro por estado -->
                <select
                    v-model="status"
                    class="h-8 rounded-md border border-input bg-background px-3 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                >
                    <option value="">Todos os estados</option>
                    <option value="prospect">Prospeto</option>
                    <option value="active">Ativo</option>
                    <option value="inactive">Inativo</option>
                </select>

                <!-- Limpar filtros -->
                <Button
                    v-if="search || status"
                    variant="ghost"
                    size="sm"
                    class="h-8 gap-1.5 text-xs rounded-md"
                    @click="clearFilters"
                >
                    <X class="w-3 h-3" />
                    Limpar
                </Button>

                <p class="ml-auto text-sm text-muted-foreground">
                    {{ entities.length }} {{ entities.length === 1 ? 'entidade' : 'entidades' }}
                </p>
            </div>

            <!-- ── Tabela ── -->
            <div class="rounded-lg border border-border overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow class="hover:bg-transparent">
                            <TableHead>Nome</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Telefone</TableHead>
                            <TableHead>NIF</TableHead>
                            <TableHead>Pessoas</TableHead>
                            <TableHead>Negócios</TableHead>
                            <TableHead>Estado</TableHead>
                            <TableHead class="w-[80px]"></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="entities.length === 0">
                            <TableCell colspan="8" class="py-16 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <Building2 class="w-10 h-10 text-muted-foreground/20" />
                                    <p class="text-sm text-muted-foreground">Nenhuma entidade encontrada</p>
                                    <Button variant="outline" size="sm" @click="openCreate" class="rounded-lg">
                                        Adicionar primeira entidade
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>

                        <TableRow v-for="entity in entities" :key="entity.id" class="group">
                            <!-- Nome — clicável para detalhe -->
                            <TableCell>
                                <Link
                                    :href="route('entities.show', entity.id)"
                                    class="flex items-center gap-3 hover:opacity-80 transition-opacity"
                                >
                                    <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-[12px] font-bold text-primary flex-shrink-0">
                                        {{ getInitial(entity.name) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-foreground hover:text-primary transition-colors">
                                            {{ entity.name }}
                                        </p>
                                        <p v-if="entity.address" class="text-xs text-muted-foreground truncate max-w-[160px]">
                                            {{ entity.address }}
                                        </p>
                                    </div>
                                </Link>
                            </TableCell>

                            <TableCell>
                                <div v-if="entity.email" class="flex items-center gap-1.5">
                                    <Mail class="w-3.5 h-3.5 text-muted-foreground/50" />
                                    <span class="text-sm text-foreground">{{ entity.email }}</span>
                                </div>
                                <span v-else class="text-sm text-muted-foreground">—</span>
                            </TableCell>

                            <TableCell>
                                <div v-if="entity.phone" class="flex items-center gap-1.5">
                                    <Phone class="w-3.5 h-3.5 text-muted-foreground/50" />
                                    <span class="text-sm text-foreground">{{ entity.phone }}</span>
                                </div>
                                <span v-else class="text-sm text-muted-foreground">—</span>
                            </TableCell>

                            <TableCell>
                                <span class="text-sm text-foreground">{{ entity.vat || '—' }}</span>
                            </TableCell>

                            <!-- Contagem de pessoas -->
                            <TableCell>
                                <Badge variant="secondary" class="text-xs rounded-md">
                                    {{ entity.people_count ?? 0 }}
                                </Badge>
                            </TableCell>

                            <!-- Contagem de negócios -->
                            <TableCell>
                                <Badge variant="secondary" class="text-xs rounded-md">
                                    {{ entity.deals_count ?? 0 }}
                                </Badge>
                            </TableCell>

                            <TableCell>
                                <Badge variant="outline" class="text-xs" :class="statusConfig[entity.status]?.class">
                                    {{ statusConfig[entity.status]?.label }}
                                </Badge>
                            </TableCell>

                            <TableCell>
                                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <Button variant="ghost" size="icon" class="w-7 h-7" @click="openEdit(entity)">
                                        <Pencil class="w-3.5 h-3.5" />
                                    </Button>
                                    <Button
                                        variant="ghost" size="icon"
                                        class="w-7 h-7 text-destructive hover:text-destructive hover:bg-destructive/10"
                                        @click="destroy(entity)"
                                    >
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>

        <!-- MODAL -->
        <Dialog v-model:open="showModal">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ editingEntity ? 'Editar Entidade' : 'Nova Entidade' }}</DialogTitle>
                </DialogHeader>
                <div class="space-y-4 py-2">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label>Nome <span class="text-destructive">*</span></Label>
                            <Input v-model="form.name" placeholder="Nome da empresa" />
                            <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <Label>NIF</Label>
                            <Input v-model="form.vat" placeholder="123456789" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label>Email</Label>
                            <Input v-model="form.email" type="email" placeholder="geral@empresa.pt" />
                        </div>
                        <div class="space-y-1.5">
                            <Label>Telefone</Label>
                            <Input v-model="form.phone" placeholder="+351 21 000 0000" />
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <Label>Estado</Label>
                        <select v-model="form.status" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
                            <option value="prospect">Prospeto</option>
                            <option value="active">Ativo</option>
                            <option value="inactive">Inativo</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <Label>Morada</Label>
                        <Input v-model="form.address" placeholder="Rua, Cidade, País" />
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showModal = false">Cancelar</Button>
                    <Button @click="submit" :disabled="form.processing">
                        {{ editingEntity ? 'Guardar alterações' : 'Criar Entidade' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>