<?php

namespace Salt\Auth0\Requesters;

use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Salt\Auth0\Exceptions\ApiException;
use Salt\Auth0\Models\AccessToken;

class ApiRequester implements RequesterInterface
{
    /** @var string */
    protected $token;

    public function __construct()
    {
        $this->token = $this->getAccessToken();
    }

    public function makeApiRequest(String $method, String $url, array $body = null): string
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer " . $this->token,
        ];

        $response = Http::withHeaders($headers)->$method($url, $body);

        if ($response->failed()) {
            throw new ApiException($this->getErrorMessage($response));
        };

        return $response->body();
    }

    public function getAccessToken(): ?string
    {
        $accessToken = AccessToken::where('name', 'auth0')->first();

        if (! $accessToken || $accessToken->refreshed_at <= Carbon::now()->subDay()) {
            return $this->refreshAccessToken();
        } else {
            return $accessToken->token;
        }
    }

    public function refreshAccessToken(): ?string
    {
        // Skip fetching a real token during test runs
        if (App::environment() === 'testing') {
            return "test_token";
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://" . config('salt-auth0.api.domain') . "/oauth/token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "grant_type=client_credentials&client_id="
                . config('salt-auth0.api.client_id')
                . "&client_secret="
                . config('salt-auth0.api.client_secret')
                . "&audience="
                . config('salt-auth0.api.audience'),
            CURLOPT_HTTPHEADER => [
                "content-type: application/x-www-form-urlencoded",
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        if ($err) {
            logger($err);
        }

        curl_close($curl);

        $response = json_decode($response);

        return AccessToken::updateOrCreate(
            [
                'name' => 'auth0',
            ],
            [
                'token' => $response->access_token,
                'refreshed_at' => now(),
            ]
        )->token;
    }

    public function getErrorMessage(\Illuminate\Http\Client\Response $response): string
    {
        return         [
            400 => 'The data sent was invalid',
            401 => 'Request unauthorized',
            403 => 'The request was forbidden or requires verification',
            404 => 'The requested resource could not be found',
            405 => 'Request method not allowed',
            429 => 'Too many attempts',
            500 => 'Internal server error',
            501 => 'Unsupported response or grant type',
            503 => 'The server is temporarily unavailable',
        ][$response->status()];
    }
}
