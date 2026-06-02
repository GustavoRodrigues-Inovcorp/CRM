<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { ref, watch, computed } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'
import listPlugin from '@fullcalendar/list'
import ptLocale from '@fullcalendar/core/locales/pt'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import {
    Dialog, DialogContent, DialogHeader,
    DialogTitle, DialogFooter,
} from '@/Components/ui/dialog'
import { Plus, MapPin, AlignLeft, Link } from 'lucide-vue-next'

const props = defineProps({
    events:   Array,
    entities: Array,
    people:   Array,
    deals:    Array,
})

const showModal    = ref(false)
const editingEvent = ref(null)

/* ─── Referência ao componente FullCalendar ─── */
const calendarRef = ref(null)

/* ─── Atualiza eventos no FullCalendar quando props mudam ─── */
watch(() => props.events, (newEvents) => {
    const calApi = calendarRef.value?.getApi()
    if (!calApi) return

    /* Remove todos os eventos existentes */
    calApi.removeAllEvents()

    /* Adiciona os novos eventos */
    newEvents.forEach(e => calApi.addEvent(e))
}, { deep: true })

const calendarOptions = ref({
    plugins:     [dayGridPlugin, timeGridPlugin, interactionPlugin, listPlugin],
    initialView: 'dayGridMonth',
    locale:      ptLocale,
    timeZone:    'local', /* ─── Usa o fuso horário local do browser ─── */
    headerToolbar: {
        left:   'prev,next today',
        center: 'title',
        right:  'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
    },
    dateClick(info) {
        openCreate(info.dateStr)
    },
    eventClick(info) {
        openEdit(info.event)
    },
    editable:    true,
    selectable:  true,
    events:      props.events,
    height:      'auto',
    buttonText: {
        today: 'Hoje',
        month: 'Mês',
        week:  'Semana',
        day:   'Dia',
        list:  'Lista',
    },
})

const typeConfig = {
    meeting: { label: 'Reunião',  color: '#3b82f6' },
    call:    { label: 'Chamada',  color: '#10b981' },
    task:    { label: 'Tarefa',   color: '#8b5cf6' },
    email:   { label: 'Email',    color: '#f59e0b' },
}

const form = useForm({
    title:          '',
    description:    '',
    type:           'task',
    start_at:       '',
    end_at:         '',
    location:       '',
    completed:      false,
    eventable_type: null,
    eventable_id:   null,
})

function openCreate(date = '') {
    editingEvent.value = null
    form.reset()
    form.type = 'task'
    if (date) {
        form.start_at = date + 'T09:00'
        form.end_at   = date + 'T10:00'
    }
    showModal.value = true
}

function openEdit(event) {
    editingEvent.value = event
    form.title       = event.title
    form.description = event.extendedProps.description ?? ''
    form.type        = event.extendedProps.type ?? 'task'
    form.location    = event.extendedProps.location ?? ''
    form.completed   = event.extendedProps.completed ?? false

    /* ─── Usa as datas do objeto diretamente sem conversão de fuso ─── */
    const start = event.start
    const end   = event.end

    form.start_at = start
        ? new Date(start.getTime() - start.getTimezoneOffset() * 60000)
            .toISOString().slice(0, 16)
        : ''

    form.end_at = end
        ? new Date(end.getTime() - end.getTimezoneOffset() * 60000)
            .toISOString().slice(0, 16)
        : ''

    showModal.value = true
}

function submit() {
    const opts = { onSuccess: () => { showModal.value = false } }
    editingEvent.value
        ? form.put(route('calendar.update', editingEvent.value.id), opts)
        : form.post(route('calendar.store'), opts)
}

function destroy() {
    if (!editingEvent.value) return
    if (confirm(`Eliminar "${editingEvent.value.title}"?`)) {
        router.delete(route('calendar.destroy', editingEvent.value.id))
        showModal.value = false
    }
}
</script>

