import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import path from "path";

// Project root stays at the repo root (index.html lives here), but the
// actual application source lives under FRONT_END/DESIGN per the required
// directory structure. Aliases below keep imports short and stable.
export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      "@design": path.resolve(__dirname, "./FRONT_END/DESIGN"),
      "@request": path.resolve(__dirname, "./FRONT_END/REQUEST"),
    },
  },
  server: {
    port: 5173,
  },
});
