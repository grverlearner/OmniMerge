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
    <p class="rounded-2xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400">
        Esta fase no llegó a formar grupos.
    </p>
@else

    <div class="grid gap-4 xl:grid-cols-2">

        @foreach ($groups as $label => $rows)
            <section
                class="
                    rounded-2xl
                    border
                    border-slate-200
                    bg-white
                    p-4
                ">

                <div
                    class="
                        mb-3
                        flex
                        items-center
                        justify-between
                    ">

                    <p class="text-sm font-black text-slate-900">
                        {{ $label ?: 'Grupo único' }}
                    </p>

                    <span class="text-[9px] font-bold uppercase text-slate-400">
                        {{ $rows->count() }} competidores
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
                    <div class="mt-4 space-y-2">

                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
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
