@php
    /*
     * Aviso: esta fase ya la usa una plantilla de torneo.
     *
     * No bloquea nada. La plantilla es del usuario y sabrá lo que hace; lo
     * que no puede pasar es que lo haga sin saberlo.
     *
     * Una plantilla de fase deja de ser un borrador propio en cuanto entra
     * en un recorrido: cambiarle las rondas, las salidas o las puertas
     * cambia todos los torneos que la montaron, y puede dejar conexiones
     * apuntando a una salida que ya no existe —algo que no se ve hasta que
     * alguien intenta jugar—.
     *
     * Por eso el aviso trae la salida razonable al lado: duplicarla y
     * editar la copia.
     *
     * Las competiciones ya jugadas no corren peligro: se juegan sobre un
     * snapshot inmutable. Se dicen igual, porque saber que una plantilla ya
     * se jugó cambia las ganas de tocarla.
     *
     * $dark  para la Super Edición, que es oscura
     */

    $dark = $dark ?? false;

    $uso = app(\App\Services\Tournaments\PhaseTemplateUsage::class)->of($phaseTemplate);

    /*
     * «2 plantillas de torneo, en 7 sitios» — lo segundo solo cuando de
     * verdad aporta: una misma plantilla puede estar montada varias veces
     * dentro del mismo recorrido.
     */
    $cuantas = $uso['tournaments']->count();

    $frase = $cuantas === 1 ? 'plantilla de torneo' : 'plantillas de torneo';

    if ($uso['nodes'] > $cuantas) {
        $frase .= ', en ' . $uso['nodes'] . ' sitios';
    }
@endphp

@if ($uso['in_use'])

    <section @class([
        'mb-6 overflow-hidden rounded-2xl border',
        'border-amber-500/40 bg-amber-500/10' => $dark,
        'border-amber-300 bg-amber-50' => ! $dark,
    ])>

        <div class="flex flex-wrap items-start gap-4 p-4 sm:p-5">

            <span class="text-2xl leading-none">⚠</span>

            <div class="min-w-0 flex-1">

                <p @class([
                    'text-[11px] font-black uppercase tracking-[0.18em]',
                    'text-amber-300' => $dark,
                    'text-amber-700' => ! $dark,
                ])>
                    Esta fase ya está en uso
                </p>

                <p @class([
                    'mt-1.5 text-sm leading-relaxed',
                    'text-slate-200' => $dark,
                    'text-slate-700' => ! $dark,
                ])>
                    La usan
                    <strong class="font-black">{{ $uso['tournaments']->count() }}</strong>
                    {{ $frase }}.
                    Lo que cambies aquí —rondas, salidas, puertas— cambia
                    <strong class="font-black">esos recorridos</strong>, y puede dejar
                    conexiones apuntando a algo que ya no existe.
                </p>

                {{-- Cuáles, por su nombre --}}

                <div class="mt-2.5 flex flex-wrap gap-1.5">
                    @foreach ($uso['tournaments'] as $torneo)
                        <a href="{{ route('tournaments.templates.show', $torneo) }}" @class([
                            'rounded-lg border px-2.5 py-1 text-[11px] font-bold transition',
                            'border-slate-700 bg-slate-950 text-slate-200 hover:border-amber-400' => $dark,
                            'border-amber-200 bg-white text-slate-700 hover:border-amber-400' => ! $dark,
                        ])>
                            {{ $torneo->name }}
                        </a>
                    @endforeach
                </div>

                @if ($uso['played'] > 0)
                    <p @class([
                        'mt-2.5 text-[11px] leading-relaxed',
                        'text-slate-400' => $dark,
                        'text-slate-500' => ! $dark,
                    ])>
                        Ya se {{ $uso['played'] === 1 ? 'ha jugado' : 'han jugado' }}
                        <strong class="font-black">{{ $uso['played'] }}</strong>
                        {{ $uso['played'] === 1 ? 'competición' : 'competiciones' }}
                        con {{ $uso['tournaments']->count() === 1 ? 'ella' : 'ellas' }}.
                        Esas no se tocan —se juegan sobre una copia congelada— pero las
                        ediciones futuras sí saldrán con lo que dejes aquí.
                    </p>
                @endif

            </div>

            {{-- La salida razonable --}}

            @can('duplicate', $phaseTemplate)
                <form method="POST" action="{{ route('tournaments.phase-templates.duplicate', $phaseTemplate) }}"
                    class="shrink-0">
                    @csrf

                    <button type="submit" @class([
                        'rounded-xl px-4 py-2.5 text-xs font-black transition',
                        'bg-amber-500 text-slate-950 hover:bg-amber-400' => $dark,
                        'bg-amber-600 text-white hover:bg-amber-500' => ! $dark,
                    ])>
                        ⧉ Duplicar y editar la copia
                    </button>

                    <p @class([
                        'mt-1.5 max-w-[190px] text-[10px] leading-relaxed',
                        'text-slate-400' => $dark,
                        'text-slate-500' => ! $dark,
                    ])>
                        Deja intactos los torneos que ya la usan.
                    </p>
                </form>
            @endcan

        </div>

    </section>

@endif
