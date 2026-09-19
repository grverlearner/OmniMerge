<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\User;
use App\Services\Admin\AdminAudit;
use App\Services\Admin\ContentFlags;
use App\Services\Admin\ContentRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| Las cuentas, vistas por un admin
|--------------------------------------------------------------------------
|
| Todo lo que se hace aquí queda en el registro de acciones. Hay tres
| límites que no dependen de nadie:
|
|   - un admin no se bloquea, no se degrada ni se borra a sí mismo
|   - a otro admin no se le bloquea ni se le borra: primero se le quita
|     el rol, y eso también queda escrito
|   - nunca se queda el sitio sin ningún admin
|
*/

class AdminUserController extends Controller
{
    public const SORTS = [
        'recientes' => 'Más recientes',
        'antiguas' => 'Más antiguas',
        'nombre' => 'Nombre',
        'actividad' => 'Última entrada',
        'contenido' => 'Más contenido',
    ];

    public function __construct(private readonly AdminAudit $audit) {}

    public function index(Request $request): View
    {
        $estado = $request->string('estado')->toString();
        $rol = $request->string('rol')->toString();
        $insignia = $request->string('insignia')->toString();
        $orden = array_key_exists($request->string('orden')->toString(), self::SORTS) ? $request->string('orden')->toString() : 'recientes';
        $buscar = trim($request->string('q')->toString());

        $consulta = User::query()
            ->withCount(['entities', 'collections', 'tournamentTemplates', 'phaseTemplates', 'universes']);

        match ($estado) {
            'bloqueadas' => $consulta->whereNotNull('banned_at'),
            'eliminadas' => $consulta->onlyTrashed(),
            'activas' => $consulta->whereNull('banned_at'),
            'sin-entrar' => $consulta->whereNull('last_login_at'),
            default => null,
        };

        if (in_array($rol, ['ADMIN', 'USER'], true)) {
            $consulta->where('role', $rol);
        }

        if ($insignia === 'ninguna') {
            $consulta->whereNull('creator_badge');
        } elseif (isset(User::CREATOR_BADGES[$insignia])) {
            $consulta->where('creator_badge', $insignia);
        }

        if ($buscar !== '') {
            $consulta->where(fn ($q) => $q
                ->where('name', 'like', "%{$buscar}%")
                ->orWhere('username', 'like', "%{$buscar}%")
                ->orWhere('email', 'like', "%{$buscar}%"));
        }

        match ($orden) {
            'antiguas' => $consulta->oldest(),
            'nombre' => $consulta->orderBy('name'),
            'actividad' => $consulta->orderByDesc('last_login_at'),
            'contenido' => $consulta->orderByRaw('(entities_count + collections_count + tournament_templates_count + phase_templates_count + universes_count) desc'),
            default => $consulta->latest(),
        };

        return view('admin.users.index', [
            'usuarios' => $consulta->paginate(24)->withQueryString(),
            'filtros' => compact('estado', 'rol', 'insignia', 'orden', 'buscar'),
            'totales' => [
                'todas' => User::query()->count(),
                'activas' => User::query()->whereNull('banned_at')->count(),
                'bloqueadas' => User::query()->whereNotNull('banned_at')->count(),
                'eliminadas' => User::onlyTrashed()->count(),
                'sin-entrar' => User::query()->whereNull('last_login_at')->count(),
            ],
        ]);
    }

    public function show(int $user, ContentFlags $flags): View
    {
        $cuenta = $this->cuenta($user);

        $contenido = collect(ContentRegistry::TYPES)
            ->except('competition')
            ->map(function ($meta, $key) use ($cuenta, $flags) {
                $modelos = $meta['model']::query()
                    ->withTrashed()
                    ->where('user_id', $cuenta->id)
                    ->latest()
                    ->get();

                return $meta + [
                    'items' => $modelos,
                    'flags' => $flags->forMany($key, $modelos->pluck('id')),
                ];
            })
            ->filter(fn ($grupo) => $grupo['items']->isNotEmpty());

        $sesiones = DB::table('sessions')
            ->where('user_id', $cuenta->id)
            ->orderByDesc('last_activity')
            ->get(['ip_address', 'user_agent', 'last_activity']);

        return view('admin.users.show', [
            'cuenta' => $cuenta,
            'contenido' => $contenido,
            'sesiones' => $sesiones,
            'historial' => AdminAction::query()
                ->with('admin')
                ->where('target_type', 'user')
                ->where('target_id', $cuenta->id)
                ->latest('created_at')
                ->limit(20)
                ->get(),
            'hechasPorEl' => AdminAction::query()->where('admin_id', $cuenta->id)->count(),
            'interacciones' => DB::table('community_interactions')
                ->where('user_id', $cuenta->id)
                ->selectRaw('interaction_type, count(*) as total')
                ->groupBy('interaction_type')
                ->pluck('total', 'interaction_type'),
        ]);
    }

