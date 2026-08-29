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
    /*
     * Sobre que fondo se dibuja.
     *
     * 'light' es el de siempre. 'dark' existe para las pantallas donde el
     * contenido son cuadros, competidores y colores sobre fondo oscuro
     * -disenar un torneo, verlo- y ponerlo sobre blanco rompia la
     * continuidad con la Super Edicion.
     *
     * El sidebar de Universos ya era oscuro, asi que oscurecer el contenido
     * acerca las dos mitades en vez de separarlas.
     */
    public function __construct(
        public ?Universe $universe = null,
        public string $surface = 'light'
    ) {}

    public function render(): View
    {
        return view(
            'layouts.universes'
        );
    }
}
