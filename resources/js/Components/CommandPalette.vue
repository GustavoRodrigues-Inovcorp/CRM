<script setup>
/**
 * CommandPalette.vue
 * Pesquisa global da aplicação — abre com ⌘K ou Ctrl+K.
 * Pesquisa em tempo real em Entidades, Pessoas e Negócios.
 */
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { Search, Building2, Users, Briefcase, X } from 'lucide-vue-next'

/* ─── Props: dados para pesquisar ─── */
const props = defineProps({
    entities: { type: Array, default: () => [] },
    people:   { type: Array, default: () => [] },
    deals:    { type: Array, default: () => [] },
})

/* ─── Estado local ─── */
const isOpen      = ref(false)
const searchQuery = ref('')
const selectedIndex = ref(0)

/* ─── Abre e fecha a palette ─── */
function open()  { isOpen.value = true; searchQuery.value = ''; selectedIndex.value = 0 }
function close() { isOpen.value = false }

/* ─── Expõe o método open para o layout ─── */
defineExpose({ open })

/* ─── Atalho de teclado ⌘K / Ctrl+K ─── */
function handleKeydown(e) {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault()
        isOpen.value ? close() : open()
    }
    if (!isOpen.value) return
    if (e.key === 'Escape') close()
    if (e.key === 'ArrowDown') { e.preventDefault(); selectedIndex.value = Math.min(selectedIndex.value + 1, allResults.value.length - 1) }
    if (e.key === 'ArrowUp')   { e.preventDefault(); selectedIndex.value = Math.max(selectedIndex.value - 1, 0) }
    if (e.key === 'Enter' && allResults.value[selectedIndex.value]) {
        navigate(allResults.value[selectedIndex.value])
    }
}

onMounted(()  => window.addEventListener('keydown', handleKeydown))
onUnmounted(() => window.removeEventListener('keydown', handleKeydown))

/* ─── Reset do índice quando a query muda ─── */
watch(searchQuery, () => { selectedIndex.value = 0 })

/* ─── Resultados filtrados por categoria ─── */
const entityResults = computed(() => {
    if (!searchQuery.value) return []
    const q = searchQuery.value.toLowerCase()
    return props.entities
        .filter(e => e.name.toLowerCase().includes(q) || e.email?.toLowerCase().includes(q))
        .slice(0, 3)
        .map(e => ({ ...e, _type: 'entity', _label: e.name, _sub: e.email || e.phone || '', _icon: 'building' }))
})

const peopleResults = computed(() => {
    if (!searchQuery.value) return []
    const q = searchQuery.value.toLowerCase()
    return props.people
        .filter(p => p.name.toLowerCase().includes(q) || p.email?.toLowerCase().includes(q))
        .slice(0, 3)
        .map(p => ({ ...p, _type: 'person', _label: p.name, _sub: p.entity?.name || p.email || '', _icon: 'user' }))
})

const dealResults = computed(() => {
    if (!searchQuery.value) return []
    const q = searchQuery.value.toLowerCase()
    return props.deals
        .filter(d => d.title.toLowerCase().includes(q))
        .slice(0, 3)
        .map(d => ({ ...d, _type: 'deal', _label: d.title, _sub: d.value ? `€${d.value}` : '', _icon: 'briefcase' }))
})

/* ─── Todos os resultados juntos ─── */
const allResults = computed(() => [
    ...entityResults.value,
    ...peopleResults.value,
    ...dealResults.value,
])

/* ─── Navega para o registo selecionado ─── */
function navigate(item) {
    close()
    if (item._type === 'entity') router.visit(route('entities.index'))
    if (item._type === 'person') router.visit(route('people.index'))
    if (item._type === 'deal')   router.visit(route('deals.index'))
}

/* ─── Iniciais para avatar ─── */
function getInitials(name) {
    const parts = name.trim().split(' ')
    return parts.length >= 2
        ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
        : parts[0][0].toUpperCase()
}
</script>

