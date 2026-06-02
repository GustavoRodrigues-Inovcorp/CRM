/**
 * useTheme.js
 * Composable que gere o tema da aplicação (dark/light).
 * Persiste a preferência no localStorage e aplica a classe no <html>.
 */
import { ref, watch, onMounted } from 'vue'

/* ─── Estado global do tema ─── */
const isDark = ref(true)

export function useTheme() {

    /**
     * Aplica o tema ao elemento <html>
     * O Tailwind usa a classe 'dark' no html para ativar o dark mode
     */
    const applyTheme = (dark) => {
        if (dark) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    }

    /**
     * Alterna entre dark e light
     */
    const toggleTheme = () => {
        isDark.value = !isDark.value
    }

    /**
     * Inicializa o tema com base na preferência guardada
     * Se não houver preferência, usa dark por padrão
     */
    const initTheme = () => {
        const saved = localStorage.getItem('crm-theme')
        if (saved) {
            isDark.value = saved === 'dark'
        } else {
            isDark.value = true
        }
        applyTheme(isDark.value)
    }

    /* ─── Persiste e aplica sempre que o tema muda ─── */
    watch(isDark, (val) => {
        localStorage.setItem('crm-theme', val ? 'dark' : 'light')
        applyTheme(val)
    })

    onMounted(() => {
        initTheme()
    })

    return { isDark, toggleTheme, initTheme }
}