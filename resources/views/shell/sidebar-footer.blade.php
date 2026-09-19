{{--
    Injected at PanelsRenderHook::SIDEBAR_FOOTER. User footer per DESIGN.md
    §Sidebar Nav: avatar + name + role. Role line is omitted when the user
    model has no Spatie roles() relation or no roles assigned.
--}}
@php
    $user = filament()->auth()->user();
    $name = $user ? (string) filament()->getUserName($user) : '';
    $role = null;
    if ($user && method_exists($user, 'roles')) {
        try {
            $role = $user->roles()->orderBy('id')->value('name');
        } catch (\Throwable) {
            $role = null;
        }
    }
    $words = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $initials = count($words) > 1
        ? mb_substr($words[0], 0, 1).mb_substr($words[1], 0, 1)
        : mb_substr($name, 0, 2);
@endphp
@if ($user)
    <div class="bazaar-sidebar-footer" data-shell-sidebar-footer>
        <span class="bazaar-sidebar-footer__avatar" aria-hidden="true">{{ mb_strtoupper($initials) }}</span>
        <span class="bazaar-sidebar-footer__text">
            <span class="bazaar-sidebar-footer__name">{{ $name }}</span>
            @if ($role)
                <span class="bazaar-sidebar-footer__role">{{ $role }}</span>
            @endif
        </span>
    </div>
@endif
