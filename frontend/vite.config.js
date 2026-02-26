import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    proxy: {
      // ✅ all backend routes for this project (NO /api)
      '^/document-manager/.*': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
      '^/document-manager$': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },

      // ✅ file preview urls like /storage/...
      '^/storage/.*': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
    },
  },
})