<script setup>
/**
 * AuthenticatedLayout.vue
 * Layout principal da aplicação — usado em todas as páginas após login.
 * Contém a sidebar de navegação, topbar e slot de conteúdo.
 */
import { computed, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar'
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import {
    LayoutDashboard, Building2, Users, Briefcase,
    Calendar, BarChart2, Zap, FileText, MessageCircle,
    Settings, Bell, Plus, Search, LogOut
} from 'lucide-vue-next'
import { useTheme } from '@/composables/useTheme'
import { Sun, Moon } from 'lucide-vue-next'
import CommandPalette from '@/Components/CommandPalette.vue'
import { ref } from 'vue'
import { Package } from 'lucide-vue-next'
import NotificationBell from '@/Components/NotificationBell.vue'

/* ─── Referência ao componente de pesquisa global ─── */
const commandPalette = ref(null)

/* ─── Toggle de tema dark/light ─── */
const { isDark, toggleTheme } = useTheme()

/* ─── URL do logo público reativo conforme o tema (dark/light) ─── */
const publicLogo = computed(() => isDark.value ? '/inovcorp_dark.png' : '/inovcorp.png')

/* ─── Atalho exibido conforme a plataforma ─── */
const searchShortcut = computed(() => {
    if (typeof navigator === 'undefined') return 'Ctrl + K'

    const platform = navigator.userAgentData?.platform ?? navigator.platform ?? ''
    const isMac = /Mac|iPhone|iPad|iPod/i.test(platform)

    return isMac ? '⌘ + K' : 'Ctrl + K'
})

/* ─── Dados do utilizador autenticado ─── */
const page = usePage()
const user = computed(() => page.props.auth.user)

// Track if avatar image failed to load so we can show initials fallback
const avatarFailed = ref(false)

watch(() => user.value?.profile_photo_url, () => {
    avatarFailed.value = false
})

/* ─── Iniciais do utilizador para o avatar ─── */
const initials = computed(() => {
    if (!user.value?.name) return '?'
    const parts = user.value.name.split(' ')
    return parts.length >= 2
        ? parts[0][0] + parts[1][0]
        : parts[0][0]
})

/* ─── Navegação principal ─── */
const navMain = [
    { label: 'Dashboard',  icon: LayoutDashboard, route: 'dashboard' },
    { label: 'Entidades',  icon: Building2,        route: 'entities.index' },
    { label: 'Pessoas',    icon: Users,            route: 'people.index' },
    { label: 'Negócios',   icon: Briefcase,        route: 'deals.index' },
    { label: 'Calendário', icon: Calendar,         route: 'calendar.index' },
]

/* ─── Ferramentas / módulos secundários ─── */
const navTools = [
    { label: 'Produtos', icon: Package, route: 'products.index' },
    { label: 'Relatórios',  icon: BarChart2,     route: 'reports.index' },
    { label: 'Automações',  icon: Zap,           route: 'automations.index' },
    { label: 'Formulários', icon: FileText,      route: 'lead-forms.index' },
    { label: 'AI Chat',     icon: MessageCircle, route: 'chat.index', badge: 'IA' },
]

/* ─── Verifica se a rota está ativa para highlight da sidebar ─── */
const isActive = (routeName) => route().current(routeName)
</script>

<template>
    <div class="flex h-screen bg-background">

        <!-- SIDEBAR — Navegação lateral fixa -->
        <aside class="w-[220px] flex-shrink-0 flex flex-col border-r border-border bg-background">

            <!-- Logo e nome da app -->
            <div class="flex items-center gap-2.5 px-4 py-[19.5px] border-b border-border">
                <img :src="publicLogo" alt="Inovcorp CRM" class="h-6 w-6 object-contain" />
                <span class="text-foreground text-sm font-semibold tracking-tight">Inovcorp CRM</span>
            </div>

            <!-- Secção: Principal -->
            <nav class="px-2 pt-3 pb-1">
                <p class="text-[10px] font-semibold text-muted-foreground/60 uppercase tracking-[0.8px] px-2 mb-1.5">
                    Principal
                </p>
                <Link
                    v-for="item in navMain"
                    :key="item.route"
                    :href="route(item.route)"
                    class="flex items-center gap-2.5 px-2.5 py-[7px] rounded-lg mb-[1px] transition-all duration-150 text-[13px]"
                    :class="isActive(item.route)
                        ? 'bg-primary/15 text-primary font-medium'
                        : 'text-muted-foreground hover:bg-white/[0.04] hover:text-foreground'"
                >
                    <component
                        :is="item.icon"
                        class="w-[15px] h-[15px] flex-shrink-0"
                        :class="isActive(item.route) ? 'text-primary' : 'text-muted-foreground/70'"
                    />
                    {{ item.label }}
                </Link>
            </nav>

            <!-- Divisor visual -->
            <div class="mx-3 mt-4 mb-3 border-t border-border/60" />

            <!-- Secção: Ferramentas -->
            <nav class="px-2 pb-1">
                <p class="text-[10px] font-semibold text-muted-foreground/60 uppercase tracking-[0.8px] px-2 mb-1.5">
                    Ferramentas
                </p>
                <Link
                    v-for="item in navTools"
                    :key="item.route"
                    :href="route(item.route)"
                    class="flex items-center gap-2.5 px-2.5 py-[7px] rounded-lg mb-[1px] transition-all duration-150 text-[13px]"
                    :class="isActive(item.route)
                        ? 'bg-primary/15 text-primary font-medium'
                        : 'text-muted-foreground hover:bg-white/[0.04] hover:text-foreground'"
                >
                    <component
                        :is="item.icon"
                        class="w-[15px] h-[15px] flex-shrink-0"
                        :class="isActive(item.route) ? 'text-primary' : 'text-muted-foreground/70'"
                    />
                    {{ item.label }}
                    <!-- Badge especial para AI Chat -->
                    <span
                        v-if="item.badge"
                        class="ml-auto text-[9px] bg-primary/20 text-primary px-1.5 py-0.5 rounded-full font-bold tracking-wide"
                    >
                        {{ item.badge }}
                    </span>
                </Link>
            </nav>

            <!-- Fundo da sidebar: Definições + Perfil do utilizador -->
            <div class="mt-auto px-2 py-2 border-t border-border">
                <!-- Link para definições -->
                <Link
                    :href="route('settings.index')"
                    class="flex items-center gap-2.5 px-2.5 py-[9.3px] rounded-lg text-muted-foreground hover:bg-white/[0.04] hover:text-foreground transition-all mb-1 text-[13px]"
                >
                    <Settings class="w-[15px] h-[15px]" />
                    Definições
                </Link>

                <!-- Avatar e nome do utilizador -->
                <Link
                    :href="route('profile.edit')"
                    class="flex items-center gap-2.5 px-2.5 py-[7px] rounded-lg hover:bg-white/[0.04] transition-all"
                >
                    <Avatar class="w-7 h-7">
                        <!-- Show either the image OR the initials fallback (not both) -->
                        <template v-if="user?.profile_photo_url && !avatarFailed">
                            <img
                                :src="user.profile_photo_url"
                                @error="avatarFailed = true"
                                @load="avatarFailed = false"
                                alt="avatar"
                                class="w-full h-full object-cover rounded-full"
                            />
                        </template>
                        <template v-else>
                            <AvatarFallback class="bg-primary/20 text-primary text-[10px] font-bold">
                                {{ initials }}
                            </AvatarFallback>
                        </template>
                    </Avatar>
                    <div class="flex-1 min-w-0">
                        <p class="text-[12px] text-foreground font-medium truncate leading-none mb-0.5">
                            {{ user?.name }}
                        </p>
                    </div>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="inline-flex items-center justify-center rounded-md p-1.5 text-muted-foreground/70 hover:text-foreground hover:bg-white/[0.05] transition"
                        title="Sair"
                    >
                        <LogOut class="w-3.5 h-3.5" />
                    </Link>
                </Link>
            </div>
        </aside>

        <!-- ÁREA PRINCIPAL — Topbar + Conteúdo -->
        <div class="flex-1 flex flex-col min-w-0 bg-background">

            <!-- Topbar: título da página + ações contextuais -->
            <header class="h-16 flex items-center px-6 border-b border-border flex-shrink-0 gap-3">

                <!-- Título da página (injetado por cada página via slot) -->
                <h1 class="text-[14px] font-semibold text-foreground tracking-tight">
                    <slot name="title" />
                </h1>

                <!-- Ações alinhadas à direita -->
                <div class="ml-auto flex items-center gap-2">

                    <!-- Pesquisa global — abre com o atalho da plataforma -->
                    <button
                        @click="commandPalette?.open()"
                        class="flex items-center gap-2 bg-secondary/80 border border-border rounded-lg px-5 py-1.5 text-muted-foreground text-[12px] hover:bg-accent transition-all"
                    >
                        <Search class="w-3.5 h-3.5" />
                        <span>Pesquisar...</span>
                        <kbd class="ml-1 text-[10px] bg-background/80 px-1.5 py-0.5 rounded border border-border">{{ searchShortcut }}</kbd>
                    </button>

                    <!-- Toggle dark/light mode -->
                    <Button
                        variant="ghost"
                        size="icon"
                        class="w-8 h-8 text-muted-foreground hover:text-foreground"
                        @click="toggleTheme"
                        :title="isDark ? 'Mudar para light mode' : 'Mudar para dark mode'"
                    >
                        <Sun  v-if="isDark"  class="w-4 h-4" />
                        <Moon v-else         class="w-4 h-4" />
                    </Button>

                    <!-- Notificações -->
                    <NotificationBell />

                    <!-- Botão de ação principal (personalizado por cada página) -->
                    <slot name="action" />
                </div>
            </header>

            <!-- Conteúdo da página -->
            <main class="flex-1 overflow-y-auto bg-background">
                <slot />
            </main>
        </div>
        <!-- Pesquisa global — montada aqui para acesso às props do layout -->
        <CommandPalette
            ref="commandPalette"
            :entities="$page.props.entities ?? []"
            :people="$page.props.people ?? []"
            :deals="$page.props.deals ?? []"
        />
    </div>
</template>