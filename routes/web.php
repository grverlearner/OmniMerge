<?php

use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Hub\HubController;

use App\Http\Controllers\Entities\EntityController;
use App\Http\Controllers\EntityTypes\EntityTypeController;
use App\Http\Controllers\ProfileController;

use App\Http\Controllers\Attributes\AttributeController;
use App\Http\Controllers\Attributes\AttributeGroupController;
use App\Http\Controllers\Attributes\AttributeOptionController;
use App\Http\Controllers\Entities\EntityAttributeController;
use App\Http\Controllers\Entities\BulkEntityController;

use App\Http\Controllers\Collections\CollectionController;
use App\Http\Controllers\Community\ExploreController;
use App\Http\Controllers\Community\CreatorController;
use App\Http\Controllers\Entities\BulkEditEntityController;

use App\Http\Controllers\Versions\VersionController;
use App\Http\Controllers\Versions\EntityVersionController;
use App\Http\Controllers\Versions\EntityVersionAttributeController;
use App\Http\Controllers\Versions\BulkEntityVersionController;
use App\Http\Controllers\Versions\VersionWorkspaceController;

use App\Http\Controllers\Attributes\AttributeStructureController;
use App\Http\Controllers\Entities\EntityPresentationController;
use App\Http\Controllers\Entities\EntityBaseVersionController;

use App\Http\Controllers\Tournaments\TournamentDashboardController;
use App\Http\Controllers\Tournaments\TournamentTemplateController;
use App\Http\Controllers\Tournaments\TournamentPhaseController;
use App\Http\Controllers\Tournaments\TournamentLabController;

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')
    ->name('home');

