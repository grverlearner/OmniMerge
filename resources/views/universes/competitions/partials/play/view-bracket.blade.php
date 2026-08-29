@php
    /*
     * Eliminación directa: el cuadro COMPLETO desde el primer momento.
     *
     * El motor genera los encuentros ronda a ronda, así que las rondas
     * futuras todavía no existen como filas. Aquí se deducen: si la
     * primera ronda tiene N encuentros, la siguiente tiene ceil(N/2), y
     * el ganador del encuentro i pasa al ceil(i/2) de la ronda siguiente.
     *
     * Esa deducción es SOLO para pintar. No se inventa ningún resultado:
     * los huecos futuros se muestran vacíos, diciendo de dónde saldrá su
     * ocupante.
     *
     * ------------------------------------------------------------------
     *
     * Las batallas de PUESTOS no son parte del cuadro y no se dibujan en
     * él. Un cuadro es un embudo —cada ronda tiene la mitad de gente que
     * la anterior— y los desempates rompen esa forma: cuatro batallas para
     * separar 9.º-16.º no son «la ronda que viene», son otra cosa.
     *
     * Se reconocen por su `group_label`: en eliminación directa solo las
     * rondas de desempate llevan uno. Van en su propia sección, debajo.
     */

    $todas = $block['rounds']->sortKeys();

    $esDesempate = fn($matches) => $matches->first()?->group_label !== null;

    $rounds = $todas->reject($esDesempate);
    $desempates = $todas->filter($esDesempate);

    $roundNumbers = $rounds->keys()->values();

    $firstRoundMatches = $roundNumbers->isNotEmpty()
        ? $rounds->get($roundNumbers->first())->values()
        : collect();

    /* Forma esperada del cuadro */
    $columns = [];
    $slots = max(1, $firstRoundMatches->count());
    $index = 0;

    while (true) {
        $columns[] = [
            'round_number' => $roundNumbers->get($index),
            'slots' => $slots,
        ];

        if ($slots <= 1) {
            break;
        }

        $slots = (int) ceil($slots / 2);
        $index++;

        /* Cortafuegos: un cuadro nunca tiene tantas rondas */
        if ($index > 12) {
            break;
        }
    }

    $roundName = function (int $slots, int $position) {
        return match ($slots) {
            1 => 'Final',
            2 => 'Semifinales',
            4 => 'Cuartos de final',
            8 => 'Octavos de final',
            16 => 'Dieciseisavos',
            default => 'Ronda ' . $position,
        };
    };

    /*
     * Qué puestos pide esta fase, para poder anunciarlos ANTES de que se
     * jueguen. Sin esto la fase arranca igual que una sin puestos
     * configurados, y no hay forma de distinguir «todavía no toca» de «no
     * se aplicó nada» — que es exactamente lo que parecía.
     */
    $plan = ($placementPlan ?? [])[$block['phase']->node_id] ?? null;

    /*
     * El tamaño de la fase.
     *
     * Un cuadro de 16 con desempates ocupa diez columnas y obliga a
     * arrastrar la pantalla de lado. Aquí se puede achicar.
     *
     * Las clases van literales, nunca compuestas: Tailwind lee el archivo
     * y una clase armada con 'w-' . $x sencillamente no existiría.
     */
    $anchos = [
        'S' => 'w-36',
        'M' => 'w-52',
        'L' => 'w-72',
    ];

    $llaveTamano = 'omnimerge.arena.' . $competition->id . '.bracket-size';
@endphp

