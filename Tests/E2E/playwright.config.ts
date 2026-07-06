import { defineConfig } from '@playwright/test'

export default defineConfig({
    testDir: './tests',
    timeout: 60000,
    retries: process.env.CI ? 2 : 0,
    workers: 1,
    use: {
        baseURL: 'http://127.0.0.1:8080',
    },
    reporter: 'list',
})
