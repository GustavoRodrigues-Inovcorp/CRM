<script setup>
/**
 * People/Index.vue
 * Módulo de Pessoas — listagem com tabela, pesquisa e modal ShadcnVue.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from'@/Components/ui/label'
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
import {
    Users, Plus, Search, Building2,
    Phone, Pencil, Trash2
} from 'lucide-vue-next'

/* ─── Props vindas do PersonController ─── */
const props = defineProps({
    people:   Array,
    entities: Array,
})

/* ─── Estado local ─── */
const showModal     = ref(false)
const editingPerson = ref(null)

/* ─── Formulário Inertia — sincroniza com o backend ─── */
const form = useForm({
    name:      '',
    email:     '',
    phone:     '',
    mobile:    '',
    position:  '',
    entity_id: null,
    status:    'active',
    notes:     '',
})

/* ─── Configuração visual dos estados ─── */
const statusConfig = {
    active:   { label: 'Ativo',   variant: 'default' },
    inactive: { label: 'Inativo', variant: 'secondary' },
}

/* ─── Abre modal no modo criação ─── */
function openCreate() {
    editingPerson.value = null
    form.reset()
    showModal.value = true
}

/* ─── Abre modal no modo edição com dados preenchidos ─── */
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

/* ─── Submete criação ou edição conforme o modo ─── */
function submit() {
    if (editingPerson.value) {
        form.put(route('people.update', editingPerson.value.id), {
            onSuccess: () => { showModal.value = false },
        })
    } else {
        form.post(route('people.store'), {
            onSuccess: () => { showModal.value = false },
        })
    }
}

/* ─── Elimina com confirmação ─── */
function destroy(person) {
    if (confirm(`Eliminar "${person.name}"?`)) {
        router.delete(route('people.destroy', person.id))
    }
}

/* ─── Gera iniciais para o avatar ─── */
function getInitials(name) {
    const parts = name.trim().split(' ')
    return parts.length >= 2
        ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
        : parts[0][0].toUpperCase()
}
</script>