    public function ban(Request $request, int $user): RedirectResponse
    {
        $cuenta = $this->cuenta($user);
        $this->noASiMismo($request, $cuenta, 'bloquearte');
        $this->noAOtroAdmin($cuenta, 'bloquear');

        $datos = $request->validate([
            'duracion' => ['required', Rule::in(['1', '7', '30', 'custom', 'forever'])],
            'hasta' => ['nullable', 'required_if:duracion,custom', 'date', 'after:now'],
            'motivo' => ['required', 'string', 'max:500'],
        ], [], ['duracion' => 'duración', 'hasta' => 'fecha de fin', 'motivo' => 'motivo']);

        $hasta = match ($datos['duracion']) {
            'forever' => null,
            'custom' => Carbon::parse($datos['hasta']),
            default => now()->addDays((int) $datos['duracion']),
        };

        $cuenta->forceFill([
            'status' => 'SUSPENDED',
            'banned_at' => now(),
            'banned_until' => $hasta,
            'ban_reason' => $datos['motivo'],
            'banned_by' => $request->user()->id,
        ])->save();

        /* Si estaba dentro, sale ya: no hay que esperar a que su sesión caduque */
        $this->cerrarSesiones($cuenta);

        $this->audit->log('user.ban', $cuenta, [
            'hasta' => $hasta?->toDateTimeString(),
            'motivo' => $datos['motivo'],
        ]);

        return back()->with('success', $hasta
            ? "La cuenta de {$cuenta->name} queda bloqueada hasta el " . $hasta->translatedFormat('j \d\e F, H:i') . '.'
            : "La cuenta de {$cuenta->name} queda bloqueada sin fecha de fin.");
    }

    public function unban(int $user): RedirectResponse
    {
        $cuenta = $this->cuenta($user);

        $cuenta->forceFill([
            'status' => 'ACTIVE',
            'banned_at' => null,
            'banned_until' => null,
            'ban_reason' => null,
            'banned_by' => null,
        ])->save();

        $this->audit->log('user.unban', $cuenta);

        return back()->with('success', "{$cuenta->name} puede volver a entrar.");
    }

    public function role(Request $request, int $user): RedirectResponse
    {
        $cuenta = $this->cuenta($user);
        $this->noASiMismo($request, $cuenta, 'cambiarte el rol');

        $datos = $request->validate(['role' => ['required', Rule::in(['USER', 'ADMIN'])]]);

        if ($cuenta->isBanned() && $datos['role'] === 'ADMIN') {
            return back()->withErrors(['role' => 'Una cuenta bloqueada no puede ser admin. Desbloquéala primero.']);
        }

        $antes = $cuenta->role;
        $cuenta->forceFill(['role' => $datos['role']])->save();

        $this->audit->log('user.role', $cuenta, ['antes' => $antes, 'ahora' => $datos['role']]);

        return back()->with('success', $datos['role'] === 'ADMIN'
            ? "{$cuenta->name} ya es administrador."
            : "{$cuenta->name} ya no es administrador.");
    }

