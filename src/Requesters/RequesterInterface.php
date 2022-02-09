<?php

namespace Salt\Auth0\Requesters;

use Illuminate\Http\Client\Response;

interface RequesterInterface
{
    /**
     * Makes an API request to the given URL with the given method
     *
     * @param String $method, GET, PUT, POST, PATCH, DELETE
     * @param String $url
     * @param array $body
     * @return string
     */
    public function makeApiRequest(String $method, String $url, array $body = null): string;

    /**
     * Fetch the access token to be used in the request to verify the sender of the request
     *
     * @return string|null
     */
    public function getAccessToken(): ?string;

    /**
     * Returns a message depending on the status code returned
     *
     * @param Response $response
     * @return string
     */
    public function getErrorMessage(Response $response): string;
}
