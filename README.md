# This is my package bazaar

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tigaphonic/bazaar.svg?style=flat-square)](https://packagist.org/packages/tigaphonic/bazaar)
[![GitHub Tests Action Status](https://github.com/spatie/package-bazaar-laravel/actions/workflows/run-tests.yml/badge.svg)](https://github.com/tigaphonic/bazaar/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://github.com/spatie/package-bazaar-laravel/actions/workflows/fix-php-code-style-issues.yml/badge.svg)](https://github.com/tigaphonic/bazaar/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/tigaphonic/bazaar.svg?style=flat-square)](https://packagist.org/packages/tigaphonic/bazaar)

Bazaar is a Composer package that installs a full Filament admin backend for an eCommerce Brand Store — catalog, order, finance, content, and more — into your existing Laravel + Filament project. It connects to your project's own panel; it never creates a second one.

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/bazaar.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/bazaar)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require tigaphonic/bazaar
```

Then connect it to your project's existing Filament panel:

```bash
php artisan bazaar:install
```

This also publishes `spatie/laravel-permission`'s own migration (`roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` — needed by the Roles & Permissions screen), `spatie/laravel-settings`' `settings` table migration (needed by Global Settings), and `spatie/laravel-medialibrary`'s `media` table migration (needed by the shared image pipeline) into your `database/migrations/`. They are published, not run automatically, so finish with:

```bash
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="bazaar-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="bazaar-views"
```

### Manual install step: `HasRoles` trait

The Users screen's roles checklist (and the Roles screen it manages) is built on `spatie/laravel-permission`. Beyond running `bazaar:install` and `migrate`, your app's own `User` model must use that package's `HasRoles` trait yourself — Bazaar never injects it into your model automatically:

```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    // Add HasRoles alongside your model's existing traits (e.g. HasFactory,
    // Notifiable) -- don't remove them.
    use HasFactory, Notifiable, HasRoles;
}
```

Without this, editing a user 500s when the form tries to hydrate/save its roles.

### First Staff user: grant access

A fresh install has no Roles, and Global Settings is gated by the `manage-settings` permission, so your first Staff user cannot open it yet. After creating that user in your app, give them the all-permission `Admin` Role:

```bash
php artisan bazaar:grant-admin you@example.com
```

The command creates the `Admin` Role if needed, syncs every Bazaar permission onto it, and assigns it to the user. It is safe to re-run, and re-running after an upgrade picks up newly shipped permissions. Everyone else gets access through Roles in the admin panel.

## Usage

### `bazaar:install`

Connects Bazaar's Resources and Pages to your project's default Filament panel (or the one named in `config('bazaar.panel')`) — it never registers a new panel.

```bash
php artisan bazaar:install
```

### `bazaar:grant-admin {email}`

Gives an existing user the all-permission `Admin` Role (see [First Staff user](#first-staff-user-grant-access)). Fails with an error if no user has that email.

```bash
php artisan bazaar:grant-admin you@example.com
```

### `bazaar:status`

Reports whether the scheduler and queue worker are actually running, via the heartbeat mechanism Bazaar's service provider registers (a scheduled task and a queued job, each writing a timestamp to cache). Safe to run any time, not just right after install — useful for catching config drift later.

```bash
php artisan bazaar:status
```

## Service Layer Integration

If your portal is a Laravel monolith (Blade + Livewire), call Bazaar's **Service Layer** directly from your own code. No HTTP request, no API token, no extra setup: every Service is resolved from Laravel's container, in the same process as your app.

The Service is the only public entry point of each domain. Call Services from your controllers, Livewire components, Blade views, Artisan commands, and queued jobs. Do not import a domain's `Models\*` or `Actions\*` from your own code, and do not write to Bazaar's tables directly; Services own the business rules, authorization, and audit trail.

### Injection (Livewire, controllers, jobs)

```php
use Livewire\Component;
use Tigaphonic\Bazaar\Settings\Services\SeoResolverService;

class ProductPage extends Component
{
    public ?string $ogImage = null;

    // Livewire resolves method arguments from the container.
    public function mount(SeoResolverService $seo, array $product): void
    {
        // Pass a plain array snapshot of your entity, never a Model.
        $this->ogImage = $seo->resolveOgImage(['og_image' => $product['og_image'] ?? null]);
    }
}
```

In a controller, job, or any class the container builds, use constructor injection: `public function __construct(private SeoResolverService $seo) {}`.

### Container resolution (Blade, helpers, anywhere)

```php
use Tigaphonic\Bazaar\Settings\Services\SettingsService;
use Tigaphonic\Bazaar\User\Services\AuditTrailService;

$settings = app(SettingsService::class)->get();   // BazaarSettings
$recent = app(AuditTrailService::class)->list();  // Collection of audit entries
```

### Common scenarios

Availability-check and checkout use the same pattern: resolve the domain's Service from the container and call its methods with plain data. The Catalog and Order Services ship in later releases; when they do, this section lists their calls. Until then, the Services above are the supported entry points.

Rules that apply to every Service:

- **Plain data in, plain data out.** Pass arrays or scalars, never another domain's Model.
- **Authorization lives in the Service.** For example, `SettingsService` requires the `manage-settings` permission when a Staff user is signed in, and throws `AuthorizationException` otherwise.
- **Cross-domain calls go through Services only.** Bazaar follows the same rule internally, so your host code and Bazaar's own domains use the same entry points.
- **Events are passive.** Bazaar dispatches Laravel Events on domain changes; listen to them in your app if you need to react. It never ships a broadcast driver.

Building a headless frontend (Next.js, Vue) instead? See [API Layer (Headless)](#api-layer-headless) below.

## API Layer (Headless)

An optional, thin HTTP layer over the same Services, for a separate frontend (Next.js, Vue). It is **off by default**: until you opt in, Bazaar registers no API route in your app.

### Enable it

```php
// config/bazaar.php
'api' => [
    'enabled' => true,   // bazaar.api.enabled
],
```

The flag is read at boot. If you cache routes, run `php artisan route:cache` again after changing it.

Payment and Shipping webhook ingress (Midtrans, RajaOngkir) is never controlled by this flag. Those routes are always registered, so a pure Service Layer install still receives vendor callbacks.

### Authenticate with a per-service Sanctum token

Requests authenticate with a Laravel Sanctum token that identifies your **portal as a service**, not an individual Customer. Add `HasApiTokens` to the User model Bazaar attaches to. In Laravel 11, you must first publish Sanctum and the API scaffolding by running:

```bash
php artisan install:api
```

Then run your app's migrations (Sanctum's `personal_access_tokens` table), and issue one token for the portal:

```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
}

$token = $user->createToken('portal-service')->plainTextToken;
```

Store the token in your portal's server-side environment. Never ship it to a browser.

Bazaar's Services authorize the signed-in user, so the token's owner needs the `manage-settings` permission to read store settings. Without it the API answers `403`.

### Call the API

```bash
curl -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" \
  https://your-app.test/bazaar/api/v1/store
```

Responses are Laravel API Resources (`{"data": {...}}`); errors use Laravel's default `message` / `errors` envelope. Routes live under `/bazaar/api/v1`. Later domains add their own endpoints there, each delegating to the same Service you would call in-process.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [nyobee](https://github.com/spatie)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
