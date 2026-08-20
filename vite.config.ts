import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import path from "node:path";
import { fileURLToPath } from "node:url";

const projectRoot = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
  plugins: [react()],
  clearScreen: false,
  define: {
    global: "globalThis",
  },
  resolve: {
    alias: {
      src: path.resolve(projectRoot, "src"),
      "@app": path.resolve(projectRoot, "src/app"),
      "@features": path.resolve(projectRoot, "src/features"),
      "@shared": path.resolve(projectRoot, "src/shared"),
      "@template": path.resolve(projectRoot, "src/template"),
      pages: path.resolve(projectRoot, "src/template/pages"),
      common: path.resolve(projectRoot, "src/template"),
      Components: path.resolve(projectRoot, "src/shared/components"),
      Layouts: path.resolve(projectRoot, "src/shared/layouts"),
    },
    dedupe: ["ckeditor5", "@ckeditor/ckeditor5-react"],
  },
  // Velzone membawa ratusan halaman contoh. Discovery otomatis Vite akan
  // menyisir semuanya sebelum server siap, padahal route sudah lazy-loaded.
  optimizeDeps: {
    noDiscovery: true,
  },
  server: {
    port: 3000,
    open: false,
    host: "127.0.0.1",
    strictPort: true,
    // Backend Laravel di-proxy agar SPA dan API berada pada origin yang sama.
    // Ini syarat cookie-based Sanctum authentication (PRD 01 §17).
    proxy: {
      "/api": { target: "http://localhost:8000", changeOrigin: false },
      "/sanctum": { target: "http://localhost:8000", changeOrigin: false },
    },
  },
  build: {
    outDir: "build",
    sourcemap: false,
  },
  css: {
    preprocessorOptions: {
      scss: {
        silenceDeprecations: [
          "legacy-js-api",
          "import",
          "global-builtin",
          "color-functions",
          "mixed-decls",
        ],
      },
    },
  },
});
