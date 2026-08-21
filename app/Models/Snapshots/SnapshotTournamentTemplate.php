<?php

namespace App\Models\Snapshots;

use App\Models\TournamentTemplate;

/*
|--------------------------------------------------------------------------
| SnapshotTournamentTemplate
|--------------------------------------------------------------------------
|
| TournamentTemplate reconstruida desde un snapshot inmutable.
|
| El motor (TournamentGraphRuntimeService::loadGraph) hace
| $template->load([...]) al principio de cada operación. Sobre una
| plantilla normal eso refresca desde la base de datos; sobre un
| snapshot eso sería exactamente la fuga que esta fase debe evitar:
| una competición ya iniciada empezaría a usar la configuración
| modificada.
|
| Por eso aquí load() y loadMissing() no hacen nada: las relaciones ya
| vienen completas desde el snapshot y son la única verdad para esta
| competición.
|
| Es una subclase de TournamentTemplate, así que satisface todos los
| type hints del motor sin tocar una sola línea de él.
|
*/

class SnapshotTournamentTemplate extends TournamentTemplate
{
    protected $table = 'tournament_templates';

    /*
     * Eloquent deriva la clave foránea del nombre de la clase: sin esto
     * buscaría 'snapshot_tournament_template_id'.
     */
    public function getForeignKey(): string
    {
        return 'tournament_template_id';
    }

    public function load($relations)
    {
        return $this;
    }

    public function loadMissing($relations)
    {
        return $this;
    }

    /*
     * Un snapshot jamás debe escribirse de vuelta sobre la plantilla
     * real. Se bloquea explícitamente en vez de confiar en que nadie
     * lo intente.
     */
    public function save(array $options = [])
    {
        return false;
    }
}
