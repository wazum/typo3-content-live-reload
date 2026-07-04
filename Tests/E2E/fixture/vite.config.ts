import { defineConfig } from 'vite'
import typo3 from 'vite-plugin-typo3'
import { contentLiveReload } from './vendor/wazum/typo3-content-live-reload/Resources/Private/Vite/dist/index.js'

export default defineConfig({
    server: {
        host: '127.0.0.1',
        port: 5273,
        strictPort: true,
    },
    plugins: [typo3(), contentLiveReload()],
})
