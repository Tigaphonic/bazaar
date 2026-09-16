<?php

namespace Tigaphonic\Bazaar\Shell\Livewire;

use Livewire\Component;

/**
 * Net-new component (DESIGN.md §Components → Empty State, AD-33). Icon +
 * headline naming what's missing + supporting caption, with a single
 * primary-action button only where the empty state is actually actionable
 * (an `actionLabel` is provided) — never for a merely-filtered-to-nothing
 * result, which is a distinct, shorter message per EXPERIENCE.md.
 */
class EmptyState extends Component
{
    public string $headline = '';

    public ?string $caption = null;

    public ?string $actionLabel = null;

    public function mount(string $headline, ?string $caption = null, ?string $actionLabel = null): void
    {
        $this->headline = $headline;
        $this->caption = $caption;
        $this->actionLabel = $actionLabel;
    }

    public function triggerAction(): void
    {
        $this->dispatch('empty-state-action');
    }

    public function render()
    {
        return view('bazaar::shell.livewire.empty-state');
    }
}
