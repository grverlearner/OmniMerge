<?php

namespace App\Support\Site;

use App\Models\Attribute;
use App\Models\Collection;
use App\Models\ContentFlag;
use App\Models\Entity;
use App\Models\PhaseTemplate;
use App\Models\TournamentTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/*
|--------------------------------------------------------------------------
| Lo que un admin ha sacado de la comunidad
|--------------------------------------------------------------------------
|
| Dos cosas dejan de verse en la comunidad sin borrarse:
|
|   - lo que un admin ha marcado como oculto
|   - todo lo de una cuenta bloqueada o eliminada, y la cuenta misma
|
| La comunidad tiene decenas de consultas repartidas por sus controladores,
| así que en vez de tocar cada una se ponen ámbitos globales a los modelos
| durante las peticiones de la comunidad —y solo en ellas: el dueño sigue
| viendo lo suyo en su Biblioteca—. Los admins no pasan por aquí: ellos lo
| ven todo.
|
*/

class CommunityModeration
{
    /** @var array<string, class-string> */
    public const MODELS = [
        'entity' => Entity::class,
        'collection' => Collection::class,
        'attribute' => Attribute::class,
        'tournament_template' => TournamentTemplate::class,
        'phase_template' => PhaseTemplate::class,
    ];

    public static function apply(): void
    {
        foreach (self::MODELS as $type => $clase) {
            $clase::addGlobalScope('omni-moderacion', function (Builder $q) use ($type) {
                $tabla = $q->getModel()->getTable();

                $q->whereNotIn("{$tabla}.id", ContentFlag::query()
                    ->where('content_type', $type)
                    ->where('flag', 'HIDDEN')
                    ->select('content_id'))
                    ->whereIn("{$tabla}.user_id", self::cuentasVisibles());
            });
        }

        User::addGlobalScope('omni-moderacion', function (Builder $q) {
            $q->where(fn ($w) => $w
                ->whereNull('users.banned_at')
                ->orWhere('users.banned_until', '<=', now()));
        });
    }

    /* Las cuentas que no están bloqueadas (las eliminadas ya las quita SoftDeletes) */
    public static function cuentasVisibles(): Builder
    {
        return User::query()
            ->withoutGlobalScope('omni-moderacion')
            ->where(fn ($w) => $w->whereNull('banned_at')->orWhere('banned_until', '<=', now()))
            ->select('id');
    }

    /* Una cuenta concreta que la comunidad no debe enseñar */
    public static function hides(?User $user): bool
    {
        return ! $user || $user->trashed() || $user->isBanned();
    }
}
