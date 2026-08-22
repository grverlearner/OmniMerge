<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
|--------------------------------------------------------------------------
| TournamentInstanceMatch
|--------------------------------------------------------------------------
|
| Proyección consultable de un encuentro. Guarda los nombres además de
| las claves, para que un encuentro histórico siga siendo legible
| aunque el competidor desaparezca del Universo.
|
*/

class TournamentInstanceMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_instance_id',
        'node_id',
        'runtime_match_id',
        'round_number',
        'label',
        'status',
        'participant_a_key',
        'participant_b_key',
        'participant_a_name',
        'participant_b_name',
        'score_a',
        'score_b',
        'winner_key',
        'loser_key',
        'is_draw',
        'series',
        'participant_a_universe_entity_id',
        'participant_b_universe_entity_id',
        'winner_universe_entity_id',
        'group_label',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'node_id' => 'integer',
            'completed_at' => 'datetime',
            'round_number' => 'integer',
            'score_a' => 'integer',
            'score_b' => 'integer',
            'is_draw' => 'boolean',
            'series' => 'array',
        ];
    }

    /*
     * Entidades desnormalizadas: permiten pintar imágenes en el
     * historial sin pasar por la tabla de participantes.
     */
    public function participantAEntity(): BelongsTo
    {
        return $this->belongsTo(
            UniverseEntity::class,
            'participant_a_universe_entity_id'
        );
    }

    public function participantBEntity(): BelongsTo
    {
        return $this->belongsTo(
            UniverseEntity::class,
            'participant_b_universe_entity_id'
        );
    }

    public function winnerEntity(): BelongsTo
    {
        return $this->belongsTo(
            UniverseEntity::class,
            'winner_universe_entity_id'
        );
    }

    public function tournamentInstance(): BelongsTo
    {
        return $this->belongsTo(
            TournamentInstance::class
        );
    }

    public function isPlayed(): bool
    {
        return $this->status === 'COMPLETED';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'PENDING' => 'Pendiente',
            'RUNNING' => 'En juego',
            'COMPLETED' => 'Jugado',
            'BYE' => 'BYE',
            default => $this->status,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | La serie (Fase 13)
    |--------------------------------------------------------------------------
    |
    | BO y FIXED_GAMES son cosas distintas y hasta ahora se veian iguales
    | —de hecho no se veian en absoluto, porque la columna llegaba vacia—.
    |
    |   BO3            gana quien llegue a 2. Puede acabar en 2 juegos.
    |   FIXED 4        se juegan los 4 SIEMPRE y decide el acumulado.
    |
    | Llamar "BO2" a una serie de 2 enfrentamientos fijos seria mentir: en
    | un BO2 nadie puede ganar, y aqui si.
    |
    */

    public function getIsFixedSeriesAttribute(): bool
    {
        return ($this->series['series_format'] ?? 'BEST_OF') === 'FIXED_GAMES';
    }

    public function getSeriesLabelAttribute(): ?string
    {
        if (! $this->series) {
            return null;
        }

        if ($this->is_fixed_series) {

            $games = (int) ($this->series['fixed_games'] ?? 1);

            return $games . ($games === 1 ? ' enfrentamiento' : ' enfrentamientos');
        }

        return 'BO' . max(1, (int) ($this->series['best_of'] ?? 1));
    }

    public function getSeriesModeLabelAttribute(): ?string
    {
        if (! $this->series) {
            return null;
        }

        return $this->is_fixed_series
            ? 'Fijo'
            : 'Al mejor de';
    }

    /*
     * Que hace falta para ganar. En FIXED no hay objetivo: se juegan
     * todos y decide el acumulado.
     */
    public function getWinsRequiredAttribute(): ?int
    {
        if (! $this->series || $this->is_fixed_series) {
            return null;
        }

        return $this->series['wins_required']
            ?? intdiv(max(1, (int) ($this->series['best_of'] ?? 1)), 2) + 1;
    }

    public function getGamesPlayedAttribute(): int
    {
        return count($this->series['games'] ?? []);
    }

    public function getGamesRemainingAttribute(): ?int
    {
        if (! $this->series) {
            return null;
        }

        if (($this->series['status'] ?? null) === 'COMPLETED') {
            return 0;
        }

        $nominal = $this->is_fixed_series
            ? (int) ($this->series['fixed_games'] ?? 1)
            : (int) ($this->series['best_of'] ?? 1);

        return max(0, $nominal - $this->games_played);
    }

    /*
     * Marcador de la serie en enfrentamientos ganados.
     */
    public function getSeriesScoreAttribute(): array
    {
        return [
            (int) ($this->series['game_wins_a'] ?? 0),
            (int) ($this->series['game_wins_b'] ?? 0),
        ];
    }

    /**
     * Los enfrentamientos de la serie, listos para pintar.
     *
     * @return array<int, array>
     */
    public function getEncounterRowsAttribute(): array
    {
        return collect($this->series['games'] ?? [])
            ->map(
                fn(array $game) => [
                    'number' => (int) ($game['number'] ?? 0),
                    'score_a' => (int) ($game['score_a'] ?? 0),
                    'score_b' => (int) ($game['score_b'] ?? 0),
                    'winner_key' => $game['winner_id'] ?? null,
                    'is_tiebreak' => (bool) ($game['is_tiebreak'] ?? false),
                    'is_draw' => ($game['winner_id'] ?? null) === null,
                ]
            )
            ->all();
    }
}
