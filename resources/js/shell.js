/**
 * Bazaar Shell runtime (Story 1.2, AD-33). Vanilla JS -- no build-time
 * framework dependency beyond what Filament/Alpine already ships. Compiled
 * once at Bazaar's own dev/release time and committed to
 * resources/dist/shell.js; never rebuilt at install time or on a client
 * request (AD-33).
 *
 * Dark-mode toggling deliberately does NOT reimplement anything: it just
 * dispatches the same `theme-changed` window event Filament core's own
 * resources/js/dark-mode.js already listens for (always loaded, ships with
 * filament/filament) -- that script owns the actual `dark` class + persisted
 * localStorage state. Shell only needed to relocate the *control* itself
 * into the topbar user menu (EXPERIENCE.md), not the mechanism.
 *
 * Locale switching has no first-party Filament equivalent, so this file owns
 * it: a small client-side dictionary mirroring resources/lang/{en,id}/shell.php
 * applies instantly to any `[data-i18n]`-tagged element Shell itself renders,
 * with no page navigation. Per-Staff *server-side* persistence (the
 * authoritative store) is Shell\Support\UserPreferences +
 * Shell\Http\Middleware\ApplyUserLocale, exercised on the next authenticated
 * request; this client-side layer covers only the same-request "instant, no
 * reload" half of AC3 the browser itself can observe.
 */
(function () {
    'use strict';

    var LOCALE_STORAGE_KEY = 'bazaar-locale';

    // Kept in sync by hand with resources/lang/{en,id}/shell.php's
    // data-i18n-eligible keys. Only Shell-owned chrome elements carry
    // data-i18n -- content owned by other domains' Resources/Pages is out of
    // this file's scope (their own bilingual work ships with each domain).
    var I18N = {
        en: {
            nav_dashboard: 'Dashboard',
        },
        id: {
            nav_dashboard: 'Dasbor',
        },
    };

    function applyLocale(locale) {
        var dict = I18N[locale] || I18N.en;

        document.documentElement.setAttribute('lang', locale);

        document.querySelectorAll('[data-i18n]').forEach(function (el) {
            var key = el.getAttribute('data-i18n');

            if (dict[key]) {
                el.textContent = dict[key];
            }
        });
    }

    function setLocale(locale) {
        try {
            window.localStorage.setItem(LOCALE_STORAGE_KEY, locale);
        } catch (error) {
            // Private-mode/blocked storage: locale still applies for this
            // page view, it just won't survive a reload client-side.
        }

        applyLocale(locale);
    }

    function markSidebar() {
        var sidebar = document.getElementById('fi-main-sidebar');

        if (sidebar) {
            sidebar.setAttribute('data-shell-sidebar', '');
        }
    }

    function restorePersistedLocale() {
        var stored = null;

        try {
            stored = window.localStorage.getItem(LOCALE_STORAGE_KEY);
        } catch (error) {
            // Ignore -- falls through to the server-rendered locale.
        }

        if (stored && I18N[stored]) {
            applyLocale(stored);
        }
    }

    function init() {
        markSidebar();
        restorePersistedLocale();
    }

    window.bazaarShell = {
        setLocale: setLocale,
        applyLocale: applyLocale,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Filament pages navigate via Livewire's wire:navigate (no full reload,
    // no `framenavigated`) -- re-run the DOM-touching parts of init() so the
    // sidebar tag and any persisted locale still apply after an in-panel
    // navigation.
    document.addEventListener('livewire:navigated', init);
})();
