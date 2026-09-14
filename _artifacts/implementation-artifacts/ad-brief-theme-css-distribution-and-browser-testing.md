# AD Brief: Theme/CSS Distribution & Browser Testing Strategy

**Status:** Input for Winston (architect) — not yet a ratified AD.
**Raised during:** ATDD red-phase scaffolding for Story 1.2 (Design Token System & Shell UI Bilingual/Dual-Theme).
**Why it's an architecture question, not just a testing question:** the two decisions below are coupled — the CSS distribution mechanism determines whether Node/npm becomes real infrastructure in this repo, which in turn changes the cost/benefit of the browser-testing tool choice. Both affect every UI story from here on (Epic 1 stories 1.3–1.7 and every later epic's Filament screens), so getting them recorded once, consistently, matters more than for a typical implementation detail.

---

## 1. The problem

Story 1.2's acceptance criteria are heavily visual/runtime: instant no-reload theme/language toggle, focus-ring AA contrast, responsive breakpoint collapse (sidebar → rail → drawer), no string-clipping from longer Indonesian labels. During ATDD scaffolding it became clear:

- This repo has **zero browser/CSS test tooling today** — no `package.json`, no Playwright/Cypress/Dusk. Testing is 100% Pest/PHPUnit via Orchestra Testbench.
- `_bmad/tea/config.yaml` has `tea_use_playwright_utils: true`, which assumes a JS/Playwright stack that doesn't exist here. Its own relevance gate (`playwright-utils-mandate.md`) correctly no-ops because there's no `package.json` — but the underlying gap (how do we verify visual/runtime behavior at all) remains unaddressed.
- Pest can verify server-side wiring, persistence, and markup contracts, but **cannot** verify actual rendered CSS, live JS toggling, or pixel-level layout. 5 of Story 1.2's scenarios were pushed to a documented "Manual QA Gate" rather than faked with weak assertions (see `_artifacts/test-artifacts/atdd-checklist-design-token-system-shell-ui-bilingual-dual-theme.md`, Step 3).
- **This will recur every UI story** unless a project-level decision is made now.

## 2. Decision 1 — How does Bazaar ship its DESIGN.md theme CSS?

Bazaar is a Filament **plugin**, not a standalone app — end users `composer require` + run one Artisan command (Story 1.1), with an explicit "no new panel, no greenfield scaffold" install UX goal (epic-1-context.md). This constrains the theming mechanism.

