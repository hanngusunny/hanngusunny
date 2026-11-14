<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AdminUser extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'admin_users';

    protected $fillable = [
        'username',
        'password_hash',
        'name',
        'avatar',
        'is_active',
        'is_super_admin',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_super_admin' => 'boolean',
    ];

    public function getAuthPassword(): string
    {
        return (string) $this->password_hash;
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin === true;
    }
}
