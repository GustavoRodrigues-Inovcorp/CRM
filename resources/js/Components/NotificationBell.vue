<script setup>
/**
 * NotificationBell.vue
 * Sino de notificações com dropdown.
 * Busca notificações via API e atualiza a cada 30 segundos.
 */
import { ref, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { Bell, Check, CheckCheck, Zap, Mail, Calendar, Briefcase } from 'lucide-vue-next'
import { Button } from '@/Components/ui/button'

/* ─── Estado local ─── */
const isOpen       = ref(false)
const notifications = ref([])
const unreadCount  = ref(0)
let   interval     = null

/* ─── Ícones por tipo de notificação ─── */
const typeIcons = {
    follow_up:  Mail,
    automation: Zap,
    calendar:   Calendar,
    deal:       Briefcase,
}

const typeColors = {
    follow_up:  'bg-blue-500/10 text-blue-500',
    automation: 'bg-violet-500/10 text-violet-500',
    calendar:   'bg-amber-500/10 text-amber-500',
    deal:       'bg-emerald-500/10 text-emerald-500',
}

/* ─── Busca notificações do servidor ─── */
async function fetchNotifications() {
    try {
        const res  = await window.axios.get('/notifications')
        notifications.value = res.data.notifications
        unreadCount.value   = res.data.unread_count
    } catch {}
}

/* ─── Marca notificação como lida e navega ─── */
async function handleClick(notification) {
    if (!notification.read) {
        await window.axios.patch(`/notifications/${notification.id}/read`)
        notification.read = true
        unreadCount.value = Math.max(0, unreadCount.value - 1)
    }
    isOpen.value = false
    if (notification.link) router.visit(notification.link)
}

/* ─── Marca todas como lidas ─── */
async function markAllRead() {
    await window.axios.patch('/notifications/read-all')
    notifications.value.forEach(n => n.read = true)
    unreadCount.value = 0
}

/* ─── Formata data relativa ─── */
function formatDate(d) {
    const diff = Math.floor((Date.now() - new Date(d)) / 60000)
    if (diff < 1)    return 'agora'
    if (diff < 60)   return `há ${diff}min`
    if (diff < 1440) return `há ${Math.floor(diff / 60)}h`
    return `há ${Math.floor(diff / 1440)}d`
}

/* ─── Fecha ao clicar fora ─── */
function handleOutsideClick(e) {
    if (!e.target.closest('.notification-bell')) {
        isOpen.value = false
    }
}

onMounted(() => {
    fetchNotifications()
    /* Atualiza a cada 30 segundos */
    interval = setInterval(fetchNotifications, 30000)
    document.addEventListener('click', handleOutsideClick)
})

onUnmounted(() => {
    clearInterval(interval)
    document.removeEventListener('click', handleOutsideClick)
})
</script>

<template>
    <div class="relative notification-bell">

        <!-- Botão do sino -->
        <Button
            variant="ghost"
            size="icon"
            class="w-8 h-8 text-muted-foreground hover:text-foreground relative"
            @click.stop="isOpen = !isOpen; fetchNotifications()"
        >
            <Bell class="w-4 h-4" />
            <!-- Badge de não lidas -->
            <span
                v-if="unreadCount > 0"
                class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-primary text-primary-foreground text-[9px] font-bold rounded-full flex items-center justify-center"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </Button>

        <!-- Dropdown de notificações -->
        <Teleport to="body">
            <div
                v-if="isOpen"
                class="notification-bell fixed z-50 w-80 rounded-xl border border-border shadow-2xl overflow-hidden"
                style="background: hsl(var(--card));"
                :style="{
                    top: '56px',
                    right: '16px',
                }"
            >
                <!-- Header -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-border">
                    <h3 class="text-sm font-semibold text-foreground">Notificações</h3>
                    <button
                        v-if="unreadCount > 0"
                        @click="markAllRead"
                        class="flex items-center gap-1 text-xs text-primary hover:underline"
                    >
                        <CheckCheck class="w-3 h-3" />
                        Marcar todas como lidas
                    </button>
                </div>

                <!-- Lista de notificações -->
                <div class="max-h-96 overflow-y-auto">

                    <!-- Estado vazio -->
                    <div v-if="notifications.length === 0" class="flex flex-col items-center justify-center py-10 gap-2">
                        <Bell class="w-8 h-8 text-muted-foreground/20" />
                        <p class="text-xs text-muted-foreground">Sem notificações</p>
                    </div>

                    <!-- Notificações -->
                    <div
                        v-for="notification in notifications"
                        :key="notification.id"
                        @click="handleClick(notification)"
                        class="flex items-start gap-3 px-4 py-3 border-b border-border/50 cursor-pointer transition-colors last:border-0"
                        :class="notification.read ? 'hover:bg-accent/30' : 'bg-primary/5 hover:bg-primary/10'"
                    >
                        <!-- Ícone do tipo -->
                        <div
                            class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5"
                            :class="typeColors[notification.type] ?? 'bg-muted text-muted-foreground'"
                        >
                            <component
                                :is="typeIcons[notification.type] ?? Bell"
                                class="w-3.5 h-3.5"
                            />
                        </div>

                        <!-- Conteúdo -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <p
                                    class="text-xs font-medium leading-snug"
                                    :class="notification.read ? 'text-muted-foreground' : 'text-foreground'"
                                >
                                    {{ notification.title }}
                                </p>
                                <!-- Ponto de não lida -->
                                <div
                                    v-if="!notification.read"
                                    class="w-2 h-2 rounded-full bg-primary flex-shrink-0 mt-1"
                                />
                            </div>
                            <p v-if="notification.message" class="text-[10px] text-muted-foreground mt-0.5 leading-snug">
                                {{ notification.message }}
                            </p>
                            <p class="text-[10px] text-muted-foreground/60 mt-1">
                                {{ formatDate(notification.created_at) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>