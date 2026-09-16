<?php

namespace Tigaphonic\Bazaar\Shell\Livewire;

use Livewire\Component;

/**
 * Net-new component (DESIGN.md §Leaf Controls → Tabs, AD-33). Content-only
 * switch, no reload; deep-linkable pages (e.g. Order detail's Timeline /
 * Shipments / CS History) pass their own `activeTab` in to land on a
 * specific tab from a bell-notification link.
 */
class Tabs extends Component
{
    /**
     * @var array<string, string>
     */
    public array $tabs = [];

    public string $activeTab = '';

    /**
     * @param  array<string, string>  $tabs
     */
    public function mount(array $tabs = [], ?string $activeTab = null): void
    {
        $this->tabs = $tabs;
        $this->activeTab = $activeTab ?? (array_key_first($tabs) ?? '');
    }

    public function select(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('bazaar::shell.livewire.tabs');
    }
}
