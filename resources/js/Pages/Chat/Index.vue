<script setup>
/**
 * Chat/Index.vue
 * AI Chat — assistente comercial inteligente com streaming.
 * Permite consultar dados do CRM em linguagem natural.
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, nextTick, computed } from 'vue'
import { Button } from '@/Components/ui/button'
import { Trash2, Send, Bot, User, Sparkles, RefreshCw } from 'lucide-vue-next'

/* ─── Props vindas do AiChatController ─── */
const props = defineProps({
    history: Array,
})

/* ─── Estado local ─── */
const message      = ref('')
const isStreaming  = ref(false)
const messagesRef  = ref(null)

/* ─── Mensagens locais (histórico + conversa atual) ─── */
const messages = ref(
    props.history.map(h => ([
        { role: 'user',      content: h.user_message, id: `u${h.id}` },
        { role: 'assistant', content: h.ai_response,  id: `a${h.id}` },
    ])).flat()
)

/* ─── Sugestões rápidas ─── */
const suggestions = [
    'Qual o volume total do meu pipeline?',
    'Quais os negócios em risco?',
    'Lista os negócios em negociação',
    'Qual o próximo passo para fechar mais negócios?',
    'Quem são os meus principais contactos?',
    'Que reuniões tenho esta semana?',
]

/* ─── Scroll para o fundo ─── */
async function scrollToBottom() {
    await nextTick()
    if (messagesRef.value) {
        messagesRef.value.scrollTop = messagesRef.value.scrollHeight
    }
}

