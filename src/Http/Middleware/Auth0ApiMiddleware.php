<?php

namespace Salt\Auth0\Http\Middleware;

use Auth0\SDK\Exception\InvalidTokenException;
use Auth0\SDK\Helpers\JWKFetcher;
use Auth0\SDK\Helpers\Tokens\AsymmetricVerifier;
use Auth0\SDK\Helpers\Tokens\TokenVerifier;

use Closure;

class Auth0ApiMiddleware
{
    /**
     * Checks if the request has a valid bearer token
     */
    public function handle($request, Closure $next)
    {
        $token = $request->bearerToken();
        if (! $token) {
            return response()->json("No bearer token provided", 401);
        }

        $this->validateToken($token);

        return $next($request);
    }

    /**
     * Verifies that the token is valid
     */
    protected function validateToken($token)
    {
        try {
            $jwksUri = config('salt-auth0.api.domain')  . '/.well-known/jwks.json';
            $jwksFetcher = new JWKFetcher(null, ['base_uri' => $jwksUri]);
            $signatureVerifier = new AsymmetricVerifier($jwksFetcher);
            $tokenVerifier = new TokenVerifier("https://" . config('salt-auth0.api.domain') . "/", config('salt-auth0.api.audience'), $signatureVerifier);

            $tokenVerifier->verify($token);
        } catch (InvalidTokenException $e) {
            throw $e;
        };
    }
}
