<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Services\Community\CreatorDirectory;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| CommunityCreatorsController
|--------------------------------------------------------------------------
|
| El directorio de creadores.
|
| No existía. Cada comunidad enseñaba a «sus» creadores destacados en un
| rincón, y nadie podía ver a la gente de la comunidad entera, ni saber si
| alguien publica solo entidades, solo torneos o las dos cosas.
|
| Ver docs/md/78-Comunidad.md
|
*/

class CommunityCreatorsController extends Controller
{
    public function __construct(
        private readonly CreatorDirectory $directorio
    ) {}


    public function __invoke(Request $request): View
    {
        $q = trim((string) $request->input('q', ''));

        $tipo = (string) $request->input('tipo', 'todos');
        $tipo = in_array($tipo, ['todos', 'completo', 'biblioteca', 'torneos'], true) ? $tipo : 'todos';

        $orden = (string) $request->input('orden', 'publicado');
        $orden = in_array($orden, ['publicado', 'copiado', 'reciente', 'nombre'], true) ? $orden : 'publicado';

        $todos = $this->directorio->creadores();

        /* Las cuentas son del conjunto, no de lo filtrado: son el índice */
        $cuentas = [
            'todos' => $todos->count(),
            'completo' => $todos->where('tipo_creador', 'completo')->count(),
            'biblioteca' => $todos->where('tipo_creador', 'biblioteca')->count(),
            'torneos' => $todos->where('tipo_creador', 'torneos')->count(),
        ];

        $creadores = $todos
            ->when($tipo !== 'todos', fn($c) => $c->where('tipo_creador', $tipo))
            ->when($q !== '', fn($c) => $c->filter(
                fn($u) => str_contains(mb_strtolower($u->name . ' ' . $u->username . ' ' . $u->headline), mb_strtolower($q))
            ));

        $creadores = match ($orden) {
            'copiado' => $creadores->sortByDesc('copias_total'),
            'reciente' => $creadores->sortByDesc(fn($u) => $u->ultima_publicacion?->timestamp ?? 0),
            'nombre' => $creadores->sortBy(fn($u) => mb_strtolower($u->name)),
            default => $creadores->sortByDesc('pub_total'),
        };

        $creadores = $creadores->values();

        return view('community.creators.index', [
            'creadores' => $creadores,
            'todos' => $todos,
            'cuentas' => $cuentas,
            'caras' => $this->directorio->caras($todos->pluck('id')),
            'sinPublicar' => $this->directorio->sinPublicar(),
            'tipos' => CreatorDirectory::TIPOS,
            'q' => $q,
            'tipo' => $tipo,
            'orden' => $orden,
        ]);
    }
}
