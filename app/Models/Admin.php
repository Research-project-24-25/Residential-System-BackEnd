<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use App\Notifications\ResetPassword;
use App\Traits\Filterable;

class Admin extends Authenticatable implements CanResetPasswordContract
{
    use HasApiTokens, Notifiable, HasFactory, SoftDeletes, CanResetPassword, Filterable;

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'age' => 'integer',
        'password' => 'hashed',
        'salary' => 'double',
    ];


    protected $filterableFields = [
        'first_name',
        'last_name',
        'age',
        'gender',
        'salary',
        'username',
        'role',
        'created_at',
        'updated_at',
    ];


    protected $searchableFields = [
        'first_name',
        'last_name',
        'phone_number',
        'age',
        'gender',
        'salary',
        'username',
        'email',
        'role',
    ];


    public function isSuperAdmin()
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function unreadNotifications()
    {
        return $this->notifications()->whereNull('read_at');
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPassword($token));
    }
}
