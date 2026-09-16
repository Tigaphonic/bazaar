<div class="tabs" role="tablist">
    @foreach ($tabs as $key => $label)
        <button
            type="button"
            role="tab"
            wire:click="select('{{ $key }}')"
            aria-selected="{{ $activeTab === $key ? 'true' : 'false' }}"
            class="tabs__tab {{ $activeTab === $key ? 'tabs__tab--active' : '' }}"
        >
            {{ $label }}
        </button>
    @endforeach
</div>
