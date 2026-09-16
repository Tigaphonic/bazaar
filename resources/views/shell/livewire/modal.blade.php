<div>
    @if ($isOpen)
        <div
            class="modal-overlay"
            x-data
            x-trap.inert.noscroll="true"
            x-on:keydown.escape.window="$wire.close()"
        >
            <div
                class="modal"
                role="dialog"
                aria-modal="true"
                @if ($title) aria-labelledby="bazaar-modal-title" @endif
            >
                <div class="modal__header">
                    @if ($title)
                        <h2 id="bazaar-modal-title" class="modal__title">{{ $title }}</h2>
                    @endif

                    <button
                        type="button"
                        class="modal__close"
                        wire:click="close"
                        aria-label="{{ __('bazaar::shell.modal_close') }}"
                    >
                        &times;
                    </button>
                </div>

                <div class="modal__body">
                    {{ $body }}
                </div>
            </div>
        </div>
    @endif
</div>
