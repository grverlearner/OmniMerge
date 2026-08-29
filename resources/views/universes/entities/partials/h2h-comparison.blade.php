@php
    /*
     * LA COMPARACIÓN
     *
     * Enfrentar cifras, no listarlas dos veces. Cada fila dice quién gana
     * esa fila, porque un 54% y un 51% puestos en columnas distintas se
     * leen como iguales.
     *
     * El marcador directo va primero y grande: es lo único que de verdad
     * enfrenta a estos dos. Todo lo demás son sus carreras por separado,
     * que se comparan pero no se han jugado una contra otra.
     */

    $L = $leftStats;
    $R = $rightStats;

    $marcador = [
        'left' => (int) ($comparison['left_wins'] ?? 0),
        'right' => (int) ($comparison['right_wins'] ?? 0),
        'draws' => (int) ($comparison['draws'] ?? 0),
        'total' => (int) ($comparison['total'] ?? 0),
    ];

    /* Filas comparables: etiqueta, valor de cada uno, y si más es mejor */
    $filas = [
        ['Torneos jugados', $L['tournaments'] ?? 0, $R['tournaments'] ?? 0, true],
        ['Títulos', $L['championships'] ?? 0, $R['championships'] ?? 0, true],
        ['Enfrentamientos', $L['matches'] ?? 0, $R['matches'] ?? 0, true],
        ['Ganados', $L['wins'] ?? 0, $R['wins'] ?? 0, true],
        ['Empatados', $L['draws'] ?? 0, $R['draws'] ?? 0, false],
        ['Perdidos', $L['losses'] ?? 0, $R['losses'] ?? 0, false],
        ['Porcentaje', $L['win_rate'] ?? 0, $R['win_rate'] ?? 0, true],
    ];
@endphp

