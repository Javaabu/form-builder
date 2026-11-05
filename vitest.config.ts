import { defineConfig } from "vitest/config"

// @javaabu/stencil is stencil's React renderer in vendor/ (composer install first),
// as javaabu/manifest's Vite plugin resolves it in apps
export default defineConfig({
  resolve: {
    alias: {
      "@javaabu/form-builder": new URL("./resources/js", import.meta.url).pathname,
      "@javaabu/stencil": new URL("./vendor/javaabu/stencil/resources/js", import.meta.url).pathname,
    },
  },
  test: {
    environment: "jsdom",
    include: ["tests/js/**/*.test.{ts,tsx}"],
    setupFiles: ["tests/js/setup.ts"],
    coverage: {
      provider: "v8",
      include: ["resources/js/**/*.{ts,tsx}"],
      reporter: ["text", "clover", "lcov"],
      reportsDirectory: "coverage",
    },
  },
})
