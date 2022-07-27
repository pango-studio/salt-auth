<?php

declare(strict_types=1);

namespace Salt\Auth0\Repositories;

use Auth0\Laravel\Contract\Auth\User\Repository;
use Salt\Auth0\Models\User;
use Salt\Auth0\Requesters\Auth0ApiRequester;

class CustomUserRepository implements Repository
{
    /**
     * Updates or creates a user from stateful session data
     * provided by authentication with auth0.
     *
     * The user returned in this function must implement the interface
     * Auth0\Laravel\Contract\Model\Stateful\User
     *
     * @param $data The user data provided by auth0
     *
     * @return $user The stateful user
     *
     * @inheritdoc
     */
    public function fromSession(
        array $data
    ): ?\Illuminate\Contracts\Auth\Authenticatable {
        $user = User::updateOrCreate(
            [
                'email' => $data['email'],
            ],
            [
                'name' => $data['name'],
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
     * @inheritdoc
     * This method is not in use.
     *
     */
    public function fromAccessToken(
        array $user
    ): ?\Illuminate\Contracts\Auth\Authenticatable {
        return null;
    }
}
