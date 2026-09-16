- source_spec: `_artifacts/implementation-artifacts/spec-1-2-design-token-system-shell-ui-bilingual-dual-theme.md`
  summary: Verifikasi `ApplyUserLocale` middleware tetap resolve user yang benar lewat `$request->user()` saat panel Filament host mengonfigurasi `authGuard()` kustom (bukan guard default).
  evidence: Mekanisme resolver Filament untuk guard kustom belum ditelusuri tuntas dan saat ini tidak reachable (belum ada `->login()`/panel auth — Story 1.3). Jika benar bermasalah, severity medium (locale gagal diterapkan diam-diam pada host ber-guard kustom). Yang perlu diverifikasi: urutan middleware auth Filament vs `$panel->middleware()`, atau test nyata begitu Story 1.3 memasang panel auth dengan guard kustom.

- source_spec: `_artifacts/implementation-artifacts/spec-1-2-design-token-system-shell-ui-bilingual-dual-theme.md`
  summary: Tulis ulang assertion `tests/Feature/Shell/AccessibilityTest.php` agar memeriksa atribut `aria-label` nyata lewat parsing DOM, bukan substring literal pesan kegagalan Pest, lalu hapus komentar HTML inert workaround di `resources/views/shell/topbar.blade.php`.
  evidence: Bentuk assertion (`toContain("aria-label", "Expected an aria-label near the {$control} control")`) sudah ada sejak baseline scaffold ATDD, bukan diperkenalkan Story 1.2 — memperbaikinya berarti mengubah assertion test yang frozen, di luar boundary story ini. Assertion saat ini bisa lolos meski aria-label nyata dihapus di masa depan, selama komentar inert-nya tetap ada.

## Deferred from: code review of spec-1-2-design-token-system-shell-ui-bilingual-dual-theme.md (2026-09-16)

- Locale Middleware Auth Context (`ApplyUserLocale.php`): Membutuhkan konteks implementasi autentikasi panel pada Story 1.3 untuk benar-benar menguji urutan middleware.
- AccessibilityTest String Matching (`AccessibilityTest.php`): Perlu diperbaiki (menggunakan DOM parsing) pada tinjauan kualitas tes yang terpisah.

- source_spec: `_artifacts/implementation-artifacts/spec-1-3-manage-role-permission.md`
  summary: `RoleResource` (dan `UserResource`) tidak punya authorization/policy gate apa pun — setiap Staff yang login bisa membuat/mengubah/menghapus Role dan Permission apa pun.
  evidence: Terkonfirmasi via grep `src/` dan `config/`: nol penggunaan `canAccess()`/Policy/`Gate::` di seluruh package. Pre-existing sejak Story 1.1 (`UserResource` juga tanpa gate), dan belum ada panel `->login()` sama sekali — enforcement "Staff berwenang" (AC1's Given clause) baru bisa dibangun begitu Story 1.4 (Manage User + Role assignment) dan panel auth terpasang.

- source_spec: `_artifacts/implementation-artifacts/spec-1-3-manage-role-permission.md`
  summary: `RoleResource`'s UI surface (field `name`, `permissions` CheckboxList, copy modal konfirmasi delete) tidak punya label bilingual EN/ID, berbeda dari komitmen bilingual Shell (Story 1.2).
  evidence: Cocok dengan `UserResource` (Story 1.1) yang juga tanpa label bilingual — bilingual Story 1.2 discope ke chrome Shell (topbar/sidebar/component kit), belum ada konvensi i18n per-Resource form label di package ini. Perlu keputusan desain: apakah tiap Resource CRUD baru wajib punya key `resources/lang/{en,id}/user.php` sendiri.

- source_spec: `_artifacts/implementation-artifacts/spec-1-3-manage-role-permission.md`
  summary: Menghapus Role yang masih dipegang User tidak memberi peringatan "Role ini masih dipakai N User" di luar modal konfirmasi generik, dan belum ada hook eksplisit ke Audit Trail (Story 1.5).
  evidence: AC3 hanya mensyaratkan modal konfirmasi generik (sudah ada); audit trail eksplisit discope ke Story 1.5 oleh epic-1-context.md ("auto-capture-nya harus berfungsi tanpa instrumentasi manual di semua epic berikutnya") — perlu diverifikasi ulang begitu Story 1.5 shipped bahwa mutasi `RoleService::delete()` benar-benar tercatat otomatis tanpa perubahan kode di sini.

## Deferred from: code review (spec-1-3-manage-role-permission.md)
- [ ] Missing Authorization Gate (canAccess) in RoleResource — Pre-existing gap logged previously, awaiting Story 1.4 for proper user management and panel auth.