/* ─── Envia mensagem com streaming ─── */
async function sendMessage(text = null) {
    const msg = text ?? message.value.trim()
    if (!msg || isStreaming.value) return

    message.value     = ''
    isStreaming.value = true

    messages.value.push({ role: 'user', content: msg, id: Date.now() + 'u' })

    const aiMsgId = Date.now() + 'a'
    messages.value.push({ role: 'assistant', content: '', id: aiMsgId, streaming: true })

    await scrollToBottom()

    try {
        /* ─── Obtém o CSRF token do meta tag ─── */
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')

        const response = await fetch('/chat/send', {
            method:  'POST',
            headers: {
                'Content-Type':  'application/json',
                'X-CSRF-TOKEN':  csrfToken,
                'Accept':        'text/event-stream',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ message: msg }),
        })

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`)
        }

        const reader  = response.body.getReader()
        const decoder = new TextDecoder()
        let   buffer  = ''

        while (true) {
            const { done, value } = await reader.read()
            if (done) break

            buffer += decoder.decode(value, { stream: true })
            const lines = buffer.split('\n')
            buffer = lines.pop() ?? ''

            for (const line of lines) {
                if (!line.startsWith('data: ')) continue
                const data = line.slice(6).trim()
                if (data === '[DONE]') break

                try {
                    const parsed = JSON.parse(data)
                    const aiMsg  = messages.value.find(m => m.id === aiMsgId)
                    if (aiMsg && parsed.text) {
                        aiMsg.content += parsed.text
                        await scrollToBottom()
                    }
                } catch {}
            }
        }

    } catch (e) {
        console.error('Erro:', e)
        const aiMsg = messages.value.find(m => m.id === aiMsgId)
        if (aiMsg) aiMsg.content = `Erro: ${e.message}. Tenta novamente.`
    } finally {
        const aiMsg = messages.value.find(m => m.id === aiMsgId)
        if (aiMsg) aiMsg.streaming = false
        isStreaming.value = false
        await scrollToBottom()
    }
}

/* ─── Limpa o histórico ─── */
function clearHistory() {
    if (!confirm('Limpar todo o histórico do chat?')) return
    router.delete(route('chat.clearHistory'), {
        onSuccess: () => { messages.value = [] },
    })
}

/* ─── Renderiza markdown simples ─── */
function renderMarkdown(text) {
    return text
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
        .replace(/`(.*?)`/g, '<code class="bg-muted px-1 py-0.5 rounded text-xs font-mono">$1</code>')
        .replace(/^### (.*$)/gm, '<h3 class="text-sm font-semibold mt-3 mb-1">$1</h3>')
        .replace(/^## (.*$)/gm, '<h2 class="text-sm font-semibold mt-3 mb-1">$1</h2>')
        .replace(/^- (.*$)/gm, '<li class="ml-4 list-disc text-sm">$1</li>')
        .replace(/\n/g, '<br>')
}
</script>

<template>
    <Head title="AI Chat" />

    <AuthenticatedLayout>
        <template #title>AI Chat</template>

        <div class="flex h-full min-h-0 flex-col overflow-hidden">

            <!-- ── Header do chat ── -->
            <div class="flex items-center justify-between px-6 py-3 border-b border-border bg-card/50">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                        <Sparkles class="w-4 h-4 text-primary" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-foreground">Assistente Comercial</p>
                        <p class="text-[11px] text-muted-foreground">Powered by Groq · Llama 3.3</p>
                    </div>
                </div>
                <Button
                    v-if="messages.length > 0"
                    variant="ghost"
                    size="sm"
                    class="text-muted-foreground gap-1.5 rounded-lg"
                    @click="clearHistory"
                >
                    <Trash2 class="w-3.5 h-3.5" />
                    Limpar
                </Button>
            </div>

            <!-- ── Área de mensagens ── -->
            <div
                ref="messagesRef"
                    class="min-h-0 flex-1 overflow-y-auto px-6 py-4 space-y-4"
            >

                <!-- Estado vazio — sugestões rápidas -->
                <div v-if="messages.length === 0" class="flex flex-col items-center justify-center h-full gap-6">
                    <div class="text-center">
                        <div class="w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center mx-auto mb-3">
                            <Bot class="w-7 h-7 text-primary" />
                        </div>
                        <p class="text-base font-semibold text-foreground">Como posso ajudar?</p>
                        <p class="text-sm text-muted-foreground mt-1">
                            Pergunta-me sobre os teus negócios, contactos ou pipeline.
                        </p>
                    </div>

                    <!-- Sugestões -->
                    <div class="grid grid-cols-2 gap-2 w-full max-w-lg">
                        <button
                            v-for="suggestion in suggestions"
                            :key="suggestion"
                            @click="sendMessage(suggestion)"
                            class="text-left px-3 py-2.5 rounded-xl border border-border hover:border-primary/30 hover:bg-accent transition-all text-xs text-muted-foreground hover:text-foreground"
                        >
                            {{ suggestion }}
                        </button>
                    </div>
                </div>

                <!-- Mensagens -->
                <template v-else>
                    <div
                        v-for="msg in messages"
                        :key="msg.id"
                        class="flex gap-3"
                        :class="msg.role === 'user' ? 'justify-end' : 'justify-start'"
                    >
                        <!-- Avatar AI -->
                        <div
                            v-if="msg.role === 'assistant'"
                            class="w-7 h-7 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0 mt-0.5"
                        >
                            <Bot class="w-3.5 h-3.5 text-primary" />
                        </div>

                        <!-- Balão de mensagem -->
                        <div
                            class="max-w-[75%] rounded-2xl px-4 py-2.5 text-sm"
                            :class="msg.role === 'user'
                                ? 'bg-primary text-primary-foreground rounded-tr-sm'
                                : 'bg-card border border-border rounded-tl-sm text-foreground'"
                        >
                            <!-- Mensagem do utilizador -->
                            <p v-if="msg.role === 'user'">{{ msg.content }}</p>

                            <!-- Resposta da AI com markdown -->
                            <div
                                v-else
                                v-html="renderMarkdown(msg.content)"
                                class="prose-sm leading-relaxed"
                            />

                            <!-- Indicador de streaming -->
                            <div v-if="msg.streaming && !msg.content" class="flex gap-1 py-1">
                                <div class="w-1.5 h-1.5 bg-muted-foreground/50 rounded-full animate-bounce" style="animation-delay: 0ms" />
                                <div class="w-1.5 h-1.5 bg-muted-foreground/50 rounded-full animate-bounce" style="animation-delay: 150ms" />
                                <div class="w-1.5 h-1.5 bg-muted-foreground/50 rounded-full animate-bounce" style="animation-delay: 300ms" />
                            </div>
                        </div>

                        <!-- Avatar utilizador -->
                        <div
                            v-if="msg.role === 'user'"
                            class="w-7 h-7 rounded-lg bg-primary flex items-center justify-center flex-shrink-0 mt-0.5"
                        >
                            <User class="w-3.5 h-3.5 text-primary-foreground" />
                        </div>
                    </div>
                </template>

            </div>

            <!-- ── Campo de input ── -->
            <div class="px-6 py-4 border-t border-border bg-card/30">
                <div class="flex gap-2 items-end max-w-4xl mx-auto">
                    <textarea
                        v-model="message"
                        placeholder="Pergunta sobre os teus negócios, contactos ou pipeline..."
                        rows="1"
                        class="flex-1 resize-none rounded-xl border border-border bg-background px-4 py-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 transition-all leading-5"
                        style="min-height: 44px; max-height: 160px;"
                        @keydown.enter.prevent="!$event.shiftKey && sendMessage()"
                        @input="$event.target.style.height = 'auto'; $event.target.style.height = $event.target.scrollHeight + 'px'"
                        :disabled="isStreaming"
                    />
                    <Button
                        @click="sendMessage()"
                        :disabled="!message.trim() || isStreaming"
                        class="h-[44px] w-[44px] p-0 rounded-xl flex-shrink-0 flex items-center justify-center"
                    >
                        <RefreshCw v-if="isStreaming" class="w-4 h-4 animate-spin" />
                        <Send v-else class="w-4 h-4" />
                    </Button>
                </div>
                <p class="text-center text-[10px] text-muted-foreground mt-2">
                    Enter para enviar · Shift+Enter para nova linha
                </p>
            </div>

        </div>
    </AuthenticatedLayout>
</template>