    public function badge(Request $request, int $user): RedirectResponse
    {
        $cuenta = $this->cuenta($user);

        $datos = $request->validate([
            'badge' => ['nullable', Rule::in(array_keys(User::CREATOR_BADGES))],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        $antes = $cuenta->creator_badge;

        $cuenta->forceFill([
            'creator_badge' => $datos['badge'] ?? null,
            'creator_badge_note' => ($datos['badge'] ?? null) ? ($datos['note'] ?? null) : null,
        ])->save();

        $this->audit->log('user.badge', $cuenta, ['antes' => $antes, 'ahora' => $datos['badge'] ?? null]);

        return back()->with('success', $cuenta->creator_badge_meta
            ? "{$cuenta->name} lleva ahora la insignia «{$cuenta->creator_badge_meta['label']}»."
            : "{$cuenta->name} ya no lleva insignia de creador.");
    }

    public function sessions(Request $request, int $user): RedirectResponse
    {
        $cuenta = $this->cuenta($user);
        $this->noASiMismo($request, $cuenta, 'cerrar tus propias sesiones desde aquí');

        $cerradas = $this->cerrarSesiones($cuenta);

        $this->audit->log('user.sessions', $cuenta, ['cerradas' => $cerradas]);

        return back()->with('success', $cerradas
            ? "Se han cerrado {$cerradas} " . Str::plural('sesión', $cerradas) . " de {$cuenta->name}."
            : "{$cuenta->name} no tenía ninguna sesión abierta.");
    }

    public function destroy(Request $request, int $user): RedirectResponse
    {
        $cuenta = $this->cuenta($user);
        $this->noASiMismo($request, $cuenta, 'eliminar tu propia cuenta desde aquí');
        $this->noAOtroAdmin($cuenta, 'eliminar');

        $this->cerrarSesiones($cuenta);
        $cuenta->delete();

        $this->audit->log('user.delete', $cuenta);

        return redirect()->route('admin.users.index', ['estado' => 'eliminadas'])
            ->with('success', "La cuenta de {$cuenta->name} está eliminada. Se puede recuperar desde aquí.");
    }

    public function restore(int $user): RedirectResponse
    {
        $cuenta = $this->cuenta($user);
        $cuenta->restore();

        $this->audit->log('user.restore', $cuenta);

        return redirect()->route('admin.users.show', $cuenta->id)
            ->with('success', "La cuenta de {$cuenta->name} vuelve a existir.");
    }

    /* Acciones sobre varias cuentas a la vez */
    public function bulk(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'accion' => ['required', Rule::in(['unban', 'badge-VERIFIED', 'badge-TRUSTED', 'badge-none', 'sessions'])],
        ], ['ids.required' => 'Marca al menos una cuenta.']);

        $cuentas = User::query()->whereIn('id', $datos['ids'])->where('id', '!=', $request->user()->id)->get();

        foreach ($cuentas as $cuenta) {
            match ($datos['accion']) {
                'unban' => $cuenta->banned_at && $this->unban($cuenta->id),
                'sessions' => $this->audit->log('user.sessions', $cuenta, ['cerradas' => $this->cerrarSesiones($cuenta)]),
                default => $this->ponerInsignia($cuenta, Str::after($datos['accion'], 'badge-')),
            };
        }

        return back()->with('success', 'Hecho en ' . $cuentas->count() . ' ' . Str::plural('cuenta', $cuentas->count()) . '.');
    }

    private function ponerInsignia(User $cuenta, string $badge): void
    {
        $nueva = $badge === 'none' ? null : $badge;

        if ($cuenta->creator_badge === $nueva) {
            return;
        }

        $antes = $cuenta->creator_badge;
        $cuenta->forceFill(['creator_badge' => $nueva, 'creator_badge_note' => null])->save();
        $this->audit->log('user.badge', $cuenta, ['antes' => $antes, 'ahora' => $nueva]);
    }

    private function cuenta(int $id): User
    {
        return User::withTrashed()->findOrFail($id);
    }

    private function cerrarSesiones(User $cuenta): int
    {
        $cuenta->forceFill(['remember_token' => Str::random(60)])->save();

        return DB::table('sessions')->where('user_id', $cuenta->id)->delete();
    }

    private function noASiMismo(Request $request, User $cuenta, string $que): void
    {
        if ($cuenta->is($request->user())) {
            abort(back()->withErrors(['cuenta' => "No puedes {$que}."]));
        }
    }

    private function noAOtroAdmin(User $cuenta, string $que): void
    {
        if ($cuenta->isAdmin()) {
            abort(back()->withErrors(['cuenta' => "Para {$que} a otro administrador, primero quítale el rol."]));
        }
    }
}
