<?php

namespace App\Services\Admin;

use App\Models\Attribute;
use App\Models\Collection;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\PhaseTemplate;
use App\Models\TournamentInstance;
use App\Models\TournamentTemplate;
use App\Models\Universe;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/*
|--------------------------------------------------------------------------
| Todo lo que un admin puede revisar, dicho de una sola forma
|--------------------------------------------------------------------------
|
| Cada tipo de contenido guarda sus cosas a su manera: unos tienen
| visibilidad y otros no, unos cuentan vistas y otros no, la ficha de una
| competición cuelga de su universo. Aquí se describe cada uno una vez, y
| el espacio de administración recorre esta lista en vez de conocer ocho
| modelos distintos.
|
*/

class ContentRegistry
{
    public const TYPES = [
        'entity' => [
            'model' => Entity::class, 'label' => 'Entidades', 'one' => 'entidad', 'icon' => 'libro', 'tone' => '#818cf8',
            'visibility' => true, 'views' => true, 'clones' => true, 'module' => 'Biblioteca',
        ],
        'collection' => [
            'model' => Collection::class, 'label' => 'Colecciones', 'one' => 'colección', 'icon' => 'capas', 'tone' => '#22d3ee',
            'visibility' => true, 'views' => true, 'clones' => true, 'module' => 'Biblioteca',
        ],
        'attribute' => [
            'model' => Attribute::class, 'label' => 'Atributos', 'one' => 'atributo', 'icon' => 'controles', 'tone' => '#a78bfa',
            'visibility' => false, 'views' => true, 'clones' => true, 'module' => 'Biblioteca',
        ],
        'entity_type' => [
            'model' => EntityType::class, 'label' => 'Tipos de entidad', 'one' => 'tipo', 'icon' => 'cuadricula', 'tone' => '#94a3b8',
            'visibility' => false, 'views' => false, 'clones' => false, 'module' => 'Biblioteca',
        ],
        'tournament_template' => [
            'model' => TournamentTemplate::class, 'label' => 'Plantillas de torneo', 'one' => 'plantilla de torneo', 'icon' => 'trofeo', 'tone' => '#fbbf24',
            'visibility' => true, 'views' => true, 'clones' => true, 'module' => 'Torneos',
        ],
        'phase_template' => [
            'model' => PhaseTemplate::class, 'label' => 'Plantillas de fase', 'one' => 'plantilla de fase', 'icon' => 'grafo', 'tone' => '#f59e0b',
            'visibility' => true, 'views' => true, 'clones' => true, 'module' => 'Torneos',
        ],
        'universe' => [
            'model' => Universe::class, 'label' => 'Universos', 'one' => 'universo', 'icon' => 'orbita', 'tone' => '#8b5cf6',
            'visibility' => false, 'views' => false, 'clones' => false, 'module' => 'Universos',
        ],
        'competition' => [
            'model' => TournamentInstance::class, 'label' => 'Competiciones', 'one' => 'competición', 'icon' => 'espadas', 'tone' => '#34d399',
            'visibility' => false, 'views' => false, 'clones' => false, 'module' => 'Universos',
        ],
    ];

    public const VISIBILITIES = [
        'PUBLIC' => 'Pública',
        'UNLISTED' => 'Oculta de listados',
        'PRIVATE' => 'Privada',
    ];

    public static function has(string $key): bool
    {
        return isset(self::TYPES[$key]);
    }

    public static function meta(string $key): array
    {
        return self::TYPES[$key] ?? abort(404);
    }

    public static function query(string $key, bool $withTrashed = false): Builder
    {
        /** @var class-string<Model> $clase */
        $clase = self::meta($key)['model'];

        $consulta = $clase::query()->with('user');

        return $withTrashed ? $consulta->withTrashed() : $consulta;
    }

    public static function find(string $key, int $id): Model
    {
        return self::query($key, true)->findOrFail($id);
    }

    public static function keyFor(Model $model): string
    {
        if ($model instanceof User) {
            return 'user';
        }

        foreach (self::TYPES as $key => $meta) {
            if ($model instanceof $meta['model']) {
                return $key;
            }
        }

        return class_basename($model);
    }

    public static function labelOf(Model $model): string
    {
        return (string) ($model->name ?? $model->username ?? ('#' . $model->getKey()));
    }

    /* Donde se ve de verdad: la ficha que usa su dueño */
    public static function url(string $key, Model $model): ?string
    {
        if ($model->trashed()) {
            return null;
        }

        return match ($key) {
            'entity' => route('entities.show', $model),
            'collection' => route('collections.show', $model),
            'attribute' => route('attributes.show', $model),
            'entity_type' => route('entity-types.show', $model),
            'tournament_template' => route('tournaments.templates.show', $model),
            'phase_template' => route('tournaments.phase-templates.show', $model),
            'universe' => route('universes.show', $model),
            'competition' => $model->universe_id ? route('universes.competitions.show', [$model->universe_id, $model]) : null,
            default => null,
        };
    }

    /* Cuántos hay de cada tipo, contando borrados aparte */
    public static function counts(): array
    {
        $salida = [];

        foreach (self::TYPES as $key => $meta) {
            $salida[$key] = [
                'total' => $meta['model']::query()->count(),
                'trashed' => $meta['model']::onlyTrashed()->count(),
            ];
        }

        return $salida;
    }
}
