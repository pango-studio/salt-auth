<?php

namespace Salt\Auth\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Salt\Auth\Auth
 */
class Auth extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'auth';
    }
}
