import { fileURLToPath } from 'node:url'
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

const resolveSourcePath = (relativePath: string): string =>
  fileURLToPath(new URL(relativePath, import.meta.url))

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],

  resolve: {
    // 별칭은 tsconfig.app.json 의 paths 와 동일하게 유지한다.
    alias: {
      '@app': resolveSourcePath('./src/app'),
      '@feature': resolveSourcePath('./src/feature'),
      '@shared': resolveSourcePath('./src/shared'),
      '@i18n': resolveSourcePath('./src/i18n'),
      '@style': resolveSourcePath('./src/style'),
    },
  },

  server: {
    // PHP 백엔드(api/public/index.php)를 별도 포트로 띄우고 /api 를 그쪽으로 넘긴다.
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8080',
        changeOrigin: true,
      },
    },
  },
})
