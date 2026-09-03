<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /*
     * Sobre que fondo se dibuja la pagina.
     *
     * 'light' es el de siempre y sigue siendo el de casi todo el modulo.
     * 'dark' existe para las pantallas rehechas -la biblioteca de entidades y
     * lo que venga detras- donde el contenido son caras, fichas y tablas: el
     * sidebar ya era oscuro, asi que oscurecer el contenido acerca las dos
     * mitades en vez de separarlas.
     *
     * Es el mismo mecanismo que TournamentLayout y UniverseLayout, a
     * proposito: tres modulos con tres formas distintas de decir lo mismo
     * acabarian divergiendo.
     */
    public function __construct(
        public ?string $title = null,
        public string $surface = 'light'
    ) {}

    public function isDark(): bool
    {
        return $this->surface === 'dark';
    }

    public function render(): View
    {
        return view('layouts.app');
    }
}