<template>
    <Head title="Calendário" />

    <AuthenticatedLayout>
        <template #title>Calendário</template>
        <template #action>
            <Button size="sm" class="gap-1.5 rounded-lg pr-3" @click="openCreate()">
                <Plus class="w-3.5 h-3.5" />
                Novo Evento
            </Button>
        </template>

        <div class="p-6">

            <!-- Legenda dos tipos de evento -->
            <div class="flex items-center gap-4 mb-4">
                <div
                    v-for="(config, type) in typeConfig"
                    :key="type"
                    class="flex items-center gap-1.5"
                >
                    <div
                        class="w-2.5 h-2.5 rounded-full"
                        :style="{ background: config.color }"
                    />
                    <span class="text-xs text-muted-foreground">{{ config.label }}</span>
                </div>
            </div>

            <!-- Calendário FullCalendar -->
            <div class="rounded-xl border border-border overflow-hidden bg-card calendar-wrapper">
                <FullCalendar ref="calendarRef" :options="calendarOptions" />
            </div>
        </div>

        <!-- MODAL — Criar / Editar Evento -->
        <Dialog v-model:open="showModal">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {{ editingEvent ? 'Editar Evento' : 'Novo Evento' }}
                    </DialogTitle>
                </DialogHeader>

                <div class="space-y-4 py-2">

                    <!-- Título -->
                    <div class="space-y-1.5">
                        <Label>Título <span class="text-destructive">*</span></Label>
                        <Input v-model="form.title" placeholder="Ex: Reunião com cliente" />
                        <p v-if="form.errors.title" class="text-xs text-destructive">{{ form.errors.title }}</p>
                    </div>

                    <!-- Tipo -->
                    <div class="space-y-1.5">
                        <Label>Tipo</Label>
                        <div class="flex gap-2">
                            <button
                                v-for="(config, type) in typeConfig"
                                :key="type"
                                @click="form.type = type"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium border transition-all"
                                :class="form.type === type
                                    ? 'border-transparent text-white'
                                    : 'border-border text-muted-foreground hover:border-primary/30'"
                                :style="form.type === type ? { background: config.color } : {}"
                            >
                                {{ config.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Datas -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label>Início <span class="text-destructive">*</span></Label>
                            <Input v-model="form.start_at" type="datetime-local" />
                            <p v-if="form.errors.start_at" class="text-xs text-destructive">{{ form.errors.start_at }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <Label>Fim</Label>
                            <Input v-model="form.end_at" type="datetime-local" />
                        </div>
                    </div>

                    <!-- Localização -->
                    <div class="space-y-1.5">
                        <Label>
                            <MapPin class="w-3.5 h-3.5 inline mr-1" />
                            Localização
                        </Label>
                        <Input v-model="form.location" placeholder="Ex: Sala de reuniões, Google Meet..." />
                    </div>

                    <!-- Descrição -->
                    <div class="space-y-1.5">
                        <Label>
                            <AlignLeft class="w-3.5 h-3.5 inline mr-1" />
                            Descrição
                        </Label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            placeholder="Notas sobre o evento..."
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring resize-none"
                        />
                    </div>

                    <!-- Associar a entidade/pessoa/negócio -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label>
                                <Link class="w-3.5 h-3.5 inline mr-1" />
                                Ligar a
                            </Label>
                            <select
                                v-model="form.eventable_type"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                            >
                                <option :value="null">Nenhum</option>
                                <option value="entity">Empresa</option>
                                <option value="person">Pessoa</option>
                                <option value="deal">Negócio</option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <Label>&nbsp;</Label>
                            <select
                                v-if="form.eventable_type === 'entity'"
                                v-model="form.eventable_id"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                            >
                                <option :value="null">Selecionar...</option>
                                <option v-for="e in entities" :key="e.id" :value="e.id">{{ e.name }}</option>
                            </select>
                            <select
                                v-else-if="form.eventable_type === 'person'"
                                v-model="form.eventable_id"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                            >
                                <option :value="null">Selecionar...</option>
                                <option v-for="p in people" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                            <select
                                v-else-if="form.eventable_type === 'deal'"
                                v-model="form.eventable_id"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                            >
                                <option :value="null">Selecionar...</option>
                                <option v-for="d in deals" :key="d.id" :value="d.id">{{ d.title }}</option>
                            </select>
                            <div v-else class="h-9" />
                        </div>
                    </div>

                    <!-- Concluído (só em edição) -->
                    <div v-if="editingEvent" class="flex items-center gap-2">
                        <input
                            v-model="form.completed"
                            type="checkbox"
                            id="completed"
                            class="w-4 h-4 accent-primary"
                        />
                        <Label for="completed" class="cursor-pointer">Marcar como concluído</Label>
                    </div>

                </div>

                <DialogFooter class="gap-2">
                    <!-- Botão eliminar só em edição -->
                    <Button
                        v-if="editingEvent"
                        variant="destructive"
                        size="sm"
                        @click="destroy"
                        class="mr-auto"
                    >
                        Eliminar
                    </Button>
                    <Button variant="outline" @click="showModal = false">Cancelar</Button>
                    <Button @click="submit" :disabled="form.processing">
                        {{ editingEvent ? 'Guardar' : 'Criar evento' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </AuthenticatedLayout>
</template>

<style>
/* ── Estilo do FullCalendar adaptado ao tema dark/light ── */
.calendar-wrapper .fc {
    --fc-border-color: hsl(var(--border));
    --fc-button-bg-color: hsl(var(--secondary));
    --fc-button-border-color: hsl(var(--border));
    --fc-button-hover-bg-color: hsl(var(--accent));
    --fc-button-hover-border-color: hsl(var(--border));
    --fc-button-active-bg-color: hsl(var(--primary));
    --fc-button-active-border-color: hsl(var(--primary));
    --fc-button-text-color: hsl(var(--foreground));
    --fc-today-bg-color: hsl(var(--primary) / 0.08);
    --fc-page-bg-color: hsl(var(--card));
    --fc-neutral-bg-color: hsl(var(--muted));
    --fc-list-event-hover-bg-color: hsl(var(--accent));
    color: hsl(var(--foreground));
    font-size: 13px;
}

.calendar-wrapper .fc .fc-toolbar-title {
    font-size: 15px;
    font-weight: 600;
    color: hsl(var(--foreground));
}

.calendar-wrapper .fc .fc-button {
    font-size: 12px;
    font-weight: 500;
    padding: 5px 12px;
    border-radius: 8px;
    text-transform: none;
}

.calendar-wrapper .fc .fc-button-active {
    background: hsl(var(--primary)) !important;
    border-color: hsl(var(--primary)) !important;
    color: white !important;
}

.calendar-wrapper .fc .fc-col-header-cell-cushion,
.calendar-wrapper .fc .fc-daygrid-day-number {
    color: hsl(var(--muted-foreground));
    font-size: 12px;
}

.calendar-wrapper .fc .fc-day-today .fc-daygrid-day-number {
    background: hsl(var(--primary));
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.calendar-wrapper .fc .fc-event {
    border-radius: 6px;
    border: none;
    font-size: 11px;
    font-weight: 500;
    padding: 1px 4px;
    cursor: pointer;
}

.calendar-wrapper .fc .fc-toolbar {
    padding: 12px 16px;
    border-bottom: 1px solid hsl(var(--border));
}
</style>