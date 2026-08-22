@php
    /*
     * Fase de grupos: cada grupo es una unidad visual independiente, con
     * su tabla y sus batallas. Nada de mezclarlos en una sola lista.
     */
    $groups = $block['groups']->sortKeys();

    $matchesByGroup = $block['matches']->groupBy('group_label');
@endphp

<div class="grid gap-5 p-5 lg:grid-cols-2 2xl:grid-cols-3">

    @foreach ($groups as $groupLabel => $rows)

        <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-950/50">

            <div class="border-b border-slate-800 px-4 py-2.5">
                <p class="text-[11px] font-black uppercase tracking-wider text-violet-300">
                    {{ $groupLabel ?: 'Grupo único' }}
                </p>
            </div>


            {{-- TABLA --}}

            <div class="space-y-1 p-3">

                @foreach ($rows->sortBy('position') as $row)
                    <div class="flex items-center gap-2.5 rounded-lg px-2 py-1.5 {{ $row->status === 'ADVANCED' ? 'bg-emerald-500/10' : '' }}">

                        <span class="w-4 shrink-0 text-center font-mono text-[11px] font-black text-slate-500">
                            {{ $row->position ?? '—' }}
                        </span>

                        <div class="h-6 w-6 shrink-0 overflow-hidden rounded bg-slate-800">
                            @if ($row->universeEntity?->image_url)
                                <img src="{{ $row->universeEntity->image_url }}" alt="" class="h-full w-full object-cover">
                            @endif
                        </div>

                        <span class="min-w-0 flex-1 truncate text-[11px] font-semibold text-slate-300">
                            {{ $row->participant_name }}
                        </span>

                        @if ($row->status === 'ADVANCED')
                            <span class="shrink-0 text-[9px] font-black text-emerald-400">▲</span>
                        @endif

                        <span class="shrink-0 font-mono text-[10px] text-slate-500">
                            {{ $row->wins }}-{{ $row->draws }}-{{ $row->losses }}
                        </span>

                        <span class="w-6 shrink-0 text-right font-mono text-xs font-black text-violet-300">
                            {{ $row->points }}
                        </span>

                    </div>
                @endforeach

            </div>


            {{-- BATALLAS DEL GRUPO --}}

            @php
                $groupMatches = $matchesByGroup->get($groupLabel, collect());
            @endphp

            @if ($groupMatches->isNotEmpty())
                <div class="space-y-2 border-t border-slate-800 p-3">
                    @foreach ($groupMatches as $match)
                        @include('universes.competitions.partials.play.match-chip', ['match' => $match])
                    @endforeach
                </div>
            @endif

        </div>
    @endforeach

</div>
