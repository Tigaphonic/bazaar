# Adversarial Review — AD-33 (Shell) Amendment

**Target:** `_artifacts/planning-artifacts/architecture/architecture-Tigaphonic/bazaar-2026-09-12/ARCHITECTURE-SPINE.md`
**Scope:** AD-33 and its ripple effects on the Structural Seed, dependency diagram, Stack table, Consistency Conventions, Deployment envelope, and Deferred section.
**Method:** for each claim in AD-33, construct two hypothetical implementations one level down — a Story 1.2+ developer building Shell, and a Story 1.3+ developer consuming it — that each satisfy the Rule as literally written, and check whether they can still diverge incompatibly. A pair that diverges is a hole; a pair that converges is confirmed sound.

## Verdict

AD-33 is directionally sound and closes the real gap it names (DESIGN.md's tokens had no architectural home), but the Rule text under-specifies enough surface area — asset registration mechanism, JS/interactivity assets, install-time build discipline, and theme/locale state ownership — that two compliant implementations of Shell, or two compliant consumers of it, can diverge into incompatible builds; the amendment also introduced a real defect (Shell is drawn as an isolated node in the dependency diagram, with no edges to the domains AD-33's prose says depend on it), which should be fixed before Story 1.2 proceeds.

---

## Findings

### F1 — CRITICAL: Shell is a floating node in the dependency diagram; AD-6's diagram-based enforcement has a blind spot for it

**Where:** Structural Seed → Domain dependency direction (lines ~312–349); AD-33 Rule (line 233): *"every other domain depends on it for its Filament UI; it depends on nothing else in-package."*

The mermaid graph adds `Shell` to the `Foundation` subgraph alongside `User` and `Settings`, but **no edge in the diagram points into or out of `Shell`** — not `Order --> Shell`, not `Catalog --> Shell`, nothing. Every other Foundation-tier dependency claim in the prose (`Order --> User`, `Catalog --> Settings`, etc.) is mirrored by an actual edge; Shell's is not. This is a real inconsistency between AD-33's prose and the diagram the spine explicitly treats as authoritative for cross-domain direction: AD-6 says "each domain-pair dependency is one-directional (**see the diagram** under Structural Seed)," and AD-5's arch-test enforcement is framed the same way — the diagram is the thing a `pestphp/pest-plugin-arch` test gets written against.

**Two units that diverge:** Dev A, implementing Order's Filament Resources in Story 1.3, includes Shell's Data Table component per AD-33's prose. Dev B, later asked to add an arch-test enforcing "domain dependencies match the diagram" (the natural extension of AD-5's existing test), writes it against the diagram as drawn — which encodes *no* constraint on Shell at all, in either direction. Nothing then stops a third developer from having Shell's Toast component read a Setting (`Shell --> Settings`) while Settings' own health-check widget (which needs Shell's Alert Banner) depends on Shell (`Settings --> Shell`) — a two-node cycle AD-6 forbids in spirit but which no test catches, because the diagram never encoded Shell's edges to begin with.

**Fix:** Redraw the diagram with explicit edges from every domain that has a Filament UI into `Shell` (or a single annotated edge/note if drawing nine individual arrows is too noisy), and explicitly state that Shell accepts zero incoming edges from any *other* Foundation member and that `Settings --> Shell` / `User --> Shell` (theme-consumption) does not license `Shell --> Settings` / `Shell --> User` in return.

### F2 — CRITICAL: AD-33's Rule fixes the CSS pipeline but says nothing about Shell's JS/interactivity assets

**Where:** AD-33 Rule (lines 235–237); Structural Seed Shell entry (lines 276–282) lists **Modal, Toast, Tabs, Dropzone** — all components that, in any real implementation, need client-side interactivity beyond what Filament's stock Alpine.js already provides (toast queuing/auto-dismiss timers, dropzone drag-and-drop handling, tab-panel state). AD-33's entire Rule paragraph — "Tailwind v4... compiled once... ships as pre-built static CSS... `FilamentAsset::register([Css::make(...)])`... a client's install needs zero Node/npm" — is scoped **exclusively to CSS**. There is no parallel sentence for JS.

**Two units that diverge:** Dev A, building Dropzone, reasons by analogy ("the same story applies to JS") and pre-bundles a small Alpine plugin at Bazaar's own release time, registering it via `FilamentAsset::register([Js::make(...)])` — fully consistent with AD-33's spirit. Dev B, finding literally no Rule text covering JS, and needing a drag-and-drop library Alpine doesn't provide out of the box, does the expedient thing: pulls a library from a CDN `<script>` tag, or worse, tells the host app's own Vite config to include Shell's JS entry point (mirroring the exact "purist" plugin-theming pattern AD-33's second paragraph explicitly *rejects for CSS* but never rejects for JS). Both developers can point to AD-33's literal text as not prohibiting their choice — because it never mentions JS at all. Dev B's version reopens the "host app needs Node" hole that is AD-33's entire reason for existing, just via the JS door instead of the CSS door.

