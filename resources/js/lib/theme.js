// Simple theme utility: manages `dark` class on <html> and persists choice in localStorage
const STORAGE_KEY = 'theme'

export function isSystemDark() {
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
}

export function getStoredTheme() {
    try {
        return localStorage.getItem(STORAGE_KEY)
    } catch (e) {
        return null
    }
}

export function setStoredTheme(value) {
    try {
        if (value === null) localStorage.removeItem(STORAGE_KEY)
        else localStorage.setItem(STORAGE_KEY, value)
    } catch (e) {
        // ignore
    }
}

export function applyTheme(theme) {
    const html = document.documentElement
    if (!html) return
    if (theme === 'dark') html.classList.add('dark')
    else html.classList.remove('dark')
}

// Remove any stray `.dark` classes from elements other than <html>
export function cleanLocalDarkClasses() {
    try {
        const html = document.documentElement
        document.querySelectorAll('.dark').forEach((el) => {
            if (el !== html) el.classList.remove('dark')
        })
    } catch (e) {
        // ignore
    }
}

// Observe DOM mutations and remove `.dark` from any element except <html>
function observeAndCleanDark() {
    try {
        if (typeof window === 'undefined') return
        // avoid creating multiple observers
        if (window.__themeObserver) return

        const html = document.documentElement
        const observer = new MutationObserver((mutations) => {
            for (const m of mutations) {
                if (m.type === 'attributes' && m.attributeName === 'class') {
                    const target = m.target
                    if (target !== html && target.classList && target.classList.contains('dark')) {
                        target.classList.remove('dark')
                    }
                }
                if (m.type === 'childList' && m.addedNodes.length) {
                    m.addedNodes.forEach((node) => {
                        if (node && node.nodeType === 1) {
                            const el = node
                            if (el !== html && el.classList && el.classList.contains('dark')) el.classList.remove('dark')
                            // also scan descendants quickly
                            el.querySelectorAll && el.querySelectorAll('.dark').forEach((d) => d.classList.remove('dark'))
                        }
                    })
                }
            }
        })

        observer.observe(document.documentElement, { attributes: true, subtree: true, childList: true })
        window.__themeObserver = observer
    } catch (e) {
        // ignore
    }
}

export function initTheme() {
    const stored = getStoredTheme()
    const theme = stored ? stored : (isSystemDark() ? 'dark' : 'light')
    applyTheme(theme)
    // cleanup local dark classes so only html.dark controls the theme
    cleanLocalDarkClasses()
    // if theme is light, start observing DOM to prevent stray local .dark classes
    if (theme === 'light') observeAndCleanDark()
    return theme
}

export function toggleTheme() {
    const html = document.documentElement
    const isDark = html.classList.contains('dark')
    const next = isDark ? 'light' : 'dark'
    applyTheme(next)
    setStoredTheme(next)
    // if switching to light, ensure no local .dark classes remain
    if (next === 'light') cleanLocalDarkClasses()
    // manage observer depending on theme
    if (next === 'light') observeAndCleanDark()
    else if (window.__themeObserver) {
        try { window.__themeObserver.disconnect(); window.__themeObserver = null } catch (e) {}
    }
    return next
}

export function getTheme() {
    const html = document.documentElement
    return html && html.classList.contains('dark') ? 'dark' : 'light'
}

export default { initTheme, toggleTheme, getTheme }
