{{--
    Injected at PanelsRenderHook::SIDEBAR_LOGO_AFTER (inside the sidebar
    header). Brand block per DESIGN.md §Sidebar Nav: initials badge + name +
    "Admin Portal" + env row. The native logo container is hidden by shell.css.
--}}
@php
    $brandName = (string) filament()->getBrandName();
    $words = preg_split('/\s+/', trim($brandName), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $initials = count($words) > 1
        ? mb_substr($words[0], 0, 1).mb_substr($words[1], 0, 1)
        : mb_substr($brandName, 0, 2);
@endphp
<div class="bazaar-brand" data-shell-brand>
    <div class="bazaar-brand__mark">
        <span class="bazaar-brand__badge" aria-hidden="true">{{ mb_strtoupper($initials) }}</span>
        <span class="bazaar-brand__text">
            <span class="bazaar-brand__name">{{ $brandName }}</span>
            <span class="bazaar-brand__sub">{{ __('bazaar::shell.brand_admin_portal') }}</span>
        </span>
    </div>
    <div class="bazaar-brand__env">
        <span class="bazaar-brand__env-dot" aria-hidden="true"></span>
        <span class="bazaar-brand__env-label">{{ __('bazaar::shell.brand_live_label') }} &middot; {{ __('bazaar::shell.brand_store') }}</span>
    </div>
</div>
