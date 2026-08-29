@php
    /*
     * Una batalla. La imagen manda; el nombre va dentro, abajo.
     *
     * Es pequeña a propósito: en un cuadro de 16 tienen que caber muchas
     * en pantalla sin hacer scroll vertical.
     *
     * $match  TournamentInstanceMatch
     */

    /* A donde pasa el ganador. Solo lo envia el bracket. */
    $destination = $destination ?? null;

    $isDone = $match->status === 'COMPLETED';

    /*
     * TODOS los que juegan, no dos.
     *
     * Una fase puede cruzar de cuatro en cuatro. Esta ficha solo pintaba A
     * y B, así que una competición de 16 jugada así enseñaba 8: de cada
     * encuentro se veía la mitad.
     *
     * `participants` es la lista completa. Cuando no está —filas de antes
     * de que existiera— se reconstruye con A y B, que es exactamente lo
     * que son los duelos.
     */
    $lista = collect($match->participants ?? [])
        ->filter(fn ($p) => ($p['key'] ?? null) !== null)
        ->values();

    if ($lista->isEmpty()) {
        $lista = collect([
            ['key' => $match->participant_a_key, 'name' => $match->participant_a_name],
            ['key' => $match->participant_b_key, 'name' => $match->participant_b_name],
        ])->filter(fn ($p) => $p['key'] !== null)->values();
    }

    /*
     * Las caras.
     *
     * El modelo solo tiene accesor para A y B, así que los demás saldrían
     * sin foto. `$participants` —la lista de la competición, que la
     * pantalla ya tiene cargada— cubre a todos sin una consulta más.
     */
    $caras = [
        $match->participant_a_key => $match->participantAEntity,
        $match->participant_b_key => $match->participantBEntity,
    ];

    $porClave = isset($participants)
        ? collect($participants)->keyBy('runtime_key')
        : collect();

    $duelo = $lista->count() === 2;

    $sides = $lista
        ->map(fn ($p, $i) => [
            'key' => $p['key'],
            'name' => $p['name'] ?? null,
            'entity' => $caras[$p['key']]
                ?? $porClave->get($p['key'])?->universeEntity,

            /*
             * El marcador solo existe por pareja. Con más de dos se calla
             * en vez de atribuirle a un tercero un número que no es suyo.
             */
            'qualified' => $p['qualified'] ?? false,

            'score' => $duelo
                ? ($match->series ? ($match->series_score[$i] ?? null) : ($i === 0 ? $match->score_a : $match->score_b))
                : null,
        ])
        ->all();

    /* Listo para jugar cuando están todos sus sitios ocupados */
    $isReady = $match->status === 'PENDING' && count($sides) >= 2;
@endphp

<button type="button"
    @click="openBattle('{{ $match->runtime_match_id }}')"
    @class([
        'group relative block w-full overflow-hidden rounded-xl border text-left transition',
        'border-emerald-500/40 bg-emerald-950/30 hover:border-emerald-400' => $isDone,
        'border-violet-500/50 bg-violet-950/40 hover:border-violet-400 hover:shadow-lg hover:shadow-violet-900/40' => $isReady,
        'border-slate-800 bg-slate-900/40 hover:border-slate-700' => !$isDone && !$isReady,
    ])>

{{--
        Un lado por participante.

        El ancho se reparte entre los que hay: con dos son mitades, con
        cuatro son cuartos. Estaba fijo en w-1/2, así que un encuentro de
        cuatro no cabía —y de hecho ni llegaba: solo se pintaban dos—.

        Literales y no interpolación: Tailwind lee el código fuente.
    --}}

    @php
        $ancho = match (count($sides)) {
            1 => 'w-full',
            2 => 'w-1/2',
            3 => 'w-1/3',
            4 => 'w-1/4',
            5 => 'w-1/5',
            default => 'w-1/6',
        };
    @endphp

    <div class="flex">

        @foreach ($sides as $index => $side)

            @php
                $isWinner = $isDone && (
                    $match->winner_key === $side['key']
                    || ($side['qualified'] ?? false)
                );
            @endphp

            <div class="relative {{ $ancho }} {{ $index + 1 < count($sides) ? 'border-r border-slate-950/60' : '' }}">

                {{-- Retrato --}}
                <div class="relative aspect-square overflow-hidden bg-slate-800">

                    @if ($side['entity']?->image_url)
                        <img src="{{ $side['entity']->image_url }}" alt="{{ $side['name'] }}"
                            @class([
                                'h-full w-full object-cover transition duration-300 group-hover:scale-105',
                                'opacity-40 grayscale' => $isDone && !$isWinner,
                            ])>
                    @else
                        <div class="flex h-full w-full items-center justify-center text-lg opacity-25">✦</div>
                    @endif

                    {{-- Nombre DENTRO de la imagen, abajo --}}
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950 via-slate-950/85 to-transparent px-1.5 pb-1 pt-3">
                        <p @class([
                            'truncate text-[10px] leading-tight',
                            'font-black text-white' => $isWinner || !$isDone,
                            'font-semibold text-slate-500' => $isDone && !$isWinner,
                        ])>
                            {{ $side['name'] ?: '—' }}
                        </p>
                    </div>

                    {{-- Marcador --}}
                    @if ($match->series || $isDone)
                        <span @class([
                            'absolute right-1 top-1 rounded-md px-1.5 py-0.5 font-mono text-[11px] font-black shadow',
                            'bg-emerald-400 text-slate-950' => $isWinner,
                            'bg-slate-950/85 text-slate-400' => !$isWinner,
                        ])>
                            {{ $side['score'] ?? 0 }}
                        </span>
                    @endif

                    {{-- Corona del ganador --}}
                    @if ($isWinner)
                        <span class="absolute left-1 top-1 text-xs drop-shadow">👑</span>
                    @endif

                </div>

            </div>
        @endforeach

    </div>


    {{-- PIE: formato de serie y estado --}}

    <div @class([
        'flex items-center justify-between gap-1 px-2 py-1',
        'bg-violet-500/25' => $isReady && !$readonly,
        'bg-slate-950/60' => !($isReady && !$readonly),
    ])>

        <span class="truncate text-[9px] font-black uppercase tracking-wider text-slate-500">
            {{ $match->label ?: $match->runtime_match_id }}
        </span>

        @if ($match->series_label)
            <span @class([
                'shrink-0 rounded px-1 text-[9px] font-black',
                'bg-sky-500/25 text-sky-300' => $match->is_fixed_series,
                'text-slate-500' => !$match->is_fixed_series,
            ])>
                {{ $match->is_fixed_series ? 'FIJO ' : '' }}{{ $match->series_label }}
            </span>
        @endif

    </div>


    {{-- A donde avanza el ganador --}}

    @if ($destination)
        <div class="flex items-center gap-1 bg-slate-950/70 px-2 py-1">
            <span class="text-[9px] text-slate-700">→</span>
            <span class="truncate text-[9px] font-bold text-slate-600">{{ $destination }}</span>
        </div>
    @endif


    @if ($isReady && !$readonly)
        <div class="bg-violet-500 px-2 py-1 text-center text-[9px] font-black uppercase tracking-wider text-white">
            ▶ Jugar
        </div>
    @elseif ($isDone)
        <div class="bg-emerald-500/15 px-2 py-1 text-center text-[9px] font-black uppercase tracking-wider text-emerald-400">
            Ver batalla
        </div>
    @elseif ($match->status === 'PENDING')
        <div class="px-2 py-1 text-center text-[9px] font-black uppercase tracking-wider text-slate-600">
            Esperando rival
        </div>
    @endif

</button>
