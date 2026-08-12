<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;


class TournamentPhase extends Model
{
    use HasFactory;
    use SoftDeletes;


    protected $fillable = [

        'tournament_template_id',

        'sequence_number',

        'code',

        'name',

        'description',

        'phase_type',

        'sort_order',

        'input_participants',

        'qualifiers_count',

        'best_of',

        'allow_byes',

        'status',

        'settings',
    ];


    protected function casts(): array
    {
        return [

            'sequence_number' =>
            'integer',

            'sort_order' =>
            'integer',

            'input_participants' =>
            'integer',

            'qualifiers_count' =>
            'integer',

            'best_of' =>
            'integer',

            'allow_byes' =>
            'boolean',

            'settings' =>
            'array',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */


    public function tournamentTemplate(): BelongsTo
    {
        return $this->belongsTo(
            TournamentTemplate::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Código
    |--------------------------------------------------------------------------
    */


    public static function formatCode(
        int $sequence
    ): string {

        return sprintf(
            'FAS%03d',
            $sequence
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Etiquetas
    |--------------------------------------------------------------------------
    */


    public function getTypeLabelAttribute(): string
    {
        return match ($this->phase_type) {

            'SINGLE_ELIMINATION' =>
            'Eliminación directa',

            'ROUND_ROBIN' =>
            'Todos contra todos',

            'GROUP_STAGE' =>
            'Fase de grupos',

            'SWISS' =>
            'Sistema suizo',

            'DOUBLE_ELIMINATION' =>
            'Doble eliminación',

            'CUSTOM' =>
            'Personalizada',

            default =>
            $this->phase_type,
        };
    }


    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {

            'ACTIVE' =>
            'Activa',

            'INACTIVE' =>
            'Inactiva',

            default =>
            $this->status,
        };
    }
}
