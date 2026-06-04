<script setup>
/**
 * LeadForms/Public.vue
 * Formulário público — acessível sem login.
 * Submetido automaticamente cria lead no CRM.
 */
import { useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { CheckCircle2 } from 'lucide-vue-next'

const props = defineProps({
    form: Object,
})

const page      = usePage()
const submitted = computed(() => page.props.flash?.submitted)

/* ─── Gera formulário dinamicamente com os campos configurados ─── */
const formData = useForm(
    Object.fromEntries(
        props.form.fields.map(field => ['field_' + field.label, ''])
    )
)

function submit() {
    formData.post(route('lead-forms.submit', props.form.slug))
}
</script>

<template>
    <div class="min-h-screen bg-background flex items-center justify-center p-6">
        <div class="w-full max-w-md">

            <!-- Cabeçalho -->
            <div class="text-center mb-8">
                <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center mx-auto mb-3">
                    <span class="text-primary text-xl font-bold">C</span>
                </div>
                <h1 class="text-xl font-bold text-foreground">{{ form.name }}</h1>
                <p v-if="form.description" class="text-sm text-muted-foreground mt-1">
                    {{ form.description }}
                </p>
            </div>

            <!-- Confirmação após submissão -->
            <div
                v-if="submitted"
                class="text-center py-12 px-6 rounded-2xl border border-emerald-500/20 bg-emerald-500/5"
            >
                <CheckCircle2 class="w-12 h-12 text-emerald-500 mx-auto mb-3" />
                <p class="text-base font-semibold text-foreground">{{ form.confirmation_message }}</p>
            </div>

            <!-- Formulário -->
            <div v-else class="bg-card border border-border rounded-2xl p-6 space-y-4 shadow-lg">
                <div
                    v-for="field in form.fields"
                    :key="field.label"
                    class="space-y-1.5"
                >
                    <label class="text-sm font-medium text-foreground">
                        {{ field.label }}
                        <span v-if="field.required" class="text-destructive ml-0.5">*</span>
                    </label>

                    <textarea
                        v-if="field.type === 'textarea'"
                        v-model="formData['field_' + field.label]"
                        rows="3"
                        :required="field.required"
                        class="flex w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"
                    />
                    <input
                        v-else
                        v-model="formData['field_' + field.label]"
                        :type="field.type === 'phone' ? 'tel' : field.type"
                        :required="field.required"
                        class="flex h-10 w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30"
                    />

                    <p
                        v-if="formData.errors['field_' + field.label]"
                        class="text-xs text-destructive"
                    >
                        {{ formData.errors['field_' + field.label] }}
                    </p>
                </div>

                <button
                    @click="submit"
                    :disabled="formData.processing"
                    class="w-full h-10 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg text-sm font-semibold transition-all disabled:opacity-50 mt-2"
                >
                    {{ formData.processing ? 'A enviar...' : 'Enviar' }}
                </button>
            </div>

            <p class="text-center text-xs text-muted-foreground mt-4">
                Powered by Inovcorp CRM
            </p>
        </div>
    </div>
</template>