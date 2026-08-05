<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }

    public function entityTypes(): HasMany
    {
        return $this->hasMany(EntityType::class);
    }

    public function entities(): HasMany
    {
        return $this->hasMany(Entity::class);
    }
    public function attributes(): HasMany
    {
        return $this->hasMany(Attribute::class);
    }

    public function attributeGroups(): HasMany
    {
        return $this->hasMany(AttributeGroup::class);
    }
}
