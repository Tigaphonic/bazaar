# Review: Stack Table Version/Reality Check — Bazaar Architecture Spine

**Reviewed artifact:** `ARCHITECTURE-SPINE.md` (Bazaar, architecture-Tigaphonic, 2026-09-12)
**Review lens:** every committed version/technology decision must be web-verified or reality-checked, not asserted from training data — current versions, continued existence/fit of each named library, and live defaults of any leaned-on scaffold.
**Review date:** 2026-09-12
**Method:** Packagist `repo.packagist.org/p2/*.json` metadata (authoritative, machine-readable require blocks + publish timestamps) plus targeted web search for narrative/changelog context. All findings below are timestamped against what was actually published as of 2026-09-12.

---

## 1. Package-by-package verification

| Package | Spine states | Verified real? | Actual latest / relevant data (as of 2026-09-12) | Verdict |
|---|---|---|---|---|
| `filament/filament` | `^5.7` | Yes | Latest is **v5.8.1** (published 2026-09-08, 4 days before spine date). v5.7.8 was the last 5.7.x patch (2026-09-01). v5.8.0 is a minor feature release (deferred schema loading, persistent table grouping, trait-named lifecycle hooks) — not flagged as breaking. `filament/filament` requires `php: ^8.2`. | **Flag — see Finding 1.** Package/major-version choice is sound; the specific `^5.7` floor is already stale and, per composer's caret semantics, will not actually pin to the 5.7 line. |
| `spatie/laravel-permission` | `^8.3` | Yes | Latest **8.3.0** (published 2026-07-03). Require: `php: ^8.3`, `illuminate/{auth,container,contracts,database}: ^12.0\|^13.0`. | **Confirmed accurate.** Correctly requires the raised Laravel floor (^12‖^13) — consistent with the spine's own Deferred-section note. |
| `spatie/laravel-model-states` | `^2.14` | Yes | Latest **2.14.2** (published 2026-07-22). Require: `php: ^8.4`, `illuminate/{contracts,database,support}: ^12.0\|^13.0`. | **Confirmed accurate.** |
| `spatie/laravel-medialibrary` | `^11.23` | Yes | Latest **11.23.7** (published 2026-09-03). Require: `php: ^8.2`, `illuminate/*: ^10.2\|^11.0\|^12.0\|^13.0`. | **Confirmed accurate.** |
| `spatie/laravel-activitylog` | `^5.0` *(spine's own parenthetical: "resolves to 5.0.x until a client app moves to Laravel 13, when 5.1 applies")* | Yes | Version history: **5.0.0** (2026-03-25) → `illuminate/*: ^12.0\|^13.0`. **5.1.0** (2026-08-12) → `illuminate/*: ^13.0` **only**. **5.1.1** (2026-09-08, 4 days before spine date) → `illuminate/*: ^12.0\|^13.0` again (support broadened back to both). | **Flag — see Finding 2.** The spine's own reasoning is already outdated as of its authoring date. With `^5.0` and 5.1.1 now supporting both Laravel 12 and 13, Composer will resolve to **5.1.1 on a Laravel-12 install too**, not stay pinned to 5.0.x as asserted. |
| `spatie/laravel-settings` | `^3.9` | Yes | Latest **3.9.0** (published 2026-05-26). Require: `illuminate/database: ^11.0\|^12.0\|^13.0` (per web search; not independently re-verified via p2 but consistent). | **Confirmed accurate.** |
| `filament/spatie-laravel-settings-plugin` | "latest compatible" | Yes | Latest **v5.8.1** (published 2026-09-08). Require block: `"filament/filament": "self.version"` — i.e. Composer forces this plugin's version to exactly equal whatever `filament/filament` resolves to; it cannot be pinned independently. | **Flag — see Finding 3 (informational).** "Latest compatible" is technically safe given the `self.version` mechanism, but the spine doesn't note that this plugin has *zero independent version identity* — it silently follows wherever Filament's `^5.7` constraint actually resolves (currently 5.8.1, not 5.7.x). |
| `laravel/sanctum` | "latest compatible" | Yes | Latest **v4.3.3** (published 2026-07-21). Require: `illuminate/*: ^11.0\|^12.0\|^13.0`. | **Confirmed accurate.** |
| `maatwebsite/excel` | `^4.0` | Yes | Latest **4.0.2** (published 2026-08-24). v4.0.0 itself only shipped 2026-08-13 — about one month before the spine was written. Require: `php: ^8.3`, `illuminate/support: ^12.0\|^13.0`, `phpoffice/phpspreadsheet: ^5.8`. | **Confirmed accurate and notably well-researched** — v4.0 is a genuinely new major (the package sat on 3.1.x for years); a training-data guess would very plausibly have missed this and defaulted to `^3.1`. This is the one call that most needed a live check, and it holds up. |
| `barryvdh/laravel-dompdf` | `^3.1` | Yes | Latest **v3.1.2** (published 2026-03-17). Require: `php: ^8.1`, `dompdf/dompdf: ^3.0`, `illuminate/support: ^9\|^10\|^11\|^12\|^13.0` (Laravel 13 support only added in 3.1.2; 3.1.0/3.1.1 topped out at ^11/^12). | **Confirmed accurate** — and the pin to `^3.1` (not `^3.0`) matters: only 3.1.2 actually supports the Laravel 13 half of the stated floor. |

## 2. Cross-cutting findings

### Finding 1 — `Filament ^5.7` is already behind the package's actual latest, and composer's `^` semantics won't keep it there *(Medium)*

Filament released **v5.8.0** and **v5.8.1** on 2026-09-07/08 — literally days before this spine's 2026-09-12 date. A composer constraint of `^5.7` means `>=5.7.0 <6.0.0`; it does **not** pin the install to the 5.7 minor line. A fresh `composer require filament/filament:^5.7` today resolves to **5.8.1**, not 5.7.x. If the spine's authors verified against Filament 5.7 specifically (e.g. reading 5.7's docs/changelog to ground AD-2's theme-override assumptions), that verification is already describing a version nobody installing from this spec today will actually get. Two fixes: (a) explicitly note in the Stack table that `^5.7` is a *floor*, not a target, and that 5.8.x is expected/acceptable (recommended, since 5.8.0 is additive not breaking); or (b) if the intent really is the 5.7 feature surface, tighten to `^5.7.0,<5.8.0` and say why.

### Finding 2 — `spatie/laravel-activitylog` version rationale is stale, invalidated within the same week it was likely written *(Medium)*

The spine carries an unusually specific, already-reasoned note: `^5.0 *(resolves to 5.0.x until a client app moves to Laravel 13, when 5.1 applies)*`. That was true for the ~4-week window between 5.1.0 (2026-08-12, Laravel-13-only) and 5.1.1 (2026-09-08, broadened back to Laravel 12 **and** 13). Because 5.1.1 now satisfies Laravel 12 too, and it's newer than 5.0.0, Composer resolves `^5.0` to **5.1.1 regardless of which Laravel version the client is on** — the documented floor/version-pinning behavior no longer holds as of 4 days before the spine's own date. This is exactly the kind of narrative-with-a-timestamp that needed (and evidently didn't get, or got just before it went stale) a same-day live check. Action: re-verify at time of implementation, since this can flip again on any future spatie release; don't rely on the parenthetical as written.

### Finding 3 — `filament/spatie-laravel-settings-plugin`'s version is not independent of Filament core *(Low / informational)*

This plugin's `composer.json` requires `"filament/filament": "self.version"` — a Composer mechanism (used across Filament's split packages) that forces the plugin's installed version to exactly match whatever `filament/filament` resolves to. The spine's "latest compatible" note is functionally correct but doesn't surface this coupling. Combined with Finding 1, it means this plugin will also silently land on 5.8.1, not a version matched to a "5.7" mental model. No action needed beyond noting the coupling explicitly so a future reader doesn't try to pin the plugin independently of core Filament.

### Finding 4 — Laravel 13 and PHP 8.4 both check out as real, current, and appropriately floored *(No issue — confirmed)*

Laravel 13 was released 2026-03-17 (confirmed via web search, zero breaking changes from Laravel 12, PHP 8.3 minimum). PHP 8.4 (released Nov 2024) remains in active support through end of 2026 and is a reasonable floor even though PHP 8.5 now exists. The spine's `illuminate/contracts ^12.0||^13.0` / PHP `^8.4` floor is internally consistent with every package verified above — nothing here was hallucinated.

## 3. Composer.json (existing scaffold) accuracy check

Compared `ARCHITECTURE-SPINE.md`'s Stack table against `/Users/mastin/devphp-valet/package-bazaar/composer.json`:

| Scaffold value (composer.json) | Spine's representation | Match? |
|---|---|---|
| `"php": "^8.4"` | `PHP \| ^8.4` | Match |
| `"illuminate/contracts": "^11.0||^12.0||^13.0"` | `^12.0 \|\| ^13.0` with explicit note *"floor raised from the skeleton's ^11.0 — see AD-note under Deferred"* | Match — accurately describes the narrowing, and the Deferred section correctly attributes this to `spatie/laravel-permission`'s requirements (confirmed above: permission 8.3.0 does require `^12.0|^13.0`, dropping ^11 support). Note: `maatwebsite/excel` 4.0.x *also* requires `^12.0|^13.0` (drops ^11), so the floor-raise is doubly justified, though the Deferred section only names `laravel-permission` as the reason — a minor incompleteness, not an error. |
| `"spatie/laravel-package-tools": "^1.16"` | `^1.16 *(existing skeleton)*` | Match |
| `require-dev`: pint ^1.14, larastan ^3.0, testbench ^9‖^10‖^11, pest ^4.0 + arch/laravel plugins ^4.0, collision ^8.8, phpstan extension-installer ^1.4, phpstan-deprecation-rules ^2.0, phpstan-phpunit ^2.0 | `Pest / Testbench / Pint / Larastan \| existing skeleton versions, unchanged` | Match (summarized, not itemized, but accurately says "unchanged" — not independently re-verified for Laravel-13 test-tooling compatibility since the spine explicitly defers CI-matrix work to `bmad-tea`). |

No discrepancies found between the scaffold's actual `composer.json` and the spine's description of it.

## 4. Overall verdict

Every package named in the Stack table is **real and exists at (or compatible with) the stated version** — none is hallucinated, and the composer.json representation is accurate. The riskiest-looking call, `maatwebsite/excel ^4.0` (a new major that a training-data guess would likely have missed), checks out and was evidently genuinely researched. However, two version claims were reality-checked against a moving target that **already moved before the spine's own date**: Filament shipped 5.8.0/5.8.1 (2026-09-07/08) making the `^5.7` floor stale by the time it was written (composer will resolve to 5.8.1, not 5.7.x, contradicting an implied "we're on 5.7" framing), and `spatie/laravel-activitylog`'s documented 5.0-vs-5.1 resolution logic was invalidated by 5.1.1 (2026-09-08) just days before the spine's date. Neither is catastrophic (no breaking changes involved), but both are exactly the class of "confirmed true a few weeks ago, false now" claim this review lens exists to catch — re-verify both at implementation time rather than trusting the spine's current wording.
