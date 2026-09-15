<?php

namespace App\Services\Community;

use App\Models\Entity;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/*
|--------------------------------------------------------------------------
| CreatorDirectory
|--------------------------------------------------------------------------
|
| Quién publica, y de qué.
|
| Cada comunidad sabía contar a sus creadores —la de la Biblioteca por
| entidades, colecciones y atributos; la de Torneos por plantillas y fases—,
| pero ninguna sabía que existía la otra. Aquí se juntan las dos cuentas, y
| de juntarlas sale lo que no se podía decir antes: si alguien es creador
| de biblioteca, de torneos, o de las dos cosas.
|
| Las reglas de «público» son las mismas que ya usan las dos comunidades:
|
|   biblioteca  PUBLIC (o scope PUBLIC) + ACTIVE + con fecha de publicación
|   torneos     el scope published() de cada plantilla
|
| Y la Biblioteca se mide como la mide hoy su explorador: entidades,
| colecciones y atributos.
|
| Ver docs/md/78-Comunidad.md
|
*/

class CreatorDirectory
{
    /* [etiqueta, color, qué publica] */
    public const TIPOS = [
        'completo' => ['Creador completo', '#34d399', 'Publica en la biblioteca y en torneos'],
        'biblioteca' => ['Creador de biblioteca', '#818cf8', 'Publica entidades, colecciones o atributos'],
        'torneos' => ['Creador de torneos', '#fbbf24', 'Publica plantillas de torneo o de fase'],
    ];


    public static function tipoDe(int $biblioteca, int $torneos): ?string
    {
        return match (true) {
            $biblioteca > 0 && $torneos > 0 => 'completo',
            $biblioteca > 0 => 'biblioteca',
            $torneos > 0 => 'torneos',
            default => null,
        };
    }


    /*
     * Personas con perfil visible y activas, con todas sus cuentas públicas.
     *
     * Sin `$soloVisibles` cuenta a cualquiera: lo usa la ficha de uno mismo.
     */
    public function consulta(bool $soloVisibles = true): Builder
    {
        $biblioteca = fn($q) => $q->where('visibility', 'PUBLIC')->where('status', 'ACTIVE')->whereNotNull('published_at');
        $atributos = fn($q) => $q->where('scope', 'PUBLIC')->where('status', 'ACTIVE')->whereNotNull('published_at');
        $publicado = fn($q) => $q->published();

        return User::query()
            ->when($soloVisibles, fn($q) => $q
                ->where('status', 'ACTIVE')
                ->where('profile_visibility', 'PUBLIC'))
            ->withCount([
                'entities as pub_entidades_count' => $biblioteca,
                'collections as pub_colecciones_count' => $biblioteca,
                'attributes as pub_atributos_count' => $atributos,
                'tournamentTemplates as pub_torneos_count' => $publicado,
                'phaseTemplates as pub_fases_count' => $publicado,
            ])
            ->withSum(['entities as copias_entidades' => $biblioteca], 'clones_count')
            ->withSum(['collections as copias_colecciones' => $biblioteca], 'clones_count')
            ->withSum(['attributes as copias_atributos' => $atributos], 'clones_count')
            ->withSum(['tournamentTemplates as copias_torneos' => $publicado], 'clones_count')
            ->withSum(['phaseTemplates as copias_fases' => $publicado], 'clones_count')
            ->withMax(['entities as ultima_entidad' => $biblioteca], 'published_at')
            ->withMax(['tournamentTemplates as ultima_torneo' => $publicado], 'published_at')
            ->withMax(['phaseTemplates as ultima_fase' => $publicado], 'published_at');
    }


    /*
     * Todos los que han publicado algo, con sus cifras ya calculadas.
     *
     * Se trae la colección entera y se filtra en memoria: el tipo sale de
     * sumar cinco subconsultas, y paginar con HAVING sobre alias rompe la
     * consulta de conteo de Laravel. Con cientos de creadores esto va sobrado;
     * con decenas de miles habría que guardar el tipo.
     */
    public function creadores(): Collection
    {
        return $this->consulta()
            ->get()
            ->map(fn(User $u) => $this->completar($u))
            ->filter(fn(User $u) => $u->tipo_creador !== null)
            ->values();
    }


    /*
     * Cuántos perfiles visibles no han publicado nada todavía. Se dice, en
     * vez de dejar que el directorio parezca más pequeño de lo que es.
     */
    public function sinPublicar(): int
    {
        return $this->consulta()
            ->get()
            ->map(fn(User $u) => $this->completar($u))
            ->filter(fn(User $u) => $u->tipo_creador === null)
            ->count();
    }


    public function resumen(User $persona): array
    {
        $u = $this->completar(
            $this->consulta(false)
                ->whereKey($persona->getKey())
                ->first() ?? $persona
        );

        return [
            'biblioteca' => $u->pub_biblioteca,
            'torneos' => $u->pub_torneos,
            'tipo' => $u->tipo_creador,
        ];
    }


    /*
     * Caras públicas de cada creador, en una sola consulta para todos.
     */
    public function caras(iterable $ids, int $porCreador = 6): Collection
    {
        return Entity::query()
            ->whereIn('user_id', collect($ids)->all())
            ->where('visibility', 'PUBLIC')
            ->where('status', 'ACTIVE')
            ->whereNotNull('published_at')
            ->whereNotNull('image')
            ->latest('published_at')
            ->get(['id', 'user_id', 'name', 'image'])
            ->groupBy('user_id')
            ->map(fn($grupo) => $grupo->take($porCreador)->values());
    }


    private function completar(User $u): User
    {
        $u->pub_biblioteca = (int) $u->pub_entidades_count + (int) $u->pub_colecciones_count + (int) $u->pub_atributos_count;
        $u->pub_torneos = (int) $u->pub_torneos_count + (int) $u->pub_fases_count;
        $u->pub_total = $u->pub_biblioteca + $u->pub_torneos;

        $u->copias_total = (int) $u->copias_entidades + (int) $u->copias_colecciones + (int) $u->copias_atributos
            + (int) $u->copias_torneos + (int) $u->copias_fases;

        $fechas = collect([$u->ultima_entidad, $u->ultima_torneo, $u->ultima_fase])->filter();
        $u->ultima_publicacion = $fechas->isEmpty() ? null : Carbon::parse($fechas->max());

        $u->tipo_creador = self::tipoDe($u->pub_biblioteca, $u->pub_torneos);

        return $u;
    }
}
