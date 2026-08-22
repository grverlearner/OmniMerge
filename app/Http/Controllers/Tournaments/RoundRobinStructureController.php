<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Models\PhaseTemplate;
use App\Services\Tournaments\Preview\PreviewCastService;
use App\Services\Tournaments\RoundRobin\RoundRobinScheduleCalculator;
use App\Services\Tournaments\RoundRobin\RoundRobinSettingsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| RoundRobinStructureController
|--------------------------------------------------------------------------
|
| La pantalla que a Round Robin le faltaba: ver el calendario completo
| antes de jugar nada.
|
| Round Robin no tiene estructura editable —su calendario es enteramente
| determinístico a partir del número de participantes y de ciclos—, así
| que esto no es un editor: es una previsualización fiel de lo que va a
| ocurrir, con caras prestadas para que se entienda de un vistazo.
|
| No se guarda nada. Cada figurante viene marcado como prestado.
|
*/

class RoundRobinStructureController extends Controller
{
    public function __construct(
        private readonly RoundRobinSettingsService $settingsService,
        private readonly RoundRobinScheduleCalculator $calculator,
        private readonly PreviewCastService $cast
    ) {}

    public function structure(
        Request $request,
        PhaseTemplate $phaseTemplate
    ): View {

        $this->authorize('update', $phaseTemplate);
        $this->ensureCorrectType($phaseTemplate);

        $settings =
            $this->settingsService->ensure($phaseTemplate);

        $participants =
            $this->participantCount($request, $phaseTemplate);

        /*
         * Sin límite de jornadas: aquí se quiere ver el calendario ENTERO,
         * no una muestra. Es la diferencia con el resumen.
         */
        $preview =
            $this->calculator->calculateStructure(
                $participants,
                (int) ($settings->cycles ?: 1),
                (int) ($settings->default_best_of ?: 1),
                (bool) $settings->allow_draws,
                999
            );

        /*
         * Caras prestadas, una por seed. El calendario habla de "Seed 3";
         * con un retrato se entiende mucho antes quién juega contra quién.
         */
        $castBySeed =
            $this->cast
            ->borrow($request->user(), $participants)
            ->values()
            ->mapWithKeys(
                fn($member, $index) => [$index + 1 => $member]
            );

        $phaseExits =
            $phaseTemplate->exits()
            ->orderBy('sort_order')
            ->get();

        return view(
            'tournaments.phase-templates.round-robin-structure',
            compact(
                'phaseTemplate',
                'settings',
                'preview',
                'participants',
                'castBySeed',
                'phaseExits'
            )
        );
    }

    /**
     * Cuántos participantes previsualizar. Se puede tantear desde la
     * propia pantalla sin guardar nada.
     */
    private function participantCount(
        Request $request,
        PhaseTemplate $phaseTemplate
    ): int {

        $requested =
            (int) $request->query('participants', 0);

        if ($requested > 0) {
            return max(2, min(128, $requested));
        }

        return max(
            2,
            (int) (
                $phaseTemplate->exact_participants
                ?: ($phaseTemplate->min_participants ?: 8)
            )
        );
    }

    private function ensureCorrectType(PhaseTemplate $phaseTemplate): void
    {
        abort_unless(
            $phaseTemplate->phase_type === 'ROUND_ROBIN',
            404
        );
    }
}
