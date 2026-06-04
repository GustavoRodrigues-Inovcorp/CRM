<script setup>
/**
 * LeadForms/Submissions.vue
 * Lista de submissões de um formulário público.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import {
    Table, TableBody, TableCell,
    TableHead, TableHeader, TableRow,
} from '@/Components/ui/table'
import { ArrowLeft, Building2, Globe } from 'lucide-vue-next'

const props = defineProps({
    form:        Object,
    submissions: Array,
})

function formatDate(d) {
    return new Date(d).toLocaleDateString('pt-PT', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    })
}
</script>

<template>
    <Head :title="'Submissões — ' + form.name" />

    <AuthenticatedLayout>
        <template #title>
            <div class="flex items-center gap-2">
                <Link :href="route('lead-forms.index')" class="text-muted-foreground hover:text-foreground transition-colors">
                    <ArrowLeft class="w-4 h-4" />
                </Link>
                Submissões — {{ form.name }}
            </div>
        </template>

        <div class="p-6">
            <div class="rounded-lg border border-border overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow class="hover:bg-transparent">
                            <TableHead>Data</TableHead>
                            <TableHead v-for="field in form.fields" :key="field.label">
                                {{ field.label }}
                            </TableHead>
                            <TableHead>Lead criada</TableHead>
                            <TableHead>IP</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="submissions.length === 0">
                            <TableCell :colspan="form.fields.length + 3" class="py-12 text-center">
                                <p class="text-sm text-muted-foreground">Sem submissões ainda</p>
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="sub in submissions" :key="sub.id">
                            <TableCell class="text-xs text-muted-foreground">
                                {{ formatDate(sub.created_at) }}
                            </TableCell>
                            <TableCell
                                v-for="field in form.fields"
                                :key="field.label"
                                class="text-sm text-foreground"
                            >
                                {{ sub.data['field_' + field.label] || '—' }}
                            </TableCell>
                            <TableCell>
                                <div v-if="sub.entity" class="flex items-center gap-1.5">
                                    <Building2 class="w-3.5 h-3.5 text-muted-foreground/50" />
                                    <span class="text-xs text-foreground">{{ sub.entity.name }}</span>
                                </div>
                                <span v-else class="text-xs text-muted-foreground">—</span>
                            </TableCell>
                            <TableCell class="text-xs text-muted-foreground">
                                {{ sub.ip }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>