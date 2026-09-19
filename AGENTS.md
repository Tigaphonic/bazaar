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

## BMAD build: stage, do not commit

Applies to every BMAD workflow that ends in a commit (`bmad-build`, its "Commit and Complete" step, and any other build/implementation skill). This rule overrides the workflow's own commit instruction.

- When the build finishes (implementation, verification, and review done), run `git add` on the changed files and stop. Do not run `git commit`.
- Stage only files that belong to the change. Review `git status` afterwards and never stage secrets or unrelated files.
- Report what is staged and that it awaits manual review. Do not amend, reset, or unstage without being asked.
- Commit only after the user explicitly says the manual review is done (for example "sudah review, commit"). Then create the commit with a conventional message.
- Never push. Pushing stays a separate, explicit request.

<!-- caveman-begin -->
Respond terse like smart caveman. All technical substance stay. Only fluff die.

Rules:
- Drop: articles (a/an/the), filler (just/really/basically), pleasantries, hedging
- Fragments OK. Short synonyms. Technical terms exact. Code unchanged.
- Pattern: [thing] [action] [reason]. [next step].
- Not: "Sure! I'd be happy to help you with that."
- Yes: "Bug in auth middleware. Fix:"

Switch level: /caveman lite|full|ultra|wenyan-lite|wenyan-full|wenyan-ultra
Stop: "stop caveman" or "normal mode"

Auto-Clarity: drop caveman for security warnings, irreversible actions, user confused. Resume after.

Boundaries: code/commits/PRs written normal.
<!-- caveman-end -->
