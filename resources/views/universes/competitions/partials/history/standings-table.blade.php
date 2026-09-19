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

    <table class="w-full min-w-max text-left">

        <thead>
            <tr class="border-b border-slate-800 text-[9px] font-black uppercase tracking-wider text-slate-600">
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

        <tbody class="divide-y divide-slate-800/70">
            @foreach ($standings as $row)
                <tr class="{{ $row->status === 'ADVANCED' ? 'bg-emerald-500/5' : '' }}">

                    <td class="py-2 pr-3">
                        <span
                            class="inline-flex h-6 w-6 items-center justify-center rounded-lg font-mono text-[10px] font-black {{ $row->position === 1 ? 'bg-violet-500 text-slate-950' : 'bg-slate-900 text-slate-500' }}">
                            {{ $row->position ?? '–' }}
                        </span>
                    </td>


                    <td class="py-2 pr-3">
                        <div class="flex items-center gap-2">

                            <div
                                class="flex h-7 w-7 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-800 bg-slate-900 text-slate-700">

                                @if ($row->face_url ?? $row->universeEntity?->image_url)
                                    <img src="{{ $row->face_url ?? $row->universeEntity?->image_url }}"
                                        alt="{{ $row->participant_name }}" class="h-full w-full object-cover">
                                @else
                                    <x-omni-icon name="usuario" size="h-3 w-3" />
                                @endif

                            </div>

                            <span class="truncate text-[11px] font-black text-slate-200">
                                {{ $row->participant_name }}
                            </span>

                        </div>
                    </td>


                    <td class="py-2 pr-2 text-center font-mono text-[11px] text-slate-400">{{ $row->matches }}</td>
                    <td class="py-2 pr-2 text-center font-mono text-[11px] text-emerald-300">{{ $row->wins }}</td>
                    <td class="py-2 pr-2 text-center font-mono text-[11px] text-slate-500">{{ $row->draws }}</td>
                    <td class="py-2 pr-2 text-center font-mono text-[11px] text-rose-300">{{ $row->losses }}</td>

                    @unless ($compact)
                        <td class="py-2 pr-2 text-center font-mono text-[11px] text-slate-600">
                            {{ $row->score_for }}
                        </td>

                        <td class="py-2 pr-2 text-center font-mono text-[11px] text-slate-600">
                            {{ $row->score_against }}
                        </td>
                    @endunless

                    <td class="py-2 pr-2 text-center font-mono text-[11px] text-slate-500">
                        {{ $row->score_difference > 0 ? '+' : '' }}{{ $row->score_difference }}
                    </td>

                    <td class="py-2 pr-3 text-center font-mono text-[11px] font-black text-white">
                        {{ $row->points }}
                    </td>

                    <td class="py-2">
                        @if ($row->status === 'ADVANCED')
                            <span class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase bg-emerald-500/15 text-emerald-300">
                                Clasifica
                            </span>
                        @elseif ($row->status === 'ELIMINATED')
                            <span class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase bg-slate-800 text-slate-500">
                                Eliminado
                            </span>
                        @endif
                    </td>

                </tr>
            @endforeach
        </tbody>

    </table>

</div>
