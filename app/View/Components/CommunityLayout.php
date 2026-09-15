<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| CommunityLayout
|--------------------------------------------------------------------------
|
| El espacio propio de la Comunidad.
|
| Había dos comunidades y ninguna tenía casa: la de la Biblioteca vivía
| dentro del layout de la Biblioteca y la de Torneos dentro del de Torneos,
| así que quien entraba a mirar lo que hacen otros acababa en un módulo que
| no era el suyo, con un sidebar que no hablaba de la comunidad.
|
| Acepta los mismos props que AppLayout y TournamentLayout —title, surface—
| a propósito: las once vistas que se mudan aquí cambian solo la etiqueta del
| layout, sin tocar nada de lo que ya estaba hecho.
|
| Ver docs/md/78-Comunidad.md
|
*/

class CommunityLayout extends Component
{
    public function __construct(
        public ?string $title = null,
        public string $surface = 'light',
        public bool $bleed = false
    ) {}

    public function isDark(): bool
    {
        return $this->surface === 'dark';
    }

    public function render(): View
    {
        return view('layouts.community');
    }
}
