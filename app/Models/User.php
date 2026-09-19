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

        // Administración
        'banned_at',
        'banned_until',
        'ban_reason',
        'banned_by',
        'creator_badge',
        'creator_badge_note',
    ];

    /*
     * Las insignias que un admin puede dar a un creador. Son solo una
     * etiqueta pública: no cambian lo que puede hacer.
     */
    public const CREATOR_BADGES = [
        'VERIFIED' => ['label' => 'Creador verificado', 'short' => 'Verificado', 'icon' => 'check', 'tone' => '#38bdf8'],
        'TRUSTED' => ['label' => 'Creador confiable', 'short' => 'Confiable', 'icon' => 'medalla', 'tone' => '#34d399'],
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
            'banned_at' => 'datetime',
            'banned_until' => 'datetime',
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
     * Bloqueada ahora mismo: con bloqueo puesto y, si tenía fecha de fin,
     * todavía sin llegar a ella. Un bloqueo caducado deja de contar solo,
     * sin que nadie tenga que quitarlo.
     */
    public function isBanned(): bool
    {
        return $this->banned_at !== null
            && ($this->banned_until === null || $this->banned_until->isFuture());
    }


    /*
     * Un bloqueo con fecha de fin se levanta solo al llegar esa fecha. Se
     * comprueba al entrar y en cada petición, así que nadie tiene que
     * acordarse de quitarlo.
     */
    public function liftExpiredBan(): bool
    {
        if ($this->banned_at === null || $this->banned_until === null || $this->banned_until->isFuture()) {
            return false;
        }

        $this->forceFill([
            'status' => 'ACTIVE',
            'banned_at' => null,
            'banned_until' => null,
            'ban_reason' => null,
            'banned_by' => null,
        ])->save();

        return true;
    }


    /* Lo que se le dice a quien intenta entrar con la cuenta bloqueada */
    public function banMessage(): string
    {
        $texto = 'Esta cuenta está bloqueada';

        $texto .= $this->banned_until
            ? ' hasta el ' . $this->banned_until->translatedFormat('j \d\e F \d\e Y, H:i') . '.'
            : '.';

        if ($this->ban_reason) {
            $texto .= ' Motivo: ' . $this->ban_reason;
        }

        return $texto;
    }


    public function getCreatorBadgeMetaAttribute(): ?array
    {
        return $this->creator_badge
            ? (self::CREATOR_BADGES[$this->creator_badge] ?? null)
            : null;
    }


    public function bannedByUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'banned_by')->withTrashed();
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

    public function attributeOptions(): HasMany
    {
        return $this->hasMany(
            AttributeOption::class
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

    public function versions(): HasMany
    {
        return $this->hasMany(
            Version::class
        );
    }


    public function entityVersions(): HasMany
    {
        return $this->hasMany(
            EntityVersion::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Plantillas de Torneos
    |--------------------------------------------------------------------------
    */


    public function tournamentTemplates(): HasMany
    {
        return $this->hasMany(
            TournamentTemplate::class
        );
    }

    public function phaseTemplates(): HasMany
    {
        return $this->hasMany(
            PhaseTemplate::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Universos
    |--------------------------------------------------------------------------
    */

    public function universes(): HasMany
    {
        return $this->hasMany(
            Universe::class
        );
    }
}
