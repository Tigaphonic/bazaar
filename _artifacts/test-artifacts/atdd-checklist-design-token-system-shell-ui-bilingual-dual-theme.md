---
stepsCompleted: ['step-01-preflight-and-context', 'step-02-generation-mode', 'step-03-test-strategy', 'step-04-generate-tests', 'step-05-validate-and-complete']
lastStep: 'step-05-validate-and-complete'
lastSaved: '2026-09-14'
storyId: '1.2'
storyKey: 'design-token-system-shell-ui-bilingual-dual-theme'
storyFile: '_artifacts/planning-artifacts/epics.md'
atddChecklistPath: '_artifacts/test-artifacts/atdd-checklist-design-token-system-shell-ui-bilingual-dual-theme.md'
generatedTestFiles:
  - 'tests/Unit/Shell/DesignTokensTest.php'
  - 'tests/Unit/Shell/TranslationParityTest.php'
  - 'tests/Feature/Shell/PanelThemeOverrideTest.php'
  - 'tests/Feature/Shell/UserThemePreferenceTest.php'
  - 'tests/Feature/Shell/UserLocalePreferenceTest.php'
  - 'tests/Feature/Shell/AccessibilityTest.php'
  - 'tests/Feature/Shell/Components/DataTableTest.php'
  - 'tests/Feature/Shell/Components/ModalTest.php'
  - 'tests/Feature/Shell/Components/ToastTest.php'
  - 'tests/Feature/Shell/Components/TabsTest.php'
  - 'tests/Feature/Shell/Components/EmptyStateTest.php'
  - 'tests/Feature/Shell/Components/AlertBannerTest.php'
inputDocuments:
  - '_artifacts/planning-artifacts/epics.md (Story 1.2, lines 295-317)'
  - '_artifacts/planning-artifacts/ux-designs/ux-Tigaphonic/bazaar-2026-09-11/DESIGN.md'
  - '_artifacts/planning-artifacts/ux-designs/ux-Tigaphonic/bazaar-2026-09-11/EXPERIENCE.md'
  - '_artifacts/planning-artifacts/architecture/architecture-Tigaphonic/bazaar-2026-09-12/ARCHITECTURE-SPINE.md'
  - '_artifacts/implementation-artifacts/epic-1-context.md'
  - '.claude/skills/bmad-testarch-atdd/resources/knowledge/data-factories.md'
  - '.claude/skills/bmad-testarch-atdd/resources/knowledge/component-tdd.md'
  - '.claude/skills/bmad-testarch-atdd/resources/knowledge/test-quality.md'
  - '.claude/skills/bmad-testarch-atdd/resources/knowledge/test-healing-patterns.md'
  - '.claude/skills/bmad-testarch-atdd/resources/knowledge/test-levels-framework.md'
  - '.claude/skills/bmad-testarch-atdd/resources/knowledge/test-priorities-matrix.md'
  - '.claude/skills/bmad-testarch-atdd/resources/knowledge/ci-burn-in.md'
---

# ATDD Checklist — Story 1.2: Design Token System & Shell UI Bilingual/Dual-Theme

## Step 1: Preflight & Context Loading

**Detected stack:** `backend` (Laravel/Filament PHP package; no `package.json`, no Playwright/Cypress config; Pest 4 + Orchestra Testbench confirmed working via `vendor/bin/pest --version`).

**Prerequisites verified:**
- Story approved with clear acceptance criteria (epics.md §Story 1.2, 4 Given/When/Then ACs + component kit + accessibility floor + responsive breakpoints)
- Test framework configured: `tests/Pest.php`, `phpunit.xml.dist`
- Dev environment available: `vendor/bin/pest 4.7.8` runs

**Story context:**
- Source: `_artifacts/planning-artifacts/epics.md` (common file, no per-story file — story_key derived by slugging the title)
- story_id: `1.2`
- story_key: `design-token-system-shell-ui-bilingual-dual-theme`

**Framework & existing patterns inspected:**
- `tests/Feature/Install/PanelNavigationTest.php` — Pest + `Filament::` facade + `$this->artisan('bazaar:install')` pattern
- `workbench/app/Providers/Filament/TestPanelProvider.php` — simulates the client's pre-existing panel
- `tests/TestCase.php` — Orchestra Testbench + WithWorkbench, `default_migration_path()` for core Laravel tables, Eloquent factory auto-resolution

