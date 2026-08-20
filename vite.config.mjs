import path from "node:path";
import { fileURLToPath } from "node:url";
import react from "@vitejs/plugin-react";
import { defineConfig } from "vite";

const projectRoot = path.dirname(fileURLToPath(import.meta.url));
const fromRoot = (relativePath) => path.resolve(projectRoot, relativePath);

export default defineConfig({
  plugins: [react()],
  clearScreen: false,
  define: { global: "globalThis" },
  resolve: {
    alias: {
      src: fromRoot("src"),
      "@app": fromRoot("src/app"),
      "@features": fromRoot("src/features"),
      "@shared": fromRoot("src/shared"),
      "@template": fromRoot("src/template"),
      pages: fromRoot("src/template/pages"),
      common: fromRoot("src/template"),
      Components: fromRoot("src/shared/components"),
      Layouts: fromRoot("src/shared/layouts"),
    },
    dedupe: ["ckeditor5", "@ckeditor/ckeditor5-react"],
  },
  optimizeDeps: { noDiscovery: true },
  server: {
    host: "127.0.0.1",
    port: 3000,
    strictPort: true,
    open: false,
    proxy: {
      "/api": { target: "http://localhost:8000", changeOrigin: false },
      "/sanctum": { target: "http://localhost:8000", changeOrigin: false },
    },
  },
  build: { outDir: "build", sourcemap: false },
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
