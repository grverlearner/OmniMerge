<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
|--------------------------------------------------------------------------
| TournamentInstanceReward
|--------------------------------------------------------------------------
|
| Un premio que existe SOLO en esta edicion.
|
| Los del torneo -UniverseTournamentReward- los heredan todas sus
| ediciones y una sola de ellas no puede tocarlos. Estos nacen aqui, se
| corrigen aqui, y se ofrecen -no se imponen- a la edicion siguiente.
|
| Ademas pueden colgar de una FASE: "quien gane los grupos se lleva esto",
| que es algo que un premio de torneo no sabe decir porque el torneo no
| sabe con que plantilla se jugara cada ano.
|
*/
class TournamentInstanceReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_instance_id',
        'node_id',
        'trigger',
        'threshold',
        'game_key',
        'stat_key',
        'operation',
        'amount',
        'universe_trophy_id',
        'label',
        'is_active',
        'carry_forward',
    ];

    protected function casts(): array
    {
        return [
            'node_id' => 'integer',
            'threshold' => 'integer',
            'amount' => 'float',
            'is_active' => 'boolean',
            'carry_forward' => 'boolean',
        ];
    }

    /*
     * Los mismos disparadores que un premio de torneo, mas los que solo
     * tienen sentido dentro de una fase.
     */
    public const TRIGGERS = [
        'POSITION' => 'Terminar en una posición',
        'PARTICIPATION' => 'Participar',
        'UNBEATEN' => 'Terminar invicto',
        'WIN_COUNT' => 'Ganar N batallas',
        'ENCOUNTER_WIN_COUNT' => 'Ganar N enfrentamientos',
    ];

    public const OPERATIONS = [
        'ADD' => 'Sumar',
        'SUBTRACT' => 'Restar',
        'MULTIPLY' => 'Multiplicar por',
        'SET' => 'Fijar en',
    ];

    public function competition(): BelongsTo
    {
        return $this->belongsTo(
            TournamentInstance::class,
            'tournament_instance_id'
        );
    }

    public function trophy(): BelongsTo
    {
        return $this->belongsTo(
            UniverseTrophy::class,
            'universe_trophy_id'
        );
    }

    /*
     * Como se lee esta regla, en una frase.
     *
     * Se construye aqui y no en la vista porque aparece en cuatro sitios
     * -el editor, la ficha, la fase y el reparto final- y tres redacciones
     * distintas de la misma regla se contradicen tarde o temprano.
     */
    public function getSentenceAttribute(): string
    {
        $quien = match ($this->trigger) {
            'POSITION' => 'Quien acabe en el puesto ' . ($this->threshold ?: 1),
            'PARTICIPATION' => 'Todo el que participe',
            'UNBEATEN' => 'Quien termine invicto',
            'WIN_COUNT' => 'Quien gane ' . ($this->threshold ?: 1) . ' batallas',
            'ENCOUNTER_WIN_COUNT' => 'Quien gane ' . ($this->threshold ?: 1) . ' enfrentamientos',
            default => $this->trigger,
        };

        $donde = $this->node_id
            ? ' de esta fase'
            : '';

        $gana = [];

        if ($this->universe_trophy_id) {
            $gana[] = 'el trofeo «' . ($this->trophy?->name ?? '?') . '»';
        }

        if ($this->stat_key) {

            $verbo = match ($this->operation) {
                'SUBTRACT' => 'resta',
                'MULTIPLY' => 'multiplica por',
                'SET' => 'fija en',
                default => 'suma',
            };

            $gana[] = $verbo . ' ' . rtrim(rtrim(number_format((float) $this->amount, 2, ',', ''), '0'), ',')
                . ' en ' . $this->stat_key;
        }

        return $gana === []
            ? $quien . $donde . ' no recibe nada todavía'
            : $quien . $donde . ' recibe ' . implode(' y ', $gana) . '.';
    }
}
