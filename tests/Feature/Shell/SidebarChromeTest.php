<?php

use Tigaphonic\Bazaar\User\Filament\Resources\UserResource;
use Workbench\App\Models\User;

function renderAdminPageFor(User $user): string
{
    return (string) test()->actingAs($user)->get(UserResource::getUrl('index'))->getContent();
}

it('renders the brand block in the sidebar header', function () {
    $this->artisan('bazaar:install');

    $user = User::create(['name' => 'Nina Staff', 'email' => 'nina-brand@example.com', 'password' => bcrypt('password')]);
    $html = renderAdminPageFor($user);

    expect($html)
        ->toContain('data-shell-brand')
        ->toContain('bazaar-brand__badge')
        ->toContain(__('bazaar::shell.brand_admin_portal'))
        ->toContain(__('bazaar::shell.brand_store'));
});

it('renders the user footer with avatar and name, and no role line when the user has no roles', function () {
    $this->artisan('bazaar:install');

    $user = User::create(['name' => 'Nina Staff', 'email' => 'nina-footer@example.com', 'password' => bcrypt('password')]);
    $html = renderAdminPageFor($user);

    expect($html)
        ->toContain('data-shell-sidebar-footer')
        ->toContain('Nina Staff')
        ->not->toContain('bazaar-sidebar-footer__role');
});

it('renders the role line in the footer when the user has a role', function () {
    $this->artisan('bazaar:install');

    $user = User::create(['name' => 'Rina Admin', 'email' => 'rina-footer@example.com', 'password' => bcrypt('password')]);

    if (! method_exists($user, 'roles')) {
        $this->markTestSkipped('Workbench user model has no HasRoles trait.');
    }

    \Spatie\Permission\Models\Role::findOrCreate('Staff', 'web');
    $user->assignRole('Staff');

    expect(renderAdminPageFor($user))->toContain('bazaar-sidebar-footer__role');
});

it('renders the Dashboard placeholder without an active highlight on a non-dashboard page', function () {
    $this->artisan('bazaar:install');

    $user = User::create(['name' => 'Nina Staff', 'email' => 'nina-dash@example.com', 'password' => bcrypt('password')]);
    $response = test()->actingAs($user)->get(UserResource::getUrl('index'));

    $response->assertOk();
    expect((string) $response->getContent())
        ->toContain('bazaar-sidebar-dashboard')
        ->not->toContain('fi-sidebar-item-active');
});