Route::middleware('auth')->group(function () {
    Route::get(
        '/hub',
        HubController::class
    )->name('hub');

    Route::get(
        '/dashboard/search',
        [
            DashboardController::class,
            'search',
        ]
    )->name('dashboard.search');


    Route::get(
        '/dashboard',
        DashboardController::class
    )->name('dashboard');;

    /*
    |--------------------------------------------------------------------------
    | TORNEOS
    |--------------------------------------------------------------------------
    */


    Route::prefix(
        'tournaments'
    )
        ->name(
            'tournaments.'
        )
        ->group(
            function () {


                /*
                |--------------------------------------------------------------------------
                | Dashboard
                |--------------------------------------------------------------------------
                */


                Route::get(
                    '/',
                    TournamentDashboardController::class
                )
                    ->name(
                        'dashboard'
                    );


                /*
                |--------------------------------------------------------------------------
                | Competition Lab
                |--------------------------------------------------------------------------
                |
                | La ruta estática debe ir antes de:
                |
                | templates/{tournamentTemplate}
                |
                */


                Route::get(
                    '/lab',
                    TournamentLabController::class
                )
                    ->name(
                        'lab.index'
                    );


                /*
                |--------------------------------------------------------------------------
                | Plantillas
                |--------------------------------------------------------------------------
                */


                Route::get(
                    '/templates',
                    [
                        TournamentTemplateController::class,
                        'index',
                    ]
                )
                    ->name(
                        'templates.index'
                    );


                Route::get(
                    '/templates/create',
                    [
                        TournamentTemplateController::class,
                        'create',
                    ]
                )
                    ->name(
                        'templates.create'
                    );


                Route::post(
                    '/templates',
                    [
                        TournamentTemplateController::class,
                        'store',
                    ]
                )
                    ->name(
                        'templates.store'
                    );


                Route::get(
                    '/templates/{tournamentTemplate}',
                    [
                        TournamentTemplateController::class,
                        'show',
                    ]
                )
                    ->name(
                        'templates.show'
                    );


                Route::get(
                    '/templates/{tournamentTemplate}/edit',
                    [
                        TournamentTemplateController::class,
                        'edit',
                    ]
                )
                    ->name(
                        'templates.edit'
                    );


                Route::put(
                    '/templates/{tournamentTemplate}',
                    [
                        TournamentTemplateController::class,
                        'update',
                    ]
                )
                    ->name(
                        'templates.update'
                    );


                Route::post(
                    '/templates/{tournamentTemplate}/duplicate',
                    [
                        TournamentTemplateController::class,
                        'duplicate',
                    ]
                )
                    ->name(
                        'templates.duplicate'
                    );


                Route::patch(
                    '/templates/{tournamentTemplate}/archive',
                    [
                        TournamentTemplateController::class,
                        'archive',
                    ]
                )
                    ->name(
                        'templates.archive'
                    );


                Route::delete(
                    '/templates/{tournamentTemplate}',
                    [
                        TournamentTemplateController::class,
                        'destroy',
                    ]
                )
                    ->name(
                        'templates.destroy'
                    );


                /*
                |--------------------------------------------------------------------------
                | Fases
                |--------------------------------------------------------------------------
                */


                Route::get(
                    '/templates/{tournamentTemplate}/phases',
                    [
                        TournamentPhaseController::class,
                        'index',
                    ]
                )
                    ->name(
                        'phases.index'
                    );


                Route::get(
                    '/templates/{tournamentTemplate}/phases/create',
                    [
                        TournamentPhaseController::class,
                        'create',
                    ]
                )
                    ->name(
                        'phases.create'
                    );


                Route::post(
                    '/templates/{tournamentTemplate}/phases',
                    [
                        TournamentPhaseController::class,
                        'store',
                    ]
                )
                    ->name(
                        'phases.store'
                    );


                Route::get(
                    '/templates/{tournamentTemplate}/phases/{phase}/edit',
                    [
                        TournamentPhaseController::class,
                        'edit',
                    ]
                )
                    ->name(
                        'phases.edit'
                    );


                Route::put(
                    '/templates/{tournamentTemplate}/phases/{phase}',
                    [
                        TournamentPhaseController::class,
                        'update',
                    ]
                )
                    ->name(
                        'phases.update'
                    );


                Route::delete(
                    '/templates/{tournamentTemplate}/phases/{phase}',
                    [
                        TournamentPhaseController::class,
                        'destroy',
                    ]
                )
                    ->name(
                        'phases.destroy'
                    );
            }
        );

    Route::resource(
        'entity-types',
        EntityTypeController::class
    );

    Route::get(
        'entities/bulk/create',
        [
            BulkEntityController::class,
            'create',
        ]
    )->name(
        'entities.bulk.create'
    );


    Route::post(
        'entities/bulk',
        [
            BulkEntityController::class,
            'store',
        ]
    )->name(
        'entities.bulk.store'
    );
    /*
    |--------------------------------------------------------------------------
    | Edición masiva de Entidades
    |--------------------------------------------------------------------------
    */

    Route::get(
        'entities/bulk-edit',
        [
            BulkEditEntityController::class,
            'index',
        ]
    )->name(
        'entities.bulk-edit.index'
    );


    Route::post(
        'entities/bulk-edit',
        [
            BulkEditEntityController::class,
            'update',
        ]
    )->name(
        'entities.bulk-edit.update'
    );

    /*
|--------------------------------------------------------------------------
| VERSIONES — WORKSPACE
|--------------------------------------------------------------------------
|
| IMPORTANTE:
| las rutas estáticas van ANTES de versions/{version}.
|
*/

    Route::get(
        'versions/entities',
        [
            VersionWorkspaceController::class,
            'entities',
        ]
    )->name(
        'versions.entities.index'
    );


    Route::get(
        'versions/coverage',
        [
            VersionWorkspaceController::class,
            'coverage',
        ]
    )->name(
        'versions.coverage'
    );


    Route::get(
        'versions/media',
        [
            VersionWorkspaceController::class,
            'media',
        ]
    )->name(
        'versions.media'
    );


    Route::get(
        'versions/resolver',
        [
            VersionWorkspaceController::class,
            'resolver',
        ]
    )->name(
        'versions.resolver'
    );


    /*
|--------------------------------------------------------------------------
| ASOCIACIÓN MASIVA
|--------------------------------------------------------------------------
*/

    Route::get(
        'versions/{version}/entities/bulk',
        [
            BulkEntityVersionController::class,
            'create',
        ]
    )->name(
        'versions.entities.bulk.create'
    );


    Route::post(
        'versions/{version}/entities/bulk',
        [
            BulkEntityVersionController::class,
            'store',
        ]
    )->name(
        'versions.entities.bulk.store'
    );


    /*
|--------------------------------------------------------------------------
| DEFINICIONES
|--------------------------------------------------------------------------
*/

    Route::resource(
        'versions',
        VersionController::class
    );


    /*
|--------------------------------------------------------------------------
| VERSIONES DE UNA ENTIDAD
|--------------------------------------------------------------------------
*/

    Route::get(
        'entities/{entity}/versions',
        [
            EntityVersionController::class,
            'index',
        ]
    )->name(
        'entity-versions.index'
    );


    Route::get(
        'entities/{entity}/versions/create',
        [
            EntityVersionController::class,
            'create',
        ]
    )->name(
        'entity-versions.create'
    );


    Route::post(
        'entities/{entity}/versions',
        [
            EntityVersionController::class,
            'store',
        ]
    )->name(
        'entity-versions.store'
    );


    /*
|--------------------------------------------------------------------------
| COMPARAR
|--------------------------------------------------------------------------
|
| Debe ir antes de:
|
| entities/{entity}/versions/{entityVersion}
|
*/

    Route::get(
        'entities/{entity}/versions/compare',
        [
            VersionWorkspaceController::class,
            'compare',
        ]
    )->name(
        'entity-versions.compare'
    );


    Route::get(
        'entities/{entity}/versions/{entityVersion}',
        [
            EntityVersionController::class,
            'show',
        ]
    )->name(
        'entity-versions.show'
    );


    Route::get(
        'entities/{entity}/versions/{entityVersion}/edit',
        [
            EntityVersionController::class,
            'edit',
        ]
    )->name(
        'entity-versions.edit'
    );


    Route::put(
        'entities/{entity}/versions/{entityVersion}',
        [
            EntityVersionController::class,
            'update',
        ]
    )->name(
        'entity-versions.update'
    );


    /*
|--------------------------------------------------------------------------
| CARACTERÍSTICAS
|--------------------------------------------------------------------------
*/

    Route::get(
        'entities/{entity}/versions/{entityVersion}/attributes',
        [
            EntityVersionAttributeController::class,
            'edit',
        ]
    )->name(
        'entity-versions.attributes.edit'
    );


    Route::put(
        'entities/{entity}/versions/{entityVersion}/attributes',
        [
            EntityVersionAttributeController::class,
            'update',
        ]
    )->name(
        'entity-versions.attributes.update'
    );


    /*
|--------------------------------------------------------------------------
| MULTIMEDIA
|--------------------------------------------------------------------------
*/

    Route::post(
        'entities/{entity}/versions/{entityVersion}/images',
        [
            EntityVersionController::class,
            'storeImages',
        ]
    )->name(
        'entity-versions.images.store'
    );


    Route::patch(
        'entities/{entity}/versions/{entityVersion}/images/reorder',
        [
            EntityVersionController::class,
            'reorderImages',
        ]
    )->name(
        'entity-versions.images.reorder'
    );


    Route::patch(
        'entities/{entity}/versions/{entityVersion}/images/{image}',
        [
            EntityVersionController::class,
            'updateImage',
        ]
    )->name(
        'entity-versions.images.update'
    );


    Route::post(
        'entities/{entity}/versions/{entityVersion}/images/{image}/primary',
        [
            EntityVersionController::class,
            'makeImagePrimary',
        ]
    )->name(
        'entity-versions.images.primary'
    );


    Route::delete(
        'entities/{entity}/versions/{entityVersion}/images/{image}',
        [
            EntityVersionController::class,
            'destroyImage',
        ]
    )->name(
        'entity-versions.images.destroy'
    );

    /*
    |--------------------------------------------------------------------------
    | Presentación pública de Entidades
    |--------------------------------------------------------------------------
    */

    Route::get(
        'entities/{entity}/presentation',
        [
            EntityPresentationController::class,
            'edit',
        ]
    )->name(
        'entities.presentation.edit'
    );


    Route::put(
        'entities/{entity}/presentation',
        [
            EntityPresentationController::class,
            'update',
        ]
    )->name(
        'entities.presentation.update'
    );

    /*
|--------------------------------------------------------------------------
| Base activa de una Entidad
|--------------------------------------------------------------------------
*/

    Route::put(
        'entities/{entity}/base-version',
        [
            EntityBaseVersionController::class,
            'update',
        ]
    )->name(
        'entities.base-version.update'
    );


    Route::delete(
        'entities/{entity}/base-version',
        [
            EntityBaseVersionController::class,
            'destroy',
        ]
    )->name(
        'entities.base-version.destroy'
    );

    Route::resource(
        'entities',
        EntityController::class
    );

    Route::get('/profile', [
        ProfileController::class,
        'edit',
    ])->name('profile.edit');

    Route::patch('/profile', [
        ProfileController::class,
        'update',
    ])->name('profile.update');

    Route::delete('/profile', [
        ProfileController::class,
        'destroy',
    ])->name('profile.destroy');

    /*
|--------------------------------------------------------------------------
| Estructura contextual de Atributos
|--------------------------------------------------------------------------
|
| IMPORTANTE:
| Deben ir antes de attributes/{attribute}.
|
*/

    Route::get(
        'attributes/structure',
        [
            AttributeStructureController::class,
            'index',
        ]
    )->name(
        'attributes.structure.index'
    );


    Route::post(
        'attributes/structure/rules',
        [
            AttributeStructureController::class,
            'storeRule',
        ]
    )->name(
        'attributes.structure.rules.store'
    );


    Route::delete(
        'attributes/structure/rules/{rule}',
        [
            AttributeStructureController::class,
            'destroyRule',
        ]
    )->name(
        'attributes.structure.rules.destroy'
    );


    Route::post(
        'attributes/structure/options',
        [
            AttributeStructureController::class,
            'storeOptionRelationship',
        ]
    )->name(
        'attributes.structure.options.store'
    );


    Route::delete(
        'attributes/structure/options/{relationship}',
        [
            AttributeStructureController::class,
            'destroyOptionRelationship',
        ]
    )->name(
        'attributes.structure.options.destroy'
    );

    Route::resource(
        'attributes',
        AttributeController::class
    );

    Route::resource(
        'attribute-groups',
        AttributeGroupController::class
    );

    Route::get(
        'entities/{entity}/attributes',
        [EntityAttributeController::class, 'edit']
    )->name('entities.attributes.edit');

    Route::put(
        'entities/{entity}/attributes',
        [EntityAttributeController::class, 'update']
    )->name('entities.attributes.update');

    Route::resource(
        'collections',
        CollectionController::class
    );

    Route::get(
        'attribute-options',
        [AttributeOptionController::class, 'index']
    )->name('attribute-options.index');

    Route::get(
        'attribute-options/create',
        [AttributeOptionController::class, 'create']
    )->name('attribute-options.create');

    Route::get(
        'attribute-options/{attributeOption}',
        [AttributeOptionController::class, 'show']
    )->name('attribute-options.show');

    Route::get(
        'attribute-options/{attributeOption}/edit',
        [AttributeOptionController::class, 'edit']
    )->name('attribute-options.edit');

    Route::post(
        'attributes/{attribute}/options',
        [AttributeOptionController::class, 'store']
    )->name('attributes.options.store');

    Route::put(
        'attributes/{attribute}/options/{option}',
        [AttributeOptionController::class, 'update']
    )->name('attributes.options.update');

    Route::delete(
        'attribute-options/{attributeOption}',
        [AttributeOptionController::class, 'destroy']
    )->name('attribute-options.destroy');

    Route::prefix('explore')
        ->name('community.')
        ->group(function () {

            /*
        |--------------------------------------------------------------------------
        | Explorador
        |--------------------------------------------------------------------------
        */

            Route::get(
                '/',
                [
                    ExploreController::class,
                    'index',
                ]
            )->name('index');


            Route::get(
                '/search',
                [
                    ExploreController::class,
                    'search',
                ]
            )->name('search');


            /*
        |--------------------------------------------------------------------------
        | Creadores
        |--------------------------------------------------------------------------
        */

            Route::get(
                '/creators/{user:username}',
                [
                    CreatorController::class,
                    'show',
                ]
            )->name('creators.show');


            /*
        |--------------------------------------------------------------------------
        | Recursos públicos
        |--------------------------------------------------------------------------
        */

            Route::get(
                '/entities/{entity}',
                [
                    ExploreController::class,
                    'entity',
                ]
            )->name('entities.show');


            Route::get(
                '/collections/{collection}',
                [
                    ExploreController::class,
                    'collection',
                ]
            )->name('collections.show');


            Route::get(
                '/attributes/{attribute}',
                [
                    ExploreController::class,
                    'attribute',
                ]
            )->name('attributes.show');


            Route::get(
                '/catalogs/{attributeOption}',
                [
                    ExploreController::class,
                    'catalog',
                ]
            )->name('catalogs.show');


            /*
        |--------------------------------------------------------------------------
        | Copiar
        |--------------------------------------------------------------------------
        */

            Route::post(
                '/entities/{entity}/clone',
                [
                    ExploreController::class,
                    'cloneEntity',
                ]
            )->name('entities.clone');


            Route::post(
                '/collections/{collection}/clone',
                [
                    ExploreController::class,
                    'cloneCollection',
                ]
            )->name('collections.clone');


            Route::post(
                '/attributes/{attribute}/clone',
                [
                    ExploreController::class,
                    'cloneAttribute',
                ]
            )->name('attributes.clone');


            Route::post(
                '/catalogs/{attributeOption}/clone',
                [
                    ExploreController::class,
                    'cloneCatalog',
                ]
            )->name('catalogs.clone');
        });
});

require __DIR__ . '/auth.php';
