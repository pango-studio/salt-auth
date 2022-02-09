<?php

namespace Salt\Auth0\Requesters;

use Illuminate\Support\Facades\Http;
use Salt\Auth0\Exceptions\ApiException;

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

        return isset($response->access_token) ? $response->access_token : null;
    }

    public function getErrorMessage(\Illuminate\Http\Client\Response $response): string
    {
        return         [
            400 => __('notifications.api.400'),
            401 => __('notifications.api.401'),
            403 => __('notifications.api.403'),
            404 => __('notifications.api.404'),
            405 => __('notifications.api.405'),
            429 => __('notifications.api.429'),
            500 => __('notifications.api.500'),
            501 => __('notifications.api.501'),
            503 => __('notifications.api.503'),
        ][$response->status()];
    }
}
