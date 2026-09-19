<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| AdminLayout
|--------------------------------------------------------------------------
|
| El espacio del administrador. Es oscuro siempre y lleva el acento rosa
| para que nunca se confunda con los módulos de un usuario: aquí lo que se
| toca es de todos.
|
*/

class AdminLayout extends Component
{
    public function __construct(
        public ?string $title = null
    ) {}

    public function render(): View
    {
        return view('layouts.admin');
    }
}
