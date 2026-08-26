<?php

namespace App\Services\Tournaments\PhaseEditor;

use App\Models\PhaseTemplate;

/*
|--------------------------------------------------------------------------
| SupportsEditableGroups
|--------------------------------------------------------------------------
|
| Para los motores que reparten a la gente en grupos con nombre propio.
|
| Va aparte del contrato principal y no dentro porque una liga no tiene
| grupos, y meterlo en el contrato obligaria a RoundRobinSuperEditor a
| escribir tres metodos vacios solo para cumplir. Un metodo vacio que nadie
| llama es una promesa que no se cumple.
|
| El controlador pregunta con instanceof: si el editor no lo implementa, las
| rutas de grupos devuelven 404 para ese tipo de fase.
|
*/
interface SupportsEditableGroups
{
    public function groupRules(): array;

    public function persistGroup(
        PhaseTemplate $phaseTemplate,
        mixed $group,
        array $data
    ): void;

    public function deleteGroup(
        PhaseTemplate $phaseTemplate,
        mixed $group
    ): void;

    /*
     * Convertir un reparto automatico en grupos con cupo propio.
     *
     * Pasar a "grupos personalizados" partiendo de cero es raro: casi
     * siempre se quiere lo que ya habia, con un grupo retocado.
     *
     * @param  array<int,int>  $sizes
     */
    public function adoptGroupSizes(
        PhaseTemplate $phaseTemplate,
        array $sizes
    ): void;
}
