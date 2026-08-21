<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * Ledger append-only. Un evento ocurrido no se reescribe nunca.
 */
class TournamentInstanceEvent extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'tournament_instance_id',
        'sequence',
        'type',
        'level',
        'message',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'context' => 'array',
        ];
    }

    public function tournamentInstance(): BelongsTo
    {
        return $this->belongsTo(
            TournamentInstance::class
        );
    }
}
