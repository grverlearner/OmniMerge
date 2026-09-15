@php
    /*
     * El mundo visto de lado.
     *
     * Una columna por temporada, con la altura de lo que se jugo en ella. No
     * hace ninguna consulta nueva: son las mismas competiciones que ya se
     * cargaron, contadas por temporada.
     *
     * Sirve para lo que ninguna cifra dice: si este mundo esta vivo o si tuvo
     * un arranque fuerte y se paro.
     */

    $techo = max(1, $lineaDelTiempo->max('competiciones') ?? 1);
@endphp

@if ($lineaDelTiempo->isNotEmpty())

    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

        <header class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-2.5">

            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-cyan-500/15 text-cyan-300">
                <x-omni-icon name="barras" size="h-4 w-4" />
            </span>

            <div class="min-w-0 flex-1">
                <h2 class="text-[13px] font-black text-white">El mundo, temporada a temporada</h2>
                <p class="text-[10px] text-slate-500">
                    Cuánto se jugó en cada una. La barra clara es lo que sigue vivo.
                </p>
            </div>

            <a href="{{ route('universes.history', $universe) }}"
                class="shrink-0 text-[11px] font-black text-cyan-300 underline transition hover:text-white">
                Historial
            </a>
        </header>

        <div class="overflow-x-auto p-4">

            <div class="flex min-w-max items-end gap-2">

                @foreach ($lineaDelTiempo as $t)
                    @php
                        $alto = max(6, (int) round($t['competiciones'] / $techo * 110));
                        $altoVivas = $t['competiciones'] > 0
                            ? (int) round($t['vivas'] / max(1, $t['competiciones']) * $alto)
                            : 0;

                        $tonoT = $t['activa']
                            ? '#a78bfa'
                            : ($t['estado'] === 'COMPLETED' ? '#22d3ee' : '#475569');
                    @endphp

                    <a href="{{ $t['url'] }}" class="group flex w-16 flex-col items-center"
                        title="T{{ $t['numero'] }} · {{ $t['nombre'] }} — {{ $t['competiciones'] }} competiciones, {{ $t['terminadas'] }} terminadas, {{ $t['vivas'] }} vivas">

                        <span class="mb-1 font-mono text-[11px] font-black"
                            style="color: {{ $t['competiciones'] > 0 ? $tonoT : '#475569' }}">
                            {{ $t['competiciones'] }}
                        </span>

                        <span class="relative flex w-full flex-col justify-end overflow-hidden rounded-lg transition group-hover:brightness-125"
                            style="height: {{ $alto }}px; background-color: {{ $tonoT }}33">

                            @if ($altoVivas > 0)
                                <span class="block w-full" style="height: {{ $altoVivas }}px; background-color: #34d399"></span>
                            @endif
                        </span>

                        <span class="mt-1 block w-full truncate text-center text-[10px] font-black"
                            style="color: {{ $t['activa'] ? '#c4b5fd' : '#64748b' }}">
                            T{{ $t['numero'] }}
                        </span>

                        <span class="block w-full truncate text-center text-[8px] text-slate-600">
                            {{ $t['nombre'] }}
                        </span>

                        @if ($t['activa'])
                            <span class="mt-0.5 rounded bg-violet-500/20 px-1 text-[8px] font-black uppercase tracking-wider text-violet-300">
                                ahora
                            </span>
                        @endif
                    </a>
                @endforeach
            </div>

            @if ($lineaDelTiempo->sum('competiciones') === 0)
                <p class="mt-3 text-[10px] leading-4 text-slate-600">
                    Las temporadas existen pero ninguna ha visto una competición todavía.
                    Las barras se llenarán solas en cuanto se juegue algo.
                </p>
            @endif
        </div>
    </section>
@endif
