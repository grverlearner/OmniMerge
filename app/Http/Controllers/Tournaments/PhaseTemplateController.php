<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StorePhaseTemplateRequest;
use App\Http\Requests\Tournaments\UpdatePhaseTemplateRequest;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\PhaseEditor\PhaseSuperEditorRegistry;
use App\Services\Tournaments\PhaseTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PhaseTemplateController extends Controller
{
    public function __construct(
        private readonly
        PhaseTemplateService $service,

        private readonly
        PhaseSuperEditorRegistry $registry
    ) {}

    public function index(
        Request $request
    ): View {
        $this->authorize(
            'viewAny',
            PhaseTemplate::class
        );

        $user =
            $request->user();

        $search =
            trim(
                (string)
                $request->input(
                    'search'
                )
            );

        $type =
            (string)
            $request->input(
                'type',
                ''
            );

        $status =
            (string)
            $request->input(
                'status',
                ''
            );

        $visibility =
            (string)
            $request->input(
                'visibility',
                ''
            );

        $sort =
            (string)
            $request->input(
                'sort',
                'newest'
            );

        $base =
            PhaseTemplate::query()
            ->ownedBy($user);

        $stats = [
            'total' => (clone $base)->count(),

            'active' => (clone $base)
                ->where(
                    'status',
                    'ACTIVE'
                )
                ->count(),

            'public' => (clone $base)
                ->where(
                    'visibility',
                    'PUBLIC'
                )
                ->count(),

            'with_exits' => (clone $base)
                ->whereHas('exits')
                ->count(),
        ];

        $query =
            PhaseTemplate::query()
            ->ownedBy($user)
            ->withCount('exits')

            ->when(
                $search,
                fn($query) =>
                $query->where(
                    function ($subquery) use (
                        $search
                    ) {
                        $subquery
                            ->where(
                                'name',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'code',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'description',
                                'like',
                                "%{$search}%"
                            );
                    }
                )
            )

            ->when(
                $type,
                fn($query) =>
                $query->where(
                    'phase_type',
                    $type
                )
            )

            ->when(
                $status,
                fn($query) =>
                $query->where(
                    'status',
                    $status
                )
            )

            ->when(
                $visibility,
                fn($query) =>
                $query->where(
                    'visibility',
                    $visibility
                )
            );

        match ($sort) {
            'oldest' =>
            $query->oldest(),

            'name_asc' =>
            $query->orderBy('name'),

            'name_desc' =>
            $query->orderByDesc('name'),

            'exits_desc' =>
            $query->orderByDesc(
                'exits_count'
            ),

            default =>
            $query->latest(),
        };

        $phaseTemplates =
            $query
            ->paginate(18)
            ->withQueryString();

        return view(
            'tournaments.phase-templates.index',
            compact(
                'phaseTemplates',
                'stats',
                'search',
                'type',
                'status',
                'visibility',
                'sort'
            )
        );
    }

    public function create(
        Request $request
    ): View {
        $this->authorize(
            'create',
            PhaseTemplate::class
        );

        $previewCode =
            $this->service
            ->previewCode(
                $request->user()
            );

        return view(
            'tournaments.phase-templates.create',
            compact(
                'previewCode'
            )
        );
    }

    public function store(
        StorePhaseTemplateRequest $request
    ): RedirectResponse {
        $phaseTemplate =
            $this->service
            ->create(
                $request->user(),
                $request->validated(),
                $request->file('image')
            );

        return redirect()
            ->route(
                'tournaments.phase-templates.show',
                $phaseTemplate
            )
            ->with(
                'success',
                'Fase creada correctamente.'
            );
    }

    public function show(
        Request $request,
        PhaseTemplate $phaseTemplate
    ): View {
        $this->authorize(
            'view',
            $phaseTemplate
        );

        $phaseTemplate
            ->load([
                'exits',
                'singleEliminationSetting',
                'roundRobinSetting',
                'groupStageSetting',
                'swissSetting',
            ])
            ->loadCount('exits');

        /*
         * Los motores con Super Edicion tienen ficha propia.
         *
         * La ficha ensena la fase COMO SE JUEGA -su estructura, sus aduanas,
         * su configuracion contada en frases- y para eso necesita el mismo
         * payload que el editor. Los que todavia no tienen editor -Swiss,
         * League- se quedan con la pantalla de siempre: darles una ficha a
         * medias seria peor que no darsela.
         */
        if (! $this->registry->supports($phaseTemplate)) {
            return view(
                'tournaments.phase-templates.show',
                compact('phaseTemplate')
            );
        }

        $editor = $this->registry->for($phaseTemplate);

        $payload = $editor->payload(
            $phaseTemplate,
            $request->user()
        );

        $summary = $editor->summary($phaseTemplate, $payload);

        return view(
            'tournaments.phase-templates.dossier',
            [
                'phaseTemplate' => $phaseTemplate,
                'payload' => $payload,
                'summary' => $summary['groups'],
                'figures' => $summary['figures'],
                'stageView' => $editor->stageView(),
                'scheduleView' => $editor->scheduleView(),
                'clientEngine' => $editor->clientEngine(),
                'typeIcon' => $this->typeIcon($phaseTemplate),
                'typeAccent' => $this->typeAccent($phaseTemplate),
            ]
        );
    }

    private function typeIcon(PhaseTemplate $phaseTemplate): string
    {
        return match ($phaseTemplate->phase_type) {
            'SINGLE_ELIMINATION' => '\u{2694}',
            'ROUND_ROBIN' => '\u{21BB}',
            'GROUP_STAGE' => '\u{25A6}',
            'LEAGUE' => '\u{21C5}',
            'SWISS' => '\u{25C6}',
            default => '\u{2726}',
        };
    }

    /*
     * El color de cada tipo de fase, que se repite en toda la ficha.
     *
     * Las clases van literales y no armadas juntando cadenas: Tailwind lee
     * el codigo fuente para decidir que compila, y una clase construida en
     * tiempo de ejecucion nunca llega al CSS.
     *
     * @return array<string,string>
     */
    private function typeAccent(PhaseTemplate $phaseTemplate): array
    {
        return match ($phaseTemplate->phase_type) {

            'SINGLE_ELIMINATION' => [
                'text' => 'text-amber-300',
                'soft' => 'bg-amber-500/15',
                'border' => 'border-amber-500/30',
                'glow' => 'bg-amber-500/10',
            ],

            'ROUND_ROBIN' => [
                'text' => 'text-cyan-300',
                'soft' => 'bg-cyan-500/15',
                'border' => 'border-cyan-500/30',
                'glow' => 'bg-cyan-500/10',
            ],

            'GROUP_STAGE' => [
                'text' => 'text-indigo-300',
                'soft' => 'bg-indigo-500/15',
                'border' => 'border-indigo-500/30',
                'glow' => 'bg-indigo-500/10',
            ],

            default => [
                'text' => 'text-slate-300',
                'soft' => 'bg-slate-700/50',
                'border' => 'border-slate-700',
                'glow' => 'bg-slate-500/10',
            ],
        };
    }

    public function edit(
        PhaseTemplate $phaseTemplate
    ): View {
        $this->authorize(
            'update',
            $phaseTemplate
        );

        $phaseTemplate->loadMissing(
            'singleEliminationSetting'
        );

        return view(
            'tournaments.phase-templates.edit',
            compact(
                'phaseTemplate'
            )
        );
    }

    public function update(
        UpdatePhaseTemplateRequest $request,
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {
        $phaseTemplate =
            $this->service
            ->update(
                $phaseTemplate,
                $request->validated(),
                $request->file('image')
            );

        return redirect()
            ->route(
                'tournaments.phase-templates.edit',
                $phaseTemplate
            )
            ->with(
                'success',
                'Fase actualizada correctamente.'
            );
    }

    public function duplicate(
        Request $request,
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {
        $this->authorize(
            'duplicate',
            $phaseTemplate
        );

        $copy =
            $this->service
            ->duplicate(
                $request->user(),
                $phaseTemplate
            );

        return redirect()
            ->route(
                'tournaments.phase-templates.edit',
                $copy
            )
            ->with(
                'success',
                'Fase duplicada. La copia comienza como borrador privado.'
            );
    }

    public function archive(
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {
        $this->authorize(
            'update',
            $phaseTemplate
        );

        $this->service
            ->archive(
                $phaseTemplate
            );

        return redirect()
            ->route(
                'tournaments.phase-templates.show',
                $phaseTemplate
            )
            ->with(
                'success',
                'Fase archivada correctamente.'
            );
    }

    public function destroy(
        PhaseTemplate $phaseTemplate
    ): RedirectResponse {
        $this->authorize(
            'delete',
            $phaseTemplate
        );

        $this->service
            ->delete(
                $phaseTemplate
            );

        return redirect()
            ->route(
                'tournaments.phase-templates.index'
            )
            ->with(
                'success',
                'Fase eliminada correctamente.'
            );
    }
}
