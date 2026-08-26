<?php

namespace App\Http\Requests\Tournaments\Concerns;

use App\Models\PhaseTemplate;
use App\Services\Tournaments\GroupStage\GroupStageExitForecastService;
use Illuminate\Validation\Validator;

/*
|--------------------------------------------------------------------------
| ValidatesGroupStageRuleScale
|--------------------------------------------------------------------------
|
| Una regla «de cada grupo» multiplica: la cantidad que se escribe no es la
| que clasifica.
|
| Sin este control se puede pedir «los 8 primeros de cada grupo» en una fase
| de 4 grupos de 4 y guardarlo sin una sola queja. La regla es válida, se
| ejecuta, y clasifica a los 16 —todos— porque la posición de todo el mundo
| es menor o igual que 8. El error no se ve al configurar ni al empezar: se
| ve cuando la fase entera ya se jugó y la siguiente puerta rechaza el doble
| de participantes de los que admite, con el torneo bloqueado y sin vuelta
| atrás.
|
| Vive en un trait porque la misma cuenta hace falta en dos sitios: al
| guardar una regla suelta y al crear una puerta de salida con su criterio
| en un solo paso.
|
*/
trait ValidatesGroupStageRuleScale
{
    protected function validateRuleScale(
        Validator $validator,
        PhaseTemplate $phaseTemplate,
        string $ruleType,
        ?int $take,
        ?int $positionFrom,
        string $takeField = 'take',
        string $positionField = 'position_from'
    ): void {

        $usesPerGroupTake =
            in_array(
                $ruleType,
                [
                    'EACH_GROUP_TOP_N',
                    'EACH_GROUP_BOTTOM_N',
                ],
                true
            );

        $usesPerGroupPosition =
            in_array(
                $ruleType,
                [
                    'EACH_GROUP_POSITION',
                    'EACH_GROUP_RANGE',
                ],
                true
            );

        if (
            ! $usesPerGroupTake
            &&
            ! $usesPerGroupPosition
        ) {
            return;
        }

        $forecaster =
            app(
                GroupStageExitForecastService::class
            );

        $sizes =
            $forecaster->groupSizes(
                $phaseTemplate,
                $forecaster->referenceParticipants(
                    $phaseTemplate
                )
            );

        /*
         * Sin un reparto en grupos válido no hay nada que comparar, y el
         * motivo ya se está avisando en la pantalla de estructura. Un
         * segundo aviso derivado del primero solo sería ruido.
         */
        if ($sizes === []) {
            return;
        }

        $smallest =
            min($sizes);

        $groupCount =
            count($sizes);

        if ($usesPerGroupPosition) {

            if ((int) $positionFrom > $smallest) {
                $validator
                    ->errors()
                    ->add(
                        $positionField,
                        'El grupo más pequeño tiene '
                        . $smallest
                        . ' participantes, así que el puesto '
                        . (int) $positionFrom
                        . ' no existe: esta regla no seleccionaría a nadie.'
                    );
            }

            return;
        }

        $take = (int) $take;

        if ($take < $smallest) {
            return;
        }

        $perGroup =
            intdiv(
                $take,
                $groupCount
            );

        $validator
            ->errors()
            ->add(
                $takeField,
                'Esta cantidad es POR GRUPO, no en total. Con '
                . $groupCount
                . ' grupos de '
                . $smallest
                . ', pedir '
                . $take
                . ' de cada uno clasifica a los '
                . ($smallest * $groupCount)
                . ' participantes de la fase: no eliminaría a nadie. '
                . (
                    $perGroup >= 1 && $perGroup < $smallest
                    ? 'Para que pasen ' . $take . ' en total, escribe ' . $perGroup . '.'
                    : 'Si la cantidad que quieres es el total, usa «Mejores N restantes».'
                )
            );
    }
}
