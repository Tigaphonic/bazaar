<x-filament-widgets::widget>
    <x-filament::section :heading="__('bazaar::settings.status_heading')">
        <ul>
            @foreach ($this->heartbeats() as $name => $heartbeat)
                <li>
                    <strong>{{ __('bazaar::settings.status_'.$name) }}</strong>:
                    {{ __('bazaar::settings.status_state_'.$heartbeat['state']) }}
                    @if ($heartbeat['last_tick'])
                        ({{ $heartbeat['last_tick']->diffForHumans() }})
                    @endif
                </li>
            @endforeach
        </ul>
    </x-filament::section>
</x-filament-widgets::widget>