<div x-data="{
        size: 'M',

        init() {
            try {
                const guardado = localStorage.getItem(@js($llaveTamano));
                if (['S', 'M', 'L'].includes(guardado)) {
                    this.size = guardado;
                }
            } catch (e) { /* modo privado, sin memoria */ }

            this.$watch('size', valor => {
                try { localStorage.setItem(@js($llaveTamano), valor); } catch (e) {}
            });
        },
    }">

    {{-- ============================================ --}}
    {{-- LOS PUESTOS QUE ESTA FASE VA A DISPUTAR --}}
    {{-- ============================================ --}}

    @if ($plan)

        <div @class([
            'flex flex-wrap items-center gap-3 border-b px-5 py-3',
            'border-amber-500/25 bg-amber-500/5' => !$plan['done'],
            'border-slate-800 bg-slate-900/30' => $plan['done'],
        ])>

            <span class="text-base">{{ $plan['done'] ? '✓' : '🏅' }}</span>

            <div class="min-w-0 flex-1">

                <p @class([
                    'text-[11px] font-black',
                    'text-amber-300' => !$plan['done'],
                    'text-emerald-300' => $plan['done'],
                ])>
                    @if ($plan['done'])
                        Los puestos ya están decididos
                    @elseif ($plan['pending'] > 0)
                        {{ $plan['pending'] }}
                        {{ $plan['pending'] === 1 ? 'batalla de puestos' : 'batallas de puestos' }}
                        por jugar — más abajo
                    @else
                        Esta fase disputa puestos al terminar el cuadro
                    @endif
                </p>

                @unless ($plan['done'])
                    <p class="mt-0.5 text-[11px] leading-relaxed text-slate-400">
                        El cuadro no decide estos puestos —dos pierden en la misma
                        ronda y ahí acaban los dos—, así que se juegan aparte
                        cuando el cuadro termine.
                    </p>
                @endunless

                {{--
                    Y POR QUÉ cada uno.

                    Un puesto se disputa porque una salida de la fase lo pide o
                    porque un premio lo entrega. Decir cuál evita la pregunta
                    obvia al ver batallas que nadie configuró a mano: se
                    configuraron, solo que desde la pestaña de premios.
                --}}
                <ul class="mt-2 flex flex-wrap gap-x-4 gap-y-1">

                    @foreach ($plan['wanted'] as $pedido)
                        <li class="text-[11px] leading-relaxed text-slate-400">
                            <strong class="font-black text-slate-200">{{ $pedido['label'] }}</strong>
                            @if (!empty($pedido['reasons']))
                                <span class="text-slate-500">
                                    · por {{ implode(' y por ', $pedido['reasons']) }}
                                </span>
                            @endif
                        </li>
                    @endforeach

                </ul>

            </div>

        </div>

    @endif


    {{-- ============================================ --}}
    {{-- TAMAÑO --}}
    {{-- ============================================ --}}

    <div class="flex items-center justify-end gap-2 px-5 pt-4">

        <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">Tamaño</span>

        <div class="flex items-center gap-1 rounded-xl bg-slate-900 p-1">

            @foreach (['S' => 'Compacto', 'M' => 'Normal', 'L' => 'Grande'] as $clave => $nombre)
                <button type="button" @click="size = '{{ $clave }}'"
                    :class="size === '{{ $clave }}'
                        ? 'bg-violet-500 text-white'
                        : 'text-slate-500 hover:text-slate-300'"
                    class="rounded-lg px-2.5 py-1 text-[10px] font-black transition">
                    {{ $nombre }}
                </button>
            @endforeach

        </div>

    </div>


    {{-- ============================================ --}}
    {{-- EL CUADRO --}}
    {{-- ============================================ --}}

    <div class="arena-scroll overflow-x-auto p-5">

        <div class="flex min-w-max items-stretch gap-4">

            @foreach ($columns as $columnIndex => $column)

                @php
                    $matches = $column['round_number'] !== null
                        ? $rounds->get($column['round_number'])->values()
                        : collect();

                    $played = $matches->where('status', 'COMPLETED')->count();

                    $name = $roundName($column['slots'], $columnIndex + 1);

                    $nextName = isset($columns[$columnIndex + 1])
                        ? $roundName($columns[$columnIndex + 1]['slots'], $columnIndex + 2)
                        : null;
                @endphp

                <div class="shrink-0"
                    :class="{ 'w-36': size === 'S', 'w-52': size === 'M', 'w-72': size === 'L' }">

                    {{-- CABECERA DE COLUMNA --}}

                    <div class="mb-3 flex items-center justify-between gap-2">

                        <p @class([
                            'truncate text-[10px] font-black uppercase tracking-wider',
                            'text-white' => $matches->isNotEmpty() && $played < $matches->count(),
                            'text-slate-500' => $matches->isEmpty() || $played === $matches->count(),
                        ])>
                            {{ $name }}
                        </p>

                        @if ($matches->isNotEmpty())
                            <span class="shrink-0 rounded bg-slate-800 px-1.5 py-0.5 font-mono text-[9px] font-black text-slate-500">
                                {{ $played }}/{{ $matches->count() }}
                            </span>
                        @else
                            <span class="shrink-0 rounded bg-slate-900 px-1.5 py-0.5 text-[9px] font-black text-slate-700">
                                —
                            </span>
                        @endif

                    </div>


                    {{-- HUECOS --}}

                    <div class="space-y-3">

                        @for ($slot = 1; $slot <= $column['slots']; $slot++)

                            @php
                                $match = $matches->get($slot - 1);

                                /* A dónde va el ganador de este hueco */
                                $destination = $nextName
                                    ? $nextName . ' · ' . (int) ceil($slot / 2)
                                    : null;

                                /* De dónde vendrá su ocupante */
                                $feeders = $columnIndex > 0
                                    ? [$slot * 2 - 1, $slot * 2]
                                    : null;

                                $previousName = $columnIndex > 0
                                    ? $roundName($columns[$columnIndex - 1]['slots'], $columnIndex)
                                    : null;

                                /*
                                 * Quien ya se ha clasificado a este hueco. El
                                 * motor no crea la ronda siguiente hasta que
                                 * termina la actual, pero el ganador de un
                                 * enfrentamiento ya jugado SI se conoce, y
                                 * dejarlo invisible hace pensar que se perdio.
                                 */
                                $qualified = [];

                                if ($feeders && $columnIndex > 0) {

                                    $previousRound = $columns[$columnIndex - 1]['round_number'];

                                    $previousMatches = $previousRound !== null
                                        ? $rounds->get($previousRound)->values()
                                        : collect();

                                    foreach ($feeders as $feeder) {

                                        $feederMatch = $previousMatches->get($feeder - 1);

                                        if (! $feederMatch || $feederMatch->status !== 'COMPLETED') {
                                            $qualified[] = null;
                                            continue;
                                        }

                                        $winnerIsA = $feederMatch->winner_key === $feederMatch->participant_a_key;

                                        $qualified[] = [
                                            'name' => $winnerIsA
                                                ? $feederMatch->participant_a_name
                                                : $feederMatch->participant_b_name,
                                            'entity' => $winnerIsA
                                                ? $feederMatch->participantAEntity
                                                : $feederMatch->participantBEntity,
                                            'from' => $feeder,
                                        ];
                                    }
                                }

                                $anyQualified = collect($qualified)->filter()->isNotEmpty();
                            @endphp

                            @if ($match)
                                @include('universes.competitions.partials.play.match-chip', [
                                    'match' => $match,
                                    'destination' => $destination,
                                ])
                            @else

                                {{-- HUECO TODAVÍA SIN OCUPANTES --}}

                                <div @class([
                                    'rounded-xl border border-dashed p-2',
                                    'border-violet-500/40 bg-violet-950/20' => $anyQualified,
                                    'border-slate-800 bg-slate-900/20' => !$anyQualified,
                                ])>

                                    {{-- Quien ya esta dentro y quien falta --}}

                                    <div class="flex gap-1.5">

                                        @foreach ($qualified ?: [null, null] as $slotIndex => $who)

                                            <div class="relative w-1/2">

                                                @if ($who)
                                                    <div class="relative aspect-square overflow-hidden rounded-lg bg-slate-800 ring-1 ring-violet-500/40">

                                                        @if ($who['entity']?->image_url)
                                                            <img src="{{ $who['entity']->image_url }}"
                                                                alt="{{ $who['name'] }}" class="h-full w-full object-cover">
                                                        @else
                                                            <div class="flex h-full w-full items-center justify-center text-sm opacity-30">✦</div>
                                                        @endif

                                                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950 via-slate-950/85 to-transparent px-1 pb-0.5 pt-2">
                                                            <p class="truncate text-[9px] font-black leading-tight text-white">
                                                                {{ $who['name'] }}
                                                            </p>
                                                        </div>

                                                        <span class="absolute left-0.5 top-0.5 text-[9px]">✔</span>

                                                    </div>
                                                @else
                                                    <div class="flex aspect-square items-center justify-center rounded-lg border border-dashed border-slate-800">
                                                        <span class="text-[9px] font-black text-slate-700">
                                                            {{ $feeders[$slotIndex] ?? '?' }}
                                                        </span>
                                                    </div>
                                                @endif

                                            </div>
                                        @endforeach

                                    </div>

                                    <p class="mt-1.5 text-[9px] font-bold leading-tight text-slate-600">
                                        @if ($anyQualified)
                                            <span class="text-violet-400">Clasificado</span> · falta el otro lado
                                        @elseif ($feeders)
                                            Ganadores de {{ $previousName }} · {{ $feeders[0] }} y {{ $feeders[1] }}
                                        @else
                                            Sin asignar
                                        @endif
                                    </p>

                                    @if ($destination)
                                        <p class="mt-1.5 truncate text-[9px] font-black text-slate-700">
                                            → {{ $destination }}
                                        </p>
                                    @endif

                                </div>
                            @endif

                        @endfor

                    </div>

                </div>


                {{-- SEPARADOR ENTRE RONDAS --}}

                @if (!$loop->last)
                    <div class="flex w-4 shrink-0 items-center justify-center">
                        <div class="h-full w-px bg-gradient-to-b from-transparent via-slate-800 to-transparent"></div>
                    </div>
                @endif
            @endforeach

        </div>

    </div>


    {{-- ============================================ --}}
    {{-- LOS DESEMPATES DE PUESTOS --}}
    {{-- ============================================ --}}
    {{--
        Van fuera del cuadro porque no son parte de él: no alimentan una
        ronda siguiente, parten un empate en dos mitades. Cada bloque es
        una banda —«Puestos 13.º–16.º»— y sus batallas deciden quién se
        queda con la mitad de arriba.
    --}}

    @if ($desempates->isNotEmpty())

        <div class="border-t border-slate-800 bg-slate-950/40 px-5 py-4">

            <div class="mb-3 flex items-center gap-2">
                <span class="text-sm">🏅</span>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-amber-400">
                    Definición de puestos
                </p>
                <span class="rounded bg-slate-800 px-1.5 py-0.5 font-mono text-[9px] font-black text-slate-500">
                    {{ $desempates->sum(fn($m) => $m->count()) }}
                </span>
            </div>

            <div class="arena-scroll overflow-x-auto">

                <div class="flex min-w-max items-stretch gap-4">

                    @foreach ($desempates as $numero => $matches)

                        @php
                            $matches = $matches->values();
                            $jugadas = $matches->where('status', 'COMPLETED')->count();
                            $titulo = $matches->first()?->group_label ?? 'Puestos';
                        @endphp

                        <div class="shrink-0"
                            :class="{ 'w-36': size === 'S', 'w-52': size === 'M', 'w-72': size === 'L' }">

                            <div class="mb-3 flex items-center justify-between gap-2">

                                <p @class([
                                    'truncate text-[10px] font-black uppercase tracking-wider',
                                    'text-amber-300' => $jugadas < $matches->count(),
                                    'text-slate-500' => $jugadas === $matches->count(),
                                ])>
                                    {{ $titulo }}
                                </p>

                                <span class="shrink-0 rounded bg-slate-800 px-1.5 py-0.5 font-mono text-[9px] font-black text-slate-500">
                                    {{ $jugadas }}/{{ $matches->count() }}
                                </span>

                            </div>

                            <div class="space-y-3">

                                @foreach ($matches as $match)
                                    @include('universes.competitions.partials.play.match-chip', [
                                        'match' => $match,
                                        'destination' => null,
                                    ])
                                @endforeach

                            </div>

                        </div>

                        @if (!$loop->last)
                            <div class="flex w-4 shrink-0 items-center justify-center">
                                <div class="h-full w-px bg-gradient-to-b from-transparent via-slate-800 to-transparent"></div>
                            </div>
                        @endif

                    @endforeach

                </div>

            </div>

            <p class="mt-3 text-[10px] leading-relaxed text-slate-600">
                Quien gana se queda con la mitad de arriba de la banda; quien
                pierde, con la de abajo. Solo se juegan las bandas que hacen
                falta para los puestos que pediste: las demás quedan
                empatadas, porque separarlas sería hacer competir a la gente
                para nada.
            </p>

        </div>

    @endif

</div>
