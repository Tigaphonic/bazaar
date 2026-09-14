---
name: 'Bazaar'
type: review
purpose: independent-verification
target: _artifacts/planning-artifacts/architecture/architecture-Tigaphonic/bazaar-2026-09-12/ARCHITECTURE-SPINE.md
scope: 'Technical-claim / version-currency review of the AD-33 amendment (Shell domain, Tailwind CSS v4 dev-tooling) plus a spot-check of pre-existing Stack pins.'
reviewer: independent (web-researched, not asserted from training data)
review-date: '2026-09-14'
status: final
---

# Version & Technical-Claim Review — ARCHITECTURE-SPINE.md (AD-33 amendment pass)

This is a fresh pass focused on the 2026-09-14 amendment (AD-33, the two new Stack rows for Tailwind CSS and Node.js/npm, and the Shell domain it introduces). It supersedes the previous `review-versions.md` for that scope. Every version/compatibility claim below was checked against Packagist, GitHub, or the vendor's own docs on 2026-09-14 — none is asserted from training-data recall alone. Sources are inline.

## Overall verdict

The amendment's core technical claims are accurate and current as of 2026-09-14 (Filament ^5.8 is genuinely the latest line, Tailwind v4 genuinely is Filament v5's theming target, Filament v3 genuinely cannot run Tailwind v4), and every spot-checked pre-existing Stack pin (`spatie/laravel-permission`, `spatie/laravel-activitylog`, `spatie/laravel-settings`, plus `laravel-model-states`, `laravel-medialibrary`, `maatwebsite/excel`, `barryvdh/laravel-dompdf`) matches the real current Packagist state — but AD-33's own cited precedent for its central design choice (shipping pre-built static CSS, modeled on "`filamentphp/plugin-skeleton`'s own `build:styles` script") does not exist in the current, default-branch version of that repository and is contradicted by what that repository's README actually recommends today.

## Findings

### HIGH — AD-33's cited precedent (`plugin-skeleton`'s `build:styles` script) does not exist on the current/default branch, and the repo's real guidance is the opposite of what AD-33 claims it supports

AD-33's rule paragraph justifies shipping pre-compiled static CSS by saying this "follows the pattern real Filament plugin authors already use for self-contained theming (e.g. `filamentphp/plugin-skeleton`'s own `build:styles` script)."

