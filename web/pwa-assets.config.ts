import { defineConfig, minimal2023Preset } from '@vite-pwa/assets-generator/config'

// `npm run icons` regenerates favicon.ico, PWA, maskable and Apple touch icons from public/favicon.svg.
export default defineConfig({
  headLinkOptions: { preset: '2023' },
  preset: {
    ...minimal2023Preset,
    maskable: { ...minimal2023Preset.maskable, resizeOptions: { background: '#00A5E6' } },
    apple: { ...minimal2023Preset.apple, resizeOptions: { background: '#00A5E6' } },
  },
  images: ['public/favicon.svg'],
})
