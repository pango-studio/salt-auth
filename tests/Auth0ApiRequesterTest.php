<?php

use Carbon\Carbon;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

use function Pest\Faker\faker;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertIsArray;
use function PHPUnit\Framework\assertTrue;
use Salt\Auth\Models\User;
use Salt\Auth\Requesters\Auth0ApiRequester;

it('can fetch user details ', function () {
    $user = User::factory('App\User')->create(['sub' => faker()->word]);

    Http::fake([
        config("core.auth0.api.audience") . "users/" . $user->sub
        => Http::response(
            [
                'user_id' => $user->sub,
                'email' => $user->email,
            ],
            200
        ),
    ]);

    $response = (new Auth0ApiRequester())->fetchAuth0UserById($user->sub);
    assertEquals($user->sub, $response->user_id);
    assertEquals($user->email, $response->email);
});

it('can get user details via an access token', function () {
    $user = User::factory('App\User')->create(['sub' => faker()->word]);
    $access_token = faker()->word;

    Http::fake([
        "https://" . config("core.auth0.api.domain") . "/userinfo"
        => Http::response(
            [
                'user_id' => $user->sub,
                'email' => $user->email,
            ],
            200
        ),
    ]);

    $response = (new Auth0ApiRequester())->getUserInfo($access_token);
    assertEquals($user->sub, $response->user_id);
    assertEquals($user->email, $response->email);
});

it('can fetch user details from Auth0 with a given email', function () {
    $user = User::factory('App\User')->create();

    Http::fake([
        config("core.auth0.api.audience") . "users-by-email?email=" . $user->email
        => Http::response(
            [
                [
                    'user_id' => $user->sub,
                    'email' => $user->email,
                ],
            ],
            200
        ),
    ]);

    $response = (new Auth0ApiRequester())->fetchAuth0UserByEmail($user->email);
    assertEquals($user->sub, $response[0]->user_id);
    assertEquals($user->email, $response[0]->email);
});

it('can search for user details from Auth0 with a given email', function () {
    $user = User::factory('App\User')->create();

    Http::fake([
        config("core.auth0.api.audience") . 'users*'
        => Http::response(
            [
                [
                    'user_id' => $user->sub,
                    'email' => $user->email,
                ],
            ],
            200
        ),
    ]);

    $response = (new Auth0ApiRequester())->searchAuth0UserByEmail($user->email);
    assertIsArray($response);
    assertEquals($user->sub, $response[0]->user_id);
    assertEquals($user->email, $response[0]->email);
});

it('can create a new Auth0 user', function () {
    Queue::fake();
    $user = User::factory('App\User')->create(['sub' => faker()->word]);

    Http::fake([
        config("core.auth0.api.audience") . 'users'
        => Http::response(
            [
                'user_id' => $user->sub,
                'email' => $user->email,
            ],
            200
        ),
    ]);

    Http::fake([
        config("api.audience") . "tickets/password-change"
        => Http::response(
            [
                'ticket' => faker()->word,
            ],
            200
        ),
    ]);

    $response = (new Auth0ApiRequester())->createAuth0User($user->email, $user->name, 'password');
    assertEquals($user->sub, $response->user_id);
    assertEquals($user->email, $response->email);
});

it('can update the details for a user on Auth0', function () {
    $user = User::factory('App\User')->create(['sub' => faker()->word]);

    Http::fake([
        config("core.auth0.api.audience") . "users/" . $user->sub
        => Http::response(
            [
                'user_id' => $user->sub,
                'email' => $user->email,
            ],
            200
        ),
    ]);

    $response = (new Auth0ApiRequester())->updateAuth0User($user->sub, $user->email, $user->name);
    assertEquals($user->sub, $response->user_id);
    assertEquals($user->email, $response->email);
});

it("can send a request to change the user's password", function () {
    $user = User::factory('App\User')->create(
        [
            'sub' => faker()->word,
        ]
    );
    Http::fake([
        config("core.auth0.api.audience") . "users/" . $user->sub
        => Http::response(true, 200),
    ]);

    $response = (new Auth0ApiRequester())->changePassword($user->sub, 'password');
    assertTrue($response);
});

it('can generate a password reset link for the user', function () {
    $user = User::factory('App\User')->create(
        [
            'sub' => faker()->word,
        ]
    );

    $ticket = faker()->word;

    Http::fake([
        config("core.auth0.api.audience") . "tickets/password-change"
        => Http::response(
            $ticket,
            200
        ),
    ]);

    $response = (new Auth0ApiRequester())->generatePasswordResetLink($user->sub);

    assertEquals($ticket, $response);
});

