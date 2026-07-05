import { chromium } from '@playwright/test'
import { mkdirSync } from 'node:fs'
import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

const demoDir = dirname(fileURLToPath(import.meta.url))
const videoDir = resolve(demoDir, 'video')
mkdirSync(videoDir, { recursive: true })

const baseUrl = 'http://web:8080'
const contentUid = process.env.DEMO_CONTENT_UID ?? '7'
const newHeadline = 'Fresh headline, saved seconds ago'

const frontendPolish = `
    body > div[style*='width: 800px'] { display: none !important; }
    body { font-family: -apple-system, 'Segoe UI', Roboto, sans-serif; background: #f4f5f7; }
    .frame { max-width: 44rem; margin: 3.5rem auto 0; background: #fff; border-radius: 10px; padding: 2.2rem 2.6rem; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
    h2 { font-weight: 650; color: #1a1a2e; margin: 0; font-size: 1.7rem; }
`

const label = (text) => `
    body::after {
        content: '${text}';
        position: fixed;
        top: 0;
        left: 0;
        padding: 6px 14px;
        background: #1a1a2e;
        color: #fff;
        font-size: 14px;
        font-family: -apple-system, 'Segoe UI', Roboto, sans-serif;
        border-bottom-right-radius: 8px;
        z-index: 99999;
    }
`

const browser = await chromium.launch()

const loginContext = await browser.newContext({ ignoreHTTPSErrors: true })
const loginPage = await loginContext.newPage()
await loginPage.goto(baseUrl + '/typo3/')
await loginPage.fill('input[name="username"]', 'demo')
await loginPage.fill('input[name="p_field"]', 'DemoDemo1!')
await loginPage.click('button[type="submit"], #t3-login-submit')
await loginPage.waitForURL('**/typo3/module/**', { timeout: 20000 }).catch(() => {})

const activate = await loginContext.newPage()
await activate.goto(baseUrl + '/')
const trigger = activate.locator('[data-typo3-role="typo3-adminPanel-trigger"]')
if (await trigger.count()) {
    const panelOpen = await activate.locator('[data-typo3-role="typo3-adminPanel-module-trigger"]').count()
    if (!panelOpen) {
        await trigger.click()
        await activate.waitForLoadState('load')
    }
}
await activate.close()
const storageState = await loginContext.storageState()
await loginContext.close()

const backendSize = { width: 1000, height: 500 }
const frontendSize = { width: 1000, height: 380 }
const contextBackend = await browser.newContext({ recordVideo: { dir: videoDir, size: backendSize }, viewport: backendSize, storageState, ignoreHTTPSErrors: true })
const contextTop = await browser.newContext({ recordVideo: { dir: videoDir, size: frontendSize }, viewport: frontendSize, storageState, ignoreHTTPSErrors: true })
const contextBottom = await browser.newContext({ recordVideo: { dir: videoDir, size: frontendSize }, viewport: frontendSize, storageState, ignoreHTTPSErrors: true })

const pageBackend = await contextBackend.newPage()
const pageTop = await contextTop.newPage()
const pageBottom = await contextBottom.newPage()

const styleTop = frontendPolish + label('Frontend tab 1 — shows this record')
const styleBottom = frontendPolish + label('Frontend tab 2 — a different page')
const applyStyles = (page, style) => page.addStyleTag({ content: style }).catch(() => {})

await pageTop.goto(baseUrl + '/')
await pageBottom.goto(baseUrl + '/other')
await applyStyles(pageTop, styleTop)
await applyStyles(pageBottom, styleBottom)
pageTop.on('load', () => applyStyles(pageTop, styleTop))
pageBottom.on('load', () => applyStyles(pageBottom, styleBottom))

await pageBackend.goto(baseUrl + `/typo3/record/edit?edit[tt_content][${contentUid}]=edit`)
const contentFrame = pageBackend.frameLocator('#typo3-contentIframe')
const headerField = contentFrame.locator(`input[data-formengine-input-name="data[tt_content][${contentUid}][header]"]`)
await headerField.waitFor({ timeout: 30000 })
await applyStyles(pageBackend, label('TYPO3 backend — the editor saves a change'))
await headerField.scrollIntoViewIfNeeded()

await pageTop.waitForTimeout(2000)

await headerField.click()
await headerField.selectText()
await pageBackend.keyboard.press('Backspace')
await pageBackend.keyboard.type(newHeadline, { delay: 55 })
await pageBackend.waitForTimeout(700)
await contentFrame.locator('button[name="_savedok"]').first().click()

await pageTop.waitForTimeout(6500)

const videos = {
    backend: await pageBackend.video().path(),
    top: await pageTop.video().path(),
    bottom: await pageBottom.video().path(),
}
await contextBackend.close()
await contextTop.close()
await contextBottom.close()
await browser.close()
console.log(JSON.stringify(videos))
