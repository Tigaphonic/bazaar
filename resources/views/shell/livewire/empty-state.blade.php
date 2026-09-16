<div class="empty-state">
    @if ($icon)
        <span class="empty-state__icon" aria-hidden="true">
            <x-dynamic-component :component="$icon" class="w-12 h-12 text-gray-400" />
        </span>
    @else
        <span class="empty-state__icon" aria-hidden="true"></span>
    @endif

    <p class="empty-state__headline">{{ $headline }}</p>

    @if ($caption)
        <p class="empty-state__caption">{{ $caption }}</p>
    @endif

    @if ($actionLabel)
        <button type="button" class="btn btn-primary" wire:click="triggerAction">
            {{ $actionLabel }}
        </button>
    @endif
</div>
