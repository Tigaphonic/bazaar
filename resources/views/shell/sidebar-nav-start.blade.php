{{--
    Injected at Filament\View\PanelsRenderHook::SIDEBAR_NAV_START (registered
    by BazaarServiceProvider::registerShellRenderHooks()) -- the only piece of
    Shell's own sidebar content actually placed into the live panel's stock
    (Filament-restyled) sidebar for this story. EXPERIENCE.md §Global chrome
    lists "Dashboard (Home)" as global chrome reached from the sidebar brand
    mark; the Dashboard page itself ships in a later epic, this is Shell's
    own translated placeholder entry into that same slot.
--}}
@php
    $dashboardUrl = \Filament\Facades\Filament::getUrl() ?? url('/');
    $isDashboardActive = rtrim(url()->current(), '/') === rtrim($dashboardUrl, '/');
    $hasNativeDashboard = collect(filament()->getNavigation())
        ->flatMap(fn ($group) => $group->getItems())
        ->contains(fn ($item) => rtrim((string) $item->getUrl(), '/') === rtrim($dashboardUrl, '/'));
@endphp
@unless ($hasNativeDashboard)
<ul class="fi-sidebar-nav-groups bazaar-sidebar-dashboard">
    <li class="fi-sidebar-group">
        <ul class="fi-sidebar-group-items">
            <li @class(['fi-sidebar-item', 'fi-active fi-sidebar-item-active' => $isDashboardActive])>
                <a
                    href="{{ $dashboardUrl }}"
                    class="nav-item fi-sidebar-item-btn"
                    data-i18n="nav_dashboard"
                >
                    <span class="fi-sidebar-item-label">{{ __('bazaar::shell.nav_dashboard') }}</span>
                </a>
            </li>
        </ul>
    </li>
</ul>
@endunless
