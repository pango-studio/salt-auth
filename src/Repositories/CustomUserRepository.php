<?php

namespace Salt\Auth0\Repositories;

use Auth0\Login\Auth0JWTUser;
use Auth0\Login\Auth0User;
use Auth0\Login\Repository\Auth0UserRepository;

use Illuminate\Contracts\Auth\Authenticatable;

use Salt\Auth0\Models\User;
use Salt\Auth0\Requesters\Auth0ApiRequester;

class CustomUserRepository extends Auth0UserRepository
{
    /**
     * Get an existing user or create a new one
     *
     * @param array $profile - Auth0 profile
     *
     * @return User
     */
    protected function upsertUser($profile)
    {
        $user = User::firstOrCreate(['email' => $profile['email']], [
            'sub' => $profile['sub'] ?? '',
            'name' => $profile['name'] ?? '',
        ]);

        return $user;
    }

    /**
     * Custom method to create or update a user on sign up and store them locally
     *
     * @param array $data - user sign up data
     *
     * @return User
     */
    public function updateOrCreate($data): object
    {
        // Update or create user
        $user = User::updateOrCreate(
            [
                'email' => $data['email'],
            ],
            [
                'name' => $data['first_name'] . " " . $data['last_name'],
            ]
        );

        // Check for auth0 user
        $user_data = (new Auth0ApiRequester())->searchAuth0UserByEmail($user->email);
        $password = isset($data['password']) ? $data['password'] : null;
        if (empty($user_data)) {
            $auth0_user = (new Auth0ApiRequester())->createAuth0User($user->email, $user->name, $password);
            $user->sub = $auth0_user->user_id;
            $user->save();
        } else {

            /**
             * This method of account retrieval is risky as there might be multiple accounts
             * associated with a single email address. When creating a new user though, its
             * still the most reliable identifier we have available
             */
            $user->sub = $user_data[0]->user_id;
            $user->save();

            // The user has now set another password
            if ($password) {
                (new Auth0ApiRequester())->changePassword($user_data[0]->user_id, $password);
            }
        }

        return $user;
    }

    /**
     * Authenticate a user with a decoded ID Token
     *
     * @param array $decodedJwt
     *
     * @return Auth0JWTUser
     */
    public function getUserByDecodedJWT(array $decodedJwt): Authenticatable
    {
        $user = $this->upsertUser((array) $decodedJwt);

        return new Auth0JWTUser($user->getAttributes());
    }

    /**
     * Authenticate a user with a decoded ID Token
     *
     * @param object $jwt
     *
     * @return int user ID
     */
    public function getUserIDByDecodedJWT($jwt): int
    {
        $user = $this->upsertUser((array) $jwt);

        return $user->id;
    }

    /**
     * Get a User from the database using Auth0 profile information
     *
     * @param array $userinfo
     *
     * @return Auth0User
     */
    public function getUserByUserInfo(array $userinfo): Authenticatable
    {
        $user = $this->upsertUser($userinfo['profile']);

        return new Auth0User($user->getAttributes(), $userinfo['accessToken']);
    }
}
