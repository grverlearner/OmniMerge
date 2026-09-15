@php
    /*
     * El pulso: los mundos comparados de lado.
     *
     * La vista que contesta la pregunta que ninguna de las otras contesta:
     * ¿cual de tus mundos esta VIVO?
     *
     * Cada mundo es una fila con dos cosas puestas en la misma escala para
     * todos -por eso se comparan de verdad-:
     *
     *   · una barra por temporada, con lo que se jugo en cada una
     *   · su gente, sus cifras y lo que tiene parado
     *
     * Un mundo con una barra alta al principio y nada despues tuvo un arranque
     * fuerte y se quedo; uno con barras parejas se juega de verdad. Eso no lo
     * dice ningun contador.
     */
@endphp

<section x-show="vista === 'pulso'" x-cloak class="space-y-2">

    <p class="px-1 text-[10px] leading-relaxed text-slate-500">
        Los mundos en la misma escala: la barra más alta de toda la pantalla son
        <strong class="text-slate-300">{{ $techoPulso }}</strong>
        {{ $techoPulso === 1 ? 'competición' : 'competiciones' }}. Así se compara un mundo con
        otro y no solo consigo mismo.
    </p>

    @foreach ($universes as $mundo)
        @php
            [$tono, $textoEstado] = $tonosEstado[$mundo->status] ?? ['#94a3b8', $mundo->status];

            $caras = $carasPorUniverso[$mundo->id] ?? collect();
            $temporada = $temporadaPorUniverso[$mundo->id] ?? null;
            $pulso = $pulsoPorUniverso[$mundo->id] ?? collect();

            $atascado = $mundo->atascadas_count > 0;
        @endphp

        <article class="overflow-hidden rounded-2xl border bg-slate-900/50"
            style="border-color: {{ $atascado ? '#fb718544' : $tono . '33' }}">

            <div class="flex flex-wrap items-stretch">

                {{-- ---------- QUIÉN ES ---------- --}}

                <div class="flex min-w-[260px] flex-1 items-center gap-3 p-3">

                    <a href="{{ $mundo->home_url }}"
                        class="h-14 w-14 shrink-0 overflow-hidden rounded-xl border-2 bg-slate-950"
                        style="border-color: {{ $tono }}66">
                        @if ($mundo->image_url)
                            <img src="{{ $mundo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                        @else
                            <span class="flex h-full w-full items-center justify-center text-slate-700">
                                <x-omni-icon name="globo" size="h-5 w-5" />
                            </span>
                        @endif
                    </a>

                    <div class="min-w-0 flex-1">
                        <a href="{{ $mundo->home_url }}"
                            class="block truncate text-[13px] font-black text-white transition hover:text-violet-300">
                            {{ $mundo->name }}
                        </a>

                        <p class="flex flex-wrap items-center gap-x-1.5 text-[9px]">
                            <span class="rounded px-1 py-0.5 font-black uppercase tracking-wider"
                                style="color: {{ $tono }}; background-color: {{ $tono }}1f">{{ $textoEstado }}</span>

                            @if ($temporada)
                                <span class="font-mono text-violet-400">T{{ $temporada->number }}</span>
                            @endif

                            <span class="font-mono text-slate-600">
                                @if ($mundo->activities_max_occurred_at)
                                    se movió hace
                                    {{ \Illuminate\Support\Carbon::parse($mundo->activities_max_occurred_at)->diffForHumans(null, true) }}
                                @else
                                    nunca se ha movido
                                @endif
                            </span>
                        </p>

                        @if ($caras->isNotEmpty())
                            <div class="mt-1 flex -space-x-1.5">
                                @foreach ($caras->take(7) as $cara)
                                    <span class="h-5 w-5 shrink-0 overflow-hidden rounded-full border border-slate-900 bg-slate-950">
                                        <img src="{{ $cara->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                    </span>
                                @endforeach

                                @if ($mundo->entities_count > 7)
                                    <span class="flex h-5 items-center rounded-full border border-slate-800 bg-slate-950 px-1.5 font-mono text-[8px] font-black text-slate-500">
                                        +{{ $mundo->entities_count - 7 }}
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>


                {{-- ---------- SU PULSO POR TEMPORADAS ---------- --}}

                <div class="flex min-w-[280px] flex-[2] items-end gap-1.5 border-l border-slate-800/70 p-3">

                    @if ($pulso->isEmpty())
                        <p class="self-center text-[10px] leading-4 text-slate-600">
                            @if ($mundo->seasons_count === 0)
                                Sin temporadas: este mundo todavía no tiene calendario.
                            @else
                                Tiene {{ $mundo->seasons_count }}
                                {{ $mundo->seasons_count === 1 ? 'temporada' : 'temporadas' }},
                                pero no se ha jugado nada en ninguna.
                            @endif
                        </p>
                    @else
                        @foreach ($pulso as $t)
                            @php
                                $altoT = max(4, (int) round($t['cuantas'] / $techoPulso * 64));

                                /* Sin temporada: ni es la cero ni va la primera */
                                $huerfana = $t['numero'] === null;
                            @endphp

                            <span class="flex w-9 shrink-0 flex-col items-center"
                                title="{{ $t['nombre'] }} — {{ $t['cuantas'] }} {{ $t['cuantas'] === 1 ? 'competición' : 'competiciones' }}{{ $huerfana ? ', sin temporada asignada' : '' }}">

                                <span class="mb-0.5 font-mono text-[9px] font-black"
                                    style="color: {{ $huerfana ? '#64748b' : $tono }}">{{ $t['cuantas'] }}</span>

                                <span class="block w-full rounded-t-md transition"
                                    style="height: {{ $altoT }}px;
                                           background: {{ $huerfana
                                               ? 'repeating-linear-gradient(45deg, #334155, #334155 3px, #1e293b 3px, #1e293b 6px)'
                                               : 'linear-gradient(to top, ' . $tono . ', ' . $tono . '66)' }}"></span>

                                <span class="mt-0.5 block w-full truncate text-center font-mono text-[8px]"
                                    style="color: {{ $huerfana ? '#475569' : '#64748b' }}">
                                    {{ $huerfana ? 'suelta' : 'T' . $t['numero'] }}
                                </span>
                            </span>
                        @endforeach
                    @endif
                </div>


                {{-- ---------- LO QUE TIENE Y LO QUE DEBE ---------- --}}

                <div class="flex min-w-[200px] flex-col justify-center gap-1.5 border-l border-slate-800/70 p-3">

                    <div class="grid grid-cols-4 gap-1">
                        @foreach ([['Gente', $mundo->entities_count, '#a78bfa'], ['Torneos', $mundo->universe_tournaments_count, '#22d3ee'], ['Hechas', $mundo->hechas_count, '#22d3ee'], ['Trofeos', $mundo->trophies_count, '#fbbf24']] as [$etiqueta, $valor, $tonoC])
                            <span class="rounded-lg border border-slate-800 bg-slate-950 py-1 text-center">
                                <span class="block font-mono text-[12px] font-black"
                                    style="color: {{ $valor > 0 ? $tonoC : '#475569' }}">{{ $valor }}</span>
                                <span class="block text-[7px] font-black uppercase tracking-wider text-slate-600">{{ $etiqueta }}</span>
                            </span>
                        @endforeach
                    </div>

                    @if ($atascado || $mundo->vivas_count > 0 || $mundo->listas_count > 0)
                        <div class="flex flex-wrap gap-1">
                            @if ($mundo->vivas_count > 0)
                                <span class="flex items-center gap-1 rounded-lg bg-emerald-500/15 px-2 py-1 text-[10px] font-black text-emerald-300">
                                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400"></span>
                                    {{ $mundo->vivas_count }} en juego
                                </span>
                            @endif

                            @if ($atascado)
                                <a href="{{ route('universes.competitions.index', $mundo) }}"
                                    class="rounded-lg bg-rose-500/15 px-2 py-1 text-[10px] font-black text-rose-300 transition hover:bg-rose-500 hover:text-white">
                                    {{ $mundo->atascadas_count }} atascada{{ $mundo->atascadas_count === 1 ? '' : 's' }}
                                </a>
                            @endif

                            @if ($mundo->listas_count > 0)
                                <a href="{{ route('universes.competitions.index', $mundo) }}?status=DRAFT"
                                    class="rounded-lg bg-sky-500/15 px-2 py-1 text-[10px] font-black text-sky-300 transition hover:bg-sky-500 hover:text-white">
                                    {{ $mundo->listas_count }} sin empezar
                                </a>
                            @endif
                        </div>
                    @else
                        <p class="text-[10px] text-slate-600">Nada pendiente aquí.</p>
                    @endif
                </div>
            </div>
        </article>
    @endforeach
</section>
