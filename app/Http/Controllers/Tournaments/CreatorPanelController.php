<?php

namespace App\Http\Controllers\Tournaments;

use App\Http\Controllers\Controller;
use App\Models\PhaseTemplate;
use App\Models\TournamentTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/*
 * El panel de creador.
 *
 * Publicar una plantilla es enseñársela a alguien, y hasta ahora no había
 * ninguna pantalla que dijera QUÉ está viendo ese alguien. Esta lo dice, y
 * deja cambiarlo sin salir de aquí:
 *
 *   quién eres      nombre, titular, biografía, sitio, y si tu perfil se ve
 *   qué has soltado lo que está publicado, con sus visitas y sus copias
 *   qué falta       lo que está a un paso de publicarse y no lo está
 *
 * El último bloque es el motivo de que este panel exista. Una plantilla
 * activa, pública y clonable es visible; si le falta cualquiera de las tres
 * cosas no la ve nadie, y hasta ahora eso solo se descubría preguntándole a
 * otro por qué no la encontraba.
 */
class CreatorPanelController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        $torneos = TournamentTemplate::query()
            ->where('user_id', $user->id)
            ->withCount(['graphNodes', 'graphTerminals'])
            ->latest('updated_at')
            ->get();

        $fases = PhaseTemplate::query()
            ->where('user_id', $user->id)
            ->withCount(['exits', 'inputGates', 'tournamentPhaseNodes'])
            ->latest('updated_at')
            ->get();

        $publicados = $torneos->filter->isPublished()
            ->concat($fases->filter->isPublished())
            ->sortByDesc('published_at')
            ->values();

        return view('tournaments.creator', [
            'creator' => $user,
            'tournaments' => $torneos,
            'phases' => $fases,
            'published' => $publicados,
            'blockers' => $this->queFaltaParaVerse($torneos, $fases, $user),
            'totals' => [
                'published' => $publicados->count(),
                'clones' => $torneos->sum('clones_count') + $fases->sum('clones_count'),
                'views' => $torneos->sum('views_count') + $fases->sum('views_count'),
                'clonable' => $publicados->filter->canBeCloned()->count(),
            ],
        ]);
    }

    /*
     * Qué impide que te vean.
     *
     * Tres condiciones tiene que cumplir una pieza para aparecer en la
     * comunidad: estar ACTIVA, ser PÚBLICA y tener fecha de publicación. Y
     * una cuarta para que sirva de algo: permitir la copia. Cada línea
     * enseña cuántas piezas se quedan fuera por cada motivo, y lleva a
     * arreglarlo.
     *
     * @return array<int,array<string,mixed>>
     */
    private function queFaltaParaVerse($torneos, $fases, $user): array
    {
        $todas = $torneos->concat($fases);

        $publicasNoActivas = $todas
            ->filter(fn ($p) => $p->visibility === 'PUBLIC' && $p->status !== 'ACTIVE')
            ->count();

        $activasNoPublicas = $todas
            ->filter(fn ($p) => $p->status === 'ACTIVE' && $p->visibility !== 'PUBLIC')
            ->count();

        $publicadasSinCopia = $todas
            ->filter(fn ($p) => $p->isPublished() && ! $p->allow_cloning)
            ->count();

        $lineas = [
            [
                'severity' => 'error',
                'title' => 'Tu perfil está oculto',
                'detail' => 'Con el perfil privado nadie puede abrir tu página de creador, aunque tus plantillas sí se vean.',
                'count' => $user->isPublicProfile() ? 0 : 1,
                'action' => null,
            ],
            [
                'severity' => 'warning',
                'title' => 'Públicas pero no activas',
                'detail' => 'Marcadas como públicas, pero siguen en borrador o archivadas: no aparecen.',
                'count' => $publicasNoActivas,
                'action' => route('tournaments.templates.index', ['visibility' => 'PUBLIC', 'status' => 'DRAFT']),
            ],
            [
                'severity' => 'info',
                'title' => 'Activas pero privadas',
                'detail' => 'Terminadas y en uso, pero solo las ves tú. Son las candidatas a publicar.',
                'count' => $activasNoPublicas,
                'action' => route('tournaments.templates.index', ['status' => 'ACTIVE', 'visibility' => 'PRIVATE']),
            ],
            [
                'severity' => 'info',
                'title' => 'Publicadas sin permitir copia',
                'detail' => 'Se ven, pero nadie puede llevárselas: sirven de escaparate, no de pieza.',
                'count' => $publicadasSinCopia,
                'action' => route('tournaments.templates.index', ['visibility' => 'PUBLIC']),
            ],
        ];

        return array_values(array_filter($lineas, fn ($l) => $l['count'] > 0));
    }

    /*
     * Guardar la parte pública del perfil.
     *
     * Solo estos cinco campos: el nombre, el correo y la contraseña siguen
     * en su pantalla de siempre, porque no son «cómo te ven» sino quién
     * eres para el sistema.
     */
    public function update(Request $request): RedirectResponse
    {
        $datos = Validator::make($request->all(), [
            'headline' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'location' => ['nullable', 'string', 'max:120'],
            'website' => ['nullable', 'url', 'max:255'],
            'profile_visibility' => ['required', 'in:PUBLIC,PRIVATE'],
        ])->validate();

        foreach (['headline', 'bio', 'location', 'website'] as $campo) {
            $datos[$campo] = trim((string) ($datos[$campo] ?? '')) ?: null;
        }

        $request->user()->update($datos);

        return redirect()
            ->route('tournaments.creator.show')
            ->with('success', 'Perfil de creador actualizado.');
    }
}