**TEA config flags read:**
- `tea_use_playwright_utils: true`, `tea_use_pactjs_utils: true`, `tea_pact_mcp: mcp`, `tea_browser_automation: auto`, `test_stack_type: auto`
- Both Playwright Utils and Pact.js Utils mandates **out of scope**: their own relevance gates require `@seontechnologies/playwright-utils` / Vitest as `package.json` dependencies, and this project has no `package.json` (pure PHP/Pest). Skipped per their own gating language, not overridden.

**Knowledge fragments loaded (backend tier):**
- Core: `data-factories.md`, `component-tdd.md`, `test-quality.md`, `test-healing-patterns.md`
- Backend: `test-levels-framework.md`, `test-priorities-matrix.md`, `ci-burn-in.md`
- Principles will be translated to Pest/Livewire idiom (Eloquent model factories instead of `faker.js`/TS factory functions, Livewire component assertions instead of Playwright locators).

**Additional source documents read:**
- `DESIGN.md` — full token set (colors incl. dark-mode pairs, typography, spacing, radius, breakpoints, component anatomy for Sidebar/Topbar/Data Table/Modal/Toast/Tabs/Empty State/Alert Banner/Pagination)
- `EXPERIENCE.md` — accessibility floor (tab order, icon-only accessible names, native focus ring), theme/language toggle behavior (instant, no reload, persist per-User)
- `ARCHITECTURE-SPINE.md` — Filament theme-override only, no swappable panel abstraction
- `epic-1-context.md` — Story 1.2 confirmed foundational to all later epic UI

## Step 2: Generation Mode Selection

**Mode chosen: AI Generation** (mandatory for `{detected_stack}=backend` — no browser recording).

**Rationale:** Acceptance criteria are clear and scenarios are standard for this stack (Filament panel theme rendering, per-User persisted preference toggles, navigation-group/component presence, accessibility-floor markup checks). Backend-mode ATDD generates from source/documentation analysis (DESIGN.md tokens, EXPERIENCE.md behavior spec, existing Filament panel source) rather than live browser recording — consistent with `tests/Feature/Install/*` already testing the Filament/Livewire layer through Pest + Testbench, not a real browser.

## Step 3: Test Strategy

**Scope boundary (important, stated up front):** Story 1.2's ACs are heavily visual/runtime (instant no-reload theme/locale switch, focus-ring AA contrast, responsive breakpoint collapse, string-clipping prevention). This project has **no browser/CSS test tooling** (no Playwright/Cypress/Dusk, no `package.json`) — confirmed in Step 1. Pest can verify everything server-side/persistence/markup-contract, but cannot verify actual rendered CSS, live JS toggling, or pixel-level layout. Scenarios below are split into **automated (Pest, red-phase)** and **manual QA gate** (documented, not scaffolded as tests) so the gap is explicit rather than papered over with a weak assertion.

### 1. Map Acceptance Criteria → Scenarios

