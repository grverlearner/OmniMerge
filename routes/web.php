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
use App\Http\Controllers\Tournaments\TournamentLabController;
use App\Http\Controllers\Universes\UniverseController;
use App\Http\Controllers\Universes\UniverseDashboardController;
use App\Http\Controllers\Universes\UniverseEntityController;
use App\Http\Controllers\Universes\UniverseSeasonController;
use App\Http\Controllers\Universes\UniverseTournamentController;
use App\Http\Controllers\Universes\TournamentInstanceController;
use App\Http\Controllers\Universes\UniverseHistoryController;
use App\Http\Controllers\Universes\UniverseRankingController;
use App\Http\Controllers\Universes\UniverseExplorerController;

use App\Http\Controllers\Tournaments\PhaseTemplateController;
use App\Http\Controllers\Tournaments\PhaseExitController;
use App\Http\Controllers\Tournaments\SingleEliminationController;
use App\Http\Controllers\Tournaments\SingleEliminationRoundRuleController;
use App\Http\Controllers\Tournaments\RoundRobinController;
use App\Http\Controllers\Tournaments\RoundRobinSimulatorController;
use App\Http\Controllers\Tournaments\RoundRobinTiebreakerController;
use App\Http\Controllers\Tournaments\GroupStageController;
use App\Http\Controllers\Tournaments\GroupStageSimulatorController;
use App\Http\Controllers\Tournaments\GroupStageGroupController;
use App\Http\Controllers\Tournaments\GroupStageAdvancementRuleController;
use App\Http\Controllers\Tournaments\GroupStageTiebreakerController;
use App\Http\Controllers\Tournaments\SwissController;
use App\Http\Controllers\Tournaments\SwissTiebreakerController;
use App\Http\Controllers\Tournaments\SwissRoundRuleController;
use App\Http\Controllers\Tournaments\SwissAdvancementRuleController;

use App\Http\Controllers\Tournaments\TournamentGraphController;
use App\Http\Controllers\Tournaments\TournamentPhaseNodeController;
use App\Http\Controllers\Tournaments\PhaseEntryPortController;
use App\Http\Controllers\Tournaments\PhaseInputGateController;
use App\Http\Controllers\Tournaments\TournamentStartController;
use App\Http\Controllers\Tournaments\TournamentTerminalController;
use App\Http\Controllers\Tournaments\TournamentPhaseConnectionController;
use App\Http\Controllers\Tournaments\TournamentGraphPresetController;
use App\Http\Controllers\Tournaments\TournamentFlowPreviewController;

