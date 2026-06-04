<script setup>
/**
 * LeadForms/Index.vue
 * Gestão de formulários públicos para geração de leads.
 * Cada formulário gera um link público e código de incorporação.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'
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
    Plus, FileText, Link, Copy, Eye,
    Pencil, Trash2, ExternalLink, Users,
    GripVertical, X, Check,
} from 'lucide-vue-next'

const props = defineProps({
    forms: Array,
})

const showModal   = ref(false)
const editingForm = ref(null)
const copied      = ref(null)
const showEmbed   = ref(null)

/* ─── Formulário de criação/edição ─── */
const form = useForm({
    name:                 '',
    description:          '',
    fields:               [
        { label: 'Nome', type: 'text', required: true },
        { label: 'Email', type: 'email', required: true },
        { label: 'Empresa', type: 'text', required: false },
        { label: 'Telefone', type: 'phone', required: false },
    ],
    confirmation_message: 'Obrigado! Entraremos em contacto em breve.',
    active:               true,
})

/* ─── Tipos de campo disponíveis ─── */
const fieldTypes = {
    text:     'Texto',
    email:    'Email',
    phone:    'Telefone',
    textarea: 'Área de texto',
    select:   'Seleção',
}

function openCreate() {
    editingForm.value = null
    form.reset()
    form.fields = [
        { label: 'Nome', type: 'text', required: true },
        { label: 'Email', type: 'email', required: true },
        { label: 'Empresa', type: 'text', required: false },
        { label: 'Telefone', type: 'phone', required: false },
    ]
    form.confirmation_message = 'Obrigado! Entraremos em contacto em breve.'
    form.active = true
    showModal.value = true
}

function openEdit(f) {
    editingForm.value         = f
    form.name                 = f.name
    form.description          = f.description ?? ''
    form.fields               = [...f.fields]
    form.confirmation_message = f.confirmation_message
    form.active               = f.active
    showModal.value           = true
}

function submit() {
    const opts = { onSuccess: () => { showModal.value = false } }
    editingForm.value
        ? form.put(route('lead-forms.update', editingForm.value.id), opts)
        : form.post(route('lead-forms.store'), opts)
}

function destroy(f) {
    if (confirm(`Eliminar "${f.name}"?`))
        router.delete(route('lead-forms.destroy', f.id))
}

/* ─── Adicionar/remover campos ─── */
function addField() {
    form.fields.push({ label: '', type: 'text', required: false })
}

function removeField(index) {
    form.fields.splice(index, 1)
}

/* ─── Copiar link público ─── */
function copyLink(slug) {
    const url = window.location.origin + '/f/' + slug
    
    /* Tenta clipboard moderno, fallback para método antigo */
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url)
    } else {
        /* Fallback para HTTP */
        const textarea = document.createElement('textarea')
        textarea.value = url
        textarea.style.position = 'fixed'
        textarea.style.opacity = '0'
        document.body.appendChild(textarea)
        textarea.select()
        document.execCommand('copy')
        document.body.removeChild(textarea)
    }
    
    copied.value = slug
    setTimeout(() => { copied.value = null }, 2000)
}

/* ─── Código de incorporação ─── */
function getEmbedCode(slug) {
    const url = window.location.origin + '/f/' + slug
    return `<iframe src="${url}" width="100%" height="600" frameborder="0"></iframe>`
}

function copyEmbed(slug) {
    const code = getEmbedCode(slug)
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(code)
    } else {
        const textarea = document.createElement('textarea')
        textarea.value = code
        textarea.style.position = 'fixed'
        textarea.style.opacity = '0'
        document.body.appendChild(textarea)
        textarea.select()
        document.execCommand('copy')
        document.body.removeChild(textarea)
    }
    
    copied.value = 'embed_' + slug
    setTimeout(() => { copied.value = null }, 2000)
}

function publicUrl(slug) {
    return window.location.origin + '/f/' + slug
}
</script>

