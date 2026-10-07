/// <reference types="vitest/config" />
import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import { VitePWA } from 'vite-plugin-pwa'
import svgr from 'vite-plugin-svgr'

// Laravel (nginx) in local dev. In production the API is served same-origin by Apache.
const BACKEND = process.env.VITE_BACKEND_URL ?? 'http://127.0.0.1:8080'

export default defineConfig({
  plugins: [
    react(),
    tailwindcss(),
    svgr(),
    VitePWA({
      strategies: 'injectManifest',
      srcDir: 'src',
      filename: 'sw.ts',
      registerType: 'prompt',
      injectRegister: false,
      injectManifest: {
        globPatterns: ['**/*.{js,css,html,svg,png,ico,woff2,webmanifest}'],
        // Precache only the font subsets Greek/English pages use (Noto Serif Display only covers Greek
        // headlines; Playfair covers Latin). Anything else still loads on demand via unicode-range.
        globIgnores: [
          '**/*cyrillic*',
          '**/*vietnamese*',
          '**/*latin-ext*',
          '**/noto-serif-display-latin-*',
          '**/*greek-ext*',
        ],
      },
      manifest: {
        id: '/',
        name: 'Disrupt Cyprus',
        short_name: 'Disrupt',
        description: 'Startup and innovation news for Cyprus and the Eastern Mediterranean, personalized by industry.',
        lang: 'el',
        dir: 'ltr',
        start_url: '/',
        scope: '/',
        display: 'standalone',
        orientation: 'portrait',
        theme_color: '#FAFAF8',
        background_color: '#FAFAF8',
        categories: ['news', 'business'],
        icons: [
          { src: '/pwa-192x192.png', sizes: '192x192', type: 'image/png' },
          { src: '/pwa-512x512.png', sizes: '512x512', type: 'image/png' },
          { src: '/maskable-icon-512x512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
        ],
        shortcuts: [
          {
            name: 'Ημερήσια Ενημέρωση',
            short_name: 'Daily',
            url: '/news?tab=daily',
            icons: [{ src: '/pwa-192x192.png', sizes: '192x192' }],
          },
          {
            name: 'Εκδηλώσεις',
            short_name: 'Events',
            url: '/events',
            icons: [{ src: '/pwa-192x192.png', sizes: '192x192' }],
          },
        ],
      },
      devOptions: { enabled: false },
    }),
  ],
  resolve: {
    alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) },
  },
  server: {
    port: 5173,
    strictPort: true,
    proxy: {
      '/api': { target: BACKEND, changeOrigin: false },
      '/sanctum': { target: BACKEND, changeOrigin: false },
      '/storage': { target: BACKEND, changeOrigin: false },
    },
  },
  test: {
    globals: true,
    environment: 'jsdom',
    setupFiles: ['./src/test/setup.ts'],
    css: false,
    restoreMocks: true,
  },
})