**Researched precedent** (Filament's own docs + real plugin ecosystem):

- Filament's asset system (`FilamentAsset::register([Css::make($id, $path)])`) is built for **pre-compiled, static CSS files** shipped inside a package — consumers need zero build step for this path. ([Filament Docs — Assets](https://filamentphp.com/docs/3.x/support/assets))
- Filament's own guidance for **Tailwind-based** CSS specifically says plugins should *not* pre-compile — because Tailwind output is unique per application — and instead should ask the consuming app to add the plugin's Blade paths to its own `tailwind.config.js`. This is the "purist" path.
- **But** the official [`filamentphp/plugin-skeleton`](https://github.com/filamentphp/plugin-skeleton) ships its own `build:styles` script (Tailwind + PostCSS + a Filament-purge step), producing a compiled `resources/dist/*.css` the plugin ships directly — i.e., real-world plugin authors routinely *do* pre-compile Tailwind themselves, purged against Filament core's own classes to avoid duplication. [Adam Weston](https://aw.codes/blog/keep-your-filament-plugins-light) (prolific Filament plugin author) writes specifically about this purge technique.
- The "purist" path (asking every client app to edit their own `tailwind.config.js`) **directly contradicts** Bazaar's own "out of the box, no greenfield scaffold" install promise. The self-contained pre-built path does not.

**Working conclusion (needs Winston's ratification, and a number in ARCHITECTURE-SPINE.md):** Bazaar compiles its DESIGN.md token CSS + component-kit CSS **once, at Bazaar's own dev/release time**, using npm/Tailwind as a **devDependency of the Bazaar repo only** — never a runtime dependency for host apps. The compiled static CSS ships inside the Composer package and is registered via `FilamentAsset::register()` (or `$panel->theme()` pointing at the compiled file). Host apps need zero Node/npm to consume Bazaar's theme.

**Consequence for the codebase:** this repo will need a `package.json` + Tailwind config + a build script, purely as internal tooling — not shipped/required downstream. This should probably become its own AD (or a clause under a theming AD), cross-referenced with:
- **AD-16** (three sanctioned extension seams — does a client overriding Bazaar's visual tokens fit one of the three seams, or does it need a fourth: a CSS-variable override point?)
- Nothing in AD-18 conflicts (identifiers), but worth a line noting the theme build pipeline is dev-tooling, not a Bazaar-owned *data* migration, so AD-18 doesn't apply to it.

## 3. Decision 2 — Browser testing tool, given Decision 1

Two real options were weighed (Dusk was my initial recommendation; revised after the user pushed back on performance):

| | **Laravel Dusk** | **Playwright** |
|---|---|---|
| Toolchain | Pure Composer (`laravel/dusk` + `orchestra/testbench-dusk` for package+workbench scenarios — exact version compatibility not yet verified) | Node/npm (`@playwright/test`) |
| Protocol | WebDriver via ChromeDriver — HTTP round-trip per command | CDP direct — fewer layers, smarter auto-waiting |
| Fit for "instant no-reload" Livewire toggles | Weaker — tends to need manual `waitFor()`/flaky | Strong — this is exactly Playwright's strength |
| Setup cost if Decision 1 → self-contained Tailwind build | Adds a **second**, unrelated toolchain (Node for CSS build + Composer/ChromeDriver for tests) | **Rides on the Node toolchain Decision 1 already requires** — not a new foreign dependency |
| Setup cost if Decision 1 → no build step at all | Lower net toolchain (stays Composer-only) | Adds Node purely for tests — the "confusing for a package library" concern is real here |

**Working conclusion:** if Decision 1 lands on the self-contained pre-built CSS path (as the research above suggests it should), **Playwright becomes the clear choice** — the Node dependency is already justified by the theme build, and Playwright's performance/reliability edge (validated, not just marketing — see CDP vs WebDriver above) has no real offsetting cost left. If Decision 1 somehow lands elsewhere (no build step), this needs to be re-opened.

## 4. Already confirmed (not open questions)

- Per-Staff theme/locale preference persists in a **Bazaar-owned `bazaar_user_preferences` table** (ULID PK per AD-18's default), never a column bolted onto the host app's own `users` table — confirmed with the user during Story 1.2 ATDD, consistent with AD-18's amendment scoping Bazaar away from owning the host's User table.
- Toast notifications should wrap **Filament's own native `Notification`/`assertNotified()` system**, not a bespoke component — confirmed via `vendor/filament/notifications` inspection.

## 5. What's needed from Winston

1. Ratify (or correct) Decision 1 — the self-contained pre-built CSS distribution mechanism — and record it as a numbered AD in ARCHITECTURE-SPINE.md, cross-referenced with AD-16.
2. Given that, ratify Decision 2 (Playwright vs Dusk vs something else) — or explicitly hand the final call back to the Test Architect (this session/persona) once Decision 1 is locked, since it's a direct function of it.
3. If Playwright is confirmed: note that `_bmad/tea/config.yaml` (`tea_use_playwright_utils`, `tea_browser_automation`) currently assumes this but the *infrastructure* to back it up doesn't exist yet — someone needs to scope "add Playwright + Node build pipeline" as real implementation work (likely inside Story 1.2 itself, since it's the story that needs the theme build in the first place), not assume it's already there.
4. Once ratified, the Test Architect will update Story 1.2's ATDD checklist Manual QA Gate and `_bmad/tea/config.yaml` to match.
