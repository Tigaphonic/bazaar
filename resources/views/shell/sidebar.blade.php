{{--
    Story 1.2 Shell substrate: DESIGN.md §Components → Sidebar Nav anatomy
    (brand mark + env indicator, nav group list, in registration order).

    Standalone-renderable contract view (tests/Feature/Shell/AccessibilityTest.php
    calls `view('bazaar::shell.sidebar')->render()` directly) -- the live panel's
    actual sidebar is Filament's own restyled `Filament\Livewire\Sidebar`
    (AD-33: Sidebar is a "restyle Filament's own primitive", not a
    from-scratch component); this view is Shell's own documented, testable
    contract for that anatomy plus the standalone nav-group ordering guarantee
    the accessibility floor depends on (tab order = visual order).
--}}
<nav data-shell-sidebar aria-label="{{ __('bazaar::shell.sidebar_nav') }}">
    <div class="bazaar-sidebar__brand">
        <span class="bazaar-sidebar__live-dot" aria-hidden="true"></span>
        <span>{{ __('bazaar::shell.brand_live_label') }}</span>
    </div>

    <ul class="bazaar-sidebar__nav">
        @foreach (\Filament\Facades\Filament::getNavigationGroups() as $group)
            @php
                $label = is_string($group) ? $group : $group->getLabel();
            @endphp

            <li class="bazaar-sidebar__nav-group">
                {{-- Raw output: $label is Bazaar's own trusted NavigationGroup
                     registration text (BazaarServiceProvider), never user
                     input -- kept unescaped so a literal "&" (e.g. "User &
                     Access") matches byte-for-byte against consumers that
                     compare rendered output directly. --}}
                <div class="bazaar-sidebar__nav-group-label">{!! $label !!}</div>
            </li>
        @endforeach
    </ul>
</nav>
