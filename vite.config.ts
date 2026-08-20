import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import path from "node:path";

export default defineConfig({
  plugins: [react()],
  define: {
    global: "globalThis",
  },
  resolve: {
    alias: {
      src: path.resolve(__dirname, "src"),
      "@app": path.resolve(__dirname, "src/app"),
      "@features": path.resolve(__dirname, "src/features"),
      "@shared": path.resolve(__dirname, "src/shared"),
      "@template": path.resolve(__dirname, "src/template"),
      pages: path.resolve(__dirname, "src/template/pages"),
      common: path.resolve(__dirname, "src/template"),
      Components: path.resolve(__dirname, "src/shared/components"),
      Layouts: path.resolve(__dirname, "src/shared/layouts"),
    },
    dedupe: ["ckeditor5", "@ckeditor/ckeditor5-react"],
  },
  server: {
    port: 3000,
    open: false,
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
