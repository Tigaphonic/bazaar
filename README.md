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

This also publishes `spatie/laravel-permission`'s own migration (`roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` — needed by the Roles & Permissions screen) into your `database/migrations/`. It is published, not run automatically, so finish with:

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

## Usage

### `bazaar:install`

Connects Bazaar's Resources and Pages to your project's default Filament panel (or the one named in `config('bazaar.panel')`) — it never registers a new panel.

```bash
php artisan bazaar:install
```

### `bazaar:status`

Reports whether the scheduler and queue worker are actually running, via the heartbeat mechanism Bazaar's service provider registers (a scheduled task and a queued job, each writing a timestamp to cache). Safe to run any time, not just right after install — useful for catching config drift later.

```bash
php artisan bazaar:status
```

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
