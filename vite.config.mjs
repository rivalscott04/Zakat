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
  // Dependency discovery sengaja dibiarkan aktif (bawaan Vite).
  //
  // `noDiscovery: true` sempat dipakai untuk mempercepat startup (commit be8a9b9),
  // tetapi itu membuat paket CommonJS tidak dikonversi ke ESM. react-router
  // mengimpor `cookie` dan `set-cookie-parser` dengan named import, keduanya CJS
  // murni, sehingga aplikasi gagal dimuat sama sekali dan layar jadi putih.
  //
  // Biayanya hanya sekali: cold start pertama sekitar 7 detik untuk memindai dan
  // mem-prebundle, sesudah itu warm start di bawah setengah detik karena hasilnya
  // di-cache pada node_modules/.vite.
  server: {
    host: "127.0.0.1",
    port: 3000,
    strictPort: true,
    open: false,
    watch: {
      ignored: ["**/backend/**", "**/build/**", "**/graphify-out/**"],
    },
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
