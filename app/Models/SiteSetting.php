<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/*
| Un ajuste del sitio entero. Se lee siempre a través de
| App\Support\Site\SiteSettings, que conoce los valores por defecto y los
| guarda en caché: esta tabla no debería consultarse a mano.
*/
class SiteSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }
}
