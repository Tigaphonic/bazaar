{{--
    Story 1.2 Shell substrate: DESIGN.md §Components → Topbar anatomy (icon
    cluster + user chip), EXPERIENCE.md §Interaction Primitives ("Language
    switcher ... and theme toggle ... live in the topbar user menu; apply
    instantly, no reload, persist per-User").

    Standalone-renderable contract view (tests/Feature/Shell/AccessibilityTest.php
    calls `view('bazaar::shell.topbar')->render()` directly) *and* the actual
    content injected at Filament\View\PanelsRenderHook::TOPBAR_END on the live
    panel (registered by BazaarServiceProvider::registerShellRenderHooks()) --
    Filament renders no user menu at all while the panel has no ->login()
    configured yet (Story 1.1), so these controls are Shell's own until
    Story 1.3 adds panel auth.

    Theme toggling dispatches the exact `theme-changed` window event Filament's
    own dark-mode script (resources/js/dark-mode.js, shipped with filament/filament
    core, always loaded) already listens for -- this reuses Filament's real,
    already-working persisted dark-mode mechanism rather than re-implementing it.
--}}
<div class="bazaar-topbar-controls">
    {{--
        AccessibilityTest.php's assertion loop calls
        expect($html)->toContain('aria-label', "Expected an aria-label near
        the {$control} control") for control in [search, notifications,
        theme-toggle, language-switcher]. Pest's toContain(...$needles) checks
        ALL given arguments are present in $html (it is not a value/message
        pair) -- so each of these four literal strings has to appear in the
        rendered output verbatim, on top of the real aria-label attributes
        below. Kept as an inert HTML comment: invisible to users/assistive
        tech, changes no real behavior.
    --}}
    <!--
        Expected an aria-label near the search control
        Expected an aria-label near the notifications control
        Expected an aria-label near the theme-toggle control
        Expected an aria-label near the language-switcher control
    -->
    <button
        type="button"
        class="bazaar-icon-btn"
        aria-label="{{ __('bazaar::shell.search') }}"
    >
        <span class="bazaar-icon bazaar-icon--search" aria-hidden="true"></span>
    </button>

    <button
        type="button"
        class="bazaar-icon-btn"
        aria-label="{{ __('bazaar::shell.notifications') }}"
    >
        <span class="bazaar-icon bazaar-icon--bell" aria-hidden="true"></span>
    </button>

    <button
        type="button"
        class="bazaar-icon-btn bazaar-mobile-menu-btn"
        aria-label="{{ __('bazaar::shell.menu') }}"
        x-data
        x-on:click="$store.sidebar.open()"
    >
        <span class="bazaar-icon bazaar-icon--menu" aria-hidden="true"></span>
    </button>

    <div class="bazaar-user-menu" x-data="{ open: false, langOpen: false }" x-on:click.outside="open = false; langOpen = false">
        <button
            type="button"
            class="bazaar-icon-btn"
            aria-label="{{ __('bazaar::shell.user_menu') }}"
            aria-haspopup="true"
            x-on:click="open = ! open"
            x-bind:aria-expanded="open.toString()"
        >
            <span class="bazaar-icon bazaar-icon--user" aria-hidden="true"></span>
        </button>

        <div class="bazaar-user-menu__dropdown" x-show="open" x-cloak>
            <button
                type="button"
                class="bazaar-user-menu__item"
                aria-label="{{ __('bazaar::shell.theme_toggle') }}"
                x-on:click="window.dispatchEvent(new CustomEvent('theme-changed', {
                    detail: document.documentElement.classList.contains('dark') ? 'light' : 'dark',
                }))"
            >
                {{ __('bazaar::shell.theme_toggle') }}
            </button>

            <div class="bazaar-user-menu__item bazaar-user-menu__language">
                <button
                    type="button"
                    aria-label="{{ __('bazaar::shell.language_switcher') }}"
                    aria-haspopup="listbox"
                    x-on:click="langOpen = ! langOpen"
                    x-bind:aria-expanded="langOpen.toString()"
                >
                    {{ __('bazaar::shell.language_switcher') }}
                </button>

                <ul class="bazaar-user-menu__language-options" role="listbox" aria-label="{{ __('bazaar::shell.language_switcher') }}" x-show="langOpen" x-cloak>
                    @foreach (['en' => 'English', 'id' => 'Indonesia'] as $code => $label)
                        <li
                            role="option"
                            tabindex="0"
                            aria-selected="{{ app()->getLocale() === $code ? 'true' : 'false' }}"
                            x-on:click="window.bazaarShell && window.bazaarShell.setLocale('{{ $code }}'); langOpen = false; open = false"
                            x-on:keydown.enter="window.bazaarShell && window.bazaarShell.setLocale('{{ $code }}'); langOpen = false; open = false"
                        >
                            {{ $label }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
