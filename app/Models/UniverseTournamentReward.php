<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
|--------------------------------------------------------------------------
| UniverseTournamentReward
|--------------------------------------------------------------------------
|
| Una consecuencia permanente de terminar un torneo: sube una stat del
| competidor, le da un trofeo, o ambas cosas.
|
| El juego y la stat son texto porque el esquema lo declara el Game
| Engine. Aquí nunca se pregunta "¿es Highest Number?": se pregunta
| "¿existe esta stat en este juego?".
|
*/

class UniverseTournamentReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'universe_tournament_id',
        'trigger',
        'threshold',
        'game_key',
        'stat_key',
        'operation',
        'amount',
        'universe_trophy_id',
        'label',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'threshold' => 'integer',
            'amount' => 'float',
            'is_active' => 'boolean',
        ];
    }

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

    public function universeTournament(): BelongsTo
    {
        return $this->belongsTo(UniverseTournament::class);
    }

    public function trophy(): BelongsTo
    {
        return $this->belongsTo(
            UniverseTrophy::class,
            'universe_trophy_id'
        );
    }

    /*
     * Cómo se lee esta regla en la interfaz. Se construye aquí para que
     * la vista no tenga que recomponerla en cada sitio donde aparece.
     */
    public function getConditionLabelAttribute(): string
    {
        return match ($this->trigger) {

            'POSITION' =>
            match ((int) $this->threshold) {
                1 => 'Campeón',
                2 => 'Finalista',
                3 => 'Tercer puesto',
                default => 'Puesto ' . (int) $this->threshold,
            },

            'PARTICIPATION' => 'Por participar',
            'UNBEATEN' => 'Terminar invicto',
            'WIN_COUNT' => 'Ganar ' . (int) $this->threshold . ' batallas',
            'ENCOUNTER_WIN_COUNT' => 'Ganar ' . (int) $this->threshold . ' enfrentamientos',

            default => $this->trigger,
        };
    }

    public function getEffectLabelAttribute(): ?string
    {
        if (! $this->stat_key) {
            return null;
        }

        $amount =
            rtrim(rtrim(number_format($this->amount, 3, '.', ''), '0'), '.');

        return match ($this->operation) {
            'ADD' => '+' . $amount . ' ' . $this->stat_key,
            'SUBTRACT' => '−' . $amount . ' ' . $this->stat_key,
            'MULTIPLY' => '×' . $amount . ' ' . $this->stat_key,
            'SET' => $this->stat_key . ' = ' . $amount,
            default => $this->stat_key,
        };
    }
}
