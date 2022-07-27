<?php

namespace Salt\Auth0\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Salt\Auth0\Database\Factories\UserFactory;
use Auth0\Laravel\Contract\Model\Stateful\User as StatefulUser;

/**
 * Salt\Auth0\Models\User
 *
 * @property string $name
 * @property string $email
 * @property string $sub
 */
class User extends Authenticatable implements StatefulUser
{
    use HasFactory;

    protected static function newFactory()
    {
        return UserFactory::new();
    }

    protected $fillable = [
        'name', 'email', 'sub',
    ];
}
