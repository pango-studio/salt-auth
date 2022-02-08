# Authentication

This package assumes you will be using (Auth0)[https://auth0.com/] as your authentication provider. It provides a bunch of methods for interacting with Auth0 APIs as well as a custom user repository which will automatically create and update user models when you login via Auth0.

If you are using another auth provider, you can ignore this section of the docs

## Installing Auth0 and setting up config variables

Install the `auth0/login package`:

```bash
composer require auth0/login
```

Publish the Auth0 config variables:

```bash
php artisan vendor:publish

# Look through the list for Auth0\Login\LoginServiceProvider and type in the corresponding number
Which provider or tag's files would you like to publish?:
1
```

You will need values for the following variables in your `.env` file:

```env
AUTH0_CLIENT_ID
AUTH0_CLIENT_SECRET
AUTH0_DB_CONNECTION

API_MACHINE_AUDIENCE
AUTH0_MACHINE_CLIENT_ID
AUTH0_MACHINE_CLIENT_SECRET
AUTH0_MACHINE_DOMAIN
```

These variables are read by `config/laravel-auth0.php` as well as `config/core.php`. The values can be retrieved from the Auth0 applications on the management dashboard.

In `config/auth.php`, change the driver for the users authentication provider to be 'auth0':

```php
// config/auth.php
<?php
    ...
    'providers' => [
        'users' => [
            'driver' => 'auth0',
            'model' => App\Models\User::class,
        ],
    ],

```

## Binding the custom user repository

You will need to bind a class that provides the app's User model each time a user is logged in or a JWT is decoded. This package provides a class, `CustomUserRepository` which serves this purpose. Add the following to `register()` method in `AppServiceProvider`:

```php
// app/Providers/AppServiceProvider.php


<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            \Auth0\Login\Contract\Auth0UserRepository::class,
            \Salt\Core\Repositories\CustomUserRepository::class
        );
    }
}

```

## Setting up login routes

You need a callback route and a controller method to handle the authentication data from Auth0's server. You will also need /login and /logout routes to handle logging in and out of the app.

Run the following command:

```bash
php artisan make:controller Auth/Auth0IndexController
```

And populate the controller file with this:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class Auth0IndexController extends Controller
{
    public function login()
    {
        if (Auth::check()) {
            return redirect()->intended('/');
        }

        return App::make('auth0')->login(
            null,
            null,
            ['scope' => 'openid name email email_verified'],
            'code'
        );
    }

    public function logout()
    {
        Auth::logout();

        $logoutUrl = sprintf(
            'https://%s/v2/logout?client_id=%s&returnTo=%s',
            config('laravel-auth0.domain'),
            config('laravel-auth0.client_id'),
            config('app.url')
        );

        return Redirect::intended($logoutUrl);
    }
}

```

Add the following routes in `routes/web.php`:

```php

<?php

use Auth0\Login\Auth0Controller;
use App\Http\Controllers\Auth\Auth0IndexController;

Route::get('/auth0/callback', [Auth0Controller::class, 'callback'])->name('auth0-callback');
Route::get('/login', [Auth0IndexController::class, 'login'])->name('login');
Route::get('/logout', [Auth0IndexController::class, 'logout'])->name('logout');
```

## Changes to the User model

This package comes with a `User` model which inherits the traits provided by the package and sets up a lot of the necessary boilerplate stuff. To save you from having to re-add all of this yourself on the `User` model, you can change it be the following:

```php
<?php

namespace App\Models;

use Salt\Core\Models\User as CoreUser;

class User extends CoreUser
{

}

```

## User database migration

The easiest way to get the right database fields is to delete the `create_users_table` migration that comes with a fresh laravel installation and then run:

```bash
php artisan vendor:publish --tag=core-migrations
```

You will then have a new `create_users_table` migration with the fields expected by this package. If that is not possible, you will need to add a new migration which adds a nullable `sub` string column to your users table
