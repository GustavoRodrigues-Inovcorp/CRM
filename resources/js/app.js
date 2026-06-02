/**
 * app.js
 * Entry point da aplicação Vue + Inertia.
 * Inicializa o tema antes de montar a app para evitar flash.
 */
import '../css/app.css'
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from 'ziggy-js'
import axios from 'axios'

window.axios = axios
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
window.axios.defaults.withXSRFToken = true
window.axios.defaults.xsrfCookieName = 'XSRF-TOKEN'
window.axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN'

/* ─── Aplica tema guardado antes de renderizar (evita flash) ─── */
const savedTheme = localStorage.getItem('crm-theme')
if (savedTheme === 'light') {
    document.documentElement.classList.remove('dark')
} else {
    document.documentElement.classList.add('dark')
}

createInertiaApp({
    title: (title) => title ? `${title} — Inovcorp CRM` : 'Inovcorp CRM',
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue')
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el)
    },
    progress: { color: '#357CF5' },
})