**Fix:** Extend AD-33's Rule (not just the Deferred note about `package.json` contents) to state explicitly that any JS Shell's interactive components require is compiled/bundled under the same dev-time-only discipline and shipped via `FilamentAsset::register([Js::make(...)])`, or state a deliberate constraint that Shell's components may use **only** Alpine directives inline in Blade (no separate JS bundle at all) if that's the intended design.

### F3 — HIGH: The Rule offers two registration mechanisms disjunctively without confirming they're equivalent — one may silently require host-side Vite

**Where:** AD-33 Rule (line 235): *"registered through Filament's own asset system (`FilamentAsset::register([Css::make(...)])`, or `Panel::theme()` pointing at the compiled file)."*

These are offered as interchangeable options ("or"), but they are not obviously the same mechanism. `FilamentAsset::register(Css::make(...))` is Filament's plugin-style static-asset registration — clearly a pre-built-file path. `Panel::theme()` is the API Filament has historically paired with `->viteTheme()` for custom panel theming (asking a consuming app to run the theme CSS through its own Vite pipeline) — precisely the "purist" pattern AD-33's own second paragraph says it is rejecting. AD-33 never confirms that Filament v5's `Panel::theme()` accepts a plain static file path without any host-side Vite processing, nor does it rule out `->viteTheme()` by name.

**Two units that diverge:** Dev A registers via `FilamentAsset::register(Css::make('bazaar-theme', __DIR__.'/../../resources/dist/theme.css'))` — a static file, zero host build. Dev B, reading the Rule's second option as equally sanctioned, wires `Panel::theme()` the way Filament's own docs demonstrate it (paired with `->viteTheme()`), because that's the officially-documented path for panel theme overrides. Dev B's implementation satisfies AD-33's literal text (`Panel::theme() pointing at the compiled file` — arguably still true even mid-Vite-pipeline) while directly violating AD-33's stated purpose (zero Node/npm for host apps).

**Fix:** Name one mechanism, not two, or if both are genuinely intended as valid, add a clause explicitly banning `->viteTheme()` / any Vite-manifest-based resolution and confirming `Panel::theme()`'s argument must be a plain static path.

### F4 — HIGH: Nothing forbids wiring the Tailwind build into an install-time hook, and `bazaar:install`/`bazaar:status` already exist as a tempting place to do it

**Where:** AD-33 Rule + Consequence paragraph (lines 235–237); Structural Seed `Install/` entry (line 304): *"Artisan commands: `bazaar:install`, `bazaar:status` (AD-17)"* — both already shipped per Story 1.1 (commit `0b25026`).

AD-33 fixes *that* the CSS is compiled at Bazaar's own dev/release time and ships pre-built, but never states that no install-time process (Composer lifecycle script, or the already-existing `bazaar:install`/`bazaar:status` commands) may invoke the build. This is a live target, not a hypothetical: a developer extending `bazaar:install` to "helpfully" regenerate or verify theme assets, or a developer adding a `composer.json` script hook for repo-local dev convenience that later gets miscopied into a path that runs for consumers, would silently reintroduce Node/npm as a runtime dependency for every client install — the exact thing AD-33 exists to prevent — while technically never touching the "compiled once at dev/release time" clause (the hook could claim to just be "re-verifying" the shipped file).