Verified directly against the repo:
- `filamentphp/plugin-skeleton`'s current default branch is `5.x` (confirmed via GitHub repo page, 2026-09-14).
- That branch's `package.json` (fetched at `https://raw.githubusercontent.com/filamentphp/plugin-skeleton/5.x/package.json`) has **no `build:styles` script and no `tailwindcss` devDependency at all** — only `esbuild` and `prettier`, with `dev`/`build` scripts that shell out to `bin/build.js`.
- `bin/build.js` (fetched directly) is a plain esbuild script that bundles `resources/js/index.js` → `resources/dist/skeleton.js`. It contains **no Tailwind CSS invocation whatsoever** — it compiles JS only, not CSS.
- The repo has no `resources/css` directory and no `tailwind.config.js`.
- The README's actual styling guidance for plugin authors is to have the **consuming application** add the plugin's own Blade paths to *its own* Tailwind build via a `@source` directive (`@source '../../../../vendor/:vendor_slug/:package_slug/resources/**/*.blade.php';`) — i.e., exactly the "purist" plugin-theming pattern (ask every consuming app to scan the plugin's Blade paths in their own Tailwind config) that AD-33 explicitly says it is rejecting.

A `build:styles` script that runs `npx tailwindcss ... --minify && npm run purge` with `filament-purge -v 3.x` does turn up in general web search for `plugin-skeleton` — but that is stale/cached content from an old (Filament v3-era) state of the repository, not the current `5.x` default branch. Citing it as "the pattern real Filament plugin authors already use" for a Filament v5/Tailwind v4-era decision is not supported by the artifact actually being cited today.

**Impact**: This doesn't necessarily mean AD-33's underlying decision (ship pre-built CSS, keep Node/Tailwind as dev-only tooling) is wrong — it's a defensible, independently justifiable choice given Story 1.1's zero-Node install promise. But the specific evidence AD-33 offers for "real Filament plugin authors already use [this pattern]" is factually incorrect as written, and the actual current official-skeleton precedent point the other way. This citation should be corrected or removed before the spine is treated as fully verified — either by finding a real current example of a Filament v5 plugin shipping pre-built static CSS via `FilamentAsset::register(Css::make(...))`, or by dropping the specific `plugin-skeleton` citation and defending the decision purely on Story 1.1's install-promise grounds (which stands on its own).

Sources:
- https://github.com/filamentphp/plugin-skeleton (default branch = `5.x`, checked 2026-09-14)
- https://raw.githubusercontent.com/filamentphp/plugin-skeleton/5.x/package.json
- https://raw.githubusercontent.com/filamentphp/plugin-skeleton/5.x/bin/build.js
- README `@source` guidance, same branch/tree

### LOW — `spatie/laravel-activitylog` 5.0/5.1 framing is slightly off from the real release history, though the practical conclusion (pin `^5.1`) is still correct

The Stack table says: "`^5.1` (5.1.1+ supports both Laravel 12 and 13 — superseded the earlier 5.0/5.1 split)."

Verified via Packagist and GitHub releases (2026-09-14):
- `5.1.1` is real and very recent — released **2026-09-08**, just six days before this document's `updated` date.
- The actual GitHub release note for 5.1.1 says it "restores Laravel 12 support, which 5.1.0 dropped unintentionally" — i.e., the regression was specifically in `5.1.0` (not a general "5.0/5.1 split"), and specifically about Laravel 12 (not both 12 and 13 being at issue).
- Packagist's current composer.json metadata for 5.0.0, 5.1.0, and 5.1.1 all now show `^12.0 || ^13.0` as the illuminate constraint, consistent with 5.1.1 being the safe pin.

The document's bottom-line pin (`^5.1`, i.e. require at least 5.1.1 in practice) is correct and well-timed — this was checked, not guessed, and it landed on the right answer given how fresh the fix was. The parenthetical explanation just overstates/mischaracterizes the mechanism slightly (calls it a "5.0/5.1 split" rather than "a regression introduced in 5.1.0"). Worth a small wording fix, not a version change.

Sources:
- https://packagist.org/packages/spatie/laravel-activitylog
- https://github.com/spatie/laravel-activitylog/releases

### INFO — Every other spot-checked/new version claim is confirmed current as of 2026-09-14

All checked directly against Packagist (or GitHub releases) today:

| Package/tool | Doc's claim | Verified latest (2026-09-14) | Verdict |
| --- | --- | --- | --- |
| `filament/filament` | `^5.8` | v5.8.1, released 2026-09-08 | Confirmed — current latest minor line |
| Tailwind CSS | `^4.0` | ~4.3.x line active; no v5 exists yet | Confirmed — v4 is still current major |
| `spatie/laravel-permission` | (implied ^8.x by AD text) `^8.3` in table | 8.3.0, released 2026-07-03; requires Laravel ^12\|\|^13 | Confirmed |
| `spatie/laravel-settings` | `^3.9` | 3.9.0 is latest | Confirmed |
| `spatie/laravel-model-states` | `^2.14` | 2.14.2, released 2026-07-22; requires Laravel ^12\|\|^13, PHP ^8.4 | Confirmed |
| `spatie/laravel-medialibrary` | `^11.23` | 11.23.7, released 2026-09-03; PHP ^8.2, Laravel ^10.2–^13.0 | Confirmed |
| `maatwebsite/excel` | `^4.0` | 4.0.2, released 2026-08-24; 4.x is current major (3.x is security-only) | Confirmed |
| `barryvdh/laravel-dompdf` | `^3.1` | v3.1.2, released 2026-02-21, still current line | Confirmed |
| `filament/spatie-laravel-settings-plugin` | "latest compatible" | v5.8.1, tracks Filament's own version, confirmed Filament v5-compatible | Confirmed |
| `spatie/laravel-package-tools` | `^1.16` *(existing skeleton)* | Upstream latest is actually 1.93.2 — but this pin matches the repo's real, already-committed `composer.json` (checked directly against `/Users/mastin/devphp-valet/package-bazaar/composer.json`), so it correctly describes existing locked code, not a stale new recommendation | Confirmed accurate to its stated scope |
| Filament v4/v5 ↔ Tailwind v4, Filament v3 ↔ Tailwind v3 only | AD-33's compatibility premise | Confirmed via Filament's own docs/discussions and multiple independent sources: Filament v4+ targets Tailwind v4 (CSS-first config, no `tailwind.config.js`); Filament v3 requires Tailwind v3 and cannot run v4 without a downgrade | Confirmed |
| `FilamentAsset::register([Css::make(...)])` API | Used by AD-33's rule as the registration mechanism | Confirmed current/correct API shape in Filament v4.x/v5.x docs (`/docs/4.x/advanced/assets`, mirrored 5.x docs) | Confirmed |
| Laravel `^12.0 \|\| ^13.0`, PHP `^8.4` | Stack floor | Laravel 13 released 2026-03-17 (min PHP 8.3 per most sources); PHP 8.5 already exists (released 2025-11-20) but `^8.4` as a composer floor still correctly admits 8.4.x and 8.5.x | Confirmed, not stale |

No claim in this table required a correction. The AD-33 "verified 2026-09-14" annotation on the Tailwind-v4/Filament-v5 pairing is itself accurate — that specific fact-check really does hold up against today's live sources.

## What was not independently re-verified

- The exact contents of a hypothetical `filamentphp/plugin-skeleton` *tag* matching Filament v3 (to confirm the `build:styles`/`filament-purge -v 3.x` script the search engine surfaced really was that old, rather than some unrelated fork) — not fetched by tag/commit history, only inferred from it being absent on the current default branch and being consistent with older Filament-v3-era tooling patterns known from general Filament ecosystem history. This doesn't change the HIGH finding above (the current-branch citation is what AD-33 relies on, and that's what was checked), but a maintainer wanting the full historical picture could pull an old tag to confirm.
- Node.js version-floor claims: AD-33 explicitly defers the exact Node version floor to Story 1.2, so there was nothing concrete to verify here.
