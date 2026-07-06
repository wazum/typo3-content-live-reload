import { createClientCore, restoreScrollPosition } from './core'
import type { ClientConfiguration } from './core'

restoreScrollPosition()
const configuration = (window as Window & { __contentLiveReload?: ClientConfiguration }).__contentLiveReload
if (configuration && import.meta.hot) {
    const core = createClientCore(configuration)
    core.announceConnection(true)
    import.meta.hot.on('vite:ws:disconnect', () => core.announceConnection(false))
    import.meta.hot.on('vite:ws:connect', () => core.announceConnection(true))
    import.meta.hot.on('typo3:content-changed', (payload) => core.handleBroadcast(payload))
}
