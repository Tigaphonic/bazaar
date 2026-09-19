---
title: 'Story 1.8: Service Layer Integration (Monolith)'
type: 'feature'
created: '2026-09-19'
status: 'done'
route: 'oneshot'
review_loop_iteration: 0
baseline_commit: '182bfac19a702dc522cf2215f7ae013113717552'
context: [
  '{project-root}/_artifacts/implementation-artifacts/epic-1-context.md',
  '{project-root}/_artifacts/test-artifacts/atdd-checklist-1-8-service-layer-integration-monolith.md',
]
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** Service Layer Epic 1 sudah bisa di-resolve dan dipanggil in-process dari kode host (AC1 terbukti oleh 12 test ATDD hijau), tetapi belum ada panduan integrasi bagi Developer Tigaphonic (AC2). Test `1.8-INT-013` merah, `1.8-INT-014` di-skip.

**Approach:** Tambah section "Service Layer Integration" di `README.md` (bahasa Inggris, sama dengan README yang ada): aturan AD-5/AD-6 (host hanya memanggil Service, tidak menyentuh Model/Action), contoh DI konstruktor pada komponen Livewire, contoh `app()` dari controller/Blade, dan catatan bahwa Service domain berikutnya (Catalog availability-check, Order checkout) mengikuti konvensi yang sama. Tanpa kode `src/` baru, tanpa Facade, tanpa mengubah assertion test ATDD.

</frozen-after-approval>

## Implementation Notes

- Yang berubah: section "Service Layer Integration" di `README.md` dan resolusi path di `tests/Feature/Install/ServiceLayerIntegrationTest.php`. Tanpa kode `src/` baru.
- Bug di ATDD: `1.8-INT-013/014` memakai `base_path()`, yang di Testbench menunjuk `vendor/orchestra/testbench-core/laravel`, bukan root package, sehingga README tidak pernah terbaca. Path diganti `dirname(__DIR__, 3)`; assertion tidak diubah.
- Contoh Catalog/Order (availability-check, checkout) tidak diberi signature karena Service-nya belum ada; README menjelaskan pola dan menunjuk Service Epic 1 sebagai entry point yang didukung. Facade tidak dibuat (AD-5 hanya menyebut DI/Service).
- Contoh Livewire memakai `mount()` injection, bukan `__construct` (Livewire tidak inject lewat konstruktor).
- ATDD: 14 passed. Pest penuh: 237 passed, 2 skipped, 4 failed; 4 failure identik dengan baseline 1.7 (`CreateRoleTest` manage-settings, `GlobalSettingsAuditTest` x2, `GlobalSettingsServiceTest`).

## Review Triage Log

Layer Blind Hunter (subagent) dilewati; self-review inline saja.

| Finding | Verdict | Route | Evidence |
|---|---|---|---|
| Contoh Livewire awal memakai `__construct` injection | medium | patch | Livewire tidak inject via konstruktor; diganti `mount()`. |
| Contoh `CatalogService`/`OrderService` memakai signature karangan | medium | patch | Service belum ada; diganti penjelasan pola tanpa signature. |
| Klaim API Layer sudah tersedia | low | patch | Story 1.9 belum dibangun; kalimat diubah jadi "planned". |
