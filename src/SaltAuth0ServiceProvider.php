<?php

namespace Salt\Auth0;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SaltAuth0ServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('auth0')
            ->hasConfigFile("salt-auth0")
            ->hasViews()
            ->hasMigration('create_users_table')
            ->hasTranslations();
    }
}
