import { test, expect } from '@playwright/test';

/**
 * Story 1.2 RED-PHASE scaffold. Promoted from the Manual QA Gate once AD-33
 * confirmed Node/Tailwind are legitimate dev-tooling here. Un-skip once Shell's
 * language switcher ships.
 *
 * AC3: "seluruh label chrome berubah instan tanpa reload, tidak ada elemen yang
 * terpotong akibat string ID yang lebih panjang."
 *
 * Per-User locale *persistence* is already covered server-side in
 * tests/Feature/Shell/UserLocalePreferenceTest.php (Pest); EN/ID *key parity* is
 * covered in tests/Unit/Shell/TranslationParityTest.php. This file covers the two
 * things only a real browser can prove: the switch is instant/no-reload, and no
 * chrome element clips the (generally longer) Indonesian string.
 */

test('language switch changes chrome labels instantly with no page navigation', async ({ page }) => {
  await page.goto('/admin');

  const navItem = page.locator('.nav-item').first();
  const beforeText = await navItem.textContent();

  let navigated = false;
  page.on('framenavigated', () => {
    navigated = true;
  });

  await page.getByRole('button', { name: /user menu|menu pengguna/i }).click();
  await page.getByRole('button', { name: /language|bahasa/i }).click();
  await page.getByRole('option', { name: /indonesia/i }).click();

  await expect(navItem).not.toHaveText(beforeText ?? '');
  expect(navigated).toBe(false);
});

test('no chrome element (nav item, button, pill) clips text after switching to the longer Indonesian labels', async ({ page }) => {
  await page.goto('/admin');

  await page.getByRole('button', { name: /user menu|menu pengguna/i }).click();
  await page.getByRole('button', { name: /language|bahasa/i }).click();
  await page.getByRole('option', { name: /indonesia/i }).click();

  // DESIGN.md §Typography: "no button, pill, or nav item may be a fixed pixel
  // width — every one of them wraps with white-space:nowrap plus horizontal
  // padding that grows with content." A clipped element has scrollWidth >
  // clientWidth (the text overflows its own box).
  const clipped = await page.evaluate(() => {
    const offenders: string[] = [];
    document.querySelectorAll('.nav-item, .btn, .pill').forEach((el) => {
      if (el.scrollWidth > el.clientWidth + 1) {
        offenders.push(el.textContent?.trim() ?? '(no text)');
      }
    });
    return offenders;
  });

  expect(clipped).toEqual([]);
});
