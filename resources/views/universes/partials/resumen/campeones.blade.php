@php
    /*
     * Los ultimos en ganar, agrupados por competicion.
     *
     * Un torneo que se queda en fase de grupos corona a todos los que
     * clasifican, asi que pedir cuatro ganadores sueltos devolvia cuatro caras
     * de una sola edicion y escondia el resto. Se agrupa por competicion y cada
     * una lleva a los suyos.
     */
@endphp

@if ($recentChampions->isNotEmpty())

    <section class="overflow-hidden rounded-2xl border border-amber-500/25 bg-amber-500/5">

        <header class="flex flex-wrap items-center gap-2 border-b border-amber-500/20 px-4 py-2.5">

            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 text-amber-300">
                <x-omni-icon name="trofeo" size="h-4 w-4" />
            </span>

            <div class="min-w-0 flex-1">
                <h2 class="text-[13px] font-black text-white">Los últimos en ganar</h2>
                <p class="text-[10px] text-amber-200/60">No es la clasificación: es lo último que pasó.</p>
            </div>
        </header>

        <div class="space-y-1.5 p-3">

            @foreach ($recentChampions as $ganadores)
                @php
                    $suEdicion = $ganadores->first()?->tournamentInstance;
                    $compartido = $ganadores->count() > 1;
                @endphp

                <article class="rounded-xl border border-slate-800 bg-slate-950 p-2">

                    <div class="flex flex-wrap items-baseline gap-x-1.5">
                        <span class="truncate text-[11px] font-black text-amber-200">
                            {{ $suEdicion?->name ?: 'Competición sin nombre' }}
                        </span>

                        @if ($suEdicion?->season)
                            <span class="rounded bg-slate-900 px-1 font-mono text-[9px] font-black text-slate-400">
                                T{{ $suEdicion->season->number }}
                            </span>
                        @endif

                        @if ($suEdicion?->completed_at)
                            <span class="font-mono text-[9px] text-slate-600">
                                {{ $suEdicion->completed_at->format('d/m/Y') }}
                            </span>
                        @endif
                    </div>

                    @if ($compartido)
                        <p class="text-[9px] leading-3 text-slate-600">
                            {{ $ganadores->count() }} ganadores: no hubo una final, se llevaron el
                            título todos los que clasificaron.
                        </p>
                    @endif

                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                        @foreach ($ganadores as $campeon)
                            @php $suEntidad = $campeon->universeEntity; @endphp

                            @if ($suEntidad)
                                <a href="{{ route('universes.entities.show', [$universe, $suEntidad]) }}"
                                    class="group relative block" title="{{ $suEntidad->display_label }}">
                                    <span class="block h-11 w-11 overflow-hidden rounded-lg border border-amber-500/40 bg-slate-900">
                                        @if ($suEntidad->image_url)
                                            <img src="{{ $suEntidad->image_url }}" alt="" loading="lazy"
                                                class="h-full w-full object-cover transition duration-300 group-hover:scale-110">
                                        @else
                                            <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                                        @endif
                                    </span>
                                </a>
                            @else
                                <span class="flex h-11 w-11 items-center justify-center rounded-lg border border-slate-800 bg-slate-900 text-slate-700"
                                    title="Ya no está en el universo">◍</span>
                            @endif
                        @endforeach
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif
