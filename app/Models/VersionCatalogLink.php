<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VersionCatalogLink extends Model
{
    use HasFactory;


    protected $fillable = [
        'user_id',
        'version_id',

        'attribute_id',
        'attribute_option_id',

        'relation_type',

        'condition_group',
        'logical_operator',
        'is_required',
        'priority',
    ];


    protected function casts(): array
    {
        return [
            'condition_group' =>
            'integer',

            'is_required' =>
            'boolean',

            'priority' =>
            'integer',
        ];
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }


    public function version(): BelongsTo
    {
        return $this->belongsTo(
            Version::class
        );
    }


    public function attribute(): BelongsTo
    {
        return $this->belongsTo(
            Attribute::class
        );
    }


    public function option(): BelongsTo
    {
        return $this->belongsTo(
            AttributeOption::class,
            'attribute_option_id'
        );
    }


    public function getRelationLabelAttribute(): string
    {
        return match ($this->relation_type) {

            'ACTIVATES' =>
            'Activa esta Versión',

            'CONTEXT' =>
            'Contexto',

            default =>
            'Relacionada',
        };
    }
}
