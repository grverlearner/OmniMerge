<?php

namespace App\Models\Snapshots;

use App\Models\PhaseTemplate;

/*
|--------------------------------------------------------------------------
| SnapshotPhaseTemplate
|--------------------------------------------------------------------------
|
| PhaseTemplate reconstruida desde un snapshot inmutable.
|
| Misma razón que SnapshotTournamentTemplate: los motores llaman a
| loadMissing() sobre la fase antes de prepararla, y eso traería las
| reglas vivas en lugar de las congeladas.
|
| Ojo: la protección real es que el snapshot esté COMPLETO. Estas
| sobrescrituras cubren las llamadas directas; una relación que no se
| hubiese congelado seguiría cargándose de forma perezosa contra la base
| de datos (el proyecto no tiene preventLazyLoading activado). Por eso
| TournamentSnapshotBuilder congela el árbol entero que declara
| TournamentGraphRuntimeService::loadGraph().
|
*/

class SnapshotPhaseTemplate extends PhaseTemplate
{
    protected $table = 'phase_templates';

    /*
     * Eloquent deriva la clave foránea del nombre de la clase, así que
     * sin esto buscaría 'snapshot_phase_template_id' y reventaría en
     * cuanto alguna relación no congelada intentara resolverse.
     */
    public function getForeignKey(): string
    {
        return 'phase_template_id';
    }

    public function load($relations)
    {
        return $this;
    }

    public function loadMissing($relations)
    {
        return $this;
    }

    public function save(array $options = [])
    {
        return false;
    }
}
