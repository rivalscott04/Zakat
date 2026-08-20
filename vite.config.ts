import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import tsconfigPaths from "vite-tsconfig-paths";
import path from "node:path";

export default defineConfig({
  plugins: [react(), tsconfigPaths()],
  define: {
    global: "globalThis",
  },
  resolve: {
    alias: {
      src: path.resolve(__dirname, "src"),
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
