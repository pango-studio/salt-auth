<?php

use function Pest\Faker\faker;
use function PHPUnit\Framework\assertEquals;

use Salt\Auth\Models\User;

it('has a name', function () {
    $name = faker()->name;
    $user = User::factory()
        ->create(
            [
                'name' => $name,
            ]
        );
    assertEquals($name, $user->name);
});

it('has an email address', function () {
    $email = faker()->email();
    $user = User::factory()
        ->create(
            [
                'email' => $email,
            ]
        );
    assertEquals($email, $user->email);
});

it('can have a sub', function () {
    $sub = faker()->word;
    $user = User::factory()
        ->create(
            [
                'sub' => $sub,
            ]
        );
    assertEquals($sub, $user->sub);
});
