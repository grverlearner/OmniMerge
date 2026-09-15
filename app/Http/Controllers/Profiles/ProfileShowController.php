<?php

namespace App\Http\Controllers\Profiles;

use App\Http\Controllers\Controller;
use App\Models\AttributeOption;
use App\Models\Entity;
use App\Models\PhaseTemplate;
use App\Models\TournamentTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| ProfileShowController
|--------------------------------------------------------------------------
|
| El perfil entero de una persona.
|
| Habia dos perfiles publicos y ninguno de los dos era el de la persona:
|
|   community.creators.show       su biblioteca, a fondo
|   tournaments.community.creator sus plantillas de torneo
|
| Los dos son buenos para lo suyo, y ninguno contesta «quien es este». Quien
| llegaba desde una entidad copiada veia su biblioteca y no se enteraba de
| que ademas diseña torneos; quien llegaba desde una plantilla veia lo
| contrario.
|
| Esta pantalla es la persona: quien es, todo lo que ha soltado -de los dos
| lados- y por donde seguir mirando. Los dos perfiles especializados se
| conservan y se enlazan desde aqui, porque para bucear siguen siendo
| mejores.
|
| Las reglas de que es publico son LAS MISMAS que usa la Comunidad, y viven
| aqui en un sitio: repetirlas por consulta es como se acaba enseñando por
| error algo que su autor no publico.
|
| Ver docs/md/76-Perfil.md
|
*/

class ProfileShowController extends Controller
{
    public function __invoke(
        Request $request,
        User $user
    ): View {

        $esMio = $request->user()?->is($user) ?? false;

        /*
         * Un perfil privado no se enseña, ni siquiera a medias: se dice que es
         * privado y se para ahi. El dueño si se ve, porque necesita saber que
         * pinta tiene lo suyo.
         */
        $visible = $user->isPublicProfile() || $esMio;

        if (! $visible) {

            return view('profiles.show', [
                'creador' => $user,
                'esMio' => false,
                'visible' => false,
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | La Biblioteca
        |--------------------------------------------------------------------------
        */

        $entidades =
            $this->entidadesPublicas($user->entities())
            ->with('entityType')
            ->latest('published_at')
            ->limit(12)
            ->get();

        $colecciones =
            $this->coleccionesPublicas($user->collections())
            ->withCount('entities')
            ->latest('published_at')
            ->limit(6)
            ->get();

        $atributos =
            $this->atributosPublicos($user->attributes())
            ->withCount('options')
            ->latest('published_at')
            ->limit(8)
            ->get();

        $catalogos =
            $this->consultaCatalogos($user)
            ->with('attribute')
            ->latest('attribute_options.created_at')
            ->limit(12)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Los Torneos
        |--------------------------------------------------------------------------
        |
        | Publicada quiere decir tres cosas a la vez -publica, activa y con
        | fecha de publicacion-, y es la misma regla que aplica la comunidad de
        | torneos.
        */

        $torneos =
            TournamentTemplate::query()
            ->where('user_id', $user->id)
            ->where('visibility', 'PUBLIC')
            ->where('status', 'ACTIVE')
            ->whereNotNull('published_at')
            ->withCount(['graphNodes', 'graphTerminals'])
            ->latest('published_at')
            ->limit(8)
            ->get();

        $fases =
            PhaseTemplate::query()
            ->where('user_id', $user->id)
            ->where('visibility', 'PUBLIC')
            ->where('status', 'ACTIVE')
            ->whereNotNull('published_at')
            ->withCount(['exits', 'inputGates'])
            ->latest('published_at')
            ->limit(8)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Las cifras
        |--------------------------------------------------------------------------
        */

        $cifras = [

            'entidades' => $this->entidadesPublicas($user->entities())->count(),
            'colecciones' => $this->coleccionesPublicas($user->collections())->count(),
            'atributos' => $this->atributosPublicos($user->attributes())->count(),
            'catalogos' => $this->consultaCatalogos($user)->count(),

            'torneos' => TournamentTemplate::query()
                ->where('user_id', $user->id)
                ->where('visibility', 'PUBLIC')
                ->where('status', 'ACTIVE')
                ->whereNotNull('published_at')
                ->count(),

            'fases' => PhaseTemplate::query()
                ->where('user_id', $user->id)
                ->where('visibility', 'PUBLIC')
                ->where('status', 'ACTIVE')
                ->whereNotNull('published_at')
                ->count(),
        ];

        $cifras['total'] =
            $cifras['entidades'] + $cifras['colecciones'] + $cifras['atributos']
            + $cifras['catalogos'] + $cifras['torneos'] + $cifras['fases'];

        /*
         * Cuanto se han llevado de aqui. Es la unica cifra de un perfil que no
         * depende de lo que uno haga, sino de lo que le sirva a otro.
         */
        $cifras['copiado'] =
            (int) Entity::query()
            ->whereIn('source_entity_id', $user->entities()->select('id'))
            ->where('user_id', '!=', $user->id)
            ->count()
            + (int) TournamentTemplate::query()
            ->where('user_id', $user->id)
            ->sum('clones_count')
            + (int) PhaseTemplate::query()
            ->where('user_id', $user->id)
            ->sum('clones_count');

        $cifras['vistas'] =
            (int) TournamentTemplate::query()->where('user_id', $user->id)->sum('views_count')
            + (int) PhaseTemplate::query()->where('user_id', $user->id)->sum('views_count');


        /*
         * El mosaico de la portada: sus caras publicas, que es como se
         * reconoce a alguien antes de leer su nombre.
         */
        $mosaico =
            $this->entidadesPublicas($user->entities())
            ->whereNotNull('image')
            ->inRandomOrder()
            ->limit(18)
            ->get(['id', 'name', 'image']);


        return view('profiles.show', [
            'creador' => $user,
            'esMio' => $esMio,
            'visible' => true,
            'cifras' => $cifras,
            'entidades' => $entidades,
            'colecciones' => $colecciones,
            'atributos' => $atributos,
            'catalogos' => $catalogos,
            'torneos' => $torneos,
            'fases' => $fases,
            'mosaico' => $mosaico,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Qué es público
    |--------------------------------------------------------------------------
    |
    | Las mismas tres reglas que aplica la Comunidad. Aquí una sola vez.
    */

    private function entidadesPublicas(Builder|Relation $query): Builder|Relation
    {
        return $query
            ->where('visibility', 'PUBLIC')
            ->where('status', 'ACTIVE')
            ->whereNotNull('published_at');
    }


    private function coleccionesPublicas(Builder|Relation $query): Builder|Relation
    {
        return $query
            ->where('visibility', 'PUBLIC')
            ->where('status', 'ACTIVE')
            ->whereNotNull('published_at');
    }


    private function atributosPublicos(Builder|Relation $query): Builder|Relation
    {
        return $query
            ->where('scope', 'PUBLIC')
            ->where('status', 'ACTIVE')
            ->whereNotNull('published_at');
    }


    /*
     * Un valor de catálogo es público cuando lo es el catálogo que lo
     * contiene: un valor no se publica por su cuenta.
     */
    private function consultaCatalogos(User $user): Builder
    {
        return AttributeOption::query()
            ->where('attribute_options.user_id', $user->id)
            ->where('attribute_options.status', 'ACTIVE')
            ->whereHas(
                'attribute',
                fn($query) => $query
                    ->where('scope', 'PUBLIC')
                    ->where('status', 'ACTIVE')
                    ->whereNotNull('published_at')
            );
    }
}
