<script setup>
/**
 * Profile/Edit.vue
 * Página de perfil do utilizador.
 * Inclui: informações básicas, password e 2FA (autenticação de dois fatores).
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card'
import { User, Lock, Shield, Trash2, CheckCircle, Smartphone } from 'lucide-vue-next'

/* ─── Props vindas do ProfileController ─── */
const props = defineProps({
    mustVerifyEmail:   Boolean,
    status:            String,
    twoFactorEnabled:  Boolean,
})

const page = usePage()
const user = page.props.auth.user

// Foto de perfil local (preview + actual)
const profilePhoto = ref(user.profile_photo_url || null)
const originalProfilePhoto = ref(user.profile_photo_url || null)
const initials = computed(() => {
    try {
        if (!user || !user.name) return ''
        return user.name.split(' ').map(n => n[0]).slice(0,2).join('').toUpperCase()
    } catch (e) {
        return ''
    }
})
const photoInput = ref(null)
const photoError = ref('')

function triggerSelectPhoto() {
    photoInput.value?.click()
}

function handlePhotoSelected(e) {
    photoError.value = ''
    const file = e.target.files?.[0]
    if (!file) return
    if (!file.type.startsWith('image/')) {
        photoError.value = 'Por favor, escolhe uma imagem.'
        return
    }
    profileForm.photo = file
    profileForm.remove_photo = false
    profilePhoto.value = URL.createObjectURL(file)
}

/* ─── Formulário de informações básicas ─── */
const profileForm = useForm({
    name:  user.name,
    email: user.email,
    photo: null,
    remove_photo: false,
})

/* ─── Formulário de password ─── */
const passwordForm = useForm({
    current_password: '',
    password:         '',
    password_confirmation: '',
})

/* ─── Estado do 2FA ─── */
const twoFactorStep  = ref(null) // null | 'setup' | 'confirm'
const qrCodeData     = ref(null)
const secretKey      = ref(null)
const twoFactorCode  = ref('')
const twoFactorError = ref('')

/* ─── Formulário de desativar 2FA ─── */
const disableForm = useForm({ password: '' })
const showDisable = ref(false)

/* ─── Formulário de eliminar conta ─── */
const deleteForm  = useForm({ password: '' })
const showDelete  = ref(false)

/* ─── Atualiza informações básicas ─── */
function updateProfile() {
    profileForm.patch(route('profile.update'), {
        forceFormData: true,
        onSuccess: () => {
            profilePhoto.value = page.props.auth.user.profile_photo_url || profilePhoto.value
            originalProfilePhoto.value = page.props.auth.user.profile_photo_url || originalProfilePhoto.value
            profileForm.photo = null
            profileForm.remove_photo = false
        },
    })
}

function removePhoto() {
    // A exclusão  ocorre quando o formulário é salvo
    profileForm.remove_photo = true
    profileForm.photo = null
    profilePhoto.value = null
}

function cancelRemovePhoto() {
    profileForm.remove_photo = false
    profilePhoto.value = originalProfilePhoto.value
}

/* ─── Atualiza password ─── */
function updatePassword() {
    passwordForm.patch(route('profile.password'), {
        onSuccess: () => passwordForm.reset(),
    })
}

/* ─── Inicia setup do 2FA ─── */
async function startTwoFactorSetup() {
    twoFactorError.value = ''
    twoFactorStep.value  = 'loading'

    const res  = await window.axios.get(route('profile.2fa.setup'))
    qrCodeData.value = res.data.qrCode
    secretKey.value  = res.data.secret
    twoFactorStep.value = 'setup'
}

/* ─── Confirma e ativa o 2FA ─── */
function confirmTwoFactor() {
    twoFactorError.value = ''

    useForm({
        code:   twoFactorCode.value,
        secret: secretKey.value,
    }).post(route('profile.2fa.enable'), {
        onSuccess: () => {
            twoFactorStep.value = 'done'
            twoFactorCode.value = ''
        },
        onError: (errors) => {
            twoFactorError.value = errors.code ?? 'Código inválido.'
        },
    })
}

/* ─── Desativa o 2FA ─── */
function disableTwoFactor() {
    disableForm.post(route('profile.2fa.disable'), {
        onSuccess: () => {
            showDisable.value = false
            disableForm.reset()
        },
    })
}

/* ─── Elimina a conta ─── */
function deleteAccount() {
    deleteForm.delete(route('profile.destroy'))
}
</script>

