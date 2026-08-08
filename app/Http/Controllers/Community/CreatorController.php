<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CreatorController extends Controller
{
    public function show(
        Request $request,
        User $user
    ): View {

        /*
         * El propietario siempre puede previsualizar su propio perfil.
         */

        $isOwner =
            $request
            ->user()
            ->is($user);


        /*
         * Un usuario inactivo no debe aparecer públicamente.
         */

        abort_unless(
            $user->isActive(),
            404
        );


        /*
         * Un perfil privado solo puede verlo su propietario.
         */

        abort_unless(
            $user->isPublicProfile()
                || $isOwner,
            404
        );


        /*
         * Estadísticas exclusivamente públicas.
         */

        $user->loadCount([

            'entities as public_entities_count'
            => fn($query) =>
            $query
                ->where(
                    'visibility',
                    'PUBLIC'
                )
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->whereNotNull(
                    'published_at'
                ),


            'collections as public_collections_count'
            => fn($query) =>
            $query
                ->where(
                    'visibility',
                    'PUBLIC'
                )
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->whereNotNull(
                    'published_at'
                ),


            'attributes as public_attributes_count'
            => fn($query) =>
            $query
                ->where(
                    'scope',
                    'PUBLIC'
                )
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->whereNotNull(
                    'published_at'
                ),
        ]);


        /*
         * Entidades públicas del creador.
         */

        $entities =
            $user
            ->entities()
            ->where(
                'visibility',
                'PUBLIC'
            )
            ->where(
                'status',
                'ACTIVE'
            )
            ->whereNotNull(
                'published_at'
            )
            ->with([
                'creator',
                'entityType',
            ])
            ->latest('published_at')
            ->limit(6)
            ->get();


        /*
         * Colecciones públicas.
         */

        $collections =
            $user
            ->collections()
            ->where(
                'visibility',
                'PUBLIC'
            )
            ->where(
                'status',
                'ACTIVE'
            )
            ->whereNotNull(
                'published_at'
            )
            ->with('creator')
            ->withCount([
                'entities' => fn($query) =>
                $query
                    ->where(
                        'entities.visibility',
                        'PUBLIC'
                    )
                    ->where(
                        'entities.status',
                        'ACTIVE'
                    )
                    ->whereNotNull(
                        'entities.published_at'
                    ),
            ])
            ->latest('published_at')
            ->limit(6)
            ->get();


        /*
         * Atributos públicos.
         */

        $attributes =
            $user
            ->attributes()
            ->where(
                'scope',
                'PUBLIC'
            )
            ->where(
                'status',
                'ACTIVE'
            )
            ->whereNotNull(
                'published_at'
            )
            ->with('creator')
            ->withCount('options')
            ->latest('published_at')
            ->limit(6)
            ->get();


        return view(
            'community.creator',
            compact(
                'user',
                'entities',
                'collections',
                'attributes',
                'isOwner'
            )
        );
    }
}
