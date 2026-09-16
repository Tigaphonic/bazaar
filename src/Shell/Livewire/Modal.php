<?php

namespace Tigaphonic\Bazaar\Shell\Livewire;

use Livewire\Component;

/**
 * Net-new component (DESIGN.md/AD-33 — no Filament core equivalent). One
 * modal open at a time; nested modals are never supported — a second action
 * from inside an open modal opens a new view instead, which this component
 * enforces at its own level by refusing a second `open()` call while already
 * open (dispatching `modal-open-refused` rather than silently stacking state).
 */
class Modal extends Component
{
    public bool $isOpen = false;

    public ?string $title = null;

    public ?string $body = null;

    public function mount(?string $title = null, ?string $body = null, bool $isOpen = false): void
    {
        $this->title = $title;
        $this->body = $body;
        $this->isOpen = $isOpen;
    }

    /**
     * @param  array{title?: string, body?: string}  $data
     */
    public function open(array $data = []): void
    {
        if ($this->isOpen) {
            $this->dispatch('modal-open-refused');

            return;
        }

        $this->title = $data['title'] ?? $this->title;
        $this->body = $data['body'] ?? $this->body;
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->title = null;
        $this->body = null;
    }

    public function render()
    {
        return view('bazaar::shell.livewire.modal');
    }
}
