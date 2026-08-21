@php
    /*
     * Tabla de posiciones. Usada por Round Robin y por cada grupo de
     * Group Stage.
     *
     * $standings  Collection<TournamentInstancePhaseParticipant>
     * $compact    bool
     */

    $compact = $compact ?? false;
@endphp


<div class="overflow-x-auto">

    <table class="w-full min-w-max text-left text-sm">

        <thead>
            <tr class="border-b border-slate-200 text-[9px] font-black uppercase tracking-wider text-slate-400">
                <th class="pb-2 pr-3">#</th>
                <th class="pb-2 pr-3">Competidor</th>
                <th class="pb-2 pr-2 text-center">PJ</th>
                <th class="pb-2 pr-2 text-center">G</th>
                <th class="pb-2 pr-2 text-center">E</th>
                <th class="pb-2 pr-2 text-center">P</th>

                @unless ($compact)
                    <th class="pb-2 pr-2 text-center">GF</th>
                    <th class="pb-2 pr-2 text-center">GC</th>
                @endunless

                <th class="pb-2 pr-2 text-center">Dif</th>
                <th class="pb-2 pr-3 text-center">Pts</th>
                <th class="pb-2"></th>
            </tr>
        </thead>

        <tbody>
            @foreach ($standings as $row)
                <tr
                    class="
                        border-b
                        border-slate-100
                        {{ $row->status === 'ADVANCED' ? 'bg-emerald-50/40' : '' }}
                    ">

                    <td class="py-2 pr-3">
                        <span
                            class="
                                inline-flex
                                h-6
                                w-6
                                items-center
                                justify-center
                                rounded-lg
                                text-[10px]
                                font-black
                                {{ $row->position === 1
                                    ? 'bg-violet-600 text-white'
                                    : 'bg-slate-100 text-slate-500' }}
                            ">
                            {{ $row->position ?? '–' }}
                        </span>
                    </td>


                    <td class="py-2 pr-3">
                        <div class="flex items-center gap-2">

                            <div
                                class="
                                    flex
                                    h-7
                                    w-7
                                    shrink-0
                                    items-center
                                    justify-center
                                    overflow-hidden
                                    rounded-lg
                                    bg-violet-100
                                    text-violet-500
                                ">

                                @if ($row->universeEntity?->image_url)
                                    <img src="{{ $row->universeEntity->image_url }}"
                                        alt="{{ $row->participant_name }}"
                                        class="h-full w-full object-cover">
                                @else
                                    <span class="text-[10px]">✦</span>
                                @endif

                            </div>

                            <span class="truncate text-xs font-black text-slate-800">
                                {{ $row->participant_name }}
                            </span>

                        </div>
                    </td>


                    <td class="py-2 pr-2 text-center text-xs tabular-nums">{{ $row->matches }}</td>
                    <td class="py-2 pr-2 text-center text-xs tabular-nums">{{ $row->wins }}</td>
                    <td class="py-2 pr-2 text-center text-xs tabular-nums">{{ $row->draws }}</td>
                    <td class="py-2 pr-2 text-center text-xs tabular-nums">{{ $row->losses }}</td>

                    @unless ($compact)
                        <td class="py-2 pr-2 text-center text-xs tabular-nums text-slate-500">
                            {{ $row->score_for }}
                        </td>

                        <td class="py-2 pr-2 text-center text-xs tabular-nums text-slate-500">
                            {{ $row->score_against }}
                        </td>
                    @endunless

                    <td class="py-2 pr-2 text-center text-xs tabular-nums text-slate-500">
                        {{ $row->score_difference > 0 ? '+' : '' }}{{ $row->score_difference }}
                    </td>

                    <td class="py-2 pr-3 text-center text-xs font-black tabular-nums">
                        {{ $row->points }}
                    </td>

                    <td class="py-2">
                        @if ($row->status === 'ADVANCED')
                            <span
                                class="rounded-full bg-emerald-100 px-2 py-0.5 text-[9px] font-black uppercase text-emerald-700">
                                Clasifica
                            </span>
                        @elseif ($row->status === 'ELIMINATED')
                            <span
                                class="rounded-full bg-slate-200 px-2 py-0.5 text-[9px] font-black uppercase text-slate-500">
                                Eliminado
                            </span>
                        @endif
                    </td>

                </tr>
            @endforeach
        </tbody>

    </table>

</div>
