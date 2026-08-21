<?php

namespace App\View\Components;

use App\Models\Universe;
use Illuminate\View\Component;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| UniverseLayout
|--------------------------------------------------------------------------
|
| Layout propio del módulo Universos.
|
| Recibe opcionalmente el Universo actual: cuando está presente, el
| sidebar cambia de "navegación del módulo" a "navegación dentro del
| Universo" (Resumen, Competidores, Temporadas, Torneos...).
|
*/

class UniverseLayout extends Component
{
    public function __construct(
        public ?Universe $universe = null
    ) {}

    public function render(): View
    {
        return view(
            'layouts.universes'
        );
    }
}
