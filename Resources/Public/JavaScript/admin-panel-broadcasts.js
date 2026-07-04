(() => {
    const storageKey = 'content-live-reload:broadcasts'

    const readEntries = () => {
        try {
            const stored = JSON.parse(sessionStorage.getItem(storageKey) ?? '[]')
            return Array.isArray(stored) ? stored : []
        } catch {
            return []
        }
    }

    const writeEntries = (entries) => {
        try {
            sessionStorage.setItem(storageKey, JSON.stringify(entries.slice(0, 20)))
        } catch {}
    }

    const render = (entries) => {
        const list = document.getElementById('content-live-reload-broadcasts')
        if (!list) return
        list.replaceChildren()
        if (entries.length === 0) {
            const row = document.createElement('tr')
            const cell = document.createElement('td')
            cell.textContent = 'Waiting for broadcasts — save a record in the backend.'
            row.appendChild(cell)
            list.appendChild(row)
            return
        }
        for (const entry of entries) {
            const row = document.createElement('tr')
            const time = document.createElement('td')
            time.textContent = entry.time
            const verdict = document.createElement('td')
            verdict.textContent = entry.verdict
            const tags = document.createElement('td')
            const code = document.createElement('code')
            code.textContent = entry.tags
            tags.appendChild(code)
            row.append(time, verdict, tags)
            list.appendChild(row)
        }
    }

    document.addEventListener('typo3:content-changed:broadcast', (event) => {
        const verdict =
            event.detail.mode === 'paused'
                ? (event.detail.matched ? 'matched (paused)' : 'no overlap (paused)')
                : (event.detail.matched ? 'matched → reload' : 'no overlap')
        const entries = [
            { time: new Date().toLocaleTimeString(), verdict, tags: event.detail.tags.join(', ') },
            ...readEntries(),
        ]
        writeEntries(entries)
        render(entries)
    })

    const initialize = () => render(readEntries())
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true })
    } else {
        initialize()
    }
})()
