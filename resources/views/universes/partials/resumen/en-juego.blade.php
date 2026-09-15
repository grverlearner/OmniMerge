@php
    /*
     * Lo que se esta jugando ahora mismo.
     *
     * Cada competicion viva con su estado de recorrido, no solo su estado
     * administrativo: «En curso» y «Bloqueada» son las dos cosas a la vez, y la
     * segunda es la que importa porque significa que esta parada.
     */

    $tonoRuntime = [
        'RUNNING' => ['#34d399', 'Ejecutándose'],
        'READY' => ['#60a5fa', 'Lista para comenzar'],
        'BLOCKED' => ['#fb7185', 'Bloqueada'],
        'AWAITING_DECISION' => ['#fbbf24', 'Esperando una decisión'],
        'COMPLETED' => ['#22d3ee', 'Recorrido completado'],
    ];
@endphp

@if ($liveCompetitions->isNotEmpty())

    <section class="overflow-hidden rounded-2xl border border-emerald-500/25 bg-emerald-500/5">

        <header class="flex flex-wrap items-center gap-2 border-b border-emerald-500/20 px-4 py-2.5">

            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-300">
                <x-omni-icon name="espadas" size="h-4 w-4" />
            </span>

            <div class="min-w-0 flex-1">
                <h2 class="text-[13px] font-black text-white">En juego ahora</h2>
                <p class="text-[10px] text-emerald-200/60">
                    {{ $statistics['competitions_running'] }}
                    {{ $statistics['competitions_running'] === 1 ? 'competición viva' : 'competiciones vivas' }}
                    en este mundo.
                </p>
            </div>

            <a href="{{ route('universes.competitions.index', $universe) }}"
                class="shrink-0 text-[11px] font-black text-emerald-300 underline transition hover:text-white">
                Verlas todas
            </a>
        </header>

        <div class="grid gap-2 p-3 sm:grid-cols-2 xl:grid-cols-3">

            @foreach ($liveCompetitions as $competicion)
                @php
                    [$tonoR, $textoR] =
                        $tonoRuntime[$competicion->runtime_status] ?? ['#94a3b8', $competicion->runtime_status ?? '—'];

                    $parada = in_array($competicion->runtime_status, ['BLOCKED', 'AWAITING_DECISION'], true);
                @endphp

                <a href="{{ route('universes.competitions.show', [$universe, $competicion]) }}"
                    class="group overflow-hidden rounded-xl border bg-slate-950 transition hover:-translate-y-0.5"
                    style="border-color: {{ $tonoR }}44">

                    <div class="flex items-center gap-2.5 p-2.5">

                        <span class="h-12 w-12 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-900">
                            @if ($competicion->image_url)
                                <img src="{{ $competicion->image_url }}" alt="" loading="lazy"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-slate-700">
                                    <x-omni-icon name="espadas" size="h-4 w-4" />
                                </span>
                            @endif
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[12px] font-black leading-tight text-white">
                                {{ $competicion->name ?: 'Competición sin nombre' }}
                            </p>

                            <p class="truncate text-[10px] text-slate-500">
                                {{ $competicion->universeTournament?->name ?? 'Sin torneo' }}
                                @if ($competicion->season)
                                    · T{{ $competicion->season->number }}
                                @endif
                            </p>

                            <p class="mt-0.5 flex items-center gap-1 text-[10px] font-black"
                                style="color: {{ $tonoR }}">
                                @if (! $parada)
                                    <span class="h-1.5 w-1.5 animate-pulse rounded-full"
                                        style="background-color: {{ $tonoR }}"></span>
                                @endif
                                {{ $textoR }}
                            </p>
                        </div>

                        <span class="shrink-0 text-right">
                            <span class="block font-mono text-[15px] font-black text-slate-300">
                                {{ $competicion->participant_count ?? '—' }}
                            </span>
                            <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">
                                compiten
                            </span>
                        </span>
                    </div>

                    @if ($parada)
                        <p class="px-2.5 pb-2 text-[10px] leading-3" style="color: {{ $tonoR }}">
                            Está parada: no avanzará sola.
                        </p>
                    @endif
                </a>
            @endforeach
        </div>
    </section>

@elseif ($statistics['competitions'] === 0)

    <section class="rounded-2xl border border-dashed border-slate-800 py-10 text-center">

        <span class="inline-flex text-slate-700"><x-omni-icon name="espadas" size="h-9 w-9" /></span>

        <p class="mt-2 text-[13px] font-black text-white">Todavía no se ha jugado nada aquí</p>

        <p class="mx-auto mt-1 max-w-md text-[11px] leading-relaxed text-slate-500">
            Un universo cobra vida cuando sus competidores se enfrentan. Define un torneo,
            crea una edición y el resto de esta pantalla empezará a llenarse solo.
        </p>

        <a href="{{ route('universes.tournaments.index', $universe) }}"
            class="mt-3 inline-flex items-center gap-1.5 rounded-xl bg-violet-500 px-4 py-2.5 text-[12px] font-black text-white transition hover:bg-violet-400">
            <x-omni-icon name="trofeo" size="h-3.5 w-3.5" />
            Ir a los torneos
        </a>
    </section>
@endif