<template>
    <Head title="Formulários" />

    <AuthenticatedLayout>
        <template #title>Formulários Públicos</template>
        <template #action>
            <Button size="sm" class="gap-1.5 rounded-lg pr-3" @click="openCreate">
                <Plus class="w-3.5 h-3.5" />
                Novo Formulário
            </Button>
        </template>

        <div class="p-6 space-y-4">

            <!-- Estado vazio -->
            <div v-if="forms.length === 0" class="flex flex-col items-center justify-center py-20 gap-3">
                <div class="w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center">
                    <FileText class="w-7 h-7 text-primary" />
                </div>
                <p class="text-base font-semibold text-foreground">Sem formulários criados</p>
                <p class="text-sm text-muted-foreground text-center max-w-sm">
                    Cria formulários públicos para o teu site e gera leads automaticamente no CRM.
                </p>
                <Button @click="openCreate" class="gap-1.5 mt-2 rounded-lg pr-3 ">
                    <Plus class="w-3.5 h-3.5" />
                    Criar primeiro formulário
                </Button>
            </div>

            <!-- Lista de formulários -->
            <div v-else class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                <Card v-for="f in forms" :key="f.id">
                    <CardHeader class="pb-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="flex items-center gap-2 mb-0.5">
                                    <CardTitle class="text-sm font-semibold">{{ f.name }}</CardTitle>
                                    <Badge
                                        variant="outline"
                                        :class="f.active
                                            ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20 rounded-md'
                                            : 'text-muted-foreground rounded-md'"
                                    >
                                        {{ f.active ? 'Ativo' : 'Inativo' }}
                                    </Badge>
                                </div>
                                <p v-if="f.description" class="text-xs text-muted-foreground">{{ f.description }}</p>
                            </div>
                            <div class="flex gap-1">
                                <Button variant="ghost" size="icon" class="w-7 h-7" @click="openEdit(f)">
                                    <Pencil class="w-3.5 h-3.5 text-muted-foreground" />
                                </Button>
                                <Button
                                    variant="ghost" size="icon"
                                    class="w-7 h-7 text-destructive hover:text-destructive hover:bg-destructive/10"
                                    @click="destroy(f)"
                                >
                                    <Trash2 class="w-3.5 h-3.5" />
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-3">

                        <!-- Estatísticas -->
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-1.5">
                                <Users class="w-3.5 h-3.5 text-muted-foreground/50" />
                                <span class="text-xs text-muted-foreground">
                                    {{ f.submissions_count }} submissão{{ f.submissions_count !== 1 ? 'ões' : '' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <FileText class="w-3.5 h-3.5 text-muted-foreground/50" />
                                <span class="text-xs text-muted-foreground">
                                    {{ f.fields.length }} campo{{ f.fields.length !== 1 ? 's' : '' }}
                                </span>
                            </div>
                        </div>

                        <!-- Link público -->
                        <div class="flex items-center gap-2 p-2 rounded-lg bg-muted/30 border border-border">
                            <Link class="w-3.5 h-3.5 text-muted-foreground/50 flex-shrink-0" />
                            <span class="text-xs text-muted-foreground truncate flex-1 font-mono">
                                /f/{{ f.slug }}
                            </span>
                            <div class="flex gap-1 flex-shrink-0">
                                <button
                                    @click="copyLink(f.slug)"
                                    class="p-1 rounded hover:bg-accent transition-colors"
                                    title="Copiar link"
                                >
                                    <Check v-if="copied === f.slug" class="w-3.5 h-3.5 text-emerald-500" />
                                    <Copy v-else class="w-3.5 h-3.5 text-muted-foreground" />
                                </button>
                                <a
                                    :href="'/f/' + f.slug"
                                    target="_blank"
                                    class="p-1 rounded hover:bg-accent transition-colors"
                                    title="Abrir formulário"
                                >
                                    <ExternalLink class="w-3.5 h-3.5 text-muted-foreground" />
                                </a>
                            </div>
                        </div>

                        <!-- Código de incorporação -->
                        <div>
                            <button
                                @click="showEmbed = showEmbed === f.id ? null : f.id"
                                class="text-xs text-primary hover:underline"
                            >
                                {{ showEmbed === f.id ? 'Ocultar' : 'Ver' }} código de incorporação
                            </button>
                            <div v-if="showEmbed === f.id" class="mt-2 relative">
                                <pre class="text-[10px] bg-muted/50 border border-border rounded-lg p-3 overflow-x-auto text-muted-foreground">{{ getEmbedCode(f.slug) }}</pre>
                                <button
                                    @click="copyEmbed(f.slug)"
                                    class="absolute top-2 right-2 p-1 rounded bg-background border border-border hover:bg-accent transition-colors"
                                >
                                    <Check v-if="copied === 'embed_' + f.slug" class="w-3 h-3 text-emerald-500" />
                                    <Copy v-else class="w-3 h-3 text-muted-foreground" />
                                </button>
                            </div>
                        </div>

                        <!-- Ver submissões -->
                        <Button
                            v-if="f.submissions_count > 0"
                            variant="outline"
                            size="sm"
                            class="w-full gap-1.5 h-8 text-xs"
                            @click="router.visit(route('lead-forms.submissions', f.id))"
                        >
                            <Eye class="w-3.5 h-3.5" />
                            Ver {{ f.submissions_count }} submissão{{ f.submissions_count !== 1 ? 'ões' : '' }}
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- ══ MODAL — Criar / Editar Formulário ══ -->
        <Dialog v-model:open="showModal">
            <DialogContent class="max-w-lg max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>
                        {{ editingForm ? 'Editar Formulário' : 'Novo Formulário' }}
                    </DialogTitle>
                </DialogHeader>

                <div class="space-y-4 py-2">

                    <div class="space-y-1.5">
                        <Label>Nome <span class="text-destructive">*</span></Label>
                        <Input v-model="form.name" placeholder="Ex: Contacto do site" />
                        <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label>Descrição</Label>
                        <Input v-model="form.description" placeholder="Descrição opcional" />
                    </div>

                    <!-- Campos do formulário -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <Label>Campos <span class="text-destructive">*</span></Label>
                            <Button variant="ghost" size="sm" class="h-7 text-xs gap-1" @click="addField">
                                <Plus class="w-3 h-3" />
                                Adicionar campo
                            </Button>
                        </div>

                        <div class="space-y-2">
                            <div
                                v-for="(field, index) in form.fields"
                                :key="index"
                                class="flex items-center gap-2 p-2.5 rounded-lg border border-border bg-muted/20"
                            >
                                <GripVertical class="w-3.5 h-3.5 text-muted-foreground/30 flex-shrink-0" />
                                <Input
                                    v-model="field.label"
                                    placeholder="Nome do campo"
                                    class="h-7 text-xs flex-1"
                                />
                                <select
                                    v-model="field.type"
                                    class="h-7 rounded-md border border-input bg-background px-2 text-xs text-foreground focus:outline-none"
                                >
                                    <option v-for="(label, type) in fieldTypes" :key="type" :value="type">
                                        {{ label }}
                                    </option>
                                </select>
                                <label class="flex items-center gap-1 text-xs text-muted-foreground whitespace-nowrap">
                                    <input v-model="field.required" type="checkbox" class="w-3 h-3 accent-primary" />
                                    Obrig.
                                </label>
                                <button
                                    @click="removeField(index)"
                                    class="text-destructive/60 hover:text-destructive transition-colors"
                                    :disabled="form.fields.length <= 1"
                                >
                                    <X class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label>Mensagem de confirmação</Label>
                        <Input
                            v-model="form.confirmation_message"
                            placeholder="Mensagem após submissão"
                        />
                    </div>

                    <div v-if="editingForm" class="flex items-center gap-2">
                        <input v-model="form.active" type="checkbox" id="active" class="w-4 h-4 accent-primary" />
                        <Label for="active" class="cursor-pointer">Formulário ativo</Label>
                    </div>

                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showModal = false">Cancelar</Button>
                    <Button @click="submit" :disabled="form.processing">
                        {{ editingForm ? 'Guardar' : 'Criar formulário' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </AuthenticatedLayout>
</template>