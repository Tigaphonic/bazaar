import { test, expect } from '@playwright/test';

/**
 * Story 1.2 RED-PHASE scaffold. Promoted from the Manual QA Gate once AD-33
 * confirmed Node/Tailwind are legitimate dev-tooling here. Un-skip once Shell's
 * responsive shell ships.
 *
 * DESIGN.md §Layout & Spacing → Responsive breakpoints:
 * - Desktop (>=1280px): unchanged, sidebar 250px.
 * - Tablet (768-1279px): sidebar collapses to a 64px icon-only rail.
 * - Mobile (<768px): sidebar becomes an off-canvas drawer, hidden until opened.
 *
 * `[data-shell-sidebar]` is a proposed marker attribute (DESIGN.md names no CSS
 * class for the sidebar itself, unlike `.nav-item`/`.btn`/`.pill`) -- Dev may swap
 * it for whatever attribute/selector Shell actually ships, same as the ATDD Pest
 * scaffolds' proposed class names.
 */

test.skip('desktop (>=1280px) keeps the sidebar at its full 250px width', async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto('/admin');

  const sidebar = page.locator('[data-shell-sidebar]');
  const box = await sidebar.boundingBox();

  expect(box?.width).toBeGreaterThanOrEqual(240);
  expect(box?.width).toBeLessThanOrEqual(260);
});

test.skip('tablet (768-1279px) collapses the sidebar to a 64px icon-only rail', async ({ page }) => {
  await page.setViewportSize({ width: 1000, height: 900 });
  await page.goto('/admin');

  const sidebar = page.locator('[data-shell-sidebar]');
  const box = await sidebar.boundingBox();

  expect(box?.width).toBeGreaterThanOrEqual(56);
  expect(box?.width).toBeLessThanOrEqual(72);
});

test.skip('mobile (<768px) hides the sidebar as an off-canvas drawer until opened from the topbar', async ({ page }) => {
  await page.setViewportSize({ width: 400, height: 800 });
  await page.goto('/admin');

  const sidebar = page.locator('[data-shell-sidebar]');
  await expect(sidebar).toBeHidden();

  await page.getByRole('button', { name: /menu|navigasi/i }).click();
  await expect(sidebar).toBeVisible();
});
