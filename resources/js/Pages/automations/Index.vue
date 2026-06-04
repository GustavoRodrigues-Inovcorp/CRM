<script setup>
/**
 * Automations/Index.vue
 * Gestão de automações — fluxos automáticos baseados em condições.
 * Cria atividades quando negócios ficam sem atividade durante X dias.
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
    Zap, Plus, Play, Pause, Trash2,
    Pencil, Clock, AlertCircle, CheckCircle2,
} from 'lucide-vue-next'

const props = defineProps({
    rules: Array,
})

const showModal    = ref(false)
const editingRule  = ref(null)

const form = useForm({
    name:                 '',
    trigger_days:         7,
    activity_type:        'task',
    activity_title:       '',
    activity_description: '',
    notify:               true,
    active:               true,
})

/* ─── Tipos de atividade disponíveis ─── */
const activityTypes = {
    call:    'Chamada',
    meeting: 'Reunião',
    task:    'Tarefa',
    email:   'Email',
    note:    'Nota',
}

function openCreate() {
    editingRule.value = null
    form.reset()
    form.trigger_days  = 7
    form.activity_type = 'task'
    form.notify        = true
    form.active        = true
    showModal.value    = true
}

function openEdit(rule) {
    editingRule.value         = rule
    form.name                 = rule.name
    form.trigger_days         = rule.trigger_days
    form.activity_type        = rule.activity_type
    form.activity_title       = rule.activity_title
    form.activity_description = rule.activity_description ?? ''
    form.notify               = rule.notify
    form.active               = rule.active
    showModal.value           = true
}

function submit() {
    const opts = { onSuccess: () => { showModal.value = false } }
    editingRule.value
        ? form.put(route('automations.update', editingRule.value.id), opts)
        : form.post(route('automations.store'), opts)
}

function destroy(rule) {
    if (confirm(`Eliminar "${rule.name}"?`))
        router.delete(route('automations.destroy', rule.id))
}

function toggle(rule) {
    router.patch(route('automations.toggle', rule.id))
}

function run(rule) {
    if (confirm(`Executar "${rule.name}" agora?\n\nSerão criadas atividades em ${rule.affected_count} negócio(s).`))
        router.post(route('automations.run', rule.id))
}
</script>

