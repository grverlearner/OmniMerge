<?php

namespace App\Services\Tournaments\PhaseEditor;

use App\Models\PhaseTemplate;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| PhaseSuperEditorContract
|--------------------------------------------------------------------------
|
| Lo que tiene que saber hacer el editor de CUALQUIER tipo de fase.
|
| La Super Edicion es una sola pantalla —cabecera, panel izquierdo, escenario
| central, panel derecho, jornadas abajo— y lo unico que cambia entre un
| Round Robin y una Eliminacion Directa es QUE se dibuja en esos huecos. El
| contrato existe para que el dia que se implemente el segundo motor no haya
| que tocar el armazon: se escribe otra clase que responda estas mismas
| preguntas.
|
| Round Robin es el primero. No es el unico previsto.
|
*/
interface PhaseSuperEditorContract
{
    /*
     * El tipo de fase que sabe editar: ROUND_ROBIN, SINGLE_ELIMINATION...
     */
    public function phaseType(): string;

    /*
     * La vista Blade del panel de configuracion (izquierda).
     */
    public function configView(): string;

    /*
     * La vista Blade del escenario central.
     */
    public function stageView(): string;

    /*
     * Todo lo que la pantalla necesita para dibujarse: contrato de la fase,
     * estructura calculada, reparto prestado, puertas y diagnostico.
     *
     * $overrides permite pedir una previsualizacion distinta a la guardada
     * —otra cantidad de participantes, otro numero de vueltas— sin tocar
     * nada en base de datos.
     */
    public function payload(
        PhaseTemplate $phaseTemplate,
        ?User $user,
        array $overrides = []
    ): array;

    /*
     * Persiste lo que SI pertenece a la fase. Recibe datos ya validados.
     */
    public function persist(
        PhaseTemplate $phaseTemplate,
        array $data
    ): void;

    /*
     * Reglas de validacion de lo persistible, para el FormRequest.
     */
    public function persistenceRules(): array;


    /*
    |--------------------------------------------------------------------------
    | Puertas
    |--------------------------------------------------------------------------
    |
    | Se crean y se editan DENTRO del editor. Vivian en otra pantalla y eso
    | obligaba a salir, configurar a ciegas y volver para ver el efecto:
    | justo lo que este editor existe para evitar.
    |
    | Cada motor pone sus propias reglas porque no significan lo mismo. En
    | una liga una puerta de entrada reparte puestos de la parrilla; en Fase
    | de grupos apunta a un grupo; en Eliminacion Directa, a un slot del
    | cuadro.
    |
    */

    public function gateRules(): array;

    public function persistGate(
        PhaseTemplate $phaseTemplate,
        mixed $gate,
        array $data
    ): void;

    public function deleteGate(
        PhaseTemplate $phaseTemplate,
        mixed $gate
    ): void;

    public function exitRules(): array;

    public function persistExit(
        PhaseTemplate $phaseTemplate,
        mixed $exit,
        array $data
    ): void;

    public function deleteExit(
        PhaseTemplate $phaseTemplate,
        mixed $exit
    ): void;
}