| AC | Scenario | Automatable in Pest? |
|---|---|---|
| AC1 — Shell renders DESIGN.md tokens as panel theme override (not stock Filament) | Panel registers a Bazaar-owned theme asset distinct from Filament's stock `filament/filament::css/app.css` | ✅ Integration |
| AC1 | Bazaar's shipped token source (CSS/config) contains the exact DESIGN.md values (primary `#00609e`, Poppins/Mulish, sidebar 250px, topbar 62px, radius/spacing scale) | ✅ Unit/content |
| AC2 — Theme toggle instant, no reload | Actual instant DOM swap with no reload | ❌ Manual QA (JS runtime, no browser tooling) |
| AC2 | Theme preference persists per-User across requests once toggled | ✅ Integration |
| AC2 | Dark-mode token values match DESIGN.md's net-new dark palette exactly (guards against a naive literal color inversion slipping in) | ✅ Unit/content |
| AC3 — Language toggle instant, no reload | Actual instant DOM swap with no reload | ❌ Manual QA |
| AC3 | Locale preference persists per-User across requests once toggled | ✅ Integration |
| AC3 | EN and ID translation files exist with identical key sets (no missing key breaks a locale) | ✅ Unit |
| AC3 | No chrome element clipped by longer ID strings | ❌ Manual QA (visual/layout) |
| Component kit — Data Table (skeleton-loading + pagination) | Table component exposes pagination and a loading/skeleton state | ✅ Integration |
| Component kit — Modal (1-level) | Opening a second action from within a modal opens a new view, never a nested modal | ✅ Integration |
| Component kit — Toast (required every action) | A sample state-changing action dispatches a Filament/Livewire notification | ✅ Integration |
| Component kit — Tabs | Tabs component renders and switches active panel | ✅ Integration |
| Component kit — Empty State | Empty State component renders headline/supporting line/optional CTA per DESIGN.md anatomy | ✅ Integration |
| Component kit — Alert Banner (3 variants) | Banner renders `danger`/`warn`/`info` with the correct semantic classes/colors, rejects a 4th | ✅ Integration |
| Accessibility — tab order matches visual order | DOM order of shell nav/topbar controls matches visual/reading order | ✅ Integration (DOM order proxy) |
| Accessibility — icon-only controls have accessible name | Every icon-only control in sidebar/topbar renders `aria-label` (or equivalent) | ✅ Integration |
| Accessibility — focus ring native, AA-contrast | Actual rendered contrast | ❌ Manual QA (visual) |
| Responsive breakpoints (Desktop/Tablet/Mobile) | Sidebar rail/drawer collapse behavior at 768px/1280px | ❌ Manual QA (CSS media query, no browser tooling) |

### 2. Test Levels Selected (backend stack)

- **Unit** — token/translation content checks that need no framework boot (token value equality, EN/ID key-set parity)
- **Integration** — Filament/Livewire component behavior through Pest + Testbench (panel theme registration, per-User preference persistence, component kit contracts, accessibility markup), following the existing `PanelNavigationTest`/`UserResourceModelTest` pattern
- **No E2E** — confirmed per backend-stack rule; no browser test runner exists in this project
- **Duplicate coverage guard applied:** token-value checks live once at Unit level (content), not re-asserted at Integration level; persistence tested once per preference (theme, locale), not per-page

### 3. Priorities (P0–P3, `risk_threshold: p1` from config)

| Priority | Scenarios |
|---|---|
| **P0** | Per-User theme preference persistence; per-User locale preference persistence — these are the new state Story 1.2 introduces that every later epic's UI depends on |
| **P1** | Panel theme override wiring (not stock Filament); DESIGN.md token content fidelity; EN/ID key parity; Toast-on-action; Data Table pagination+skeleton; Alert Banner 3-variant contract; icon-only accessible names |
| **P2** | Dark-token-not-inverted content check; Modal single-level; Tabs; Empty State; tab-order DOM sequence |
| **P3** | — (none; every in-scope automatable scenario is a foundational contract for later epics, none deferred to P3) |

### 4. Red-Phase Confirmation

None of the above exists yet (`src/` has no Theme/Shell/UI-kit domain, no lang files, no per-User preference columns — confirmed empty in Step 1 reconnaissance). Every scaffold in Step 4 will fail on a missing class/route/column, satisfying TDD red phase by construction, not by assertion design.

### 5. Manual QA Gate (not scaffolded — hand off to human/visual review)

- Instant no-reload theme and language switch (JS/Livewire runtime behavior)
- Focus ring visible at AA contrast in both themes
- Responsive breakpoint collapse (sidebar → rail at 768–1279px, sidebar → drawer at <768px)
- No chrome element clipped by longer Indonesian strings
- Dark-mode palette reads correctly against real rendered surfaces (beyond token-value equality)

## Step 4: Red-Phase Test Scaffold Generation

**Orchestration adaptation:** `step-04-generate-tests.md`'s Worker A (API)/Worker B (E2E) subagent dispatch is hard-coded to Playwright/TypeScript output (`test.skip()`, `apiRequest`, JSON files under `/tmp/tea-atdd-*.json`). Per the same relevance gate applied to Playwright Utils in Step 1, this does not fit a pure-PHP/Pest backend project — dispatching it would produce TypeScript files with no runner to execute them. Adapted: scaffolds were authored directly (sequential, no subagent dispatch needed for 12 files) in Pest, using `->skip('reason')` as the direct native equivalent of `test.skip()` — same red-phase intent (scaffold ships skipped; a developer un-skips it during `dev-story`, and it fails until implemented).

