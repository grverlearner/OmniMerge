<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/*
| El espacio de administración es solo para admins. A cualquier otro se le
| responde «no encontrado» y no «prohibido»: no hace falta anunciar que la
| puerta existe.
*/
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isAdmin(), 404);

        return $next($request);
    }
}
