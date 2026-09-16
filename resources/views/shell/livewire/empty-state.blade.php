<div class="empty-state">
    <span class="empty-state__icon" aria-hidden="true"></span>

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
