<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
</script>

<template>
    <Head title="Settings" />

    <AuthenticatedLayout>
        <template #title>Settings</template>

        <div class="p-6">
            <div class="rounded-xl border border-border bg-card p-6">
                <p class="text-sm font-medium text-foreground">Settings</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Secção reservada para configuração da aplicação.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
