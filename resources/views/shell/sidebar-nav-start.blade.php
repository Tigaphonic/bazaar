{{--
    Injected at Filament\View\PanelsRenderHook::SIDEBAR_NAV_START (registered
    by BazaarServiceProvider::registerShellRenderHooks()) -- the only piece of
    Shell's own sidebar content actually placed into the live panel's stock
    (Filament-restyled) sidebar for this story. EXPERIENCE.md §Global chrome
    lists "Dashboard (Home)" as global chrome reached from the sidebar brand
    mark; the Dashboard page itself ships in a later epic, this is Shell's
    own translated placeholder entry into that same slot.
--}}
<ul class="fi-sidebar-nav-groups bazaar-sidebar-dashboard">
    <li class="fi-sidebar-group">
        <ul class="fi-sidebar-group-items">
            <li class="fi-sidebar-item">
                <a
                    href="{{ \Filament\Facades\Filament::getUrl() ?? url('/') }}"
                    class="nav-item fi-sidebar-item-label"
                    data-i18n="nav_dashboard"
                >
                    {{ __('bazaar::shell.nav_dashboard') }}
                </a>
            </li>
        </ul>
    </li>
</ul>