it('can delete an Auth0 user', function () {
    $user = User::factory('App\User')->create(['sub' => faker()->word]);

    Http::fake([
        config("core.auth0.api.audience") . "users/" . $user->sub
        => Http::response(
            [
                'statusCode' => 204,
                'message' => "User deleted",
            ],
            204
        ),
    ]);

    $response = (new Auth0ApiRequester())->deleteAuth0User($user->sub);
    assertEquals("204", $response->statusCode);
});

it('can get the roles for an Auth0 user', function () {
    $user = User::factory('App\User')->create(['sub' => faker()->word]);
    $roles = [
        [
            'id' => faker()->word,
            'name' => 'admin',
            'description' => faker()->sentence(),
        ],
        [
            'id' => faker()->word,
            'name' => 'user',
            'description' => faker()->sentence(),
        ],
    ];

    Http::fake([
        config("core.auth0.api.audience") . "users/" . $user->sub . "/roles"
        => Http::response(
            $roles,
            200
        ),
    ]);

    $response = (new Auth0ApiRequester())->getRolesAuth0User($user->sub);
    assertEquals($roles[0]['id'], $response[0]->id);
});

it('can fetch login logs', function () {
    $user = User::factory('App\User')->create(['sub' => faker()->word]);
    $start = Carbon::now()->subdays(7);
    $end = Carbon::now();

    $full_logs = [
        [
            'date' => faker()->date(),
            'type' => 'fu',
            'user_name' => faker()->email,
            'description' => faker()->sentence,
        ],
        [
            'date' => faker()->date(),
            'type' => 's',
            'user_name' => faker()->email,
            'description' => faker()->sentence,
        ],
    ];

    $user_logs = [
        [
            'date' => faker()->date(),
            'type' => 's',
            'user_name' => $user->email,
            'description' => faker()->sentence,
        ],
    ];

    // Fake full log query
    Http::fake([
        config("core.auth0.api.audience") . "logs?q=*"
        => Http::response(
            $full_logs,
            200
        ),
    ]);

    // Fake user-specific log query
    Http::fake([
        config("core.auth0.api.audience") . "users/{$user->sub}/logs?q=*"
        => Http::response(
            $user_logs,
            200
        ),
    ]);

    $response = (new Auth0ApiRequester())->getLoginLogs($start, $end);
    assertEquals($full_logs[0]['date'], $response[0]->date);
    assertEquals($full_logs[1]['date'], $response[1]->date);

    $response = (new Auth0ApiRequester())->getLoginLogs($start, $end, $user->sub);
    assertEquals($user_logs[0]['date'], $response[0]->date);
    assertEquals($user_logs[0]['user_name'], $user->email);
});

it('can start a passwordless email login flow', function () {
    $user = User::factory('App\User')->create(['sub' => faker()->word]);

    Http::fake([
        "https://" . config("core.auth0.api.domain") . "/passwordless/start"
        => Http::response([
            'id' => $user->sub,
            'email' => $user->email,
        ], 200),
    ]);

    $response = (new Auth0ApiRequester())->startPasswordlessEmailFlow($user->email);

    assertEquals($user->sub, $response->id);
    assertEquals($user->email, $response->email);
});

it('can verify a passwordless email code', function () {
    $user = User::factory('App\User')->create(['sub' => faker()->word]);
    $access_token = faker()->word;

    Http::fake([
        "https://" . config("core.auth0.api.domain") . "/oauth/token"
        => Http::response(
            [
                'id_token' => faker()->word,
                'access_token' => $access_token,
                'refresh_token' => faker()->word,
            ],
            200
        ),
    ]);

    $response = (new Auth0ApiRequester())->verifyPasswordlessEmailCode($user->email, '1234');

    assertEquals($access_token, $response->access_token);
});

it('can verify an authorization code', function () {
    $access_token = faker()->word;

    Http::fake([
        "https://" . config("core.auth0.api.domain") . "/oauth/token"
        => Http::response(
            [
                'id_token' => faker()->word,
                'access_token' => $access_token,
                'refresh_token' => faker()->word,
            ],
            200
        ),
    ]);

    $response = (new Auth0ApiRequester())->verifyAuthorizationCode('1234');
    assertEquals($access_token, $response->access_token);
});

it('can link two accounts together', function () {
    $primary = User::factory('App\User')->create(['sub' => "auth0|" . faker()->word]);
    $secondary = User::factory('App\User')->create(['sub' => "auth0|" . faker()->word]);

    $access_token = faker()->word;

    Http::fake([
        "https://" . config("core.auth0.api.domain") . "/api/v2/users/*"
        => Http::response(
            [
                'id_token' => faker()->word,
                'access_token' => $access_token,
                'refresh_token' => faker()->word,
            ],
            200
        ),
    ]);

    $response = (new Auth0ApiRequester())->linkUserAccounts($primary->sub, $secondary->sub);
    assertEquals($access_token, $response->access_token);
});
