<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
|--------------------------------------------------------------------------
| TournamentInstanceState
|--------------------------------------------------------------------------
|
| Estado vivo del motor. Es el mismo array que el Competition Lab
| guarda en un token cifrado, pero persistido.
|
| revision implementa bloqueo optimista frente a dos pestañas abiertas.
|
*/

class TournamentInstanceState extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_instance_id',
        'schema_version',
        'revision',
        'state',
    ];

    protected function casts(): array
    {
        return [
            'schema_version' => 'integer',
            'revision' => 'integer',
            'state' => 'array',
        ];
    }

    public function tournamentInstance(): BelongsTo
    {
        return $this->belongsTo(
            TournamentInstance::class
        );
    }
}
