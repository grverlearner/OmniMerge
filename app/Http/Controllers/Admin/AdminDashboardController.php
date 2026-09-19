<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\ContentFlag;
use App\Models\TournamentInstance;
use App\Models\User;
use App\Services\Admin\ContentRegistry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| El resumen del administrador
|--------------------------------------------------------------------------
|
| Lo que tiene que contestar de un vistazo: cuánta gente hay y cuánta
| llega, cuánto se crea y de qué, qué está pasando ahora mismo (cuentas
| bloqueadas, contenido oculto, competiciones en juego) y qué han hecho
| los admins últimamente.
|
*/

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $hace7 = now()->subDays(7);

        $personas = [
            'total' => User::query()->count(),
            'nuevas' => User::query()->where('created_at', '>=', $hace7)->count(),
            'activas' => User::query()->where('last_login_at', '>=', $hace7)->count(),
            'bloqueadas' => User::query()->whereNotNull('banned_at')->count(),
            'admins' => User::query()->where('role', 'ADMIN')->count(),
            'eliminadas' => User::onlyTrashed()->count(),
        ];

        $contenido = collect(ContentRegistry::counts())
            ->map(fn ($c, $key) => $c + ContentRegistry::TYPES[$key] + [
                'nuevos' => ContentRegistry::TYPES[$key]['model']::query()->where('created_at', '>=', $hace7)->count(),
            ]);

        $marcas = ContentFlag::query()
            ->selectRaw('flag, count(*) as total')
            ->groupBy('flag')
            ->pluck('total', 'flag');

        $competiciones = TournamentInstance::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        /* Catorce días de altas y de contenido nuevo, día a día */
        $dias = collect(range(13, 0))->map(fn ($n) => now()->subDays($n)->startOfDay());
        $desde = $dias->first();

        $altas = $this->porDia(User::query(), $desde);

        $creado = collect(ContentRegistry::TYPES)
            ->map(fn ($meta) => $this->porDia($meta['model']::query(), $desde))
            ->reduce(function ($suma, $serie) {
                foreach ($serie as $dia => $n) {
                    $suma[$dia] = ($suma[$dia] ?? 0) + $n;
                }

                return $suma;
            }, []);

        $actividad = $dias->map(fn (Carbon $d) => [
            'dia' => $d,
            'altas' => $altas[$d->toDateString()] ?? 0,
            'creado' => $creado[$d->toDateString()] ?? 0,
        ]);

        $vistas = DB::table('community_interactions')
            ->where('created_at', '>=', $hace7)
            ->selectRaw('interaction_type, count(*) as total')
            ->groupBy('interaction_type')
            ->pluck('total', 'interaction_type');

        $masVisto = collect(['entity', 'collection', 'tournament_template'])
            ->flatMap(fn ($key) => ContentRegistry::query($key)
                ->where('views_count', '>', 0)
                ->orderByDesc('views_count')
                ->limit(6)
                ->get()
                ->map(fn ($m) => ['key' => $key, 'model' => $m]))
            ->sortByDesc(fn ($fila) => $fila['model']->views_count)
            ->take(6)
            ->values();

        $creadores = User::query()
            ->withCount(['entities', 'collections', 'tournamentTemplates', 'universes'])
            ->get()
            ->sortByDesc(fn ($u) => $u->entities_count + $u->collections_count + $u->tournament_templates_count + $u->universes_count)
            ->take(6)
            ->values();

        return view('admin.dashboard', [
            'personas' => $personas,
            'contenido' => $contenido,
            'marcas' => $marcas,
            'competiciones' => $competiciones,
            'actividad' => $actividad,
            'vistas' => $vistas,
            'masVisto' => $masVisto,
            'creadores' => $creadores,
            'recientes' => User::query()->latest()->limit(6)->get(),
            'acciones' => AdminAction::query()->with('admin')->latest('created_at')->limit(8)->get(),
        ]);
    }

    private function porDia($consulta, Carbon $desde): array
    {
        return $consulta
            ->where('created_at', '>=', $desde)
            ->selectRaw('DATE(created_at) as dia, count(*) as total')
            ->groupBy('dia')
            ->pluck('total', 'dia')
            ->all();
    }
}
