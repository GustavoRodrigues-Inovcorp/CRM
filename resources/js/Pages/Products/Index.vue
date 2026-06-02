<script setup>
/**
 * Products/Index.vue
 * Gestão de produtos — criação, edição e listagem.
 * Produtos são associados a negócios para gerar estatísticas.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import { Badge } from '@/Components/ui/badge'
import {
    Dialog, DialogContent, DialogHeader,
    DialogTitle, DialogFooter,
} from '@/Components/ui/dialog'
import {
    Table, TableBody, TableCell,
    TableHead, TableHeader, TableRow,
} from '@/Components/ui/table'
import { Plus, Pencil, Trash2, Package } from 'lucide-vue-next'

const props = defineProps({
    products: Array,
})

const showModal      = ref(false)
const editingProduct = ref(null)

const form = useForm({
    name:        '',
    description: '',
    price:       '',
    unit:        '',
    active:      true,
})

function openCreate() {
    editingProduct.value = null
    form.reset()
    form.active = true
    showModal.value = true
}

function openEdit(product) {
    editingProduct.value = product
    form.name        = product.name
    form.description = product.description ?? ''
    form.price       = product.price
    form.unit        = product.unit ?? ''
    form.active      = product.active
    showModal.value  = true
}

function submit() {
    const opts = { onSuccess: () => { showModal.value = false } }
    editingProduct.value
        ? form.put(route('products.update', editingProduct.value.id), opts)
        : form.post(route('products.store'), opts)
}

function destroy(product) {
    if (confirm(`Eliminar "${product.name}"?`))
        router.delete(route('products.destroy', product.id))
}

function formatEuro(value) {
    return new Intl.NumberFormat('pt-PT', {
        style: 'currency', currency: 'EUR',
    }).format(value || 0)
}
</script>

<template>
    <Head title="Produtos" />
    <AuthenticatedLayout>
        <template #title>Produtos</template>
        <template #action>
            <Button size="sm" class="gap-1.5 rounded-lg pr-3" @click="openCreate">
                <Plus class="w-3.5 h-3.5" />
                Novo Produto
            </Button>
        </template>

        <div class="p-6">
            <div class="rounded-lg border border-border overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow class="hover:bg-transparent">
                            <TableHead>Nome</TableHead>
                            <TableHead>Preço</TableHead>
                            <TableHead>Unidade</TableHead>
                            <TableHead>Negócios</TableHead>
                            <TableHead>Estado</TableHead>
                            <TableHead class="w-[80px]"></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="products.length === 0">
                            <TableCell colspan="6" class="py-16 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <Package class="w-10 h-10 text-muted-foreground/20" />
                                    <p class="text-sm text-muted-foreground">Nenhum produto criado</p>
                                    <Button variant="outline" size="sm" @click="openCreate" class="rounded-lg">
                                        Criar primeiro produto
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>

                        <TableRow v-for="product in products" :key="product.id" class="group">
                            <TableCell>
                                <div>
                                    <p class="text-sm font-medium text-foreground">{{ product.name }}</p>
                                    <p v-if="product.description" class="text-xs text-muted-foreground truncate max-w-[200px]">
                                        {{ product.description }}
                                    </p>
                                </div>
                            </TableCell>
                            <TableCell>
                                <span class="text-sm font-semibold text-foreground">
                                    {{ formatEuro(product.price) }}
                                </span>
                            </TableCell>
                            <TableCell>
                                <span class="text-sm text-muted-foreground">{{ product.unit || '—' }}</span>
                            </TableCell>
                            <TableCell>
                                <Badge variant="secondary" class="text-xs rounded-md">
                                    {{ product.deals_count }} negócio{{ product.deals_count !== 1 ? 's' : '' }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <Badge
                                    variant="outline"
                                    :class="product.active
                                        ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20 rounded-md'
                                        : 'text-muted-foreground rounded-md'"
                                >
                                    {{ product.active ? 'Ativo' : 'Inativo' }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <Button variant="ghost" size="icon" class="w-7 h-7" @click="openEdit(product)">
                                        <Pencil class="w-3.5 h-3.5" />
                                    </Button>
                                    <Button variant="ghost" size="icon" class="w-7 h-7 text-destructive hover:text-destructive hover:bg-destructive/10" @click="destroy(product)">
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>

        <Dialog v-model:open="showModal">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ editingProduct ? 'Editar Produto' : 'Novo Produto' }}</DialogTitle>
                </DialogHeader>
                <div class="space-y-4 py-2">
                    <div class="space-y-1.5">
                        <Label>Nome <span class="text-destructive">*</span></Label>
                        <Input v-model="form.name" placeholder="Ex: Consultoria, Licença, Hora de trabalho" />
                        <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label>Preço (€) <span class="text-destructive">*</span></Label>
                            <Input v-model="form.price" type="number" min="0" step="0.01" placeholder="0.00" />
                            <p v-if="form.errors.price" class="text-xs text-destructive">{{ form.errors.price }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <Label>Unidade</Label>
                            <Input v-model="form.unit" placeholder="Ex: hora, mês, unidade" />
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <Label>Descrição</Label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            placeholder="Descrição do produto..."
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring resize-none"
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <input v-model="form.active" type="checkbox" id="active" class="w-4 h-4 accent-primary" />
                        <Label for="active" class="cursor-pointer">Produto ativo</Label>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showModal = false">Cancelar</Button>
                    <Button @click="submit" :disabled="form.processing">
                        {{ editingProduct ? 'Guardar' : 'Criar produto' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>