<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
|--------------------------------------------------------------------------
| TournamentInstanceSnapshot
|--------------------------------------------------------------------------
|
| Configuración congelada de una competición.
|
| INMUTABLE: se escribe al crear la competición y no vuelve a tocarse.
| Por eso no tiene updated_at y el modelo no expone 'snapshot' como
| campo actualizable después de la creación.
|
*/

class TournamentInstanceSnapshot extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'tournament_instance_id',
        'schema_version',
        'hash',
        'snapshot',
    ];

    protected function casts(): array
    {
        return [
            'schema_version' => 'integer',
            'snapshot' => 'array',
        ];
    }

    public function tournamentInstance(): BelongsTo
    {
        return $this->belongsTo(
            TournamentInstance::class
        );
    }
}
