@php
    /*
     * Group Stage: un panel por grupo con su mini tabla, y los
     * encuentros de cada grupo debajo.
     *
     * $groups   Collection<string|null, Collection<PhaseParticipant>>
     * $matches  Collection<Match>
     */

    $matchesByGroup = $matches->groupBy('group_label');
@endphp


@if ($groups->isEmpty())
    <p class="rounded-xl border border-dashed border-slate-800 p-8 text-center text-[11px] text-slate-600">
        Esta fase no llegó a formar grupos.
    </p>
@else

    <div class="grid gap-3 xl:grid-cols-2">

        @foreach ($groups as $label => $rows)
            <section class="rounded-xl border border-slate-800 bg-slate-900/60 p-3">

                <div class="mb-2 flex items-center gap-2">

                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-cyan-500/15 text-cyan-300">
                        <x-omni-icon name="cuadricula" size="h-3.5 w-3.5" />
                    </span>

                    <p class="min-w-0 flex-1 truncate text-[13px] font-black text-white">
                        {{ $label ?: 'Grupo único' }}
                    </p>

                    <span class="shrink-0 font-mono text-[10px] font-black text-slate-600">
                        {{ $rows->count() }}
                    </span>

                </div>


                @include('universes.competitions.partials.history.standings-table', [
                    'standings' => $rows->sortBy('position'),
                    'compact' => true,
                ])


                @php
                    $groupMatches = $matchesByGroup->get($label, collect());
                @endphp

                @if ($groupMatches->isNotEmpty())
                    <div class="mt-3 space-y-1.5">

                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                            Enfrentamientos
                        </p>

                        @foreach ($groupMatches as $match)
                            @include('universes.competitions.partials.history.match-card', [
                                'match' => $match,
                                'compact' => true,
                            ])
                        @endforeach

                    </div>
                @endif

            </section>
        @endforeach

    </div>
@endif
