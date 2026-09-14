<?php

// Story 1.2 RED-PHASE scaffold: Tigaphonic\Bazaar\Shell\Livewire\DataTable does not
// exist yet. DESIGN.md §Components → Data Table: skeleton-loading + pagination,
// reusable across every later epic's list screens (epic-1-context.md line 53).

use Livewire\Livewire;
use Tigaphonic\Bazaar\Shell\Livewire\DataTable;

it('shows a skeleton-loading state while rows are not yet available', function () {
    Livewire::test(DataTable::class, ['rows' => null, 'isLoading' => true])
        ->assertSee('skeleton', escape: false);
})->skip('Story 1.2 not implemented — Shell\Livewire\DataTable does not exist yet');

it('paginates rows once loaded', function () {
    $rows = collect(range(1, 45))->map(fn (int $i) => ['id' => $i, 'label' => "Row {$i}"]);

    Livewire::test(DataTable::class, ['rows' => $rows, 'isLoading' => false, 'perPage' => 20])
        ->assertViewHas('paginator')
        ->assertSee('Row 1')
        ->assertDontSee('Row 21');
})->skip('Story 1.2 not implemented — Shell\Livewire\DataTable does not exist yet');
