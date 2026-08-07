<?php

namespace App\Http\Controllers\Hub;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HubController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Estadísticas generales
        |--------------------------------------------------------------------------
        |
        | Estas estadísticas resumen actualmente la Biblioteca del usuario.
        | Más adelante podremos agregar universos, torneos, simulaciones, etc.
        |
        */

        $publicContent =
            $user->entities()
            ->where('visibility', 'PUBLIC')
            ->count()
            +
            $user->collections()
            ->where('visibility', 'PUBLIC')
            ->count()
            +
            $user->attributes()
            ->where('scope', 'PUBLIC')
            ->count();

        $statistics = [
            'entities' => $user->entities()->count(),

            'attributes' => $user->attributes()->count(),

            'collections' => $user->collections()->count(),

            'public_content' => $publicContent,
        ];


        /*
        |--------------------------------------------------------------------------
        | Actividad reciente
        |--------------------------------------------------------------------------
        |
        | Tomamos las últimas entidades, atributos y colecciones creadas y las
        | combinamos en una sola lista.
        |
        */

        $recentEntities = $user->entities()
            ->latest()
            ->limit(4)
            ->get()
            ->map(function ($entity) {
                return [
                    'type' => 'Entidad',

                    'name' => $entity->name,

                    'icon' => '✦',

                    'description' =>
                    'Entidad creada en tu biblioteca.',

                    'url' => route(
                        'entities.show',
                        $entity
                    ),

                    'created_at' =>
                    $entity->created_at,
                ];
            });


        $recentAttributes = $user->attributes()
            ->latest()
            ->limit(4)
            ->get()
            ->map(function ($attribute) {
                return [
                    'type' => 'Atributo',

                    'name' => $attribute->name,

                    'icon' => '☷',

                    'description' =>
                    'Atributo disponible para tus entidades.',

                    'url' => route(
                        'attributes.show',
                        $attribute
                    ),

                    'created_at' =>
                    $attribute->created_at,
                ];
            });


        $recentCollections = $user->collections()
            ->latest()
            ->limit(4)
            ->get()
            ->map(function ($collection) {
                return [
                    'type' => 'Colección',

                    'name' => $collection->name,

                    'icon' => '▤',

                    'description' =>
                    'Colección creada para organizar entidades.',

                    'url' => route(
                        'collections.show',
                        $collection
                    ),

                    'created_at' =>
                    $collection->created_at,
                ];
            });


        $recentActivity = collect()
            ->concat($recentEntities)
            ->concat($recentAttributes)
            ->concat($recentCollections)
            ->sortByDesc('created_at')
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
