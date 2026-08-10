<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntityPresentation extends Model
{
    use HasFactory;


    protected $fillable = [
        'user_id',
        'entity_id',

        'mode',

        'entity_version_id',
        'entity_version_image_id',

        'use_version_name',
        'use_version_description',
    ];


    protected function casts(): array
    {
        return [
            'use_version_name' =>
            'boolean',

            'use_version_description' =>
            'boolean',
        ];
    }


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


    public function mediaImage(): BelongsTo
    {
        return $this->belongsTo(
            EntityVersionImage::class,
            'entity_version_image_id'
        );
    }
}
