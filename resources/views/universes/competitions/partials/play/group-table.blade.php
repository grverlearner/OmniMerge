@php
    /*
     * La tabla de un grupo.
     *
     * Se extrajo porque la pintan dos modos distintos y duplicarla
     * significaba que una corrección en la línea de corte solo llegaba a
     * la mitad de la pantalla.
     *
     * $ordered        filas ya ordenadas
     * $rowState       fila, índice, total => 'in' | 'out' | 'open'
     * $groupCut       cuántos pasan de este grupo, o null
     * $phaseResolved  si la fase ya repartió
     */

    $total = $ordered->count();
@endphp

<div class="space-y-1 p-3">

    @foreach ($ordered as $index => $row)

        @php
            $state = $rowState($row, $index, $total);
            $rowPoints = $points->get((int) $row->universe_entity_id);
        @endphp

        {{-- Dónde queda la frontera de clasificación --}}
        @if (! $phaseResolved && $groupCut !== null && $groupCut < $total && $index === $groupCut)
            <div class="flex items-center gap-2 py-0.5">
                <span class="h-px flex-1 bg-rose-500/40"></span>
                <span class="text-[8px] font-black uppercase tracking-wider text-rose-500">
                    pasan {{ $groupCut }}
                </span>
                <span class="h-px flex-1 bg-rose-500/40"></span>
            </div>
        @endif

        <div @class([
            'flex items-center gap-2.5 rounded-lg border-l-2 px-2 py-1.5',
            'border-emerald-400 bg-emerald-500/10' => $state === 'in',
            'border-rose-500/50 bg-rose-500/5' => $state === 'out',
            'border-transparent' => $state === 'open',
        ])>

            <span class="w-4 shrink-0 text-center font-mono text-[11px] font-black text-slate-500">
                {{ $index + 1 }}
            </span>

            <div class="h-6 w-6 shrink-0 overflow-hidden rounded bg-slate-800">
                @if ($row->universeEntity?->image_url)
                    <img src="{{ $row->universeEntity->image_url }}" alt="" class="h-full w-full object-cover">
                @endif
            </div>

            <span class="min-w-0 flex-1 truncate text-[11px] font-semibold text-slate-300">
                {{ $row->participant_name }}
            </span>

            @if ($state === 'in')
                <span class="shrink-0 text-[9px] font-black text-emerald-400">▲</span>
            @elseif ($state === 'out')
                <span class="shrink-0 text-[9px] font-black text-rose-400/70">▼</span>
            @endif

            <span class="shrink-0 font-mono text-[10px] text-slate-500">
                {{ $row->wins }}-{{ $row->draws }}-{{ $row->losses }}
            </span>

            @if ($showPoints && $rowPoints)
                <span class="shrink-0 font-mono text-[10px] {{ $rowPoints['difference'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }}"
                    title="{{ $rowPoints['for'] }} a favor · {{ $rowPoints['against'] }} en contra">
                    {{ $rowPoints['difference'] >= 0 ? '+' : '' }}{{ $rowPoints['difference'] }}
                </span>
            @endif

            <span class="w-6 shrink-0 text-right font-mono text-xs font-black text-violet-300">
                {{ $row->points }}
            </span>

        </div>
    @endforeach

</div>
