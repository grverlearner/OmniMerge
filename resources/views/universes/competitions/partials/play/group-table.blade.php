@php
    /*
     * La tabla de un grupo.
     *
     * Se extrajo porque la pintan dos modos distintos y duplicarla
     * significaba que una corrección en la línea de corte solo llegaba a
     * la mitad de la pantalla.
     *
     * Lleva las mismas columnas que la clasificación de Round Robin
     * —jugados, ganados, empatados, perdidos, a favor, en contra,
     * diferencia y puntos— porque son la misma clase de tabla y no había
     * motivo para que una dijera menos que la otra. Cabe dentro de una
     * tarjeta de grupo estrecha porque se desplaza a lo ancho por dentro.
     *
     * $ordered        filas ya ordenadas
     * $rowState       fila, índice, total => 'in' | 'out' | 'open'
     * $groupCut       cuántos pasan de este grupo, o null
     * $phaseResolved  si la fase ya repartió
     */

    $total = $ordered->count();

    /* Sin puntuación no hay a favor / en contra / diferencia que enseñar */
    $columnas = $showPoints ? 10 : 7;
@endphp

<div class="overflow-x-auto">

    <table class="w-full min-w-[460px]">

        <thead>
            <tr class="border-b border-slate-800 text-[8px] font-black uppercase tracking-wider text-slate-500">
                <th class="py-1.5 pl-3 pr-1 text-left">#</th>
                <th class="px-1 py-1.5 text-left">Competidor</th>
                <th class="px-1 py-1.5 text-center" title="Enfrentamientos jugados">PJ</th>
                <th class="px-1 py-1.5 text-center" title="Ganados">PG</th>
                <th class="px-1 py-1.5 text-center" title="Empatados">PE</th>
                <th class="px-1 py-1.5 text-center" title="Perdidos">PP</th>

                @if ($showPoints)
                    <th class="px-1 py-1.5 text-center text-emerald-500" title="A favor">AF</th>
                    <th class="px-1 py-1.5 text-center text-rose-500" title="En contra">EC</th>
                    <th class="px-1 py-1.5 text-center" title="Diferencia">DIF</th>
                @endif

                <th class="py-1.5 pl-1 pr-3 text-center text-violet-400">PTS</th>
            </tr>
        </thead>

        <tbody>

            @foreach ($ordered as $index => $row)

                @php
                    $state = $rowState($row, $index, $total);
                    $rowPoints = $points->get((int) $row->universe_entity_id);
                    $place = $index + 1;
                @endphp

                {{-- Dónde queda la frontera de clasificación --}}
                @if (! $phaseResolved && $groupCut !== null && $groupCut < $total && $index === $groupCut)
                    <tr>
                        <td colspan="{{ $columnas }}" class="px-3 py-1">
                            <div class="flex items-center gap-2">
                                <span class="h-px flex-1 bg-rose-500/40"></span>
                                <span class="text-[8px] font-black uppercase tracking-wider text-rose-500">
                                    pasan {{ $groupCut }}
                                </span>
                                <span class="h-px flex-1 bg-rose-500/40"></span>
                            </div>
                        </td>
                    </tr>
                @endif

                <tr @class([
                    'border-b border-slate-800/50 border-l-2 transition hover:bg-slate-900/40',
                    'border-l-emerald-400 bg-emerald-500/10' => $state === 'in',
                    'border-l-rose-500/50 bg-rose-500/5' => $state === 'out',
                    'border-l-transparent' => $state === 'open',
                ])>

                    <td class="py-2 pl-3 pr-1">
                        <span @class([
                            'flex h-5 w-5 items-center justify-center rounded font-mono text-[10px] font-black',
                            'bg-amber-400 text-slate-950' => $place === 1,
                            'bg-slate-400 text-slate-950' => $place === 2,
                            'bg-orange-600 text-white' => $place === 3,
                            'text-slate-500' => $place > 3,
                        ])>
                            {{ $place }}
                        </span>
                    </td>

                    <td class="px-1 py-2">
                        <div class="flex items-center gap-2">

                            <div class="h-6 w-6 shrink-0 overflow-hidden rounded bg-slate-800">
                                @if ($row->universeEntity?->image_url)
                                    <img src="{{ $row->universeEntity->image_url }}" alt=""
                                        class="h-full w-full object-cover">
                                @endif
                            </div>

                            <div class="min-w-0">
                                <p class="truncate text-[11px] font-bold text-slate-200">
                                    {{ $row->participant_name }}
                                </p>

                                @if ($state === 'in')
                                    <span class="text-[8px] font-black uppercase tracking-wider text-emerald-400">
                                        ▲ {{ $phaseResolved ? 'Clasificado' : 'Pasando' }}
                                    </span>
                                @elseif ($state === 'out')
                                    <span class="text-[8px] font-black uppercase tracking-wider text-rose-400/80">
                                        ▼ {{ $phaseResolved ? 'Eliminado' : 'Fuera' }}
                                    </span>
                                @endif
                            </div>

                        </div>
                    </td>

                    <td class="px-1 py-2 text-center font-mono text-[11px] text-slate-400">{{ $row->matches }}</td>
                    <td class="px-1 py-2 text-center font-mono text-[11px] font-black text-emerald-400">{{ $row->wins }}</td>
                    <td class="px-1 py-2 text-center font-mono text-[11px] text-slate-500">{{ $row->draws }}</td>
                    <td class="px-1 py-2 text-center font-mono text-[11px] text-rose-400">{{ $row->losses }}</td>

                    @if ($showPoints)
                        <td class="px-1 py-2 text-center font-mono text-[11px] text-emerald-300">
                            {{ $rowPoints['for'] ?? '—' }}
                        </td>

                        <td class="px-1 py-2 text-center font-mono text-[11px] text-rose-300">
                            {{ $rowPoints['against'] ?? '—' }}
                        </td>

                        <td class="px-1 py-2 text-center">
                            @if ($rowPoints)
                                <span class="font-mono text-[11px] font-black {{ $rowPoints['difference'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                                    {{ $rowPoints['difference'] >= 0 ? '+' : '' }}{{ $rowPoints['difference'] }}
                                </span>
                            @else
                                <span class="text-[11px] text-slate-600">—</span>
                            @endif
                        </td>
                    @endif

                    <td class="py-2 pl-1 pr-3 text-center">
                        <span class="font-mono text-sm font-black text-violet-300">{{ $row->points }}</span>
                    </td>

                </tr>
            @endforeach

        </tbody>

    </table>

</div>

@if ($showPoints)
    <p class="border-t border-slate-800/60 px-3 py-2 text-[9px] leading-relaxed text-slate-500">
        Con los mismos puntos manda la <strong class="font-black text-slate-400">diferencia</strong>,
        y después lo <strong class="font-black text-slate-400">anotado a favor</strong>.
    </p>
@endif