<template>
    <Head title="Perfil" />

    <AuthenticatedLayout>
        <template #title>Perfil</template>

        <div class="p-6 max-w-8xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-5">

            <!-- ── Informações básicas ── -->
            <Card class="h-full">
                <CardHeader class="pb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                            <User class="w-4 h-4 text-primary" />
                        </div>
                        <div>
                            <CardTitle class="text-sm font-semibold">Informações básicas</CardTitle>
                            <CardDescription class="text-xs">Nome e endereço de email</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4 flex flex-col justify-between h-full">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label>Nome</Label>
                            <Input v-model="profileForm.name" placeholder="O teu nome" />
                            <p v-if="profileForm.errors.name" class="text-xs text-destructive">{{ profileForm.errors.name }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <Label>Email</Label>
                            <Input v-model="profileForm.email" type="email" placeholder="email@exemplo.pt" />
                            <p v-if="profileForm.errors.email" class="text-xs text-destructive">{{ profileForm.errors.email }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 mt-2 pt-2">
                        <div class="w-16 h-16 rounded-full overflow-hidden bg-muted flex items-center justify-center ring-2 ring-offset-2 ring-offset-background ring-primary/30">
                            <img v-if="profilePhoto" :src="profilePhoto" alt="Foto de perfil" class="w-full h-full object-cover" />
                            <div v-else class="text-sm font-semibold text-muted-foreground">{{ initials }}</div>
                        </div>
                        <div class="flex-1">
                            <input ref="photoInput" type="file" accept="image/*" class="hidden" @change="handlePhotoSelected" />
                            <div class="flex items-center gap-2">
                                <Button size="sm" variant="outline" @click="triggerSelectPhoto" class="rounded-lg">Escolher foto</Button>
                                <Button size="sm" variant="destructive" v-if="profilePhoto && !profileForm.remove_photo" @click="removePhoto">
                                    <Trash2 class="w-4 h-4 mr-1" />Remover foto
                                </Button>
                                <Button size="sm" variant="outline" v-else-if="profileForm.remove_photo" @click="cancelRemovePhoto" class="rounded-lg">
                                    Cancelar remoção
                                </Button>
                            </div>
                            <p v-if="photoError || profileForm.errors.photo" class="text-xs text-destructive mt-2">{{ photoError || profileForm.errors.photo }}</p>
                            <p v-if="profileForm.photo" class="text-xs text-muted-foreground mt-1">Foto selecionada. Clica em Guardar para a aplicar.</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-between pt-1">
                        <p v-if="status === 'profile-updated'" class="text-xs text-emerald-500 flex items-center gap-1">
                            <CheckCircle class="w-3.5 h-3.5" />
                            Perfil atualizado com sucesso.
                        </p>
                        <span v-else />
                        <Button size="sm" @click="updateProfile" :disabled="profileForm.processing" class="rounded-lg">
                            Guardar
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- ── Password ── -->
            <Card class="h-full">
                <CardHeader class="pb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                            <Lock class="w-4 h-4 text-primary" />
                        </div>
                        <div>
                            <CardTitle class="text-sm font-semibold">Password</CardTitle>
                            <CardDescription class="text-xs">Altera a tua palavra-passe</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4 flex flex-col justify-between h-full">
                    <div class="space-y-1.5">
                        <Label>Password atual</Label>
                        <Input v-model="passwordForm.current_password" type="password" placeholder="••••••••" />
                        <p v-if="passwordForm.errors.current_password" class="text-xs text-destructive">{{ passwordForm.errors.current_password }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label>Nova password</Label>
                            <Input v-model="passwordForm.password" type="password" placeholder="••••••••" />
                            <p v-if="passwordForm.errors.password" class="text-xs text-destructive">{{ passwordForm.errors.password }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <Label>Confirmar password</Label>
                            <Input v-model="passwordForm.password_confirmation" type="password" placeholder="••••••••" />
                        </div>
                    </div>
                    <div class="flex items-center justify-between pt-1">
                        <p v-if="status === 'password-updated'" class="text-xs text-emerald-500 flex items-center gap-1">
                            <CheckCircle class="w-3.5 h-3.5" />
                            Password atualizada.
                        </p>
                        <span v-else />
                        <Button size="sm" @click="updatePassword" :disabled="passwordForm.processing" class="rounded-lg">
                            Atualizar password
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- ── 2FA ── -->
            <Card class="h-full">
                <CardHeader class="pb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                            :class="twoFactorEnabled ? 'bg-emerald-500/10' : 'bg-primary/10'">
                            <Shield class="w-4 h-4" :class="twoFactorEnabled ? 'text-emerald-500' : 'text-primary'" />
                        </div>
                        <div>
                            <CardTitle class="text-sm font-semibold">
                                Autenticação de dois fatores
                                <span v-if="twoFactorEnabled"
                                    class="ml-2 text-[10px] bg-emerald-500/10 text-emerald-500 px-1.5 py-0.5 rounded-full font-medium">
                                    Ativo
                                </span>
                            </CardTitle>
                            <CardDescription class="text-xs">
                                Adiciona uma camada extra de segurança à tua conta
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4 flex flex-col justify-between h-full">

                    <!-- 2FA já ativo -->
                    <div v-if="twoFactorEnabled && !showDisable">
                        <p class="text-sm text-muted-foreground mb-4">
                            O 2FA está ativo. A tua conta está protegida com o Google Authenticator.
                        </p>
                        <Button variant="outline" size="sm" class="text-destructive rounded-lg border-destructive/30 hover:bg-destructive/10"
                            @click="showDisable = true">
                            Desativar 2FA
                        </Button>
                    </div>

                    <!-- Confirmar desativação -->
                    <div v-else-if="showDisable" class="space-y-3">
                        <p class="text-sm text-muted-foreground">Introduz a tua password para desativar o 2FA.</p>
                        <Input v-model="disableForm.password" type="password" placeholder="Password atual" class="max-w-xs" />
                        <p v-if="disableForm.errors.password" class="text-xs text-destructive">{{ disableForm.errors.password }}</p>
                        <div class="flex gap-2">
                            <Button variant="outline" size="sm" @click="showDisable = false" class="rounded-lg">
                                Cancelar
                            </Button>
                            <Button size="sm" variant="destructive" @click="disableTwoFactor" :disabled="disableForm.processing" class="rounded-lg">
                                Confirmar desativação
                            </Button>
                        </div>
                    </div>

                    <!-- 2FA não ativo — botão para ativar -->
                    <div v-else-if="!twoFactorStep">
                        <p class="text-sm text-muted-foreground mb-4">
                            Protege a tua conta com o Google Authenticator ou qualquer app TOTP.
                        </p>
                        <Button size="sm" @click="startTwoFactorSetup" class="rounded-lg">
                            <Smartphone class="w-3.5 h-3.5 mr-1.5" />
                            Ativar 2FA
                        </Button>
                    </div>

                    <!-- A carregar QR Code -->
                    <div v-else-if="twoFactorStep === 'loading'" class="text-sm text-muted-foreground">
                        A gerar QR Code...
                    </div>

                    <!-- Setup: mostrar QR Code -->
                    <div v-else-if="twoFactorStep === 'setup'" class="space-y-4">
                        <p class="text-sm text-muted-foreground">
                            Lê o QR Code com o <strong>Google Authenticator</strong> ou outra app TOTP.
                        </p>
                        <div class="flex gap-6 items-start">
                            <!-- QR Code -->
                            <div class="bg-white p-3 rounded-xl border border-border inline-block">
                                <img :src="qrCodeData" alt="QR Code 2FA" class="w-40 h-40" />
                            </div>
                            <div class="flex-1 space-y-3">
                                <div>
                                    <p class="text-xs text-muted-foreground mb-1">Chave manual (se não conseguires ler o QR):</p>
                                    <code class="text-xs bg-muted px-2 py-1 rounded font-mono break-all">{{ secretKey }}</code>
                                </div>
                                <div class="space-y-1.5">
                                    <Label>Código de verificação (6 dígitos)</Label>
                                    <Input
                                        v-model="twoFactorCode"
                                        placeholder="000000"
                                        maxlength="6"
                                        class="max-w-[140px] tracking-widest text-center font-mono text-lg"
                                        @keyup.enter="confirmTwoFactor"
                                    />
                                    <p v-if="twoFactorError" class="text-xs text-destructive">{{ twoFactorError }}</p>
                                </div>
                                <div class="flex gap-2">
                                    <Button variant="outline" size="sm" @click="twoFactorStep = null" class="rounded-lg">Cancelar</Button>
                                    <Button size="sm" @click="confirmTwoFactor" :disabled="twoFactorCode.length !== 6" class="rounded-lg">
                                        Confirmar e ativar
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2FA ativado com sucesso -->
                    <div v-else-if="twoFactorStep === 'done'" class="flex items-center gap-2 text-emerald-500">
                        <CheckCircle class="w-4 h-4" />
                        <span class="text-sm font-medium">2FA ativado com sucesso!</span>
                    </div>

                </CardContent>
            </Card>

            <!-- ── Eliminar conta ── -->
            <Card class="border-destructive/20 h-full">
                <CardHeader class="pb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-destructive/10 flex items-center justify-center">
                            <Trash2 class="w-4 h-4 text-destructive" />
                        </div>
                        <div>
                            <CardTitle class="text-sm font-semibold text-destructive">Eliminar conta</CardTitle>
                            <CardDescription class="text-xs">Esta ação é irreversível</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4 flex flex-col justify-between h-full">
                    <div v-if="!showDelete">
                        <p class="text-sm text-muted-foreground mb-4">
                            Ao eliminar a conta, todos os dados serão permanentemente apagados.
                        </p>
                        <Button variant="outline" size="sm" class="text-destructive rounded-lg border-destructive/30 hover:bg-destructive/10"
                            @click="showDelete = true">
                            Eliminar conta
                        </Button>
                    </div>
                    <div v-else class="space-y-3">
                        <p class="text-sm text-muted-foreground">Introduz a tua password para confirmar.</p>
                        <Input v-model="deleteForm.password" type="password" placeholder="Password atual" class="max-w-xs" />
                        <p v-if="deleteForm.errors.password" class="text-xs text-destructive">{{ deleteForm.errors.password }}</p>
                        <div class="flex gap-2">
                            <Button variant="outline" size="sm" @click="showDelete = false" class="rounded-lg">Cancelar</Button>
                            <Button size="sm" variant="destructive" @click="deleteAccount" :disabled="deleteForm.processing">
                                Confirmar eliminação
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

        </div>
    </AuthenticatedLayout>
</template>