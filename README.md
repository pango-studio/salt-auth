[![Latest Version on Packagist](https://img.shields.io/packagist/v/salt/auth0.svg?style=flat-square)](https://packagist.org/packages/salt/auth0)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/salt/auth/run-tests?label=tests)](https://github.com/pango-studio/salt-auth0/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/salt/auth/Check%20&%20fix%20styling?label=code%20style)](https://github.com/pango-studio/salt-auth0/auth/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/salt/auth.svg?style=flat-square)](https://packagist.org/packages/salt/auth0)

This package adds some helper classes for integrating Laravel applications with Auth0

## Installation

You can install the package via composer:

```bash
composer require salt/auth0
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="auth0-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="auth0-config"
```

This is the contents of the published config file:

```php
return [
    'app' => array(
        'client_id' => env('AUTH0_CLIENT_ID', ''),
        'client_secret' => env('AUTH0_CLIENT_SECRET', ''),
        'db_connection' => env('AUTH0_DB_CONNECTION', '')
    ),
    'api' => array(
        'audience' => env('API_MACHINE_AUDIENCE', ''),
        'client_id' => env('AUTH0_MACHINE_CLIENT_ID', ''),
        'client_secret' => env('AUTH0_MACHINE_CLIENT_SECRET', ''),
        'domain' => env('AUTH0_MACHINE_DOMAIN')
    ),
    'url' => env('APP_URL', '')
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="auth0-views"
```

## Documentation

[View the documentation for this package here](https://salt-auth0-package.netlify.app/)

## Testing

```bash
composer test
```

## Releasing a new version

To release a new version, first create a tag on the `main` branch with the new version number. E.g "1.0.1":

```
git tag -a 1.0.1 -m "Release version 1.0.1"
```

Then push that tag up to GitHub:

```
git push origin 1.0.1
```

A new version will automatically be created on packagist which will then be available for installation.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

-   [Salt](https://github.com/pango-studio)
-   [All Contributors](../../contributors)
