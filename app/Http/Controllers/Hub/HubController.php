<?php

namespace App\Http\Controllers\Hub;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;


class HubController extends Controller
{
    public function __invoke(
        Request $request
    ): View {

        $user =
            $request->user();


        /*
        |--------------------------------------------------------------------------
        | Estadísticas generales
        |--------------------------------------------------------------------------
        */


        $publicContent =

            $user->entities()
            ->where(
                'visibility',
                'PUBLIC'
            )
            ->count()

            +

            $user->collections()
            ->where(
                'visibility',
                'PUBLIC'
            )
            ->count()

            +

            $user->attributes()
            ->where(
                'scope',
                'PUBLIC'
            )
            ->count();


        $statistics = [

            'entities' =>
            $user->entities()
                ->count(),

            'attributes' =>
            $user->attributes()
                ->count(),

            'collections' =>
            $user->collections()
                ->count(),

            'tournaments' =>
            $user->tournamentTemplates()
                ->count(),

            'universes' =>
            $user->universes()
                ->count(),

            /*
             * Todavía no incluimos TournamentTemplates
             * en public_content porque Comunidad de
             * Torneos aún no ha sido implementada.
             */
            'public_content' =>
            $publicContent,
        ];


        /*
        |--------------------------------------------------------------------------
        | Entidades recientes
        |--------------------------------------------------------------------------
        */


        $recentEntities =
            $user->entities()
            ->latest()
            ->limit(4)
            ->get()
            ->map(
                function ($entity) {

                    return [

                        'type' =>
                        'Entidad',

                        'name' =>
                        $entity->name,

                        'icon' =>
                        '✦',

                        'description' =>
                        'Entidad creada en tu biblioteca.',

                        'url' =>
                        route(
                            'entities.show',
                            $entity
                        ),

                        'created_at' =>
                        $entity->created_at,
                    ];
                }
            );


        /*
        |--------------------------------------------------------------------------
        | Atributos recientes
        |--------------------------------------------------------------------------
        */


        $recentAttributes =
            $user->attributes()
            ->latest()
            ->limit(4)
            ->get()
            ->map(
                function ($attribute) {

                    return [

                        'type' =>
                        'Atributo',

                        'name' =>
                        $attribute->name,

                        'icon' =>
                        '☷',

                        'description' =>
                        'Atributo disponible para tus entidades.',

                        'url' =>
                        route(
                            'attributes.show',
                            $attribute
                        ),

                        'created_at' =>
                        $attribute->created_at,
                    ];
                }
            );


        /*
        |--------------------------------------------------------------------------
        | Colecciones recientes
        |--------------------------------------------------------------------------
        */


        $recentCollections =
            $user->collections()
            ->latest()
            ->limit(4)
            ->get()
            ->map(
                function ($collection) {

                    return [

                        'type' =>
                        'Colección',

                        'name' =>
                        $collection->name,

                        'icon' =>
                        '▤',

                        'description' =>
                        'Colección creada para organizar entidades.',

                        'url' =>
                        route(
                            'collections.show',
                            $collection
                        ),

                        'created_at' =>
                        $collection->created_at,
                    ];
                }
            );


        /*
        |--------------------------------------------------------------------------
        | Plantillas de Torneo recientes
        |--------------------------------------------------------------------------
        */


        $recentTournamentTemplates =
            $user->tournamentTemplates()
            ->latest()
            ->limit(4)
            ->get()
            ->map(
                function ($template) {

                    return [

                        'type' =>
                        'Torneo',

                        'name' =>
                        $template->name,

                        'icon' =>
                        '🏆',

                        'description' =>
                        'Plantilla competitiva creada en Tournament Designer.',

                        'url' =>
                        route(
                            'tournaments.templates.show',
                            $template
                        ),

                        'created_at' =>
                        $template->created_at,
                    ];
                }
            );


        /*
        |--------------------------------------------------------------------------
        | Actividad combinada
        |--------------------------------------------------------------------------
        */


        $recentActivity =
            collect()
            ->concat(
                $recentEntities
            )
            ->concat(
                $recentAttributes
            )
            ->concat(
                $recentCollections
            )
            ->concat(
                $recentTournamentTemplates
            )
            ->sortByDesc(
                'created_at'
            )
            ->take(6)
            ->values();


        return view(
            'hub.index',
            compact(
                'statistics',
                'recentActivity'
            )
        );
    }
}
