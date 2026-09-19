<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Admin\ContentFlags;
use App\Support\Site\CommunityModeration;
use App\Support\Site\SiteSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/*
|--------------------------------------------------------------------------
| Lo que decide un admin, aplicado en cada petición
|--------------------------------------------------------------------------
|
|   cuenta bloqueada     se cierra la sesión aunque ya estuviera dentro:
|                        bloquear a alguien no puede esperar a que salga
|   mantenimiento        todos ven la página de mantenimiento salvo los
|                        admins y la pantalla de entrada
|   registro cerrado     /register responde que ahora no se admiten altas
|   comunidad cerrada    la comunidad deja de estar a la vista
|
| Los admins nunca quedan fuera: si un admin cierra el sitio, tiene que
| poder volver a abrirlo.
|
*/

class EnforceSiteRules
{
    public function __construct(private readonly SiteSettings $site) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $user->liftExpiredBan();

            if ($user->isBanned()) {
                $mensaje = $user->banMessage();

                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors(['email' => $mensaje]);
            }
        }

        $esAdmin = (bool) $user?->isAdmin();

        if (! $esAdmin && $this->site->inMaintenance() && ! $this->libreEnMantenimiento($request)) {
            return response()->view('site.maintenance', [
                'mensaje' => $this->site->get('maintenance_message'),
            ], 503);
        }

        if (! $this->site->get('registration_open') && $request->routeIs('register')) {
            return redirect()->route('login')->withErrors([
                'email' => 'Ahora mismo no se admiten cuentas nuevas.',
            ]);
        }

        if (! $esAdmin && ! $this->site->get('community_open') && $this->esComunidad($request)) {
            return response()->view('site.closed', [
                'titulo' => 'La comunidad está cerrada',
                'mensaje' => 'Ahora mismo no se puede explorar lo que hacen otros. Tu biblioteca, tus torneos y tus universos siguen funcionando.',
            ], 403);
        }

        if (! $esAdmin && ($this->esComunidad($request) || $request->routeIs('profiles.show'))) {
            CommunityModeration::apply();

            /*
             * Los parámetros de la ruta ya se resolvieron antes de llegar aquí,
             * así que lo que se abre por enlace directo se comprueba a mano.
             */
            foreach ($request->route()?->parameters() ?? [] as $parametro) {
                if ($this->retirado($parametro, $user)) {
                    abort(404);
                }
            }
        }

        return $next($request);
    }

    /* Algo que la comunidad no debe enseñar: oculto por un admin o de una cuenta bloqueada */
    private function retirado(mixed $parametro, ?User $yo): bool
    {
        if ($parametro instanceof User) {
            return ! $parametro->is($yo) && CommunityModeration::hides($parametro);
        }

        foreach (CommunityModeration::MODELS as $type => $clase) {
            if ($parametro instanceof $clase) {
                if ($parametro->user_id === $yo?->id) {
                    return false;
                }

                return in_array('HIDDEN', app(ContentFlags::class)->of($type, $parametro->getKey()), true)
                    || CommunityModeration::hides(User::withTrashed()->withoutGlobalScope('omni-moderacion')->find($parametro->user_id));
            }
        }

        return false;
    }

    /* Entrar y salir siguen funcionando: un admin tiene que poder entrar a apagarlo */
    private function libreEnMantenimiento(Request $request): bool
    {
        return $request->routeIs('login', 'logout', 'password.*')
            || $request->is('build/*', 'storage/*', 'up');
    }

    private function esComunidad(Request $request): bool
    {
        return $request->routeIs('community.*', 'tournaments.community.*');
    }
}