<template>
    <Head title="Automações" />

    <AuthenticatedLayout>
        <template #title>Automações</template>
        <template #action>
            <Button size="sm" class="gap-1.5 rounded-lg pr-3" @click="openCreate">
                <Plus class="w-3.5 h-3.5" />
                Nova Automação
            </Button>
        </template>

        <div class="p-6 space-y-5">

            <!-- Explicação -->
            <Card class="border-primary/20 bg-primary/5">
                <CardContent class="pt-2 flex items-start gap-3">
                    <Zap class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
                    <div>
                        <p class="text-sm font-medium text-foreground">Como funcionam as automações</p>
                        <p class="text-xs text-muted-foreground mt-0.5">
                            Define regras que criam automaticamente atividades nos negócios parados.
                            Por exemplo: se um negócio não tiver atividade há 7 dias, criar uma tarefa de follow-up.
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Estado vazio -->
            <div v-if="rules.length === 0" class="flex flex-col items-center justify-center py-20 gap-3">
                <div class="w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center">
                    <Zap class="w-7 h-7 text-primary" />
                </div>
                <p class="text-base font-semibold text-foreground">Sem automações criadas</p>
                <p class="text-sm text-muted-foreground text-center max-w-sm">
                    Cria a tua primeira automação para poupar tempo e manter os negócios sempre acompanhados.
                </p>
                <Button @click="openCreate" class="gap-1.5 mt-2 rounded-lg pr-3">
                    <Plus class="w-3.5 h-3.5" />
                    Criar primeira automação
                </Button>
            </div>

            <!-- Lista de regras -->
            <div v-else class="space-y-3">
                <Card
                    v-for="rule in rules"
                    :key="rule.id"
                    class="transition-all"
                    :class="!rule.active ? 'opacity-60' : ''"
                >
                    <CardContent class="pt-4">
                        <div class="flex items-start justify-between gap-4">

                            <!-- Info da regra -->
                            <div class="flex items-start gap-3 flex-1 min-w-0">
                                <div
                                    class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0"
                                    :class="rule.active ? 'bg-primary/10' : 'bg-muted'"
                                >
                                    <Zap class="w-4 h-4" :class="rule.active ? 'text-primary' : 'text-muted-foreground'" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap mb-1">
                                        <p class="text-sm font-semibold text-foreground">{{ rule.name }}</p>
                                        <Badge
                                            variant="outline"
                                            :class="rule.active
                                                ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20 rounded-md'
                                                : 'text-muted-foreground rounded-md'"
                                        >
                                            {{ rule.active ? 'Ativa' : 'Pausada' }}
                                        </Badge>
                                        <Badge
                                            v-if="rule.affected_count > 0"
                                            variant="outline"
                                            class="bg-amber-500/10 text-amber-500 border-amber-500/20 rounded-md"
                                        >
                                            <AlertCircle class="w-3 h-3 mr-1" />
                                            {{ rule.affected_count }} negócio{{ rule.affected_count !== 1 ? 's' : '' }} afetado{{ rule.affected_count !== 1 ? 's' : '' }}
                                        </Badge>
                                    </div>

                                    <!-- Descrição da regra em linguagem natural -->
                                    <p class="text-xs text-muted-foreground">
                                        Se um negócio não tiver atividade há
                                        <strong class="text-foreground">{{ rule.trigger_days }} dias</strong>,
                                        criar uma
                                        <strong class="text-foreground">{{ activityTypes[rule.activity_type] }}</strong>:
                                        "<em>{{ rule.activity_title }}</em>"
                                    </p>

                                    <!-- Última execução -->
                                    <div class="flex items-center gap-1.5 mt-1.5">
                                        <Clock class="w-3 h-3 text-muted-foreground/50" />
                                        <span class="text-[10px] text-muted-foreground">
                                            {{ rule.last_run_at
                                                ? 'Última execução: ' + new Date(rule.last_run_at).toLocaleDateString('pt-PT')
                                                : 'Nunca executada' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Ações -->
                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                <!-- Executar agora -->
                                <Button
                                    v-if="rule.active && rule.affected_count > 0"
                                    size="sm"
                                    variant="outline"
                                    class="gap-1.5 h-8 text-xs rounded-md"
                                    @click="run(rule)"
                                >
                                    <Play class="w-3 h-3" />
                                    Executar
                                </Button>

                                <!-- Pausar/Ativar -->
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    class="w-8 h-8"
                                    @click="toggle(rule)"
                                    :title="rule.active ? 'Pausar' : 'Ativar'"
                                >
                                    <Pause v-if="rule.active" class="w-3.5 h-3.5 text-muted-foreground" />
                                    <Play v-else class="w-3.5 h-3.5 text-muted-foreground" />
                                </Button>

                                <!-- Editar -->
                                <Button size="icon" variant="ghost" class="w-8 h-8" @click="openEdit(rule)">
                                    <Pencil class="w-3.5 h-3.5 text-muted-foreground" />
                                </Button>

                                <!-- Eliminar -->
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    class="w-8 h-8 text-destructive hover:text-destructive hover:bg-destructive/10"
                                    @click="destroy(rule)"
                                >
                                    <Trash2 class="w-3.5 h-3.5" />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- ══ MODAL — Criar / Editar Automação ══ -->
        <Dialog v-model:open="showModal">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {{ editingRule ? 'Editar Automação' : 'Nova Automação' }}
                    </DialogTitle>
                </DialogHeader>

                <div class="space-y-4 py-2">

                    <div class="space-y-1.5">
                        <Label>Nome da regra <span class="text-destructive">*</span></Label>
                        <Input v-model="form.name" placeholder="Ex: Follow-up semanal" />
                        <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <!-- Gatilho -->
                    <div class="p-3 rounded-lg border border-border bg-muted/20 space-y-3">
                        <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Gatilho</p>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-foreground">Se não houver atividade há</span>
                            <Input
                                v-model="form.trigger_days"
                                type="number"
                                min="1"
                                max="365"
                                class="w-16 h-8 text-center text-sm"
                            />
                            <span class="text-sm text-foreground">dias</span>
                        </div>
                        <p v-if="form.errors.trigger_days" class="text-xs text-destructive">{{ form.errors.trigger_days }}</p>
                    </div>

                    <!-- Ação -->
                    <div class="p-3 rounded-lg border border-border bg-muted/20 space-y-3">
                        <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Ação</p>
                        <div class="space-y-1.5">
                            <Label class="text-xs">Tipo de atividade</Label>
                            <div class="flex gap-1.5 flex-wrap">
                                <button
                                    v-for="(label, type) in activityTypes"
                                    :key="type"
                                    @click="form.activity_type = type"
                                    class="px-2.5 py-1 rounded-lg text-xs font-medium border transition-all"
                                    :class="form.activity_type === type
                                        ? 'bg-primary text-primary-foreground border-primary'
                                        : 'border-border text-muted-foreground hover:border-primary/30'"
                                >
                                    {{ label }}
                                </button>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs">Título da atividade <span class="text-destructive">*</span></Label>
                            <Input
                                v-model="form.activity_title"
                                placeholder="Ex: Fazer follow-up ao cliente"
                                class="h-8 text-sm"
                            />
                            <p v-if="form.errors.activity_title" class="text-xs text-destructive">{{ form.errors.activity_title }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs">Descrição (opcional)</Label>
                            <textarea
                                v-model="form.activity_description"
                                rows="2"
                                placeholder="Instrução adicional para o responsável..."
                                class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring resize-none"
                            />
                        </div>
                    </div>

                    <!-- Notificação -->
                    <div class="flex items-center gap-2">
                        <input
                            v-model="form.notify"
                            type="checkbox"
                            id="notify"
                            class="w-4 h-4 accent-primary"
                        />
                        <Label for="notify" class="cursor-pointer text-sm">
                            Notificar o responsável quando a atividade for criada
                        </Label>
                    </div>

                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showModal = false">Cancelar</Button>
                    <Button @click="submit" :disabled="form.processing">
                        {{ editingRule ? 'Guardar' : 'Criar automação' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </AuthenticatedLayout>
</template>