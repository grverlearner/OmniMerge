<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

        // Perfil OmniMerge
        'avatar',
        'headline',
        'bio',
        'location',
        'website',
        'profile_visibility',

        // Sistema
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


    /*
    |--------------------------------------------------------------------------
    | Estado del usuario
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }


    public function isAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }


    /*
    |--------------------------------------------------------------------------
    | Perfil público
    |--------------------------------------------------------------------------
    */

    public function isPublicProfile(): bool
    {
        return $this->profile_visibility === 'PUBLIC';
    }


    /*
    |--------------------------------------------------------------------------
    | Avatar
    |--------------------------------------------------------------------------
    */

    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar) {
            return null;
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        if (! $disk->exists($this->avatar)) {
            return null;
        }

        return $disk->url($this->avatar);
    }


    /*
    |--------------------------------------------------------------------------
    | Iniciales
    |--------------------------------------------------------------------------
    |
    | Si el usuario todavía no tiene avatar se utilizan hasta dos
    | iniciales del nombre.
    |
    | Grover Chambilla -> GC
    |
    */

    public function getInitialsAttribute(): string
    {
        $parts = preg_split(
            '/\s+/',
            trim($this->name)
        ) ?: [];

        return collect($parts)
            ->filter()
            ->take(2)
            ->map(
                fn($part) =>
                Str::upper(
                    Str::substr(
                        $part,
                        0,
                        1
                    )
                )
            )
            ->implode('');
    }


    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function entityTypes(): HasMany
    {
        return $this->hasMany(
            EntityType::class
        );
    }


    public function entities(): HasMany
    {
        return $this->hasMany(
            Entity::class
        );
    }


    public function attributes(): HasMany
    {
        return $this->hasMany(
            Attribute::class
        );
    }


    public function attributeGroups(): HasMany
    {
        return $this->hasMany(
            AttributeGroup::class
        );
    }


    public function collections(): HasMany
    {
        return $this->hasMany(
            Collection::class
        );
    }
}