<template>
    <!-- Overlay escuro -->
    <Teleport to="body">
        <div
            v-if="isOpen"
            class="fixed inset-0 z-50 flex items-start justify-center pt-[20vh]"
            style="background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);"
            @click.self="close"
        >
            <!-- Painel da pesquisa -->
            <div class="w-full max-w-xl bg-card border border-border rounded-xl shadow-2xl overflow-hidden mx-4">

                <!-- Campo de pesquisa -->
                <div class="flex items-center gap-3 px-4 py-3 border-b border-border">
                    <Search class="w-4 h-4 text-muted-foreground flex-shrink-0" />
                    <input
                        ref="inputRef"
                        v-model="searchQuery"
                        autofocus
                        placeholder="Pesquisar entidades, pessoas, negócios..."
                        class="flex-1 bg-transparent text-sm text-foreground placeholder:text-muted-foreground focus:outline-none"
                    />
                    <button @click="close" class="text-muted-foreground hover:text-foreground transition-colors">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- Resultados -->
                <div class="max-h-80 overflow-y-auto">

                    <!-- Estado inicial — sugestões rápidas -->
                    <div v-if="!searchQuery" class="p-4">
                        <p class="text-xs text-muted-foreground mb-3 font-medium uppercase tracking-wide">
                            Acesso rápido
                        </p>
                        <div class="space-y-1">
                            <button
                                @click="router.visit(route('entities.index')); close()"
                                class="w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-accent transition-colors text-left"
                            >
                                <div class="w-7 h-7 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                                    <Building2 class="w-3.5 h-3.5 text-primary" />
                                </div>
                                <span class="text-sm text-foreground">Entidades</span>
                            </button>
                            <button
                                @click="router.visit(route('people.index')); close()"
                                class="w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-accent transition-colors text-left"
                            >
                                <div class="w-7 h-7 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                                    <Users class="w-3.5 h-3.5 text-primary" />
                                </div>
                                <span class="text-sm text-foreground">Pessoas</span>
                            </button>
                            <button
                                @click="router.visit(route('deals.index')); close()"
                                class="w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-accent transition-colors text-left"
                            >
                                <div class="w-7 h-7 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                                    <Briefcase class="w-3.5 h-3.5 text-primary" />
                                </div>
                                <span class="text-sm text-foreground">Negócios</span>
                            </button>
                        </div>
                    </div>

                    <!-- Resultados da pesquisa -->
                    <div v-else-if="allResults.length > 0" class="p-2">

                        <!-- Entidades -->
                        <div v-if="entityResults.length > 0">
                            <p class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wide px-3 py-1.5">
                                Entidades
                            </p>
                            <button
                                v-for="(item, i) in entityResults"
                                :key="'e'+item.id"
                                @click="navigate(item)"
                                class="w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-colors text-left"
                                :class="selectedIndex === i ? 'bg-accent' : 'hover:bg-accent/50'"
                            >
                                <div class="w-7 h-7 rounded-lg bg-primary/10 flex items-center justify-center text-[11px] font-bold text-primary flex-shrink-0">
                                    {{ getInitials(item._label) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-foreground truncate">{{ item._label }}</p>
                                    <p v-if="item._sub" class="text-xs text-muted-foreground truncate">{{ item._sub }}</p>
                                </div>
                                <Building2 class="w-3.5 h-3.5 text-muted-foreground/40 flex-shrink-0" />
                            </button>
                        </div>

                        <!-- Pessoas -->
                        <div v-if="peopleResults.length > 0">
                            <p class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wide px-3 py-1.5">
                                Pessoas
                            </p>
                            <button
                                v-for="(item, i) in peopleResults"
                                :key="'p'+item.id"
                                @click="navigate(item)"
                                class="w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-colors text-left"
                                :class="selectedIndex === entityResults.length + i ? 'bg-accent' : 'hover:bg-accent/50'"
                            >
                                <div class="w-7 h-7 rounded-full bg-primary/10 flex items-center justify-center text-[11px] font-bold text-primary flex-shrink-0">
                                    {{ getInitials(item._label) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-foreground truncate">{{ item._label }}</p>
                                    <p v-if="item._sub" class="text-xs text-muted-foreground truncate">{{ item._sub }}</p>
                                </div>
                                <Users class="w-3.5 h-3.5 text-muted-foreground/40 flex-shrink-0" />
                            </button>
                        </div>

                        <!-- Negócios -->
                        <div v-if="dealResults.length > 0">
                            <p class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wide px-3 py-1.5">
                                Negócios
                            </p>
                            <button
                                v-for="(item, i) in dealResults"
                                :key="'d'+item.id"
                                @click="navigate(item)"
                                class="w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-colors text-left"
                                :class="selectedIndex === entityResults.length + peopleResults.length + i ? 'bg-accent' : 'hover:bg-accent/50'"
                            >
                                <div class="w-7 h-7 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                                    <Briefcase class="w-3.5 h-3.5 text-primary" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-foreground truncate">{{ item._label }}</p>
                                    <p v-if="item._sub" class="text-xs text-muted-foreground truncate">{{ item._sub }}</p>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Sem resultados -->
                    <div v-else class="py-12 text-center">
                        <p class="text-sm text-muted-foreground">Sem resultados para "{{ searchQuery }}"</p>
                    </div>
                </div>

                <!-- Rodapé com atalhos -->
                <div class="flex items-center gap-4 px-4 py-2.5 border-t border-border bg-muted/30">
                    <span class="text-[11px] text-muted-foreground flex items-center gap-1">
                        <kbd class="text-[10px] bg-background border border-border px-1.5 py-0.5 rounded">↑↓</kbd>
                        navegar
                    </span>
                    <span class="text-[11px] text-muted-foreground flex items-center gap-1">
                        <kbd class="text-[10px] bg-background border border-border px-1.5 py-0.5 rounded">↵</kbd>
                        selecionar
                    </span>
                    <span class="text-[11px] text-muted-foreground flex items-center gap-1">
                        <kbd class="text-[10px] bg-background border border-border px-1.5 py-0.5 rounded">Esc</kbd>
                        fechar
                    </span>
                </div>
            </div>
        </div>
    </Teleport>
</template>