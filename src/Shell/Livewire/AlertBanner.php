<?php

namespace Tigaphonic\Bazaar\Shell\Livewire;

use Illuminate\Support\Facades\Validator;
use Livewire\Component;

/**
 * Net-new component (DESIGN.md §Components → Alert Banner, AD-33 — verified
 * Filament core ships no alert/banner component at all). Exactly three
 * semantic variants: danger/warn/info — no fourth. This component renders no
 * dismiss control of its own: DESIGN.md's policy is that `danger`/`warn` may
 * be dismissed after reading while `info` scope-notices never are (their
 * underlying constraint doesn't change, EXPERIENCE.md §Component Patterns) —
 * that dismiss affordance/behavior, if a consuming page wants one, is left
 * entirely to the page hosting this banner (e.g. by conditionally rendering
 * it at all), not implemented here.
 */
class AlertBanner extends Component
{
    public string $variant = 'info';

    public string $message = '';

    public function mount(string $variant, string $message): void
    {
        Validator::make(
            ['variant' => $variant],
            ['variant' => 'required|in:danger,warn,info'],
        )->validate();

        $this->variant = $variant;
        $this->message = $message;
    }

    public function render()
    {
        return view('bazaar::shell.livewire.alert-banner');
    }
}
