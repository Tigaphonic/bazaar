<?php

// Story 1.3 RED-PHASE scaffold. AC3: "sistem tidak memiliki role hardcoded bernama
// 'Approval Role'". Regression guard: Bazaar's own install must never seed a Role
// row at all -- Role composition is entirely Staff-driven (AC1), never
// developer-seeded, so there is nothing for a hardcoded "Approval Role" to hide in.

use Spatie\Permission\Models\Role;

it('seeds no Role at all after bazaar:install, let alone one hardcoded as "Approval Role"', function () {
    $this->artisan('bazaar:install');

    expect(Role::count())->toBe(0);
});