<div class="space-y-3">


    {{-- ============ EL MARCADOR DIRECTO ============ --}}

    <section class="overflow-hidden rounded-2xl border border-rose-500/30 bg-slate-900/50">

        <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2 p-4">

            {{-- Él --}}
            <div class="flex flex-col items-center gap-1.5 text-center">
                <span class="flex h-24 w-20 items-center justify-center overflow-hidden rounded-xl bg-slate-950">
                    @if ($entity->image_url)
                        <img src="{{ $entity->image_url }}" alt="" class="h-full w-full object-cover">
                    @else
                        <span class="font-mono text-[16px] font-black text-slate-700">
                            {{ mb_strtoupper(mb_substr($entity->display_label, 0, 2)) }}
                        </span>
                    @endif
                </span>

                <a href="{{ route('universes.entities.show', [$universe, $entity]) }}"
                    class="text-[12px] font-black text-slate-100 transition hover:text-emerald-300">
                    {{ $entity->display_label }}
                </a>
            </div>

            {{-- El marcador --}}
            <div class="text-center">
                <p class="font-mono text-[36px] font-black leading-none">
                    <span class="{{ $marcador['left'] > $marcador['right'] ? 'text-emerald-300' : 'text-slate-400' }}">{{ $marcador['left'] }}</span>
                    <span class="text-slate-700">–</span>
                    <span class="{{ $marcador['right'] > $marcador['left'] ? 'text-emerald-300' : 'text-slate-400' }}">{{ $marcador['right'] }}</span>
                </p>

                <p class="text-[9px] uppercase tracking-wider text-slate-600">
                    {{ $marcador['total'] }} {{ $marcador['total'] === 1 ? 'enfrentamiento' : 'enfrentamientos' }}
                    @if ($marcador['draws'])
                        · {{ $marcador['draws'] }} en tablas
                    @endif
                </p>

                @if ($marcador['total'] === 0)
                    <p class="mt-1 max-w-[12rem] text-[10px] leading-relaxed text-slate-500">
                        Nunca se han cruzado. Lo de abajo compara sus carreras por
                        separado, no lo que han hecho el uno contra el otro.
                    </p>
                @endif
            </div>

            {{-- El rival --}}
            <div class="flex flex-col items-center gap-1.5 text-center">
                <span class="flex h-24 w-20 items-center justify-center overflow-hidden rounded-xl bg-slate-950">
                    @if ($rival->image_url)
                        <img src="{{ $rival->image_url }}" alt="" class="h-full w-full object-cover">
                    @else
                        <span class="font-mono text-[16px] font-black text-slate-700">
                            {{ mb_strtoupper(mb_substr($rival->display_label, 0, 2)) }}
                        </span>
                    @endif
                </span>

                <a href="{{ route('universes.entities.show', [$universe, $rival]) }}"
                    class="text-[12px] font-black text-slate-100 transition hover:text-emerald-300">
                    {{ $rival->display_label }}
                </a>
            </div>
        </div>

        {{-- La barra del marcador --}}

        @if ($marcador['total'] > 0)
            <div class="mx-4 mb-4 flex h-2.5 overflow-hidden rounded-full bg-slate-950">
                <div class="bg-emerald-500" style="width: {{ round($marcador['left'] / $marcador['total'] * 100) }}%"></div>
                <div class="bg-slate-600" style="width: {{ round($marcador['draws'] / $marcador['total'] * 100) }}%"></div>
                <div class="bg-rose-500" style="width: {{ round($marcador['right'] / $marcador['total'] * 100) }}%"></div>
            </div>
        @endif
    </section>


    {{-- ============ SUS CARRERAS, ENFRENTADAS ============ --}}

    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

        <div class="flex items-center gap-2 border-b border-slate-800 bg-slate-950/60 px-4 py-2">
            <span class="text-[11px]">⚖</span>
            <h2 class="text-[11px] font-black uppercase tracking-wider text-slate-300">
                Sus carreras, una al lado de la otra
            </h2>
        </div>

        <div class="divide-y divide-slate-800/60">
            @foreach ($filas as [$label, $a, $b, $masEsMejor])
                @php
                    $gana = $a === $b ? null : (($a > $b) === $masEsMejor ? 'left' : 'right');
                    $tope = max((int) $a, (int) $b, 1);
                @endphp

                <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2 px-3 py-1.5">

                    {{-- Su barra crece hacia dentro, para que se toquen en el centro --}}
                    <div class="flex items-center justify-end gap-2">
                        <span class="font-mono text-[13px] font-black
                            {{ $gana === 'left' ? 'text-emerald-300' : 'text-slate-500' }}">
                            {{ $a }}{{ $label === 'Porcentaje' ? '%' : '' }}
                        </span>

                        <span class="h-1.5 w-24 overflow-hidden rounded-full bg-slate-950 sm:w-40">
                            <span class="ml-auto block h-full {{ $gana === 'left' ? 'bg-emerald-500' : 'bg-slate-700' }}"
                                style="width: {{ round((int) $a / $tope * 100) }}%"></span>
                        </span>
                    </div>

                    <span class="w-32 text-center text-[10px] font-black uppercase tracking-wider text-slate-500">
                        {{ $label }}
                    </span>

                    <div class="flex items-center gap-2">
                        <span class="h-1.5 w-24 overflow-hidden rounded-full bg-slate-950 sm:w-40">
                            <span class="block h-full {{ $gana === 'right' ? 'bg-rose-500' : 'bg-slate-700' }}"
                                style="width: {{ round((int) $b / $tope * 100) }}%"></span>
                        </span>

                        <span class="font-mono text-[13px] font-black
                            {{ $gana === 'right' ? 'text-rose-300' : 'text-slate-500' }}">
                            {{ $b }}{{ $label === 'Porcentaje' ? '%' : '' }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </section>


    {{-- ============ SUS CAPACIDADES, JUEGO A JUEGO ============ --}}

    @php
        $porJuego = collect($rightGames)->keyBy(fn ($g) => $g['definition']['key']);
    @endphp

    @foreach ($leftGames as $izq)
        @php
            $def = $izq['definition'];
            $der = $porJuego->get($def['key']);
            $vIzq = (array) ($izq['stats']->stats ?? []);
            $vDer = (array) ($der['stats']->stats ?? []);
        @endphp

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <div class="flex items-center gap-2 border-b border-slate-800 bg-slate-950/60 px-4 py-2">
                <span class="text-[12px]">{{ $def['icon'] ?? '🎲' }}</span>
                <h2 class="text-[11px] font-black text-slate-200">{{ $def['name'] }}</h2>
                <span class="ml-auto font-mono text-[9px] text-slate-600">
                    {{ $izq['record']['battles'] ?? 0 }} vs {{ $der['record']['battles'] ?? 0 }} batallas
                </span>
            </div>

            <div class="divide-y divide-slate-800/60">
                @foreach (($def['stats'] ?? []) as $stat)
                    @php
                        $a = (float) ($vIzq[$stat['key']] ?? 0);
                        $b = (float) ($vDer[$stat['key']] ?? 0);
                        $gana = $a === $b ? null : ($a > $b ? 'left' : 'right');
                        $tope = max($a, $b, 0.0001);
                        $num = fn ($v) => rtrim(rtrim(number_format($v, 2, ',', ''), '0'), ',');
                    @endphp

                    <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2 px-3 py-1.5">

                        <div class="flex items-center justify-end gap-2">
                            <span class="font-mono text-[13px] font-black
                                {{ $gana === 'left' ? 'text-emerald-300' : 'text-slate-500' }}">{{ $num($a) }}</span>

                            <span class="h-1.5 w-24 overflow-hidden rounded-full bg-slate-950 sm:w-40">
                                <span class="ml-auto block h-full {{ $gana === 'left' ? 'bg-emerald-500' : 'bg-slate-700' }}"
                                    style="width: {{ round($a / $tope * 100) }}%"></span>
                            </span>
                        </div>

                        <span class="w-32 text-center text-[10px] font-black text-slate-500">{{ $stat['label'] }}</span>

                        <div class="flex items-center gap-2">
                            <span class="h-1.5 w-24 overflow-hidden rounded-full bg-slate-950 sm:w-40">
                                <span class="block h-full {{ $gana === 'right' ? 'bg-rose-500' : 'bg-slate-700' }}"
                                    style="width: {{ round($b / $tope * 100) }}%"></span>
                            </span>

                            <span class="font-mono text-[13px] font-black
                                {{ $gana === 'right' ? 'text-rose-300' : 'text-slate-500' }}">{{ $num($b) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach


    {{-- ============ CADA VEZ QUE SE VIERON ============ --}}

    @if ($marcador['total'] > 0)
        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <div class="flex items-center gap-2 border-b border-slate-800 bg-rose-500/10 px-4 py-2">
                <span class="text-[11px]">⚔</span>
                <h2 class="text-[11px] font-black uppercase tracking-wider text-rose-300">
                    Cada vez que se vieron
                </h2>
                <span class="ml-auto font-mono text-[10px] text-slate-600">{{ $marcador['total'] }}</span>
            </div>

            <div class="divide-y divide-slate-800/60">
                @foreach ($comparison['matches'] as $m)
                    @php
                        $comp = $m->tournamentInstance ?? null;
                        $ganoIzq = (int) ($m->winner_universe_entity_id ?? 0) === (int) $entity->id;
                        $ganoDer = (int) ($m->winner_universe_entity_id ?? 0) === (int) $rival->id;
                    @endphp

                    <a href="{{ $comp ? route('universes.competitions.show', [$universe, $comp]) : '#' }}"
                        class="flex flex-wrap items-center gap-2 px-3 py-2 transition hover:bg-slate-800/40">

                        <span class="w-7 shrink-0 text-center text-[13px]">
                            {{ $ganoIzq ? '◀' : ($ganoDer ? '▶' : '=') }}
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[11px] font-black text-slate-200">
                                {{ $comp->name ?? 'Competición' }}
                            </span>
                            <span class="block truncate font-mono text-[9px] text-slate-600">
                                {{ $m->phase_name ?? $m->node_name ?? '' }}
                                @if ($comp?->completed_at)
                                    · {{ $comp->completed_at->format('d/m/Y') }}
                                @endif
                            </span>
                        </span>

                        <span class="shrink-0 rounded px-2 py-0.5 text-[10px] font-black
                            {{ $ganoIzq ? 'bg-emerald-500/20 text-emerald-300'
                                : ($ganoDer ? 'bg-rose-500/20 text-rose-300' : 'bg-slate-800 text-slate-400') }}">
                            {{ $ganoIzq ? $entity->display_label : ($ganoDer ? $rival->display_label : 'tablas') }}
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>
