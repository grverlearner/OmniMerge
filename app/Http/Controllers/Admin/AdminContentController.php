<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\ContentFlag;
use App\Services\Admin\AdminAudit;
use App\Services\Admin\ContentFlags;
use App\Services\Admin\ContentRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| Todo el contenido del sitio, sea de quien sea
|--------------------------------------------------------------------------
|
| Una sola pantalla para los ocho tipos —ver ContentRegistry—. Desde aquí
| un admin busca, filtra y ordena lo de todos, y sobre cada pieza puede:
|
|   - marcarla (verificada, confiable, destacada) o ocultarla de la
|     comunidad sin borrarla
|   - cambiar su visibilidad, si el tipo la tiene
|   - eliminarla y recuperarla (es un borrado blando: se puede deshacer)
|
| Editarla por dentro se hace en su propia ficha: un admin entra en ella
| como si fuera suya (ver Gate::before en AppServiceProvider).
|
*/

class AdminContentController extends Controller
{
    public const SORTS = [
        'recientes' => 'Más recientes',
        'antiguos' => 'Más antiguos',
        'nombre' => 'Nombre',
        'vistas' => 'Más vistos',
        'clones' => 'Más clonados',
    ];

    public function __construct(
        private readonly AdminAudit $audit,
        private readonly ContentFlags $flags
    ) {}

    public function index(Request $request, string $type): View
    {
        $meta = ContentRegistry::meta($type);

        $filtros = [
            'q' => trim($request->string('q')->toString()),
            'dueno' => $request->integer('dueno') ?: null,
            'visibilidad' => $request->string('visibilidad')->toString(),
            'marca' => $request->string('marca')->toString(),
            'estado' => $request->string('estado')->toString(),
            'orden' => array_key_exists($request->string('orden')->toString(), self::SORTS) ? $request->string('orden')->toString() : 'recientes',
        ];

        $consulta = ContentRegistry::query($type);

        if ($filtros['estado'] === 'eliminados') {
            $consulta->onlyTrashed();
        }

        if ($filtros['q'] !== '') {
            $consulta->where(fn (Builder $q) => $q
                ->where('name', 'like', "%{$filtros['q']}%")
                ->orWhere('code', 'like', "%{$filtros['q']}%"));
        }

        if ($filtros['dueno']) {
            $type === 'competition'
                ? $consulta->whereHas('universe', fn ($q) => $q->where('user_id', $filtros['dueno']))
                : $consulta->where('user_id', $filtros['dueno']);
        }

        if ($meta['visibility'] && isset(ContentRegistry::VISIBILITIES[$filtros['visibilidad']])) {
            $consulta->where('visibility', $filtros['visibilidad']);
        }

        if ($filtros['marca'] === 'sin-marca') {
            $consulta->whereNotIn('id', ContentFlag::query()->where('content_type', $type)->select('content_id'));
        } elseif (isset(ContentFlag::FLAGS[$filtros['marca']])) {
            $consulta->whereIn('id', ContentFlag::query()
                ->where('content_type', $type)
                ->where('flag', $filtros['marca'])
                ->select('content_id'));
        }

        match ($filtros['orden']) {
            'antiguos' => $consulta->oldest(),
            'nombre' => $consulta->orderBy('name'),
            'vistas' => $meta['views'] ? $consulta->orderByDesc('views_count') : $consulta->latest(),
            'clones' => $meta['clones'] ? $consulta->orderByDesc('clones_count') : $consulta->latest(),
            default => $consulta->latest(),
        };

        $pagina = $consulta->paginate(30)->withQueryString();

        return view('admin.content.index', [
            'type' => $type,
            'meta' => $meta,
            'items' => $pagina,
            'flags' => $this->flags->forMany($type, $pagina->pluck('id')),
            'filtros' => $filtros,
            'duenos' => $this->duenos($type),
            'totales' => [
                'activos' => $meta['model']::query()->count(),
                'eliminados' => $meta['model']::onlyTrashed()->count(),
                'marcas' => ContentFlag::query()->where('content_type', $type)
                    ->selectRaw('flag, count(*) as total')->groupBy('flag')->pluck('total', 'flag'),
            ],
        ]);
    }

    public function show(string $type, int $id): View
    {
        $meta = ContentRegistry::meta($type);
        $modelo = ContentRegistry::find($type, $id);

        $interacciones = in_array($type, ['entity', 'collection', 'attribute'], true)
            ? DB::table('community_interactions')
                ->where('content_type', strtoupper($type))
                ->where('content_id', $id)
                ->selectRaw('interaction_type, count(*) as total')
                ->groupBy('interaction_type')
                ->pluck('total', 'interaction_type')
            : collect();

        return view('admin.content.show', [
            'type' => $type,
            'meta' => $meta,
            'modelo' => $modelo,
            'dueno' => $type === 'competition' ? $modelo->universe?->user : $modelo->user,
            'marcas' => ContentFlag::query()->with('creator')
                ->where('content_type', $type)->where('content_id', $id)->get()->keyBy('flag'),
            'interacciones' => $interacciones,
            'historial' => AdminAction::query()->with('admin')
                ->where('target_type', $type)->where('target_id', $id)
                ->latest('created_at')->limit(20)->get(),
            'enlace' => ContentRegistry::url($type, $modelo),
        ]);
    }

