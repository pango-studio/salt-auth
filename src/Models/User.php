<?php

namespace Salt\Auth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Salt\Auth\Models\User
 *
 * @property string $name
 * @property string $email
 * @property string $sub
 */
class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'sub',
    ];
}