**Two units that diverge:** Dev A ships the compiled CSS as a committed/packaged static file, and `bazaar:install`/`bazaar:status` never reference the Tailwind toolchain. Dev B, wanting `bazaar:status` to warn "theme assets stale, rebuild needed" as a nice diagnostic (a very AD-17-flavored instinct, since AD-17 already establishes `bazaar:status` as the pull-based health-check surface), adds an optional `npm run build` invocation gated behind a Node-present check — which then either silently no-ops (confusing) or crashes for the overwhelming majority of client installs that correctly have no Node.

**Fix:** Add one sentence to AD-33 (or AD-17, since it's the domain that owns `bazaar:status`): the theme build is a maintainer/CI-time-only action; `Install/`'s Artisan commands must never invoke, check for, or depend on Node/npm/Tailwind at any point.

### F5 — MEDIUM: Two plausible owners for theme/locale preference state — AD-33 (Shell) vs. AD-21 (Settings) vs. AD-18 (User)

**Where:** AD-33 Rule (line 233): Shell owns *"bilingual EN/ID and dual light/dark-theme mechanics."* AD-21 (lines 157–161): Settings owns *"runtime, staff-editable operational parameters."*

AD-33 assigns Shell the *mechanics* of theme/locale switching, but no AD fixes where the *preference value* is persisted. Three equally-literal readings exist: (a) a per-Staff-User column/preference (a User-domain concern, AD-18-relevant since it's a Bazaar-owned table), (b) a single Global Setting applying install-wide (AD-21's stated territory: "runtime, staff-editable operational parameters"), or (c) unpersisted client-side state (cookie/localStorage), which never touches either domain.

**Two units that diverge:** Dev A implements dark/light mode as a per-Staff toggle stored on `User` — each Staff member gets their own preference. Dev B, reading AD-21 as the obvious home for anything "runtime, staff-editable," adds a single `ThemeSettings` class — one theme for the whole install, overridable only by whoever has Settings-edit permission. These are not just different implementations; they are different *products* (personal preference vs. install policy), and a client relying on one behavior after Story 1.2 ships would see it silently invert if a later story "fixes" it against the other reading.

**Fix:** One line in AD-33 or a new sub-clause: theme/locale preference is [per-Staff-User column on `User` | a Global Setting | client-side-only, never persisted] — pick one.

### F6 — MEDIUM: Confirmed AD-33/AD-16 gap — and AD-16's "no class in the package" wording doesn't obviously reach compiled/static assets

**Where:** Deferred section (line 419) already flags this: *"Client-side visual token override... doesn't yet fit any of AD-16's three sanctioned seams... a CSS-variable/token override point may need a fourth. Not resolved now."* AD-16 Rule (lines 126–131): *"No **class** in the package is 'just edit it directly.'"*

This is correctly self-flagged as deferred, but two things make it riskier than a typical "revisit later" item. First, AD-16's actual prohibition is worded around **classes** (container bindings, Events, Resource classes) — a client hand-patching Shell's *shipped compiled CSS file* in `vendor/` is not obviously forking "a class," so a literal reading of AD-16 doesn't clearly forbid the one form of forking AD-33 makes newly possible (a static asset to edit, where none existed before). Second, Shell's build lands in Story 1.2 — now, not "when a client actually needs it" — so the pressure to invent an unreviewed fourth seam arrives immediately, not on a comfortable future timeline.

**Two units that diverge:** Dev A honors the Deferred note and ships no override seam at all in Story 1.2 — a client wanting brand colors has no supported path. Dev B, unblocking a real client under deadline pressure, reaches for a mechanism that looks locally sanctioned: a `custom_css` field on Settings (AD-21-compliant: it's runtime, staff-editable), rendered into a raw `<style>` tag via a Filament render hook. This ships a de facto, unreviewed fourth seam — with no CSP/injection review — that Dev A's story never anticipated, and that other clients now start depending on before it's been architected.

**Fix:** Either explicitly forbid any Settings-driven raw-CSS-injection shortcut until the fourth seam is designed (closing Dev B's exit), or fast-track the fourth-seam design given Shell's build lands in the very next story, not a hypothetical future one. Also extend AD-16's Rule text to state the no-forking prohibition covers Shell's shipped assets, not just PHP classes.

### F7 — MEDIUM: "Reusable UI kit" component list doesn't say whether these are new components or theme-overrides of Filament's own

**Where:** Structural Seed Shell entry (lines 276–282): *"the reusable UI kit (Data Table, Modal, Toast, Tabs, Pagination, Empty State, Alert Banner, Dropzone)."* AD-33 Rule (line 233): *"as a Filament panel theme override, never the stock Filament theme."*

Filament already ships native Table, Modal, Tabs, and Pagination building blocks. "Theme override, never the stock Filament theme" reads most naturally as *re-skinning Filament's existing components via CSS/Blade view overrides* — but the Structural Seed's component list reads like a from-scratch component library with its own Blade/Livewire classes and public API. These two readings produce very different codebases (a view+CSS override package vs. a full parallel component library with independent state management, JS footprint, and AD-24 semver surface).

**Two units that diverge:** Dev A publishes Blade view overrides (`resources/views/vendor/filament-tables/...`) that restyle Filament's stock `Table` builder — small, low-risk, stays inside "theme override." Dev B builds standalone `<x-bazaar-shell::data-table>` Livewire components with their own sorting/filtering/pagination state, because "Data Table" in the Structural Seed reads as a component to build. Every domain's Filament Resource built against Dev B's kit is incompatible with a later decision to go with Dev A's (cheaper, lower-maintenance) approach, and vice versa.

**Fix:** One clarifying clause on which of the two Shell actually is.

### F8 — LOW: AD-5's Models/Services/Actions folder convention doesn't obviously fit Shell; no stated carve-out

**Where:** AD-5 Rule (lines 56–60): *"Each domain is its own namespace (`src/{Domain}/{Models,Services,Actions,Enums,States,Events,Filament}`)... Presentation code... may only call a Service."* Structural Seed Shell entry never lists Models/Services/Actions, unlike every other domain's entry.

AD-5 binds "all domains," and Shell is now a domain — but it plausibly has no Models/business entities at all (pure UI substrate). Left unstated, this invites two divergent readings: Dev A treats Shell as exempt from AD-5's Service-call discipline and has domains include Shell's Blade components directly; Dev B, reading AD-5 literally, builds a pointless `ShellService` facade so that "presentation code may only call a Service" is satisfied to the letter, adding an indirection layer no other implementation expects or reuses consistently.

**Fix:** One line in AD-33 or AD-5 carving Shell out of (or explicitly into) the Service-call discipline.

### F9 — LOW: "Infrastructure" is used in two different senses across the doc

**Where:** Deployment & operational envelope (line 384): *"Bazaar has no infrastructure of its own..."* vs. AD-33 Consequence (line 237): *"Node/npm/Tailwind are now legitimate, real infrastructure in this repo (dev-tooling only)."*

Not a logical contradiction on a careful read (one is about client-runtime infra, the other about the package's own dev-tooling) — but the reused word is a skim-reader trap, and this spine will be skimmed by future story authors under deadline pressure.

**Fix:** Reword one of the two occurrences (e.g., "dev-time tooling" vs. "deployment infrastructure") to remove the collision.

### F10 — LOW: No single designated owner of Filament asset registration

**Where:** AD-33 Rule (line 235).

Nothing states that Shell's ServiceProvider is the *only* place `FilamentAsset::register()` may be called for CSS/JS. If a second domain later registers its own convenience CSS snippet the same way, asset-key collisions or unpredictable load order become possible, with no AD to point to for "that call belongs to Shell alone."

**Fix:** One sentence reserving `FilamentAsset::register()` calls to Shell's own ServiceProvider; other domains contribute Blade/CSS through Shell's kit, not by registering their own Filament assets.

---

## What's correctly left deferred (not holes)

- PostgreSQL CI matrix, queue driver choice, Sanctum token rotation, Member Login v2 flow, FR-29 SEO defaults, browser/visual test tool choice for Shell, and `package.json`/build-script contents are genuinely implementation-level and correctly deferred to `bmad-tea`/story work — no change recommended.
- The Deferred section's own flag on "client-side visual token override" (F6 above) is the right instinct; the gap is that its urgency is understated given Shell ships in the very next story, not a hypothetical future one.
