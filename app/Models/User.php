<?php

namespace App\Models;

use App\Builders\UserBuilder;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'deactivated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function hasAbility($ability)
    {
        $role = Role::whereLabel($this->role)->first();

        return in_array($ability, explode(',', $role->abilities));
    }

    public function isDeactivated()
    {
        return is_null($this->deactivated_at) ? false : true;
    }

    public function letters()
    {
        return $this->belongsToMany(Letter::class);
    }

    public function newEloquentBuilder($query)
    {
        return new UserBuilder($query);
    }
}
