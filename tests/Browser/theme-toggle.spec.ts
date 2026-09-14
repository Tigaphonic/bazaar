import { test, expect } from '@playwright/test';

/**
 * Story 1.2 RED-PHASE scaffold. Promoted from the Manual QA Gate to an automated
 * Playwright spec once AD-33 confirmed Node/Tailwind are legitimate dev-tooling in
 * this repo. Un-skip once Shell's theme toggle ships.
 *
 * AC2: "tema berganti terang↔gelap instan tanpa reload, persisten per-User, dan
 * token dark-mode diterapkan (bukan inversi warna literal)."
 *
 * The per-User *persistence* half is already covered server-side in
 * tests/Feature/Shell/UserThemePreferenceTest.php (Pest) -- this file covers the
 * one thing only a real browser can prove: the switch is instant, causes no page
 * navigation, and Tailwind's `dark` class strategy is actually applied to <html>.
 */

test.skip('theme toggle switches dark mode instantly with no page navigation', async ({ page }) => {
  await page.goto('/admin');

  const html = page.locator('html');
  await expect(html).not.toHaveClass(/dark/);

  let navigated = false;
  page.on('framenavigated', () => {
    navigated = true;
  });

  // Theme toggle lives in the topbar user menu (EXPERIENCE.md §Component Patterns).
  await page.getByRole('button', { name: /user menu|menu pengguna/i }).click();
  await page.getByRole('button', { name: /theme|tema/i }).click();

  // Tailwind v4 / Filament's dark-mode strategy toggles a `dark` class on <html>;
  // this alone proves the switch happened without needing Shell-specific selectors.
  await expect(html).toHaveClass(/dark/);

  expect(navigated).toBe(false);
});

test.skip('dark mode survives a reload (persisted, not just an in-memory toggle)', async ({ page }) => {
  await page.goto('/admin');

  await page.getByRole('button', { name: /user menu|menu pengguna/i }).click();
  await page.getByRole('button', { name: /theme|tema/i }).click();
  await expect(page.locator('html')).toHaveClass(/dark/);

  await page.reload();

  await expect(page.locator('html')).toHaveClass(/dark/);
});