    public function flag(Request $request, string $type, int $id): RedirectResponse
    {
        $modelo = ContentRegistry::find($type, $id);

        $datos = $request->validate([
            'flag' => ['required', Rule::in(array_keys(ContentFlag::FLAGS))],
            'on' => ['required', 'boolean'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $this->flags->toggle($type, $modelo, $datos['flag'], (bool) $datos['on'], $datos['note'] ?? null);

        $etiqueta = ContentFlag::FLAGS[$datos['flag']]['label'];

        return back()->with('success', $datos['on']
            ? "«{$modelo->name}» queda marcado como {$etiqueta}."
            : "«{$modelo->name}» ya no está marcado como {$etiqueta}.");
    }

    public function visibility(Request $request, string $type, int $id): RedirectResponse
    {
        $meta = ContentRegistry::meta($type);
        abort_unless($meta['visibility'], 404);

        $modelo = ContentRegistry::find($type, $id);

        $datos = $request->validate([
            'visibility' => ['required', Rule::in(array_keys(ContentRegistry::VISIBILITIES))],
        ]);

        $antes = $modelo->visibility;
        $modelo->forceFill(['visibility' => $datos['visibility']])->save();

        $this->audit->log('content.visibility', $modelo, ['antes' => $antes, 'ahora' => $datos['visibility']]);

        return back()->with('success', "«{$modelo->name}» ahora es " . Str::lower(ContentRegistry::VISIBILITIES[$datos['visibility']]) . '.');
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        $modelo = ContentRegistry::find($type, $id);

        if (! $modelo->trashed()) {
            $modelo->delete();
            $this->audit->log('content.delete', $modelo, ['tipo' => $type]);
        }

        return back()->with('success', "«{$modelo->name}» está eliminado. Se puede recuperar desde «Eliminados».");
    }

    public function restore(string $type, int $id): RedirectResponse
    {
        $modelo = ContentRegistry::find($type, $id);

        if ($modelo->trashed()) {
            $modelo->restore();
            $this->audit->log('content.restore', $modelo, ['tipo' => $type]);
        }

        return back()->with('success', "«{$modelo->name}» vuelve a estar disponible.");
    }

    /* Lo mismo, sobre varias piezas marcadas en la lista */
    public function bulk(Request $request, string $type): RedirectResponse
    {
        $meta = ContentRegistry::meta($type);

        $datos = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'accion' => ['required', 'string'],
        ], ['ids.required' => 'Marca al menos un elemento.']);

        $accion = $datos['accion'];
        $modelos = ContentRegistry::query($type, true)->whereIn('id', $datos['ids'])->get();

        foreach ($modelos as $modelo) {
            if (preg_match('/^(flag|unflag)-([A-Z]+)$/', $accion, $m) && isset(ContentFlag::FLAGS[$m[2]])) {
                $this->flags->toggle($type, $modelo, $m[2], $m[1] === 'flag');
            } elseif (Str::startsWith($accion, 'visibility-') && $meta['visibility']) {
                $nueva = Str::after($accion, 'visibility-');
                abort_unless(isset(ContentRegistry::VISIBILITIES[$nueva]), 422);

                if ($modelo->visibility !== $nueva) {
                    $antes = $modelo->visibility;
                    $modelo->forceFill(['visibility' => $nueva])->save();
                    $this->audit->log('content.visibility', $modelo, ['antes' => $antes, 'ahora' => $nueva]);
                }
            } elseif ($accion === 'delete' && ! $modelo->trashed()) {
                $modelo->delete();
                $this->audit->log('content.delete', $modelo, ['tipo' => $type]);
            } elseif ($accion === 'restore' && $modelo->trashed()) {
                $modelo->restore();
                $this->audit->log('content.restore', $modelo, ['tipo' => $type]);
            }
        }

        return back()->with('success', 'Hecho en ' . $modelos->count() . ' ' . Str::plural('elemento', $modelos->count()) . '.');
    }

    /* Los dueños que tienen algo de este tipo, para el filtro */
    private function duenos(string $type)
    {
        if ($type === 'competition') {
            return \App\Models\User::query()
                ->whereIn('id', \App\Models\Universe::query()->withTrashed()->select('user_id'))
                ->orderBy('name')->get(['id', 'name', 'username']);
        }

        $modelo = ContentRegistry::meta($type)['model'];

        return \App\Models\User::query()
            ->whereIn('id', $modelo::query()->withTrashed()->select('user_id'))
            ->orderBy('name')->get(['id', 'name', 'username']);
    }
}
