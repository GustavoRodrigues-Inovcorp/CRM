<script setup>
/**
 * Settings/Index.vue
 * Página de definições — inclui logs detalhados de auditoria.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Badge } from '@/Components/ui/badge'
import {
    Table, TableBody, TableCell,
    TableHead, TableHeader, TableRow,
} from '@/Components/ui/table'
import {
    Shield, Plus, Edit, Trash2,
    LogIn, LogOut, Activity, ChevronLeft, ChevronRight,
} from 'lucide-vue-next'

const props = defineProps({
    logs: Object,
})

/* ─── Configuração visual por ação ─── */
const actionConfig = {
    create:  { label: 'Criação',   icon: Plus,     class: 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' },
    update:  { label: 'Edição',    icon: Edit,     class: 'bg-blue-500/10 text-blue-500 border-blue-500/20' },
    delete:  { label: 'Eliminação', icon: Trash2,  class: 'bg-red-500/10 text-red-500 border-red-500/20' },
    login:   { label: 'Login',     icon: LogIn,    class: 'bg-violet-500/10 text-violet-500 border-violet-500/20' },
    logout:  { label: 'Logout',    icon: LogOut,   class: 'bg-gray-500/10 text-gray-400 border-gray-500/20' },
}

function formatDate(d) {
    return new Date(d).toLocaleDateString('pt-PT', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    })
}
</script>

<template>
    <Head title="Definições" />

    <AuthenticatedLayout>
        <template #title>Definições</template>

        <div class="p-6 space-y-5">

            <!-- Logs de auditoria -->
            <Card>
                <CardHeader class="pb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                            <Shield class="w-4 h-4 text-primary" />
                        </div>
                        <div>
                            <CardTitle class="text-sm font-semibold">Logs de Auditoria</CardTitle>
                            <p class="text-xs text-muted-foreground">Registo de todas as ações realizadas na conta</p>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead>Ação</TableHead>
                                <TableHead>Descrição</TableHead>
                                <TableHead>Alterações</TableHead>
                                <TableHead>IP</TableHead>
                                <TableHead>Data</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="logs.data.length === 0">
                                <TableCell colspan="5" class="py-12 text-center">
                                    <Activity class="w-8 h-8 text-muted-foreground/20 mx-auto mb-2" />
                                    <p class="text-sm text-muted-foreground">Sem logs ainda</p>
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="log in logs.data" :key="log.id">
                                <!-- Ação -->
                                <TableCell>
                                    <Badge
                                        variant="outline"
                                        class="text-xs gap-1 rounded-md"
                                        :class="actionConfig[log.action]?.class ?? ''"
                                    >
                                        <component
                                            :is="actionConfig[log.action]?.icon ?? Activity"
                                            class="w-3 h-3"
                                        />
                                        {{ actionConfig[log.action]?.label ?? log.action }}
                                    </Badge>
                                </TableCell>

                                <!-- Descrição -->
                                <TableCell class="text-sm text-foreground max-w-[250px]">
                                    <p class="truncate">{{ log.description }}</p>
                                    <p v-if="log.model" class="text-xs text-muted-foreground">{{ log.model }} #{{ log.model_id }}</p>
                                </TableCell>

                                <!-- Alterações -->
                                <TableCell>
                                    <div v-if="log.changes" class="space-y-0.5 max-w-[200px]">
                                        <div
                                            v-for="(change, field) in log.changes"
                                            :key="field"
                                            class="text-[10px] text-muted-foreground"
                                        >
                                            <span class="font-medium text-foreground">{{ field }}:</span>
                                            <span class="line-through ml-1 text-red-400/70">{{ change.from ?? '—' }}</span>
                                            <span class="ml-1 text-emerald-400/70">→ {{ change.to ?? '—' }}</span>
                                        </div>
                                    </div>
                                    <span v-else class="text-xs text-muted-foreground">—</span>
                                </TableCell>

                                <!-- IP -->
                                <TableCell class="text-xs text-muted-foreground font-mono">
                                    {{ log.ip ?? '—' }}
                                </TableCell>

                                <!-- Data -->
                                <TableCell class="text-xs text-muted-foreground whitespace-nowrap">
                                    {{ formatDate(log.created_at) }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- Paginação -->
                    <div v-if="logs.last_page > 1" class="flex items-center justify-between px-5 py-3 border-t border-border">
                        <p class="text-xs text-muted-foreground">
                            {{ logs.from }}–{{ logs.to }} de {{ logs.total }} registos
                        </p>
                        <div class="flex gap-1">
                            <Link
                                v-if="logs.prev_page_url"
                                :href="logs.prev_page_url"
                                class="w-7 h-7 rounded-lg border border-border flex items-center justify-center hover:bg-accent transition-colors"
                            >
                                <ChevronLeft class="w-3.5 h-3.5" />
                            </Link>
                            <Link
                                v-if="logs.next_page_url"
                                :href="logs.next_page_url"
                                class="w-7 h-7 rounded-lg border border-border flex items-center justify-center hover:bg-accent transition-colors"
                            >
                                <ChevronRight class="w-3.5 h-3.5" />
                            </Link>
                        </div>
                    </div>
                </CardContent>
            </Card>

        </div>
    </AuthenticatedLayout>
</template>