import { defineConfig, devices } from '@playwright/test';

/**
 * Bazaar browser tests (Story 1.2, ARCHITECTURE-SPINE.md AD-33 / AD-33 Consequence).
 *
 * Targets the `workbench/` app -- Testbench's simulated "client host project" --
 * served for real via `testbench serve`, never a client's own install. This is the
 * only place in the repo that boots a real browser against a real HTTP server;
 * everything else is Pest/PHPUnit through Testbench's in-process request kernel.
 *
 * The panel currently has no `->login()` configured (Story 1.1 shipped no auth UI
 * yet), so `/admin` is reachable unauthenticated. Once Story 1.3 (Role & Permission)
 * adds panel auth, these specs will need an `actingAs`/login step -- not needed today.
 */
export default defineConfig({
  testDir: './tests/Browser',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  reporter: [['list']],
  use: {
    baseURL: 'http://127.0.0.1:8765',
    trace: 'on-first-retry',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
  webServer: {
    // Idempotent: drops any stale db (ignored if it doesn't exist yet), then
    // recreates + migrates + seeds from a clean slate, then serves. This is
    // dev/CI-time tooling only -- see AD-33, it is never part of a client install.
    command:
      "php vendor/bin/testbench workbench:drop-sqlite-db --no-interaction; " +
      'php vendor/bin/testbench workbench:create-sqlite-db --no-interaction && ' +
      'php vendor/bin/testbench migrate --no-interaction --force && ' +
      "php vendor/bin/testbench db:seed --no-interaction --class='Workbench\\Database\\Seeders\\DatabaseSeeder' && " +
      'php vendor/bin/testbench serve --port=8765 --no-reload',
    url: 'http://127.0.0.1:8765/admin',
    reuseExistingServer: !process.env.CI,
    timeout: 60_000,
  },
});
