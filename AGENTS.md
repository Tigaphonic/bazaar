<!-- bmad:context -->
<!-- Verified 2026-09-12 against e70fc8b. Managed by bmad-project-context; edits inside this block are replaced on refresh. Keep anything you want preserved outside the markers. -->

## Bazaar

Laravel package (`tigaphonic/bazaar`) providing a headless Filament admin backend for an eCommerce Brand Store — catalog/order/finance/etc., new + second-hand/serialized inventory — consumed by a separate customer-facing portal via a Service/API layer the package exposes. Still the unmodified `spatie/package-skeleton-laravel` scaffold; no domain code exists yet. Planning lives under `_artifacts/planning-artifacts/` (PRD, UX spine, architecture spine, each in its own dated subfolder).

## Where things are

- Product requirements: `prd.md` in `_artifacts/planning-artifacts/prds/prd-Tigaphonic/bazaar-2026-09-11/` (9 domains, FR-1–FR-40, status final).
- Technical addendum: `addendum.md` in the same folder — architecture principles (P1–P10), schema detail, gateway choices. Superseded as the binding source by the architecture spine below; kept as its verified input.
- Architecture spine: `_artifacts/planning-artifacts/architecture/architecture-Tigaphonic/bazaar-2026-09-12/ARCHITECTURE-SPINE.md` — status final, 32 architecture decisions (AD-1–AD-32) governing domain boundaries, stack, identifiers, and the operational envelope. Read this before writing any domain code.
- UX spine: `DESIGN.md` + `EXPERIENCE.md` in `_artifacts/planning-artifacts/ux-designs/ux-Tigaphonic/bazaar-2026-09-11/` — visual tokens and dashboard behavior for the Filament admin.
- Original product brief: `_artifacts/business-draft/brandstore-package-brief.md`.

<!-- /bmad:context -->
