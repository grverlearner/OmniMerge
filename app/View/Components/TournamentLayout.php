<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;


class TournamentLayout extends Component
{
    /*
     * Sobre que fondo se dibuja la pagina.
     *
     * 'light' es el de siempre y sigue siendo el de todo el modulo. 'dark'
     * existe para las pantallas que ensenan una fase tal y como se juega
     * -la ficha, la Super Edicion- donde el contenido son cuadros, tablas y
     * caras sobre fondo oscuro, y ponerlo sobre blanco rompia la
     * continuidad con el editor.
     *
     * El sidebar ya era oscuro, asi que oscurecer el contenido acerca las
     * dos mitades en vez de separarlas.
     */
    public function __construct(
        public string $surface = 'light'
    ) {}

    public function isDark(): bool
    {
        return $this->surface === 'dark';
    }

    public function render(): View
    {
        return view(
            'layouts.tournaments'
        );
    }
}
