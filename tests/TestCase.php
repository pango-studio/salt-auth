<?php

namespace Salt\Auth\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Salt\Auth\AuthServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Salt\\Auth\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            AuthServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');


        // Auth0 API testing variables
        config()->set('core.auth0.api.audience', "https://alt-testing.eu.auth0.com/api/v2/");
        config()->set('core.auth0.api.domain', 'alt-testing-eu-auth0.com');

        $usersTable = include  __DIR__ . '/../database/migrations/create_users_table.php.stub';
        $usersTable->up();
    }
}
