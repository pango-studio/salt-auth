<?php

namespace Salt\Auth\Requesters;

use Carbon\Carbon;

use Illuminate\Support\Facades\Http;
use Salt\Auth\Exceptions\ApiException;

class Auth0ApiRequester extends ApiRequester implements RequesterInterface
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Fetch the user details from the Auth0 api
     *
     * Docs - https://auth0.com/docs/api/management/v2#!/Users/get_users_by_id
     *
     * @param String $user_id the Auth0 user id
     *
     * @return Object $user an object containing all the user deta for the Auth0 user
     */
    public function fetchAuth0UserById(String $user_id): Object
    {
        $method = "GET";

        $url = config("core.auth0.api.audience") . "users/" . $user_id;

        $response = $this->makeApiRequest($method, $url);
        $user = json_decode($response);

        return $user;
    }

    /**
     * Fetch the user details from the Auth0 api via access token
     *
     * Docs - https://auth0.com/docs/api/authentication#get-user-info
     *
     * @param String $access_token the Auth0 access token
     *
     * @return Object $user an object containing all the user deta for the Auth0 user
     */
    public function getUserInfo(String $access_token): Object
    {
        $url = 'https://' . config("core.auth0.api.domain") . "/userinfo";
        $response = Http::withToken($access_token)->get($url);

        if ($response->failed()) {
            throw new ApiException($this->getErrorMessage($response));
        };
        $user = json_decode($response->body());

        return $user;
    }

    /**
     *
     * Fetch the user details from Auth0 associated with a given email address.
     * NB! Use with caution: in most cases this returns an item with one array, but it might return multiple entries
     *
     * Docs - https://auth0.com/docs/api/management/v2#!/Users_By_Email/get_users_by_email
     *
     * @param String $email The email to search for
     * @return Array An array of user details which are associated with the email address
     */
    public function fetchAuth0UserByEmail(String $email): array
    {
        $method = "GET";
        $url = config("core.auth0.api.audience") . "users-by-email?email=" . $email;

        $response = $this->makeApiRequest($method, $url);

        $user_data = json_decode($response);

        return $user_data;
    }

    /**
     *
     * Find a user from Auth0 associated with a given email address & current connection.
     *
     * Docs - https://auth0.com/docs/api/management/v2#!/Users_By_Email/get_users_by_email
     *
     * @param String $email The email to search for
     * @return Array An array of user details which are associated with the email address
     */
    public function searchAuth0UserByEmail(String $email): array
    {
        $method = "GET";
        $url = config("core.auth0.api.audience")
            . 'users?search_engine=v3&q=(email:"'
            . $email
            . '" AND identities.connection:"'
            . config('core.auth0.app.db_connection') . '")';

        $response = $this->makeApiRequest($method, $url);

        $user_data = json_decode($response);

        return $user_data;
    }

    /**
     * Creates a new user on auth0 using the given name and email address
     *
     * Docs - https://auth0.com/docs/api/management/v2#!/Users/post_users
     *
     * @param String $email Email address of the user to be created
     * @param String $name Full name of the user to be created
     * @param String|null $password the initial password to set for the user (optional)
     * @return Object $auth0_user The data for the newly created Auth0 user
     */
    public function createAuth0User(String $email, String $name, ?String $password = null): Object
    {
        $method = "POST";
        $url = config("core.auth0.api.audience") . "users";

        // If a password was not passed to this method, generate a dummy one
        if (!$password) {
            /**
             * It doesn't really matter what this password is, as it won't be used to login.
             * It should however be hashed so that someone isn't able to login as the user
             * before they have had a chance to change the password.
             */
            $password = password_hash("TestPassword123", PASSWORD_DEFAULT);
        }

        $connection = config('core.auth0.app.db_connection');

        $body = [
            'email' => $email,
            'name' => $name,
            'connection' => $connection,
            'password' => $password,
            "email_verified" => true,
        ];

        $response = $this->makeApiRequest($method, $url, $body);
        $auth0_user = json_decode($response);

        return $auth0_user;
    }

    /**
     * Updates the user's email address and name on Auth0
     *
     * Docs - https://auth0.com/docs/api/management/v2#!/Users/patch_users_by_id
     *
     * @param String $user_id the Auth0 ID
     * @param String $email The email address to be updated
     * @param String $name The name to be updated
     *
     * @return Object The updated Auth0 user data
     */
    public function updateAuth0User(String $user_id, String $email, String $name): Object
    {
        $method = "PATCH";
        $url = config("core.auth0.api.audience") . "users/" . $user_id;
        $body = [
            'email' => $email,
            'name' => $name,
            "email_verified" => true,
        ];

        $response = $this->makeApiRequest($method, $url, $body);

        $auth0_user = json_decode($response);

        return $auth0_user;
    }

    /**
     * Update the user_metadata property of the given Auth0 user
     *
     * Docs - https://auth0.com/docs/users/guides/update-metadata-properties-with-management-api
     *
     * @param String $user_id the Auth0 ID
     * @param Mixed $meta the meta properties to be updated.
     * @return Mixed the updated user_metadata for the given Auth0 user
     */
    public function updateAuth0UserMeta(String $user_id, $meta)
    {
        $method = "PATCH";
        $url = config("core.auth0.api.audience") . "users/" . $user_id;

        $body = [
            'user_metadata' => $meta,
        ];

        $response = $this->makeApiRequest($method, $url, $body);
        $auth0_user = json_decode($response);

        return $auth0_user->user_metadata;
    }

    /**
     * Change the user's password
     *
     * Docs - https://auth0.com/docs/connections/database/password-change#use-the-management-api
     *
     * @param String $user_id
     * @param String $password
     * @return bool true if successful
     */
    public function changePassword(String $user_id, String $password): bool
    {
        $method = "PATCH";
        $url = config("core.auth0.api.audience") . "users/" . $user_id;

        $body = [
            'password' => $password,
        ];

        $this->makeApiRequest($method, $url, $body);

        return true;
    }

    /**
     * Generates a password reset link for the given Auth0 user
     *
     * Docs - https://auth0.com/docs/api/management/v2#!/Tickets/post_password_change
     *
     * @param String $user_id the Auth0 ID for the user
     * @return String $response includes a ticket property which contains
     * a URL for resetting the password
     */
    public function generatePasswordResetLink(String $user_id): String
    {
        $method = "POST";
        $url = config("core.auth0.api.audience") . "tickets/password-change";

        $body = [
            'user_id' => $user_id,
            'result_url' => config('core.url'),
            'ttl_sec' => 2592000,
        ];

        $response = $this->makeApiRequest($method, $url, $body);

        return $response;
    }

    /**
     * Deletes the Auth0 user associated with a given ID
     *
     * Docs - https://auth0.com/docs/api/management/v2#!/Users/delete_users_by_id
     *
     * @param String $user_id The Auth0 Id of the user
     * @return Object $response
     */
    public function deleteAuth0User(String $user_id): Object
    {
        $method = "DELETE";
        $url = config("core.auth0.api.audience") . "users/" . $user_id;

        $response = $this->makeApiRequest($method, $url);

        return json_decode($response);
    }

    /**
     * Get the auth0 roles for a user
     *
     * Docs - https://auth0.com/docs/api/management/v2#!/Users/get_user_roles
     *
     * @param String $user_id The Auth0 Id of the user
     * @return Array $response
     */
    public function getRolesAuth0User($user_id): array
    {
        $method = "GET";
        $url = config("core.auth0.api.audience") . "users/" . $user_id . "/roles";

        $response = $this->makeApiRequest($method, $url);

        return json_decode($response);
    }

    /**
     * Fetch login logs from Auth0
     *
     * Docs: https://auth0.com/docs/logs/retrieve-log-events-using-mgmt-api
     *
     * @param Carbon $start A carbon object containing the date and time to start searching from
     * @param Carbon $end A Carbon object containing the end date and time to search up until
     * @param string $user_id (Optional) The auth0 ID of the user to fetch logs for
     * @return array $response An array of logs returned from auth0
     */
    public function getLoginLogs(Carbon $start, Carbon $end, string $user_id = null): array
    {
        // Setup date query
        $date_from = $start->toIso8601String();
        $date_to = $end->toIso8601String();
        $date_query = "date:[{$date_from} TO {$date_to}]";

        // Setup connection query
        $connection = config('core.auth0.app.db_connection');
        $connection_query = " AND (connection:" . "$connection)";

        $type = " AND (type:s OR type:fu OR type:fp)";

        // If a user auth0 ID (sub) was passed, fetch logs just for that user. Otherwise fetch all logs
        if ($user_id) {
            $url = config("core.auth0.api.audience") . "users/{$user_id}/logs?q={$date_query}{$connection_query}{$type}";
        } else {
            $url = config("core.auth0.api.audience") . "logs?q={$date_query}{$connection_query}{$type}";
        }

        $method = "GET";
        $response = $this->makeApiRequest($method, $url);

        $logs = json_decode($response);

        return $logs;
    }

    /**
     * Get an access token for the user by sending the user's username and password directly
     * to Auth0
     *
     * Docs: https://auth0.com/docs/api/authentication#resource-owner-password
     *
     * @param string $username
     * @param string $password
     * @return Object
     */
    public function getTokenViaResourceOwnerPassword(string $username, string $password): Object
    {
        $method = "POST";
        $url = 'https://' . config("core.auth0.api.domain") . "/oauth/token";
        $body = [
            'grant_type' => 'password',
            'username' => $username,
            'password' => $password,
            'audience' => config("core.auth0.api.audience"),
            'client_id' => config('core.auth0.app.client_id'),
            'client_secret' => config('core.auth0.app.client_secret'),
            'scope' => 'openid profile email offline_access',
        ];
        $response = $this->makeApiRequest($method, $url, $body);
        $authorize_data = json_decode($response);

        return $authorize_data;
    }

    /**
     * Begins the passwordless authentication process via a code sent to the specified email address
     *
     * Docs: https://auth0.com/docs/connections/passwordless/implement-login/embedded-login/relevant-api-endpoints#post-passwordless-start
     *
     * @param string $email
     * @return Object
     */
    public function startPasswordlessEmailFlow(string $email): Object
    {
        $method = "POST";
        $url = 'https://' . config("core.auth0.api.domain") . "/passwordless/start";
        $body = [
            'connection' => 'email',
            'email' => $email,
            'send' => 'code',
            'client_id' => config('core.auth0.app.client_id'),
            'client_secret' => config('core.auth0.app.client_secret'),
        ];
        $response = $this->makeApiRequest($method, $url, $body);
        $authorize_data = json_decode($response);

        return $authorize_data;
    }

    /**
     * Verifies that the specified code is valid for the specified email address
     *
     * Docs: https://auth0.com/docs/connections/passwordless/implement-login/embedded-login/relevant-api-endpoints#post-oauth-token
     *
     * @param string $email
     * @param string $code
     * @return Object
     */
    public function verifyPasswordlessEmailCode(string $email, string $code): Object
    {
        $method = "POST";
        $url = 'https://' . config("core.auth0.api.domain") . "/oauth/token";
        $body = [
            "grant_type" => "http://auth0.com/oauth/grant-type/passwordless/otp",
            'username' => $email,
            'otp' => $code,
            'realm' => 'email',
            'audience' => config("core.auth0.api.audience"),
            'client_id' => config('core.auth0.app.client_id'),
            'client_secret' => config('core.auth0.app.client_secret'),
            'scope' => 'openid profile email offline_access',
        ];

        $response = $this->makeApiRequest($method, $url, $body);
        $authorize_data = json_decode($response);


        return $authorize_data;
    }

    /**
     * Exchanges a valid authorization code for an access token
     *
     * Docs: https://auth0.com/docs/api/authentication#get-token
     *
     * @param string $code
     * @param string $redirect_uri
     */
    public function verifyAuthorizationCode($code, $redirect_uri = null)
    {
        $method = "POST";
        $url = 'https://' . config("core.auth0.api.domain") . "/oauth/token";
        $body = [
            "grant_type" => "authorization_code",
            'client_id' => config('core.auth0.app.client_id'),
            'client_secret' => config('core.auth0.app.client_secret'),
            'code' => $code,
            'redirect_uri' => $redirect_uri,
        ];
        $response = $this->makeApiRequest($method, $url, $body);

        $authorize_data = json_decode($response);

        return $authorize_data;
    }

    /**
     * Links user accounts together to form a primary and secondary relationship.
     *
     * Docs: https://auth0.com/docs/users/user-account-linking/link-user-accounts
     *
     * @param string $primary_sub
     * @param string $secondary_sub
     */
    public function linkUserAccounts($primary_sub, $secondary_sub)
    {
        list($primary_provider, $primary_id) = explode('|', $primary_sub);
        list($secondary_provider, $secondary_id) = explode('|', $secondary_sub);

        $method = "POST";
        $url = 'https://' . config("core.auth0.api.domain") . "/api/v2/users/" . $primary_sub . "/identities";

        $body = [
            "provider" => $secondary_provider,
            "user_id" => $secondary_id,
        ];
        $response = $this->makeApiRequest($method, $url, $body);
        $authorize_data = json_decode($response);

        return $authorize_data;
    }
}
