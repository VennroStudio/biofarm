// @ts-ignore
import { defineConfig } from 'vite';
// @ts-ignore
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  publicDir: false,
  build: {
    outDir: 'public/build',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: {
        app: 'assets/styles/app.css',
        mount: 'assets/react/mount.tsx',
      },
    },
  },
});
