<?php

namespace App\Http\Controllers\Universes;

use App\Http\Controllers\Controller;
use App\Models\TournamentStart;
use App\Models\Universe;
use App\Models\UniverseEntity;
use App\Models\UniverseTournament;
use App\Services\Universes\CompetitionStartRouting;
use App\Services\Universes\ParticipantRoomBuilder;
use App\Services\Universes\UniverseTournamentEligibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| UniverseTournamentParticipantsController
|--------------------------------------------------------------------------
|
| La sala de participantes de un torneo del universo.
|
| Quién entra -por atributos, valores de catálogo con sus hijos, grupos y a
| mano-, por qué puerta del recorrido -con reglas, repartido solo o uno a
| uno- y con qué cara -la versión que la Biblioteca vincula al catálogo
| que pide la regla, o la que se elija-.
|
| Todo se guarda en `eligibility` del torneo: es lo que heredan sus
| ediciones. Ver docs/md/79-Sala-De-Participantes.md
|
*/

class UniverseTournamentParticipantsController extends Controller
{
    public function show(
        Universe $universe,
        UniverseTournament $universeTournament,
        ParticipantRoomBuilder $builder
    ): View {

        $this->authorize('update', $universe);

        abort_unless((int) $universeTournament->universe_id === (int) $universe->id, 404);

        $universeTournament->load('tournamentTemplate');

        return view('universes.tournaments.participants', [
            'universe' => $universe,
            'torneo' => $universeTournament,
            'sala' => $builder->build($universe, $universeTournament),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | ¿Se puede jugar con este diseño?
    |--------------------------------------------------------------------------
    |
    | La sala avisa en el acto cuando a una fase de entrada le llegan menos
    | de los que pide. Lo que pasa MAS ADELANTE —la liguilla solo manda ocho
    | a una fase de grupos que pide dieciseis— solo se sabe jugando.
    |
    | Aqui se juega: con el diseño que hay en pantalla, sin guardarlo, se
    | monta una edicion provisional, se ensaya con el mismo motor
    | (EditionRehearsal) y se deshace todo. No queda nada escrito.
    |
    */

    public function rehearse(
        Request $request,
        Universe $universe,
        UniverseTournament $universeTournament
    ): \Illuminate\Http\JsonResponse {

        $this->authorize('update', $universe);

        abort_unless((int) $universeTournament->universe_id === (int) $universe->id, 404);

        $diseno = json_decode((string) $request->input('design', '{}'), true);
        $diseno = is_array($diseno) ? $diseno : [];

        /* En la sala del torneo el diseño es el propio torneo, sobre todo el universo */
        if ($request->input('context') !== 'EDITION') {
            $diseno['source'] = 'CUSTOM';
            $diseno['scope'] = 'UNIVERSE';
        }

        $templateId = (int) ($request->input('template_id') ?: $universeTournament->tournament_template_id);

        $participantes = app(\App\Services\Universes\EditionParticipants::class);

        $db = \Illuminate\Support\Facades\DB::connection();
        $db->beginTransaction();

        try {
            $assignments = array_filter(
                $participantes->resolve($universe, $universeTournament, $diseno, $templateId)['assignments'],
                fn ($ids) => $ids !== []
            );

            if ($assignments === []) {
                return response()->json([
                    'ok' => false,
                    'problem' => 'Con este diseño no entra nadie, o nadie cabe en las puertas.',
                ]);
            }

            $instance = app(\App\Services\Tournaments\Runtime\TournamentInstanceService::class)->create(
                $universe,
                $universeTournament,
                [
                    'name' => 'Ensayo',
                    'tournament_template_id' => $templateId,
                    'series_format' => $universeTournament->series_format ?: 'BEST_OF',
                    'best_of' => $universeTournament->best_of ?: 1,
                    'fixed_games' => $universeTournament->fixed_games ?: 1,
                    'game_key' => $universeTournament->game_key,
                ],
                $assignments
            );

            $problema = app(\App\Services\Tournaments\Runtime\EditionRehearsal::class)
                ->problem($instance->fresh());

            return response()->json([
                'ok' => $problema === null,
                'problem' => $problema,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'ok' => false,
                'problem' => collect($e->errors())->flatten()->first() ?: 'No se pudo preparar el ensayo.',
            ]);
        } finally {
            $db->rollBack();
        }
    }


    public function update(
        Request $request,
        Universe $universe,
        UniverseTournament $universeTournament,
        UniverseTournamentEligibility $eligibility,
        CompetitionStartRouting $routing
    ): RedirectResponse {

        $this->authorize('update', $universe);

        abort_unless((int) $universeTournament->universe_id === (int) $universe->id, 404);

        $data = $request->validate([
            'design' => ['required', 'json', 'max:500000'],
        ], [
            'design.required' => 'No llegó nada que guardar.',
            'design.json' => 'Lo que llegó de la sala no se pudo leer. Vuelve a intentarlo.',
        ]);

        $diseno = json_decode($data['design'], true) ?: [];

        /* ------------------------------------ quien entra y con que cara */

        $propios = UniverseEntity::query()
            ->where('universe_id', $universe->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->flip();

        $reglas = $eligibility->normalize($diseno);

        /* Nadie de otro universo, ni a mano ni con cara elegida */
        $reglas['include'] = array_values(array_filter($reglas['include'], fn ($id) => isset($propios[$id])));
        $reglas['exclude'] = array_values(array_filter($reglas['exclude'], fn ($id) => isset($propios[$id])));
        $reglas['faces'] = array_filter($reglas['faces'], fn ($v, $id) => isset($propios[$id]), ARRAY_FILTER_USE_BOTH);

        /* ------------------------------------------------ las puertas */

        $puertas = TournamentStart::query()
            ->where('tournament_template_id', $universeTournament->tournament_template_id)
            ->where('status', 'ACTIVE')
            ->orderBy('sequence_number')
            ->get()
            ->mapWithKeys(fn ($s) => [(int) $s->id => $s->expected_participants ? (int) $s->expected_participants : null])
            ->all();

        $doors = $routing->normalizeDoors($diseno['doors'] ?? null);

        $doors['template_id'] = (int) $universeTournament->tournament_template_id;
        $doors['rules'] = array_values(array_filter($doors['rules'], fn ($r) => array_key_exists($r['start_id'], $puertas)));
        $doors['manual'] = array_intersect_key($doors['manual'], $puertas);
        $doors['value_doors'] = array_filter($doors['value_doors'], fn ($p) => array_key_exists($p, $puertas));

        foreach ($doors['manual'] as $puerta => $ids) {
            $doors['manual'][$puerta] = array_values(array_filter($ids, fn ($id) => isset($propios[$id])));
        }

        $universeTournament->update([
            'eligibility' => $reglas + ['doors' => $doors],
        ]);

        /* ---------------------------------- lo que queda, para decirlo */

        $guardado = $universeTournament->fresh()->eligibility;
        $entran = $eligibility->matching($universe, $guardado)->count();
        $plan = $routing->plan($universe, $doors, $puertas, $guardado);
        $colocados = collect($plan['assignments'])->flatten()->count();

        $mensaje = $entran === 1 ? 'Guardado: 1 competidor cumple las reglas' : "Guardado: {$entran} competidores cumplen las reglas";

        if (count($puertas) > 0 && $colocados < $entran) {
            $mensaje .= ', y ' . ($entran - $colocados) . ' no caben en las puertas';
        }

        return redirect()
            ->route('universes.tournaments.participants', [$universe, $universeTournament])
            ->with('success', $mensaje . '. Las ediciones nuevas lo heredan.');
    }
}
