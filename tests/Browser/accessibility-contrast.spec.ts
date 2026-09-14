import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

/**
 * Story 1.2 RED-PHASE scaffold. Promoted from the Manual QA Gate once AD-33
 * confirmed Node/Tailwind are legitimate dev-tooling here. Un-skip once Shell ships.
 *
 * EXPERIENCE.md §Accessibility Floor: "Focus (form fields, table rows) | Every
 * surface | Native focus ring, visible at AA contrast against surface — no custom
 * focus chrome, no removed outline." DESIGN.md §Colors: body/heading/pill text
 * against their surfaces target WCAG 2.1 AA (4.5:1 normal / 3:1 large text) as
 * *directional* guidance, both themes.
 *
 * axe-core's color-contrast rule checks static text/background contrast (the
 * DESIGN.md half); the second test below checks the EXPERIENCE.md half directly
 * (no removed outline) since axe has no reliable focus-indicator-contrast rule yet.
 */

test.skip('admin panel has no automated accessibility violations in light mode', async ({ page }) => {
  await page.goto('/admin');

  const results = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa']).analyze();

  expect(results.violations).toEqual([]);
});

test.skip('admin panel has no automated accessibility violations in dark mode', async ({ page }) => {
  await page.goto('/admin');
  await page.getByRole('button', { name: /user menu|menu pengguna/i }).click();
  await page.getByRole('button', { name: /theme|tema/i }).click();

  const results = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa']).analyze();

  expect(results.violations).toEqual([]);
});

test.skip('a focused interactive element keeps a visible native focus ring, never a removed outline', async ({ page }) => {
  await page.goto('/admin');

  // Tab to the first focusable chrome control and read its computed outline.
  await page.keyboard.press('Tab');

  const outline = await page.evaluate(() => {
    const el = document.activeElement;
    if (!el) return null;
    const style = window.getComputedStyle(el);
    return { outlineStyle: style.outlineStyle, outlineWidth: style.outlineWidth };
  });

  expect(outline).not.toBeNull();
  expect(outline?.outlineStyle).not.toBe('none');
  expect(outline?.outlineWidth).not.toBe('0px');
});
