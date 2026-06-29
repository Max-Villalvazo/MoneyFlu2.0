import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// Proxy: todas las peticiones a /api/* en el navegador (puerto 5173)
// se redirigen "por debajo" al backend de Docker (puerto 8080).
// Esto hace que, desde la perspectiva del navegador, todo viva en el
// mismo origen -> las cookies de sesión (PHPSESSID) funcionan sin lios de CORS.
export default defineConfig({
  plugins: [react()],
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost:8080',
        changeOrigin: true,
        secure: false,
      },
    },
  },
});
