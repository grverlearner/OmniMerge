@php
    /*
     * Lo que se juega ahora mismo, venga del mundo que venga.
     *
     * Cada tarjeta lleva su universo delante, porque aqui una competicion suelta
     * no significa nada: lo que importa es en que mundo esta pasando.
     *
     * Y lleva su estado de RECORRIDO, no solo el administrativo: «En curso» y
     * «Bloqueada» conviven, y la segunda es la que importa porque significa que
     * no avanzara sola.
     */

    $tonoRuntime = [
        'RUNNING' => ['#34d399', 'Ejecutándose'],
        'READY' => ['#60a5fa', 'Lista para comenzar'],
        'BLOCKED' => ['#fb7185', 'Bloqueada'],
        'AWAITING_DECISION' => ['#fbbf24', 'Esperando una decisión'],
        'COMPLETED' => ['#22d3ee', 'Recorrido completado'],
    ];
@endphp

@if ($enJuego->isNotEmpty())

    <section class="overflow-hidden rounded-2xl border border-emerald-500/25 bg-emerald-500/5">

        <header class="flex flex-wrap items-center gap-2 border-b border-emerald-500/20 px-4 py-2.5">

            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-300">
                <x-omni-icon name="espadas" size="h-4 w-4" />
            </span>

            <div class="min-w-0 flex-1">
                <h2 class="text-[13px] font-black text-white">En juego ahora, en todos tus mundos</h2>
                <p class="text-[10px] text-emerald-200/60">
                    {{ $statistics['en_juego'] }}
                    {{ $statistics['en_juego'] === 1 ? 'competición viva' : 'competiciones vivas' }}
                    @if ($statistics['en_juego'] > $enJuego->count())
                        · se enseñan las {{ $enJuego->count() }} más recientes
                    @endif
                </p>
            </div>
        </header>

        <div class="grid gap-2 p-3 sm:grid-cols-2 xl:grid-cols-4">

            @foreach ($enJuego as $competicion)
                @php
                    $suMundo = $mundos[$competicion->universe_id] ?? null;

                    [$tonoR, $textoR] =
                        $tonoRuntime[$competicion->runtime_status] ?? ['#94a3b8', $competicion->runtime_status ?? '—'];

                    $parada = in_array($competicion->runtime_status, ['BLOCKED', 'AWAITING_DECISION'], true);
                @endphp

                <a href="{{ $suMundo ? route('universes.competitions.show', [$suMundo, $competicion]) : '#' }}"
                    class="group overflow-hidden rounded-xl border bg-slate-950 transition hover:-translate-y-0.5"
                    style="border-color: {{ $tonoR }}44">

                    {{-- De qué mundo --}}
                    @if ($suMundo)
                        <span class="flex items-center gap-1.5 border-b border-slate-800/70 px-2 py-1">
                            <span class="h-4 w-4 shrink-0 overflow-hidden rounded bg-slate-900">
                                @if ($suMundo->image_url)
                                    <img src="{{ $suMundo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-slate-700">
                                        <x-omni-icon name="globo" size="h-2.5 w-2.5" />
                                    </span>
                                @endif
                            </span>
                            <span class="truncate text-[9px] font-black uppercase tracking-wider text-slate-500">
                                {{ $suMundo->name }}
                            </span>
                        </span>
                    @endif

                    <span class="flex items-center gap-2 p-2.5">

                        <span class="h-11 w-11 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-900">
                            @if ($competicion->image_url)
                                <img src="{{ $competicion->image_url }}" alt="" loading="lazy"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-slate-700">
                                    <x-omni-icon name="espadas" size="h-4 w-4" />
                                </span>
                            @endif
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[12px] font-black leading-tight text-white">
                                {{ $competicion->name ?: 'Competición sin nombre' }}
                            </span>

                            <span class="block truncate text-[10px] text-slate-500">
                                {{ $competicion->universeTournament?->name ?? 'Sin torneo' }}
                                @if ($competicion->season)
                                    · T{{ $competicion->season->number }}
                                @endif
                            </span>

                            <span class="mt-0.5 flex items-center gap-1 text-[10px] font-black"
                                style="color: {{ $tonoR }}">
                                @if (! $parada)
                                    <span class="h-1.5 w-1.5 animate-pulse rounded-full"
                                        style="background-color: {{ $tonoR }}"></span>
                                @endif
                                {{ $textoR }}
                            </span>
                        </span>

                        <span class="shrink-0 text-right">
                            <span class="block font-mono text-[14px] font-black text-slate-300">
                                {{ $competicion->participant_count ?? '—' }}
                            </span>
                            <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">
                                compiten
                            </span>
                        </span>
                    </span>
                </a>
            @endforeach
        </div>
    </section>
@endif
