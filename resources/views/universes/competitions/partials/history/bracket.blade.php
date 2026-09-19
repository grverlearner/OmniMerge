@php
    /*
     * Bracket de Single Elimination.
     *
     * Se construye desde los encuentros JUGADOS, agrupados por ronda:
     * representa lo que ocurrió, no el diseño de la plantilla.
     *
     * $rounds  Collection<int, Collection<Match>>
     */

    $ordered = $rounds->sortKeys();
    $lastRound = $ordered->keys()->last();
@endphp


@if ($ordered->isEmpty())
    <p class="rounded-xl border border-dashed border-slate-800 p-8 text-center text-[11px] text-slate-600">
        Esta fase no llegó a generar encuentros.
    </p>
@else

    <div class="overflow-x-auto pb-2">

        <div class="flex min-w-max gap-3">

            @foreach ($ordered as $roundNumber => $matches)
                <div class="w-72 shrink-0">

                    <div class="mb-2 flex items-center justify-between gap-2">

                        <p
                            class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-wider {{ $roundNumber === $lastRound ? 'text-amber-300' : 'text-slate-600' }}">

                            @if ($roundNumber === $lastRound)
                                <x-omni-icon name="trofeo" size="h-3.5 w-3.5" />
                                Final
                            @else
                                Ronda {{ $roundNumber }}
                            @endif
                        </p>

                        <span class="font-mono text-[10px] font-black text-slate-600">
                            {{ $matches->count() }}
                        </span>

                    </div>


                    {{-- Los encuentros se reparten verticalmente para que
                         la progresión del bracket se lea de izquierda a
                         derecha. --}}
                    <div class="flex h-full flex-col justify-around gap-2">

                        @foreach ($matches as $match)
                            @include('universes.competitions.partials.history.match-card', [
                                'match' => $match,
                            ])
                        @endforeach

                    </div>

                </div>
            @endforeach

        </div>

    </div>
@endif