<template>
    <Head title="Pessoas" />

    <AuthenticatedLayout>
        <template #title>Pessoas</template>

        <!-- Botão contextual da página -->
        <template #action>
            <Button size="sm" class="gap-1.5 rounded-lg pr-3" @click="openCreate">
                <Plus class="w-3.5 h-3.5" />
                Nova Pessoa
            </Button>
        </template>

        <div class="p-6 space-y-4">

            <!-- Contagem simples -->
            <p class="text-sm text-muted-foreground">
                {{ people.length }}
                {{ people.length === 1 ? 'pessoa' : 'pessoas' }}
            </p>

            <!-- ── Tabela ShadcnVue ── -->
            <div class="rounded-lg border border-border overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow class="hover:bg-transparent">
                            <TableHead class="w-[280px]">Nome</TableHead>
                            <TableHead>Empresa</TableHead>
                            <TableHead>Contacto</TableHead>
                            <TableHead>Cargo</TableHead>
                            <TableHead>Estado</TableHead>
                            <TableHead class="w-[80px]"></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>

                        <!-- Estado vazio -->
                        <TableRow v-if="people.length === 0">
                            <TableCell colspan="6" class="py-16 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <Users class="w-10 h-10 text-muted-foreground/20" />
                                    <p class="text-sm text-muted-foreground">Nenhuma pessoa encontrada</p>
                                    <Button variant="outline" size="sm" @click="openCreate"
                                        class="gap-1.5 rounded-lg bg-primary/10 border-primary hover:bg-primary/20 text-primary hover:text-primary">
                                        Adicionar primeira pessoa
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>

                        <!-- Linhas de dados -->
                        <TableRow
                            v-for="person in people"
                            :key="person.id"
                            class="group"
                        >
                            <!-- Nome + email -->
                            <TableCell>
                                <div class="flex items-center gap-3">
                                    <!-- Avatar com iniciais -->
                                    <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-[11px] font-bold text-primary flex-shrink-0">
                                        {{ getInitials(person.name) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-foreground leading-none mb-0.5">
                                            {{ person.name }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ person.email || '—' }}
                                        </p>
                                    </div>
                                </div>
                            </TableCell>

                            <!-- Empresa associada -->
                            <TableCell>
                                <div v-if="person.entity" class="flex items-center gap-1.5">
                                    <Building2 class="w-3.5 h-3.5 text-muted-foreground/50" />
                                    <span class="text-sm text-foreground">{{ person.entity.name }}</span>
                                </div>
                                <span v-else class="text-sm text-muted-foreground">—</span>
                            </TableCell>

                            <!-- Telefone/telemóvel -->
                            <TableCell>
                                <div v-if="person.mobile || person.phone" class="flex items-center gap-1.5">
                                    <Phone class="w-3.5 h-3.5 text-muted-foreground/50" />
                                    <span class="text-sm text-foreground">
                                        {{ person.mobile || person.phone }}
                                    </span>
                                </div>
                                <span v-else class="text-sm text-muted-foreground">—</span>
                            </TableCell>

                            <!-- Cargo -->
                            <TableCell>
                                <span class="text-sm text-foreground">
                                    {{ person.position || '—' }}
                                </span>
                            </TableCell>

                            <!-- Estado com Badge -->
                            <TableCell>
                                <Badge
                                    :variant="statusConfig[person.status].variant"
                                    class="text-xs"
                                    :class="person.status === 'active'
                                        ? 'bg-emerald-500/10 rounded-md text-emerald-600 dark:text-emerald-400 border-emerald-500/20 hover:bg-emerald-500/10'
                                        : ''"
                                >
                                    {{ statusConfig[person.status].label }}
                                </Badge>
                            </TableCell>

                            <!-- Ações: editar + eliminar -->
                            <TableCell>
                                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="w-7 h-7"
                                        @click="openEdit(person)"
                                    >
                                        <Pencil class="w-3.5 h-3.5" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
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

        <!--
         MODAL — Criar / Editar Pessoa
         Usa Dialog do ShadcnVue com portal automático 
        -->
        <Dialog v-model:open="showModal">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {{ editingPerson ? 'Editar Pessoa' : 'Nova Pessoa' }}
                    </DialogTitle>
                </DialogHeader>

                <div class="space-y-4 py-2">

                    <!-- Nome + Cargo -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label>Nome <span class="text-destructive">*</span></Label>
                            <Input v-model="form.name" placeholder="Nome completo" />
                            <p v-if="form.errors.name" class="text-xs text-destructive">
                                {{ form.errors.name }}
                            </p>
                        </div>
                        <div class="space-y-1.5">
                            <Label>Cargo</Label>
                            <Input v-model="form.position" placeholder="Ex: Director Comercial" />
                        </div>
                    </div>

                    <!-- Email + Empresa -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label>Email</Label>
                            <Input v-model="form.email" type="email" placeholder="email@empresa.pt" />
                            <p v-if="form.errors.email" class="text-xs text-destructive">
                                {{ form.errors.email }}
                            </p>
                        </div>
                        <div class="space-y-1.5">
                            <Label>Empresa</Label>
                            <select
                                v-model="form.entity_id"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring text-foreground"
                            >
                                <option :value="null">Nenhuma</option>
                                <option
                                    v-for="entity in entities"
                                    :key="entity.id"
                                    :value="entity.id"
                                >
                                    {{ entity.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Telefone + Telemóvel -->
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

                    <!-- Estado -->
                    <div class="space-y-1.5">
                        <Label>Estado</Label>
                        <select
                            v-model="form.status"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring text-foreground"
                        >
                            <option value="active">Ativo</option>
                            <option value="inactive">Inativo</option>
                        </select>
                    </div>

                    <!-- Notas -->
                    <div class="space-y-1.5">
                        <Label>Notas</Label>
                        <Textarea
                            v-model="form.notes"
                            placeholder="Notas sobre esta pessoa..."
                            :rows="3"
                        />
                    </div>

                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showModal = false">Cancelar</Button>
                    <Button @click="submit" :disabled="form.processing">
                        {{ editingPerson ? 'Guardar alterações' : 'Criar Pessoa' }}
                    </Button>
                </DialogFooter>

            </DialogContent>
        </Dialog>

    </AuthenticatedLayout>
</template>