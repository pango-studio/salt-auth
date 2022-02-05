<?php

namespace Salt\Auth\Http\Controllers\Auth;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

use Salt\Auth\Http\Controllers\Controller;

class Auth0IndexController extends Controller
{
    public function login()
    {
        if (Auth::check()) {
            return redirect()->intended('/');
        }

        return App::make('auth0')->login(
            null,
            null,
            ['scope' => 'openid name email email_verified'],
            'code'
        );
    }

    public function logout()
    {
        Auth::logout();

        $logoutUrl = sprintf(
            'https://%s/v2/logout?client_id=%s&returnTo=%s',
            config('core.auth0.api.domain'),
            config('core.auth0.app.client_id'),
            config('core.url')
        );

        return Redirect::intended($logoutUrl);
    }
}
