<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
</script>

<template>
    <Head title="Lead Forms" />

    <AuthenticatedLayout>
        <template #title>Lead Forms</template>

        <div class="p-6">
            <div class="rounded-xl border border-border bg-card p-6">
                <p class="text-sm font-medium text-foreground">Lead Forms</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Secção reservada para formulários de captação de leads.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
