/// <reference types="vitest/config" />
import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import { VitePWA } from 'vite-plugin-pwa'
import svgr from 'vite-plugin-svgr'

// Laravel (nginx) in local dev. In production the API is served same-origin by Apache.
const BACKEND = process.env.VITE_BACKEND_URL ?? 'http://127.0.0.1:8080'
const PROXY = {
  '/api': { target: BACKEND, changeOrigin: false },
  '/sanctum': { target: BACKEND, changeOrigin: false },
  '/storage': { target: BACKEND, changeOrigin: false },
}

// Same policy as the production Apache vhost (docs/DEPLOYMENT.md), so `npm run preview` catches CSP violations.
const CSP =
  "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self'; " +
  "connect-src 'self'; worker-src 'self'; manifest-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'"

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
    proxy: PROXY,
  },
  build: {
    rolldownOptions: {
      output: {
        codeSplitting: {
          groups: [
            // zod and its config in one chunk, config first: schemas are created at import time, and creating one
            // probes for eval unless `jitless` is already set (the probe is a CSP violation). See src/lib/zodConfig.ts.
            { name: 'zod', test: /node_modules[\\/]zod[\\/]|src[\\/]lib[\\/]zodConfig\.ts/ },
          ],
        },
      },
    },
  },
  // Production build locally (Lighthouse, CSP and service-worker checks): `npm run build && npm run preview`.
  preview: {
    port: 4173,
    strictPort: true,
    proxy: PROXY,
    headers: { 'Content-Security-Policy': CSP },
  },
  test: {
    globals: true,
    environment: 'jsdom',
    setupFiles: ['./src/test/setup.ts'],
    css: false,
    restoreMocks: true,
    testTimeout: 15000,
    // Full-route tests are CPU-heavy (lazy chunks + jsdom); too many workers starve each other on small machines.
    maxWorkers: 4,
  },
})
