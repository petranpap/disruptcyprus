import './lib/zodConfig'
import '@fontsource-variable/inter/wght.css'
import '@fontsource-variable/source-serif-4/wght.css'
import '@fontsource-variable/playfair-display/wght.css'
import '@fontsource-variable/noto-serif-display/wght.css'
import './styles/app.css'
import './i18n'
import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { App } from './app/App'
import { captureInstallPrompt } from './stores/engagement'

captureInstallPrompt()

const root = document.getElementById('root')

if (root) {
  createRoot(root).render(
    <StrictMode>
      <App />
    </StrictMode>,
  )
}
