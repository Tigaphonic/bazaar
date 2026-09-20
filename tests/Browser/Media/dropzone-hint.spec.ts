import { test, expect } from '@playwright/test';

/**
 * Story 1.10 RED-PHASE scaffold.
 *
 * AC3: "Dropzone (leaf control dari Story 1.2) dipakai sebagai UI upload seragam
 * di semua domain, dengan hint line yang menyatakan syarat minimum sesuai
 * konteksnya (mis. "0 foto diunggah — minimal 1 foto wajib")."
 *
 * Scope test ini sempit: membuktikan Dropzone UI-nya tampil dengan hint line
 * yang benar di konteks Settings (Story 1.6 logo/favicon upload point) —
 * titik upload paling awal yang bisa ditest karena Settings sudah done.
 *
 * Un-skip setelah:
 *   1. HasBazaarMedia terpasang di Settings (logo/favicon) — Story 1.10.
 *   2. Filament form Settings menampilkan SpatieMediaLibraryFileUpload
 *      dengan Dropzone component dan hint line yang sesuai.
 *
 * Catatan: Tidak menggunakan merged-fixtures / playwright-utils karena
 * @seontechnologies/playwright-utils belum di-install sebagai paket
 * (config.yaml: tea_use_playwright_utils = true tapi paket tidak ada).
 * Spec ini mengikuti pola Browser tests yang sudah ada (theme-toggle.spec.ts).
 * Re-evaluate ketika paket di-install.
 *
 * Tests browser: `npx playwright test tests/Browser/Media/`
 * Server: `php artisan testbench:serve`
 */

// ---------------------------------------------------------------------------
// AC3: Dropzone tampil dengan hint line yang benar di Settings page
// ---------------------------------------------------------------------------

test.skip('[P1] dropzone shows correct hint line for logo upload — "0 foto diunggah — minimal 1 foto wajib"', async ({ page }) => {
  // THIS TEST WILL FAIL — Dropzone hint line belum diimplementasi di Settings form

  await page.goto('/admin/settings');

  // Dropzone untuk logo harus tampil
  const dropzone = page.getByRole('region', { name: /logo|logo toko/i });
  await expect(dropzone).toBeVisible();

  // Hint line yang benar saat belum ada gambar yang diunggah
  // Teks exact disesuaikan saat UI diimplementasi; regex ini cukup untuk red phase
  await expect(dropzone.getByText(/0 foto diunggah/i)).toBeVisible();
  await expect(dropzone.getByText(/minimal 1 foto wajib/i)).toBeVisible();
});

test.skip('[P1] dropzone shows correct hint line for favicon upload', async ({ page }) => {
  // THIS TEST WILL FAIL — Dropzone hint line belum diimplementasi di Settings form

  await page.goto('/admin/settings');

  const dropzone = page.getByRole('region', { name: /favicon/i });
  await expect(dropzone).toBeVisible();

  // Favicon tidak wajib tapi hint tetap ada
  await expect(dropzone.getByText(/0 foto diunggah/i)).toBeVisible();
});

// ---------------------------------------------------------------------------
// AC3: Dropzone UI upload seragam — bukan <input type="file"> biasa
// ---------------------------------------------------------------------------

test.skip('[P2] settings page uses Dropzone component, not a plain file input, for logo upload', async ({ page }) => {
  // THIS TEST WILL FAIL — Settings form belum menggunakan Dropzone

  await page.goto('/admin/settings');

  // Dropzone dari Shell (Story 1.2) harus digunakan, bukan native input langsung
  // Heuristic: Dropzone biasanya dibungkus dalam div dengan data attribute atau
  // label "drag and drop" / "drop files here"
  const dropzoneArea = page.locator('[data-testid="bazaar-dropzone"], [data-component="dropzone"]').first();
  await expect(dropzoneArea).toBeVisible();
});

// ---------------------------------------------------------------------------
// AC1 (sanity via browser): Setelah upload, varian WebP tersedia — bukan menggantikan original
// ---------------------------------------------------------------------------

test.skip('[P1] after uploading a logo image, the original file URL and the webp variant URL are both accessible', async ({ page }) => {
  // THIS TEST WILL FAIL — Media pipeline + Dropzone belum diimplementasi

  await page.goto('/admin/settings');

  // Upload gambar via Dropzone
  const fileInput = page.locator('input[type="file"]').first();
  await fileInput.setInputFiles({
    name: 'store-logo.jpg',
    mimeType: 'image/jpeg',
    buffer: Buffer.from('fake-jpeg-content'),
  });

  // Tunggu upload selesai (tanpa waitForTimeout — tunggu response)
  await page.waitForResponse(
    (resp) => resp.url().includes('/livewire') && resp.status() === 200,
  );

  // Gambar yang tampil di preview harus bisa diakses (tidak 404)
  const previewImg = page.locator('img[src*="webp"], img[src*="storage"]').first();
  await expect(previewImg).toBeVisible();

  const imgSrc = await previewImg.getAttribute('src');
  expect(imgSrc).toBeTruthy();

  // Verifikasi image accessible (response 200)
  const imgResponse = await page.request.get(imgSrc!);
  expect(imgResponse.status()).toBe(200);
});
