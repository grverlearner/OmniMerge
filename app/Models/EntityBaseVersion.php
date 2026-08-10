<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntityBaseVersion extends Model
{
    use HasFactory;


    protected $fillable = [
        'user_id',
        'entity_id',
        'entity_version_id',
    ];


    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }


    public function entity(): BelongsTo
    {
        return $this->belongsTo(
            Entity::class
        );
    }


    public function entityVersion(): BelongsTo
    {
        return $this->belongsTo(
            EntityVersion::class
        );
    }
}
