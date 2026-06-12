<script setup>
/**
 * People/Index.vue
 * Módulo de Pessoas — listagem com pesquisa, filtros e acesso ao detalhe.
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
    Users, Plus, Search, Building2,
    Phone, Pencil, Trash2, X,
} from 'lucide-vue-next'

const props = defineProps({
    people:   Array,
    entities: Array,
    filters:  Object,
})

/* ─── Filtros ─── */
const search = ref(props.filters?.search ?? '')
const status = ref(props.filters?.status ?? '')
const showModal     = ref(false)
const editingPerson = ref(null)

let searchTimeout = null
watch(search, () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => applyFilters(), 400)
})
watch(status, () => applyFilters())

function applyFilters() {
    router.get(route('people.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true, replace: true })
}

function clearFilters() {
    search.value = ''
    status.value = ''
    router.get(route('people.index'))
}

/* ─── Formulário ─── */
const form = useForm({
    name: '', email: '', phone: '', mobile: '',
    position: '', entity_id: null, status: 'active', notes: '',
})

const statusConfig = {
    active:   { label: 'Ativo',   class: 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20 rounded-md' },
    inactive: { label: 'Inativo', class: 'bg-muted text-muted-foreground border-muted-foreground/20 rounded-md' },
}

function openCreate() {
    editingPerson.value = null
    form.reset()
    form.status = 'active'
    showModal.value = true
}

function openEdit(person) {
    editingPerson.value = person
    form.name      = person.name
    form.email     = person.email     ?? ''
    form.phone     = person.phone     ?? ''
    form.mobile    = person.mobile    ?? ''
    form.position  = person.position  ?? ''
    form.entity_id = person.entity_id ?? null
    form.status    = person.status
    form.notes     = person.notes     ?? ''
    showModal.value = true
}

function submit() {
    const opts = { onSuccess: () => { showModal.value = false } }
    editingPerson.value
        ? form.put(route('people.update', editingPerson.value.id), opts)
        : form.post(route('people.store'), opts)
}

function destroy(person) {
    if (confirm(`Eliminar "${person.name}"?`))
        router.delete(route('people.destroy', person.id))
}

function getInitials(name) {
    const parts = name?.trim().split(' ') ?? []
    return parts.length >= 2
        ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
        : (parts[0]?.[0] ?? '?').toUpperCase()
}
</script>

<template>
    <Head title="Pessoas" />
    <AuthenticatedLayout>
        <template #title>Pessoas</template>
        <template #action>
            <Button size="sm" class="gap-1.5 rounded-lg pr-3" @click="openCreate">
                <Plus class="w-3.5 h-3.5" />
                Nova Pessoa
            </Button>
        </template>

        <div class="p-6 space-y-4">

            <!-- ── Filtros ── -->
            <div class="flex items-center gap-3 flex-wrap">
                <div class="relative flex-1 min-w-48 max-w-72">
                    <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-muted-foreground pointer-events-none" />
                    <Input v-model="search" placeholder="Pesquisar por nome, email, cargo..." class="pl-8 h-8 text-sm" />
                </div>
                <select
                    v-model="status"
                    class="h-8 rounded-md border border-input bg-background px-3 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                >
                    <option value="">Todos os estados</option>
                    <option value="active">Ativo</option>
                    <option value="inactive">Inativo</option>
                </select>
                <Button v-if="search || status" variant="ghost" size="sm" class="h-8 gap-1.5 text-xs rounded-lg" @click="clearFilters">
                    <X class="w-3 h-3" />
                    Limpar
                </Button>
                <p class="ml-auto text-sm text-muted-foreground">
                    {{ people.length }} {{ people.length === 1 ? 'pessoa' : 'pessoas' }}
                </p>
            </div>

            <!-- ── Tabela ── -->
            <div class="rounded-lg border border-border overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow class="hover:bg-transparent">
                            <TableHead class="w-[280px]">Nome</TableHead>
                            <TableHead>Empresa</TableHead>
                            <TableHead>Contacto</TableHead>
                            <TableHead>Cargo</TableHead>
                            <TableHead>Negócios</TableHead>
                            <TableHead>Estado</TableHead>
                            <TableHead class="w-[80px]"></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="people.length === 0">
                            <TableCell colspan="7" class="py-16 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <Users class="w-10 h-10 text-muted-foreground/20" />
                                    <p class="text-sm text-muted-foreground">Nenhuma pessoa encontrada</p>
                                    <Button variant="outline" size="sm" @click="openCreate" class="gap-1.5 rounded-lg">
                                        Adicionar primeira pessoa
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>

                        <TableRow v-for="person in people" :key="person.id" class="group">
                            <!-- Nome — clicável para detalhe -->
                            <TableCell>
                                <Link
                                    :href="route('people.show', person.id)"
                                    class="flex items-center gap-3 hover:opacity-80 transition-opacity"
                                >
                                    <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-[11px] font-bold text-primary flex-shrink-0">
                                        {{ getInitials(person.name) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-foreground hover:text-primary transition-colors">
                                            {{ person.name }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">{{ person.email || '—' }}</p>
                                    </div>
                                </Link>
                            </TableCell>

                            <TableCell>
                                <div v-if="person.entity" class="flex items-center gap-1.5">
                                    <Building2 class="w-3.5 h-3.5 text-muted-foreground/50" />
                                    <span class="text-sm text-foreground">{{ person.entity.name }}</span>
                                </div>
                                <span v-else class="text-sm text-muted-foreground">—</span>
                            </TableCell>

                            <TableCell>
                                <div v-if="person.mobile || person.phone" class="flex items-center gap-1.5">
                                    <Phone class="w-3.5 h-3.5 text-muted-foreground/50" />
                                    <span class="text-sm text-foreground">{{ person.mobile || person.phone }}</span>
                                </div>
                                <span v-else class="text-sm text-muted-foreground">—</span>
                            </TableCell>

                            <TableCell>
                                <span class="text-sm text-foreground">{{ person.position || '—' }}</span>
                            </TableCell>

                            <TableCell>
                                <Badge variant="secondary" class="text-xs rounded-md">
                                    {{ person.deals_count ?? 0 }}
                                </Badge>
                            </TableCell>

                            <TableCell>
                                <Badge variant="outline" class="text-xs rounded-md" :class="statusConfig[person.status]?.class">
                                    {{ statusConfig[person.status]?.label }}
                                </Badge>
                            </TableCell>

                            <TableCell>
                                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <Button variant="ghost" size="icon" class="w-7 h-7" @click="openEdit(person)">
                                        <Pencil class="w-3.5 h-3.5" />
                                    </Button>
                                    <Button
                                        variant="ghost" size="icon"
                                        class="w-7 h-7 text-destructive hover:text-destructive hover:bg-destructive/10"
                                        @click="destroy(person)"
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
                    <DialogTitle>{{ editingPerson ? 'Editar Pessoa' : 'Nova Pessoa' }}</DialogTitle>
                </DialogHeader>
                <div class="space-y-4 py-2">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label>Nome <span class="text-destructive">*</span></Label>
                            <Input v-model="form.name" placeholder="Nome completo" />
                            <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <Label>Cargo</Label>
                            <Input v-model="form.position" placeholder="Ex: Director Comercial" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label>Email</Label>
                            <Input v-model="form.email" type="email" placeholder="email@empresa.pt" />
                        </div>
                        <div class="space-y-1.5">
                            <Label>Empresa</Label>
                            <select v-model="form.entity_id" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
                                <option :value="null">Nenhuma</option>
                                <option v-for="entity in entities" :key="entity.id" :value="entity.id">
                                    {{ entity.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label>Telefone</Label>
                            <Input v-model="form.phone" placeholder="+351 21 000 0000" />
                        </div>
                        <div class="space-y-1.5">
                            <Label>Telemóvel</Label>
                            <Input v-model="form.mobile" placeholder="+351 912 345 678" />
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <Label>Estado</Label>
                        <select v-model="form.status" class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
                            <option value="active">Ativo</option>
                            <option value="inactive">Inativo</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <Label>Notas</Label>
                        <Textarea v-model="form.notes" placeholder="Notas sobre esta pessoa..." :rows="3" />
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showModal = false">Cancelar</Button>
                    <Button @click="submit" :disabled="form.processing">
                        {{ editingPerson ? 'Guardar alterações' : 'Criar pessoa' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>