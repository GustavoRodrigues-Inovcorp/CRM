<script setup>
/**
 * Entities/Index.vue
 * Módulo de Entidades — listagem, criação, edição e eliminação.
 * Usa ShadcnVue Table + Dialog com overlay correto.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import { Textarea } from '@/Components/ui/textarea'
import { Badge } from '@/Components/ui/badge'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/Components/ui/dialog'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table'
import { Building2, Plus, Search, Globe, Mail, Phone, Pencil, Trash2 } from 'lucide-vue-next'

/* ─── Props vindas do EntityController ─── */
const props = defineProps({
    entities: Array,
})

/* ─── Estado local ─── */
const showModal      = ref(false)
const editingEntity  = ref(null)

/* ─── Formulário Inertia ─── */
const form = useForm({
    name:    '',
    vat:     '',
    email:   '',
    phone:   '',
    address: '',
    status:  'prospect',
    notes:   '',
})

/* ─── Configuração visual dos estados ─── */
const statusConfig = {
    prospect: { label: 'Prospeto', class: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20 hover:bg-amber-500/10' },
    active:   { label: 'Ativo',    class: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20 hover:bg-emerald-500/10' },
    inactive: { label: 'Inativo',  class: '' },
}

/* ─── Abre modal no modo criação ─── */
function openCreate() {
    editingEntity.value = null
    form.reset()
    form.status = 'prospect'
    showModal.value = true
}

/* ─── Abre modal no modo edição ─── */
function openEdit(entity) {
    editingEntity.value = entity
    form.name    = entity.name
    form.vat     = entity.vat     ?? ''
    form.email   = entity.email   ?? ''
    form.phone   = entity.phone   ?? ''
    form.address = entity.address ?? ''
    form.status  = entity.status
    form.notes   = entity.notes   ?? ''
    showModal.value = true
}

/* ─── Submete criação ou edição ─── */
function submit() {
    if (editingEntity.value) {
        form.put(route('entities.update', editingEntity.value.id), {
            onSuccess: () => { showModal.value = false },
        })
    } else {
        form.post(route('entities.store'), {
            onSuccess: () => { showModal.value = false },
        })
    }
}

/* ─── Elimina com confirmação ─── */
function destroy(entity) {
    if (confirm(`Eliminar "${entity.name}"?`)) {
        router.delete(route('entities.destroy', entity.id))
    }
}

/* ─── Inicial para o avatar ─── */
function getInitial(name) {
    return name?.charAt(0).toUpperCase() ?? '?'
}
</script>

<template>
    <Head title="Entidades" />

    <AuthenticatedLayout>
        <template #title>Entidades</template>

        <!-- Botão contextual na topbar -->
        <template #action>
            <Button size="sm" class="gap-1.5 rounded-lg pr-3" @click="openCreate">
                <Plus class="w-3.5 h-3.5" />
                Nova Entidade
            </Button>
        </template>

        <div class="p-6 space-y-4">

            <!-- Contagem simples -->
            <p class="text-sm text-muted-foreground mb-4">
                {{ entities.length }}
                {{ entities.length === 1 ? 'entidade' : 'entidades' }}
            </p>

            <!-- ── Tabela ShadcnVue ── -->
            <div class="rounded-lg border border-border overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow class="hover:bg-transparent">
                            <TableHead class="w-[260px]">Nome</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Telefone</TableHead>
                            <TableHead>NIF</TableHead>
                            <TableHead>Estado</TableHead>
                            <TableHead class="w-[80px]"></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>

                        <!-- Estado vazio -->
                        <TableRow v-if="entities.length === 0">
                            <TableCell colspan="6" class="py-16 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <Building2 class="w-10 h-10 text-muted-foreground/20" />
                                    <p class="text-sm text-muted-foreground">Nenhuma entidade encontrada</p>
                                    <Button variant="outline" size="sm" @click="openCreate"
                                        class="gap-1.5 rounded-lg bg-primary/10 border-primary hover:bg-primary/20 text-primary hover:text-primary">
                                        Adicionar primeira entidade
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>

                        <!-- Linhas de dados -->
                        <TableRow
                            v-for="entity in entities"
                            :key="entity.id"
                            class="group"
                        >
                            <!-- Nome -->
                            <TableCell>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-[12px] font-bold text-primary flex-shrink-0">
                                        {{ getInitial(entity.name) }}
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-sm text-foreground">{{ entity.name }}</span>
                                        <span v-if="entity.address" class="text-xs text-muted-foreground">{{ entity.address }}</span>
                                    </div>
                                </div>
                            </TableCell>

                            <!-- Email -->
                            <TableCell>
                                <div v-if="entity.email" class="flex items-center gap-1.5">
                                    <Mail class="w-3.5 h-3.5 text-muted-foreground/50" />
                                    <span class="text-sm text-foreground">{{ entity.email }}</span>
                                </div>
                                <span v-else class="text-sm text-muted-foreground">—</span>
                            </TableCell>

                            <!-- Telefone -->
                            <TableCell>
                                <div v-if="entity.phone" class="flex items-center gap-1.5">
                                    <Phone class="w-3.5 h-3.5 text-muted-foreground/50" />
                                    <span class="text-sm text-foreground">{{ entity.phone }}</span>
                                </div>
                                <span v-else class="text-sm text-muted-foreground">—</span>
                            </TableCell>

                            <!-- NIF -->
                            <TableCell>
                                <span class="text-sm text-foreground">{{ entity.vat || '—' }}</span>
                            </TableCell>

                            <!-- Estado com Badge -->
                            <TableCell>
                                <Badge
                                    :variant="statusConfig[entity.status].variant"
                                    class="text-xs"
                                    :class="entity.status === 'active'
                                        ? 'bg-emerald-500/10 rounded-md text-emerald-600 dark:text-emerald-400 border-emerald-500/20 hover:bg-emerald-500/10'
                                        : 'bg-muted/10 rounded-md text-muted-foreground'"
                                >
                                    {{ statusConfig[entity.status].label }}
                                </Badge>
                            </TableCell>

                            <!-- Ações -->
                            <TableCell>
                                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="w-7 h-7"
                                        @click="openEdit(entity)"
                                    >
                                        <Pencil class="w-3.5 h-3.5" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
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

        <!-- MODAL — Criar / Editar Entidade -->
        <Dialog v-model:open="showModal">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {{ editingEntity ? 'Editar Entidade' : 'Nova Entidade' }}
                    </DialogTitle>
                </DialogHeader>

                <div class="space-y-4 py-2">

                    <!-- Nome + NIF -->
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

                    <!-- Email + Telefone -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label>Email</Label>
                            <Input v-model="form.email" type="email" placeholder="geral@empresa.pt" />
                            <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <Label>Telefone</Label>
                            <Input v-model="form.phone" placeholder="+351 21 000 0000" />
                        </div>
                    </div>

                    <!-- Morada -->
                    <div class="space-y-1.5">
                        <Label>Morada</Label>
                        <Input v-model="form.address" placeholder="Rua, Cidade, País" />
                    </div>

                    <!-- Estado -->
                    <div class="space-y-1.5">
                        <Label>Estado</Label>
                        <select
                            v-model="form.status"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring text-foreground"
                        >
                            <option value="prospect">Prospeto</option>
                            <option value="active">Ativo</option>
                            <option value="inactive">Inativo</option>
                        </select>
                    </div>


                    <!-- Notas -->
                    <div class="space-y-1.5">
                        <Label>Notas</Label>
                        <Textarea
                            v-model="form.notes"
                            placeholder="Notas sobre a entidade..."
                            :rows="3"
                        />
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