**Open design question resolved during generation:** where does per-Staff theme/locale preference persist? AD-18's amendment scopes Bazaar away from owning the host's `users` table, so this wasn't free to invent. Confirmed with the user: a **Bazaar-owned `bazaar_user_preferences` table** (ULID PK per AD-18 default), never a column bolted onto the host's `users` table. This shapes `UserThemePreferenceTest.php` and `UserLocalePreferenceTest.php`.

**Filament API grounded in `vendor/filament/filament/src/Panel/Concerns/`** (not invented): `HasTheme::theme()/getTheme()` (stock default theme id is `'app'`), `HasColors::colors()/getColors()`, `HasDarkMode::darkMode()/themeSwitcher()` (both already default `true` in Filament core — **not** re-tested here, since asserting an already-true Filament default would pass with zero Bazaar code and violate red-phase). `Filament\Notifications\Notification` + its `assertNotified()` test helper confirmed Toast should wrap Filament's native notification system, not a bespoke component.

**12 test files generated** (27 individual scenarios — `AlertBannerTest.php`'s variant check is a `->with([...])` dataset, 3 cases):

| File | Level | Scenarios covered |
|---|---|---|
| `tests/Unit/Shell/DesignTokensTest.php` | Unit | AC1 token content fidelity; AC2 dark-token-not-inverted |
| `tests/Unit/Shell/TranslationParityTest.php` | Unit | AC3 EN/ID key parity |
| `tests/Feature/Shell/PanelThemeOverrideTest.php` | Integration | AC1 panel theme override wiring (not stock Filament) |
| `tests/Feature/Shell/UserThemePreferenceTest.php` | Integration | AC2 per-User theme persistence (P0) |
| `tests/Feature/Shell/UserLocalePreferenceTest.php` | Integration | AC3 per-User locale persistence + app-locale application (P0) |
| `tests/Feature/Shell/AccessibilityTest.php` | Integration | Icon-only accessible names; tab-order DOM sequence |
| `tests/Feature/Shell/Components/DataTableTest.php` | Integration | Skeleton-loading state; pagination |
| `tests/Feature/Shell/Components/ModalTest.php` | Integration | 1-level modal (no stacking) |
| `tests/Feature/Shell/Components/ToastTest.php` | Integration | Toast fires on action; semantic variant mapping |
| `tests/Feature/Shell/Components/TabsTest.php` | Integration | Renders tabs; switches active tab |
| `tests/Feature/Shell/Components/EmptyStateTest.php` | Integration | Headline/caption; conditional CTA |
| `tests/Feature/Shell/Components/AlertBannerTest.php` | Integration | 3 semantic variants (dataset); rejects a 4th |

**Verification run:** `vendor/bin/pest tests/Unit/Shell tests/Feature/Shell` → **27 skipped, 0 assertions, 0 errors** (clean parse/collection, no crashes referencing not-yet-existing classes). Full suite: `vendor/bin/pest` → **27 skipped, 32 passed (48 assertions)** — Story 1.1's existing coverage is untouched.

## Step 5: Validate & Complete

**Validation checklist:**
- [x] Prerequisites satisfied (Step 1)
- [x] Test files created correctly — 12 files, all collect cleanly under Pest
- [x] Checklist matches acceptance criteria — every ✅ row in Step 3's mapping table has a corresponding scenario above; every ❌ row is named in the Manual QA Gate, not silently dropped
- [x] Tests generated as red-phase scaffolds — Pest `->skip('reason')`, the native equivalent of `test.skip()` confirmed in Step 4
- [x] Story metadata and handoff paths captured (frontmatter: `storyId`, `storyKey`, `storyFile`, `generatedTestFiles`)
- [x] Temp artifacts stored in `_artifacts/test-artifacts/`, not `/tmp` (no subagent dispatch occurred, so no `/tmp/tea-atdd-*.json` was ever created)
- N/A CLI/browser sessions — no browser tooling used in this stack
