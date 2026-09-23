import { defineConfig, minimal2023Preset as preset } from '@vite-pwa/assets-generator/config'

// `npm run icons` renders the PNG icons the web manifest needs from public/favicon.svg.
export default defineConfig({
  headLinkOptions: { preset: '2023' },
  preset,
  images: ['public/favicon.svg'],
})
