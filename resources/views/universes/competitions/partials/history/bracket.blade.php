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
    <p class="rounded-2xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400">
        Esta fase no llegó a generar encuentros.
    </p>
@else

    <div class="overflow-x-auto pb-2">

        <div class="flex min-w-max gap-4">

            @foreach ($ordered as $roundNumber => $matches)
                <div class="w-72 shrink-0">

                    <div
                        class="
                            mb-3
                            flex
                            items-center
                            justify-between
                            gap-2
                        ">

                        <p
                            class="
                                text-[10px]
                                font-black
                                uppercase
                                tracking-wider
                                {{ $roundNumber === $lastRound ? 'text-violet-600' : 'text-slate-400' }}
                            ">
                            {{ $roundNumber === $lastRound ? '🏆 Final' : 'Ronda ' . $roundNumber }}
                        </p>

                        <span class="text-[9px] font-bold text-slate-400">
                            {{ $matches->count() }}
                        </span>

                    </div>


                    {{-- Los encuentros se reparten verticalmente para que
                         la progresión del bracket se lea de izquierda a
                         derecha. --}}
                    <div class="flex h-full flex-col justify-around gap-3">

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
