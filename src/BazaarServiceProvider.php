<?php

namespace Tigaphonic\Bazaar;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Tigaphonic\Bazaar\Commands\BazaarCommand;

class BazaarServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('bazaar')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_bazaar_table')
            ->hasCommand(BazaarCommand::class);
    }
}