use App\Http\Controllers\Tournaments\SingleEliminationSimulatorController;
use App\Http\Controllers\Tournaments\SingleEliminationStructureController;
use App\Http\Controllers\Tournaments\SingleEliminationGraphController;

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
    | UNIVERSOS
    |--------------------------------------------------------------------------
    |
    | Módulo propio, con su propia interfaz y sidebar. Un Universo es el
    | contenedor donde las Entidades de la Biblioteca adquieren contexto
    | competitivo (Competidores), el Universo adquiere tiempo propio
    | (Temporadas) y adopta plantillas de torneo (Torneos).
    |
    | Ver docs/md/23-Fase-Universos-Workspace.md
    |
    */


    Route::prefix(
        'universes'
    )
        ->name(
            'universes.'
        )
        ->group(
            function () {


                /*
                |--------------------------------------------------------------------------
                | Módulo
                |--------------------------------------------------------------------------
                |
                | Las rutas estáticas van antes de /{universe}.
                |
                */


                Route::get(
                    '/',
                    [
                        UniverseController::class,
                        'index',
                    ]
                )->name('index');

                Route::get(
                    '/dashboard',
                    UniverseDashboardController::class
                )->name('dashboard');

                Route::get(
                    '/create',
                    [
                        UniverseController::class,
                        'create',
                    ]
                )->name('create');

                Route::post(
                    '/',
                    [
                        UniverseController::class,
                        'store',
                    ]
                )->name('store');


                /*
                |--------------------------------------------------------------------------
                | Contenido de un Universo
                |--------------------------------------------------------------------------
                |
                | scopeBindings() garantiza que el hijo pertenezca al
                | Universo de la URL: si no, responde 404 en vez de
                | permitir acceso cruzado entre Universos.
                |
                */


                Route::prefix('/{universe}')
                    ->scopeBindings()
                    ->group(
                        function () {


                            /*
                            | Competidores
                            */

                            Route::prefix('entities')
                                ->name('entities.')
                                ->group(
                                    function () {

                                        Route::get(
                                            '/',
                                            [
                                                UniverseEntityController::class,
                                                'index',
                                            ]
                                        )->name('index');

                                        Route::get(
                                            '/create',
                                            [
                                                UniverseEntityController::class,
                                                'create',
                                            ]
                                        )->name('create');

                                        Route::post(
                                            '/',
                                            [
                                                UniverseEntityController::class,
                                                'store',
                                            ]
                                        )->name('store');

                                        Route::put(
                                            '/{entity}',
                                            [
                                                UniverseEntityController::class,
                                                'update',
                                            ]
                                        )->name('update');

                                        Route::delete(
                                            '/{entity}',
                                            [
                                                UniverseEntityController::class,
                                                'destroy',
                                            ]
                                        )->name('destroy');

                                        Route::get(
                                            '/{entity}/head-to-head',
                                            [
                                                UniverseEntityController::class,
                                                'headToHead',
                                            ]
                                        )->name('head-to-head');

                                        /*
                                         * Al final: si estuviera antes
                                         * capturaria /create.
                                         */
                                        Route::get(
                                            '/{entity}',
                                            [
                                                UniverseEntityController::class,
                                                'show',
                                            ]
                                        )->name('show');
                                    }
                                );


                            /*
                            | Temporadas
                            */

                            Route::prefix('seasons')
                                ->name('seasons.')
                                ->group(
                                    function () {

                                        Route::get(
                                            '/',
                                            [
                                                UniverseSeasonController::class,
                                                'index',
                                            ]
                                        )->name('index');

                                        Route::get(
                                            '/create',
                                            [
                                                UniverseSeasonController::class,
                                                'create',
                                            ]
                                        )->name('create');

                                        Route::post(
                                            '/',
                                            [
                                                UniverseSeasonController::class,
                                                'store',
                                            ]
                                        )->name('store');

                                        Route::get(
                                            '/{season}/edit',
                                            [
                                                UniverseSeasonController::class,
                                                'edit',
                                            ]
                                        )->name('edit');

                                        Route::put(
                                            '/{season}',
                                            [
                                                UniverseSeasonController::class,
                                                'update',
                                            ]
                                        )->name('update');

                                        Route::patch(
                                            '/{season}/activate',
                                            [
                                                UniverseSeasonController::class,
                                                'activate',
                                            ]
                                        )->name('activate');

                                        Route::patch(
                                            '/{season}/complete',
                                            [
                                                UniverseSeasonController::class,
                                                'complete',
                                            ]
                                        )->name('complete');

                                        Route::patch(
                                            '/{season}/archive',
                                            [
                                                UniverseSeasonController::class,
                                                'archive',
                                            ]
                                        )->name('archive');

                                        Route::delete(
                                            '/{season}',
                                            [
                                                UniverseSeasonController::class,
                                                'destroy',
                                            ]
                                        )->name('destroy');

                                        /*
                                         * Al final: antes capturaria
                                         * /create como si fuera {season}.
                                         */
                                        Route::get(
                                            '/{season}',
                                            [
                                                UniverseSeasonController::class,
                                                'show',
                                            ]
                                        )->name('show');
                                    }
                                );


                            /*
                            | Torneos del Universo
                            */

                            Route::prefix('tournaments')
                                ->name('tournaments.')
                                ->group(
                                    function () {

                                        Route::get(
                                            '/',
                                            [
                                                UniverseTournamentController::class,
                                                'index',
                                            ]
                                        )->name('index');

                                        Route::get(
                                            '/create',
                                            [
                                                UniverseTournamentController::class,
                                                'create',
                                            ]
                                        )->name('create');

                                        Route::post(
                                            '/',
                                            [
                                                UniverseTournamentController::class,
                                                'store',
                                            ]
                                        )->name('store');

                                        Route::get(
                                            '/{universeTournament}/edit',
                                            [
                                                UniverseTournamentController::class,
                                                'edit',
                                            ]
                                        )->name('edit');

                                        Route::put(
                                            '/{universeTournament}',
                                            [
                                                UniverseTournamentController::class,
                                                'update',
                                            ]
                                        )->name('update');

                                        Route::delete(
                                            '/{universeTournament}',
                                            [
                                                UniverseTournamentController::class,
                                                'destroy',
                                            ]
                                        )->name('destroy');

                                        /*
                                         * Va al final: si estuviera antes
                                         * capturaría /create como si fuera
                                         * un {universeTournament}.
                                         */
                                        Route::get(
                                            '/{universeTournament}',
                                            [
                                                UniverseTournamentController::class,
                                                'show',
                                            ]
                                        )->name('show');
                                    }
                                );


                            /*
                            | Competiciones reales (Tournament Runtime persistente)
                            |
                            | Ver docs/md/24-Fase-6-Tournament-Runtime-Persistente.md
                            */

                            Route::prefix('competitions')
                                ->name('competitions.')
                                ->group(
                                    function () {

                                        Route::get(
                                            '/',
                                            [
                                                TournamentInstanceController::class,
                                                'index',
                                            ]
                                        )->name('index');

                                        Route::get(
                                            '/create',
                                            [
                                                TournamentInstanceController::class,
                                                'create',
                                            ]
                                        )->name('create');

                                        Route::post(
                                            '/',
                                            [
                                                TournamentInstanceController::class,
                                                'store',
                                            ]
                                        )->name('store');

                                        Route::get(
                                            '/{competition}',
                                            [
                                                TournamentInstanceController::class,
                                                'show',
                                            ]
                                        )->name('show');

                                        /*
                                         * Motor: cada acción persiste el
                                         * estado en base de datos.
                                         */
                                        Route::post(
                                            '/{competition}/action',
                                            [
                                                TournamentInstanceController::class,
                                                'action',
                                            ]
                                        )->name('action');

                                        Route::patch(
                                            '/{competition}/pause',
                                            [
                                                TournamentInstanceController::class,
                                                'pause',
                                            ]
                                        )->name('pause');

                                        Route::patch(
                                            '/{competition}/resume',
                                            [
                                                TournamentInstanceController::class,
                                                'resume',
                                            ]
                                        )->name('resume');

                                        Route::patch(
                                            '/{competition}/cancel',
                                            [
                                                TournamentInstanceController::class,
                                                'cancel',
                                            ]
                                        )->name('cancel');

                                        Route::delete(
                                            '/{competition}',
                                            [
                                                TournamentInstanceController::class,
                                                'destroy',
                                            ]
                                        )->name('destroy');
                                    }
                                );


                            /*
                            | Historial (Fase 8)
                            */

                            Route::get(
                                '/history',
                                [
                                    UniverseHistoryController::class,
                                    'index',
                                ]
                            )->name('history');


                            /*
                            | Clasificacion y exploracion (Fase 10)
                            */

                            Route::get(
                                '/ranking',
                                [
                                    UniverseRankingController::class,
                                    'index',
                                ]
                            )->name('ranking');

                            Route::put(
                                '/ranking/points',
                                [
                                    UniverseRankingController::class,
                                    'updatePoints',
                                ]
                            )->name('ranking.points');

                            Route::get(
                                '/explorer',
                                [
                                    UniverseExplorerController::class,
                                    'index',
                                ]
                            )->name('explorer');


                            /*
                            | El propio Universo
                            */

                            Route::get(
                                '/edit',
                                [
                                    UniverseController::class,
                                    'edit',
                                ]
                            )->name('edit');

                            Route::get(
                                '/',
                                [
                                    UniverseController::class,
                                    'show',
                                ]
                            )->name('show');

                            Route::put(
                                '/',
                                [
                                    UniverseController::class,
                                    'update',
                                ]
                            )->name('update');

                            Route::patch(
                                '/archive',
                                [
                                    UniverseController::class,
                                    'archive',
                                ]
                            )->name('archive');

                            Route::delete(
                                '/',
                                [
                                    UniverseController::class,
                                    'destroy',
                                ]
                            )->name('destroy');
                        }
                    );
            }
        );


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
                    [
                        TournamentLabController::class,
                        'index',
                    ]
                )->name(
                    'lab.index'
                );

                Route::get(
                    '/templates/{tournamentTemplate}/lab',
                    [
                        TournamentLabController::class,
                        'show',
                    ]
                )->name(
                    'lab.show'
                );

                Route::post(
                    '/templates/{tournamentTemplate}/lab/initialize',
                    [
                        TournamentLabController::class,
                        'initialize',
                    ]
                )->name(
                    'lab.initialize'
                );

                Route::post(
                    '/templates/{tournamentTemplate}/lab/action',
                    [
                        TournamentLabController::class,
                        'action',
                    ]
                )->name(
                    'lab.action'
                );

                /*
                |--------------------------------------------------------------------------
                | Biblioteca de Fases
                |--------------------------------------------------------------------------
                |
                | Estas Fases son reutilizables.
                | NO pertenecen todavía a un TournamentTemplate.
                |
                */

                Route::get(
                    '/phases',
                    [
                        PhaseTemplateController::class,
                        'index',
                    ]
                )->name(
                    'phase-templates.index'
                );

                Route::get(
                    '/phases/create',
                    [
                        PhaseTemplateController::class,
                        'create',
                    ]
                )->name(
                    'phase-templates.create'
                );

                Route::post(
                    '/phases',
                    [
                        PhaseTemplateController::class,
                        'store',
                    ]
                )->name(
                    'phase-templates.store'
                );

                Route::get(
                    '/phases/{phaseTemplate}',
                    [
                        PhaseTemplateController::class,
                        'show',
                    ]
                )->name(
                    'phase-templates.show'
                );

                Route::get(
                    '/phases/{phaseTemplate}/edit',
                    [
                        PhaseTemplateController::class,
                        'edit',
                    ]
                )->name(
                    'phase-templates.edit'
                );

                Route::put(
                    '/phases/{phaseTemplate}',
                    [
                        PhaseTemplateController::class,
                        'update',
                    ]
                )->name(
                    'phase-templates.update'
                );

                Route::post(
                    '/phases/{phaseTemplate}/duplicate',
                    [
                        PhaseTemplateController::class,
                        'duplicate',
                    ]
                )->name(
                    'phase-templates.duplicate'
                );

                Route::patch(
                    '/phases/{phaseTemplate}/archive',
                    [
                        PhaseTemplateController::class,
                        'archive',
                    ]
                )->name(
                    'phase-templates.archive'
                );

                Route::delete(
                    '/phases/{phaseTemplate}',
                    [
                        PhaseTemplateController::class,
                        'destroy',
                    ]
                )->name(
                    'phase-templates.destroy'
                );

                /*
                |--------------------------------------------------------------------------
                | Puertas de salida
                |--------------------------------------------------------------------------
                */

                Route::post(
                    '/phases/{phaseTemplate}/exits',
                    [
                        PhaseExitController::class,
                        'store',
                    ]
                )->name(
                    'phase-exits.store'
                );

                Route::put(
                    '/phases/{phaseTemplate}/exits/{phaseExit}',
                    [
                        PhaseExitController::class,
                        'update',
                    ]
                )->name(
                    'phase-exits.update'
                );

                Route::delete(
                    '/phases/{phaseTemplate}/exits/{phaseExit}',
                    [
                        PhaseExitController::class,
                        'destroy',
                    ]
                )->name(
                    'phase-exits.destroy'
                );

                /*
                |--------------------------------------------------------------------------
                | SINGLE ELIMINATION ENGINE
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/phases/{phaseTemplate}/single-elimination',
                    [
                        SingleEliminationController::class,
                        'show',
                    ]
                )->name(
                    'single-elimination.show'
                );

                Route::put(
                    '/phases/{phaseTemplate}/single-elimination',
                    [
                        SingleEliminationController::class,
                        'update',
                    ]
                )->name(
                    'single-elimination.update'
                );

                Route::post(
                    '/phases/{phaseTemplate}/single-elimination/preview',
                    [
                        SingleEliminationController::class,
                        'preview',
                    ]
                )->name(
                    'single-elimination.preview'
                );

                /*
                |--------------------------------------------------------------------------
                | Estructura interna de Eliminación Simple
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/phases/{phaseTemplate}/single-elimination/structure',
                    [
                        SingleEliminationStructureController::class,
                        'show',
                    ]
                )->name(
                    'single-elimination.structure.show'
                );
                Route::get(
                    '/phases/{phaseTemplate}/single-elimination/structure/io',
                    [
                        SingleEliminationStructureController::class,
                        'io',
                    ]
                )->name(
                    'single-elimination.structure.io'
                );

                Route::post(
                    '/phases/{phaseTemplate}/single-elimination/structure/generate',
                    [
                        SingleEliminationStructureController::class,
                        'generate',
                    ]
                )->name(
                    'single-elimination.structure.generate'
                );

                Route::post(
                    '/phases/{phaseTemplate}/single-elimination/structure/validate',
                    [
                        SingleEliminationStructureController::class,
                        'validateStructure',
                    ]
                )->name(
                    'single-elimination.structure.validate'
                );

                Route::put(
                    '/phases/{phaseTemplate}/single-elimination/structure/elements/{elementType}/{element}',
                    [
                        SingleEliminationStructureController::class,
                        'updateElement',
                    ]
                )
                    ->whereIn(
                        'elementType',
                        [
                            'INPUT_GATE',
                            'ROUND',
                            'ENCOUNTER',
                            'SLOT',
                            'RESULT',
                            'CONNECTION',
                            'PHASE_EXIT',
                        ]
                    )
                    ->whereNumber(
                        'element'
                    )
                    ->name(
                        'single-elimination.structure.elements.update'
                    );

                /*
                |--------------------------------------------------------------------------
                | Simulador de Eliminación Simple
                |--------------------------------------------------------------------------
                |
                | Prueba la configuración real de la fase con participantes
                | ficticios, sin depender de un TournamentTemplate. Reutiliza
                | los mismos motores que el Competition Lab; no persiste nada.
                |
                */

                Route::get(
                    '/phases/{phaseTemplate}/single-elimination/simulator',
                    [
                        SingleEliminationSimulatorController::class,
                        'show',
                    ]
                )->name(
                    'single-elimination.simulator.show'
                );

                Route::post(
                    '/phases/{phaseTemplate}/single-elimination/simulator/initialize',
                    [
                        SingleEliminationSimulatorController::class,
                        'initialize',
                    ]
                )->name(
                    'single-elimination.simulator.initialize'
                );

                Route::post(
                    '/phases/{phaseTemplate}/single-elimination/simulator/action',
                    [
                        SingleEliminationSimulatorController::class,
                        'action',
                    ]
                )->name(
                    'single-elimination.simulator.action'
                );

                /*
                |--------------------------------------------------------------------------
                | Diseñador de grafo interno personalizado
                |--------------------------------------------------------------------------
                */

                Route::post(
                    '/phases/{phaseTemplate}/single-elimination/structure/custom/initialize',
                    [SingleEliminationGraphController::class, 'initialize']
                )->name('single-elimination.graph.initialize');

                Route::post(
                    '/phases/{phaseTemplate}/single-elimination/structure/custom/stages',
                    [SingleEliminationGraphController::class, 'storeStage']
                )->name('single-elimination.graph.stages.store');

                Route::delete(
                    '/phases/{phaseTemplate}/single-elimination/structure/custom/stages/{round}',
                    [SingleEliminationGraphController::class, 'destroyStage']
                )->name('single-elimination.graph.stages.destroy');

                Route::post(
                    '/phases/{phaseTemplate}/single-elimination/structure/custom/encounters',
                    [SingleEliminationGraphController::class, 'storeEncounter']
                )->name('single-elimination.graph.encounters.store');

                Route::put(
                    '/phases/{phaseTemplate}/single-elimination/structure/custom/encounters/{encounter}',
                    [SingleEliminationGraphController::class, 'updateEncounter']
                )->name('single-elimination.graph.encounters.update');

                Route::delete(
                    '/phases/{phaseTemplate}/single-elimination/structure/custom/encounters/{encounter}',
                    [SingleEliminationGraphController::class, 'destroyEncounter']
                )->name('single-elimination.graph.encounters.destroy');

                Route::post(
                    '/phases/{phaseTemplate}/single-elimination/structure/custom/routes',
                    [SingleEliminationGraphController::class, 'storeRoute']
                )->name('single-elimination.graph.routes.store');

                Route::delete(
                    '/phases/{phaseTemplate}/single-elimination/structure/custom/routes/{connection}',
                    [SingleEliminationGraphController::class, 'destroyRoute']
                )->name('single-elimination.graph.routes.destroy');

                /*
                |--------------------------------------------------------------------------
                | Puertas de entrada y mapeo hacia slots
                |--------------------------------------------------------------------------
                */

                Route::post(
                    '/phases/{phaseTemplate}/single-elimination/input-gates',
                    [
                        PhaseInputGateController::class,
                        'store',
                    ]
                )->name(
                    'single-elimination.input-gates.store'
                );

                Route::put(
                    '/phases/{phaseTemplate}/single-elimination/input-gates/{phaseInputGate}',
                    [
                        PhaseInputGateController::class,
                        'update',
                    ]
                )->name(
                    'single-elimination.input-gates.update'
                );

                Route::post(
                    '/phases/{phaseTemplate}/single-elimination/input-gates/{phaseInputGate}/duplicate',
                    [
                        PhaseInputGateController::class,
                        'duplicate',
                    ]
                )->name(
                    'single-elimination.input-gates.duplicate'
                );

                Route::delete(
                    '/phases/{phaseTemplate}/single-elimination/input-gates/{phaseInputGate}',
                    [
                        PhaseInputGateController::class,
                        'destroy',
                    ]
                )->name(
                    'single-elimination.input-gates.destroy'
                );

                /*
                |--------------------------------------------------------------------------
                | Reglas por ronda
                |--------------------------------------------------------------------------
                */

                Route::post(
                    '/phases/{phaseTemplate}/single-elimination/round-rules',
                    [
                        SingleEliminationRoundRuleController::class,
                        'store',
                    ]
                )->name(
                    'single-elimination.round-rules.store'
                );

                Route::put(
                    '/phases/{phaseTemplate}/single-elimination/round-rules/{roundRule}',
                    [
                        SingleEliminationRoundRuleController::class,
                        'update',
                    ]
                )->name(
                    'single-elimination.round-rules.update'
                );

                Route::delete(
                    '/phases/{phaseTemplate}/single-elimination/round-rules/{roundRule}',
                    [
                        SingleEliminationRoundRuleController::class,
                        'destroy',
                    ]
                )->name(
                    'single-elimination.round-rules.destroy'
                );

                /*
                |--------------------------------------------------------------------------
                | ROUND ROBIN ENGINE
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/phases/{phaseTemplate}/round-robin',
                    [
                        RoundRobinController::class,
                        'show',
                    ]
                )->name(
                    'round-robin.show'
                );

                Route::put(
                    '/phases/{phaseTemplate}/round-robin',
                    [
                        RoundRobinController::class,
                        'update',
                    ]
                )->name(
                    'round-robin.update'
                );

                Route::get(
                    '/phases/{phaseTemplate}/round-robin/io',
                    [
                        RoundRobinController::class,
                        'io',
                    ]
                )->name(
                    'round-robin.io'
                );

                /*
                |--------------------------------------------------------------------------
                | Simulador de Round Robin
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/phases/{phaseTemplate}/round-robin/simulator',
                    [
                        RoundRobinSimulatorController::class,
                        'show',
                    ]
                )->name(
                    'round-robin.simulator.show'
                );

                Route::post(
                    '/phases/{phaseTemplate}/round-robin/simulator/initialize',
                    [
                        RoundRobinSimulatorController::class,
                        'initialize',
                    ]
                )->name(
                    'round-robin.simulator.initialize'
                );

                Route::post(
                    '/phases/{phaseTemplate}/round-robin/simulator/action',
                    [
                        RoundRobinSimulatorController::class,
                        'action',
                    ]
                )->name(
                    'round-robin.simulator.action'
                );

                /*
                |--------------------------------------------------------------------------
                | Round Robin — Tiebreakers
                |--------------------------------------------------------------------------
                */

                Route::post(
                    '/phases/{phaseTemplate}/round-robin/tiebreakers',
                    [
                        RoundRobinTiebreakerController::class,
                        'store',
                    ]
                )->name(
                    'round-robin.tiebreakers.store'
                );

                Route::put(
                    '/phases/{phaseTemplate}/round-robin/tiebreakers/{tiebreaker}',
                    [
                        RoundRobinTiebreakerController::class,
                        'update',
                    ]
                )->name(
                    'round-robin.tiebreakers.update'
                );

                Route::delete(
                    '/phases/{phaseTemplate}/round-robin/tiebreakers/{tiebreaker}',
                    [
                        RoundRobinTiebreakerController::class,
                        'destroy',
                    ]
                )->name(
                    'round-robin.tiebreakers.destroy'
                );

                Route::patch(
                    '/phases/{phaseTemplate}/round-robin/tiebreakers/{tiebreaker}/move-up',
                    [
                        RoundRobinTiebreakerController::class,
                        'moveUp',
                    ]
                )->name(
                    'round-robin.tiebreakers.move-up'
                );

                Route::patch(
                    '/phases/{phaseTemplate}/round-robin/tiebreakers/{tiebreaker}/move-down',
                    [
                        RoundRobinTiebreakerController::class,
                        'moveDown',
                    ]
                )->name(
                    'round-robin.tiebreakers.move-down'
                );

                /*
                |--------------------------------------------------------------------------
                | GROUP STAGE ENGINE
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/phases/{phaseTemplate}/group-stage',
                    [
                        GroupStageController::class,
                        'show',
                    ]
                )->name(
                    'group-stage.show'
                );

                Route::put(
                    '/phases/{phaseTemplate}/group-stage',
                    [
                        GroupStageController::class,
                        'update',
                    ]
                )->name(
                    'group-stage.update'
                );

                /*
                |--------------------------------------------------------------------------
                | Group Definitions
                |--------------------------------------------------------------------------
                */

                Route::post(
                    '/phases/{phaseTemplate}/group-stage/groups',
                    [
                        GroupStageGroupController::class,
                        'store',
                    ]
                )->name(
                    'group-stage.groups.store'
                );

                Route::put(
                    '/phases/{phaseTemplate}/group-stage/groups/{group}',
                    [
                        GroupStageGroupController::class,
                        'update',
                    ]
                )->name(
                    'group-stage.groups.update'
                );

                Route::delete(
                    '/phases/{phaseTemplate}/group-stage/groups/{group}',
                    [
                        GroupStageGroupController::class,
                        'destroy',
                    ]
                )->name(
                    'group-stage.groups.destroy'
                );

                /*
                |--------------------------------------------------------------------------
                | Advancement Rules
                |--------------------------------------------------------------------------
                */

                Route::post(
                    '/phases/{phaseTemplate}/group-stage/advancement-rules',
                    [
                        GroupStageAdvancementRuleController::class,
                        'store',
                    ]
                )->name(
                    'group-stage.advancement-rules.store'
                );

                Route::put(
                    '/phases/{phaseTemplate}/group-stage/advancement-rules/{advancementRule}',
                    [
                        GroupStageAdvancementRuleController::class,
                        'update',
                    ]
                )->name(
                    'group-stage.advancement-rules.update'
                );

                Route::delete(
                    '/phases/{phaseTemplate}/group-stage/advancement-rules/{advancementRule}',
                    [
                        GroupStageAdvancementRuleController::class,
                        'destroy',
                    ]
                )->name(
                    'group-stage.advancement-rules.destroy'
                );

                Route::patch(
                    '/phases/{phaseTemplate}/group-stage/advancement-rules/{advancementRule}/move-up',
                    [
                        GroupStageAdvancementRuleController::class,
                        'moveUp',
                    ]
                )->name(
                    'group-stage.advancement-rules.move-up'
                );

                Route::patch(
                    '/phases/{phaseTemplate}/group-stage/advancement-rules/{advancementRule}/move-down',
                    [
                        GroupStageAdvancementRuleController::class,
                        'moveDown',
                    ]
                )->name(
                    'group-stage.advancement-rules.move-down'
                );

                /*
                |--------------------------------------------------------------------------
                | Cross Group Tiebreakers
                |--------------------------------------------------------------------------
                */

                Route::post(
                    '/phases/{phaseTemplate}/group-stage/tiebreakers',
                    [
                        GroupStageTiebreakerController::class,
                        'store',
                    ]
                )->name(
                    'group-stage.tiebreakers.store'
                );

                Route::put(
                    '/phases/{phaseTemplate}/group-stage/tiebreakers/{tiebreaker}',
                    [
                        GroupStageTiebreakerController::class,
                        'update',
                    ]
                )->name(
                    'group-stage.tiebreakers.update'
                );

                Route::delete(
                    '/phases/{phaseTemplate}/group-stage/tiebreakers/{tiebreaker}',
                    [
                        GroupStageTiebreakerController::class,
                        'destroy',
                    ]
                )->name(
                    'group-stage.tiebreakers.destroy'
                );

                Route::patch(
                    '/phases/{phaseTemplate}/group-stage/tiebreakers/{tiebreaker}/move-up',
                    [
                        GroupStageTiebreakerController::class,
                        'moveUp',
                    ]
                )->name(
                    'group-stage.tiebreakers.move-up'
                );

                Route::patch(
                    '/phases/{phaseTemplate}/group-stage/tiebreakers/{tiebreaker}/move-down',
                    [
                        GroupStageTiebreakerController::class,
                        'moveDown',
                    ]
                )->name(
                    'group-stage.tiebreakers.move-down'
                );

                Route::get(
                    '/phases/{phaseTemplate}/group-stage/simulator',
                    [
                        GroupStageSimulatorController::class,
                        'show',
                    ]
                )->name(
                    'group-stage.simulator.show'
                );

                Route::post(
                    '/phases/{phaseTemplate}/group-stage/simulator/initialize',
                    [
                        GroupStageSimulatorController::class,
                        'initialize',
                    ]
                )->name(
                    'group-stage.simulator.initialize'
                );

                Route::post(
                    '/phases/{phaseTemplate}/group-stage/simulator/action',
                    [
                        GroupStageSimulatorController::class,
                        'action',
                    ]
                )->name(
                    'group-stage.simulator.action'
                );

                /*
                |--------------------------------------------------------------------------
                | SWISS ENGINE
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/phases/{phaseTemplate}/swiss',
                    [
                        SwissController::class,
                        'show',
                    ]
                )->name(
                    'swiss.show'
                );

                Route::put(
                    '/phases/{phaseTemplate}/swiss',
                    [
                        SwissController::class,
                        'update',
                    ]
                )->name(
                    'swiss.update'
                );

                /*
                |--------------------------------------------------------------------------
                | Swiss — Tiebreakers
                |--------------------------------------------------------------------------
                */

                Route::post(
                    '/phases/{phaseTemplate}/swiss/tiebreakers',
                    [
                        SwissTiebreakerController::class,
                        'store',
                    ]
                )->name(
                    'swiss.tiebreakers.store'
                );

                Route::put(
                    '/phases/{phaseTemplate}/swiss/tiebreakers/{tiebreaker}',
                    [
                        SwissTiebreakerController::class,
                        'update',
                    ]
                )->name(
                    'swiss.tiebreakers.update'
                );

                Route::delete(
                    '/phases/{phaseTemplate}/swiss/tiebreakers/{tiebreaker}',
                    [
                        SwissTiebreakerController::class,
                        'destroy',
                    ]
                )->name(
                    'swiss.tiebreakers.destroy'
                );

                Route::patch(
                    '/phases/{phaseTemplate}/swiss/tiebreakers/{tiebreaker}/move-up',
                    [
                        SwissTiebreakerController::class,
                        'moveUp',
                    ]
                )->name(
                    'swiss.tiebreakers.move-up'
                );

                Route::patch(
                    '/phases/{phaseTemplate}/swiss/tiebreakers/{tiebreaker}/move-down',
                    [
                        SwissTiebreakerController::class,
                        'moveDown',
                    ]
                )->name(
                    'swiss.tiebreakers.move-down'
                );

                /*
                |--------------------------------------------------------------------------
                | Swiss — Match / Round Rules
                |--------------------------------------------------------------------------
                */

                Route::post(
                    '/phases/{phaseTemplate}/swiss/round-rules',
                    [
                        SwissRoundRuleController::class,
                        'store',
                    ]
                )->name(
                    'swiss.round-rules.store'
                );

                Route::put(
                    '/phases/{phaseTemplate}/swiss/round-rules/{roundRule}',
                    [
                        SwissRoundRuleController::class,
                        'update',
                    ]
                )->name(
                    'swiss.round-rules.update'
                );

                Route::delete(
                    '/phases/{phaseTemplate}/swiss/round-rules/{roundRule}',
                    [
                        SwissRoundRuleController::class,
                        'destroy',
                    ]
                )->name(
                    'swiss.round-rules.destroy'
                );

                /*
                |--------------------------------------------------------------------------
                | Swiss — Advancement Rules
                |--------------------------------------------------------------------------
                */

                Route::post(
                    '/phases/{phaseTemplate}/swiss/advancement-rules',
                    [
                        SwissAdvancementRuleController::class,
                        'store',
                    ]
                )->name(
                    'swiss.advancement-rules.store'
                );

                Route::put(
                    '/phases/{phaseTemplate}/swiss/advancement-rules/{advancementRule}',
                    [
                        SwissAdvancementRuleController::class,
                        'update',
                    ]
                )->name(
                    'swiss.advancement-rules.update'
                );

                Route::delete(
                    '/phases/{phaseTemplate}/swiss/advancement-rules/{advancementRule}',
                    [
                        SwissAdvancementRuleController::class,
                        'destroy',
                    ]
                )->name(
                    'swiss.advancement-rules.destroy'
                );

                Route::patch(
                    '/phases/{phaseTemplate}/swiss/advancement-rules/{advancementRule}/move-up',
                    [
                        SwissAdvancementRuleController::class,
                        'moveUp',
                    ]
                )->name(
                    'swiss.advancement-rules.move-up'
                );

                Route::patch(
                    '/phases/{phaseTemplate}/swiss/advancement-rules/{advancementRule}/move-down',
                    [
                        SwissAdvancementRuleController::class,
                        'moveDown',
                    ]
                )->name(
                    'swiss.advancement-rules.move-down'
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
| TOURNAMENT GRAPH FOUNDATION
|--------------------------------------------------------------------------
*/

                Route::get(
                    '/templates/{tournamentTemplate}/graph',
                    [
                        TournamentGraphController::class,
                        'show',
                    ]
                )->name(
                    'graph.show'
                );


                Route::post(
                    '/templates/{tournamentTemplate}/graph/validate',
                    [
                        TournamentGraphController::class,
                        'validateGraph',
                    ]
                )->name(
                    'graph.validate'
                );


                Route::post(
                    '/templates/{tournamentTemplate}/graph/auto-layout',
                    [
                        TournamentGraphController::class,
                        'autoLayout',
                    ]
                )->name(
                    'graph.auto-layout'
                );

                Route::post(
                    '/templates/{tournamentTemplate}/graph/presets',
                    [
                        TournamentGraphPresetController::class,
                        'store',
                    ]
                )->name(
                    'graph.presets.store'
                );

                Route::get(
                    '/templates/{tournamentTemplate}/graph/preview',
                    [
                        TournamentFlowPreviewController::class,
                        'show',
                    ]
                )->name(
                    'graph.preview.show'
                );

                Route::post(
                    '/templates/{tournamentTemplate}/graph/preview',
                    [
                        TournamentFlowPreviewController::class,
                        'run',
                    ]
                )->name(
                    'graph.preview.run'
                );


                /*
                |--------------------------------------------------------------------------
                | Graph Nodes
                |--------------------------------------------------------------------------
                */

                Route::post(
                    '/templates/{tournamentTemplate}/graph/nodes',
                    [
                        TournamentPhaseNodeController::class,
                        'store',
                    ]
                )->name(
                    'graph.nodes.store'
                );


                Route::put(
                    '/templates/{tournamentTemplate}/graph/nodes/{node}',
                    [
                        TournamentPhaseNodeController::class,
                        'update',
                    ]
                )->name(
                    'graph.nodes.update'
                );


                Route::patch(
                    '/templates/{tournamentTemplate}/graph/nodes/{node}/position',
                    [
                        TournamentPhaseNodeController::class,
                        'position',
                    ]
                )->name(
                    'graph.nodes.position'
                );


                Route::post(
                    '/templates/{tournamentTemplate}/graph/nodes/{node}/duplicate',
                    [
                        TournamentPhaseNodeController::class,
                        'duplicate',
                    ]
                )->name(
                    'graph.nodes.duplicate'
                );


                Route::delete(
                    '/templates/{tournamentTemplate}/graph/nodes/{node}',
                    [
                        TournamentPhaseNodeController::class,
                        'destroy',
                    ]
                )->name(
                    'graph.nodes.destroy'
                );


                /*
|--------------------------------------------------------------------------
| Entry Ports
|--------------------------------------------------------------------------
*/

                Route::post(
                    '/templates/{tournamentTemplate}/graph/nodes/{node}/entry-ports',
                    [
                        PhaseEntryPortController::class,
                        'store',
                    ]
                )->name(
                    'graph.entry-ports.store'
                );


                Route::put(
                    '/templates/{tournamentTemplate}/graph/nodes/{node}/entry-ports/{entryPort}',
                    [
                        PhaseEntryPortController::class,
                        'update',
                    ]
                )->name(
                    'graph.entry-ports.update'
                );


                Route::delete(
                    '/templates/{tournamentTemplate}/graph/nodes/{node}/entry-ports/{entryPort}',
                    [
                        PhaseEntryPortController::class,
                        'destroy',
                    ]
                )->name(
                    'graph.entry-ports.destroy'
                );


                /*
|--------------------------------------------------------------------------
| Starts
|--------------------------------------------------------------------------
*/

                Route::post(
                    '/templates/{tournamentTemplate}/graph/starts',
                    [
                        TournamentStartController::class,
                        'store',
                    ]
                )->name(
                    'graph.starts.store'
                );


                Route::put(
                    '/templates/{tournamentTemplate}/graph/starts/{start}',
                    [
                        TournamentStartController::class,
                        'update',
                    ]
                )->name(
                    'graph.starts.update'
                );


                Route::patch(
                    '/templates/{tournamentTemplate}/graph/starts/{start}/position',
                    [
                        TournamentStartController::class,
                        'position',
                    ]
                )->name(
                    'graph.starts.position'
                );


                Route::delete(
                    '/templates/{tournamentTemplate}/graph/starts/{start}',
                    [
                        TournamentStartController::class,
                        'destroy',
                    ]
                )->name(
                    'graph.starts.destroy'
                );


                /*
|--------------------------------------------------------------------------
| Terminals
|--------------------------------------------------------------------------
*/

                Route::post(
                    '/templates/{tournamentTemplate}/graph/terminals',
                    [
                        TournamentTerminalController::class,
                        'store',
                    ]
                )->name(
                    'graph.terminals.store'
                );


                Route::put(
                    '/templates/{tournamentTemplate}/graph/terminals/{terminal}',
                    [
                        TournamentTerminalController::class,
                        'update',
                    ]
                )->name(
                    'graph.terminals.update'
                );


                Route::patch(
                    '/templates/{tournamentTemplate}/graph/terminals/{terminal}/position',
                    [
                        TournamentTerminalController::class,
                        'position',
                    ]
                )->name(
                    'graph.terminals.position'
                );


                Route::delete(
                    '/templates/{tournamentTemplate}/graph/terminals/{terminal}',
                    [
                        TournamentTerminalController::class,
                        'destroy',
                    ]
                )->name(
                    'graph.terminals.destroy'
                );


                /*
|--------------------------------------------------------------------------
| Connections
|--------------------------------------------------------------------------
*/

                Route::post(
                    '/templates/{tournamentTemplate}/graph/connections',
                    [
                        TournamentPhaseConnectionController::class,
                        'store',
                    ]
                )->name(
                    'graph.connections.store'
                );


                Route::put(
                    '/templates/{tournamentTemplate}/graph/connections/{connection}',
                    [
                        TournamentPhaseConnectionController::class,
                        'update',
                    ]
                )->name(
                    'graph.connections.update'
                );


                Route::delete(
                    '/templates/{tournamentTemplate}/graph/connections/{connection}',
                    [
                        TournamentPhaseConnectionController::class,
                        'destroy',
                    ]
                )->name(
                    'graph.connections.destroy'
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
| (El historial competitivo ya no vive en la Biblioteca)
|--------------------------------------------------------------------------
|
| La Biblioteca es solo material reutilizable: no acumula torneos,
| victorias ni campeonatos. Eso pertenece a la entidad del UNIVERSO, y
| se consulta en universes.entities.show.
|
| Ver docs/md/27-Entidades-Propias-Del-Universo.md
|
*/



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
