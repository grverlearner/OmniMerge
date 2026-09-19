<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\User;
use App\Services\Admin\ContentFlags;
use App\Services\Admin\ContentRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| Lo más visto y lo que han hecho los admins
|--------------------------------------------------------------------------
|
| «Lo más visto» tiene dos lecturas y las dos son de verdad:
|
|   de siempre    los contadores que guarda cada pieza (views_count,
|                 clones_count)
|   del periodo   las visitas y clonaciones registradas en la comunidad
|                 en los últimos días (community_interactions)
|
*/

class AdminInsightController extends Controller
{
    /* Los tipos que llevan contador de vistas y clones */
    public const POPULAR_TYPES = ['entity', 'collection', 'attribute', 'tournament_template', 'phase_template'];

    public const PERIODS = ['7' => 'Últimos 7 días', '30' => 'Últimos 30 días', '90' => 'Últimos 90 días', 'siempre' => 'Desde siempre'];

    public function popular(Request $request, ContentFlags $flags): View
    {
        $tipo = in_array($request->string('tipo')->toString(), self::POPULAR_TYPES, true) ? $request->string('tipo')->toString() : null;
        $periodo = array_key_exists($request->string('periodo')->toString(), self::PERIODS) ? $request->string('periodo')->toString() : 'siempre';
        $medida = $request->string('medida')->toString() === 'clones' ? 'clones' : 'vistas';

        $tipos = $tipo ? [$tipo] : self::POPULAR_TYPES;

        $filas = collect();

        foreach ($tipos as $key) {
            if ($periodo === 'siempre') {
                $columna = $medida === 'clones' ? 'clones_count' : 'views_count';

                $modelos = ContentRegistry::query($key)
                    ->where($columna, '>', 0)
                    ->orderByDesc($columna)
                    ->limit(40)
                    ->get();

                $conteo = $modelos->mapWithKeys(fn ($m) => [$m->id => (int) $m->{$columna}]);
            } else {
                $conteo = DB::table('community_interactions')
                    ->where('content_type', strtoupper($key))
                    ->where('interaction_type', $medida === 'clones' ? 'CLONE' : 'VIEW')
                    ->where('created_at', '>=', now()->subDays((int) $periodo))
                    ->selectRaw('content_id, count(*) as total')
                    ->groupBy('content_id')
                    ->orderByDesc('total')
                    ->limit(40)
                    ->pluck('total', 'content_id');

                $modelos = ContentRegistry::query($key)->whereIn('id', $conteo->keys())->get();
            }

            $marcas = $flags->forMany($key, $modelos->pluck('id'));

            foreach ($modelos as $modelo) {
                $filas->push([
                    'key' => $key,
                    'meta' => ContentRegistry::TYPES[$key],
                    'model' => $modelo,
                    'total' => (int) ($conteo[$modelo->id] ?? 0),
                    'vistas' => (int) ($modelo->views_count ?? 0),
                    'clones' => (int) ($modelo->clones_count ?? 0),
                    'flags' => $marcas[$modelo->id] ?? [],
                ]);
            }
        }

        $filas = $filas->sortByDesc('total')->take(60)->values();

        /* Los creadores cuyo contenido más se mira */
        $creadores = $filas->groupBy(fn ($f) => $f['model']->user_id)
            ->map(fn ($grupo) => ['user' => $grupo->first()['model']->user, 'total' => $grupo->sum('total'), 'piezas' => $grupo->count()])
            ->filter(fn ($c) => $c['user'])
            ->sortByDesc('total')
            ->take(8)
            ->values();

        return view('admin.popular', [
            'filas' => $filas,
            'creadores' => $creadores,
            'filtros' => compact('tipo', 'periodo', 'medida'),
            'maximo' => max(1, (int) $filas->max('total')),
        ]);
    }

    public function audit(Request $request): View
    {
        $accion = array_key_exists($request->string('accion')->toString(), AdminAction::LABELS) ? $request->string('accion')->toString() : null;
        $admin = $request->integer('admin') ?: null;
        $objetivo = $request->string('objetivo')->toString();

        $consulta = AdminAction::query()->with('admin')->latest('created_at');

        if ($accion) {
            $consulta->where('action', $accion);
        }

        if ($admin) {
            $consulta->where('admin_id', $admin);
        }

        if ($objetivo !== '') {
            $consulta->where('target_type', $objetivo);
        }

        return view('admin.audit', [
            'acciones' => $consulta->paginate(40)->withQueryString(),
            'filtros' => compact('accion', 'admin', 'objetivo'),
            'admins' => User::withTrashed()->whereIn('id', AdminAction::query()->select('admin_id'))->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
