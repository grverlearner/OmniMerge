@php
    /*
     * HISTORIAL — cada competición que jugó, agrupada por temporada.
     *
     * Por temporada y no como lista plana porque un Universo tiene tiempo
     * propio: «la temporada 3» dice más que una fecha.
     */
@endphp

<div x-show="tab === 'history'" x-cloak class="space-y-2">

    @forelse ($historyBySeason as $temporada => $participaciones)
        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <div class="flex items-center gap-2 border-b border-slate-800 bg-slate-950/60 px-4 py-2">
                <span class="text-[11px]">🕘</span>
                <h2 class="text-[11px] font-black uppercase tracking-wider text-slate-300">
                    {{ $temporada > 0 ? 'Temporada ' . $temporada : 'Sin temporada' }}
                </h2>
                <span class="ml-auto font-mono text-[10px] text-slate-600">
                    {{ count($participaciones) }}
                </span>
            </div>

            <div class="divide-y divide-slate-800/60">
                @foreach ($participaciones as $p)
                    @php
                        $comp = $p->tournamentInstance;
                        $ganadas = (int) ($p->wins ?? 0);
                        $perdidas = (int) ($p->losses ?? 0);
                        $campeon = ($p->outcome ?? null) === 'CHAMPION';
                    @endphp

                    <a href="{{ $comp ? route('universes.competitions.show', [$universe, $comp]) : '#' }}"
                        class="flex flex-wrap items-center gap-2 px-3 py-2 transition hover:bg-slate-800/40">

                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg
                            {{ $campeon ? 'bg-amber-500/20 text-amber-300' : 'bg-slate-950 text-slate-600' }}">
                            <span class="text-[12px]">{{ $campeon ? '★' : '◆' }}</span>
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[11px] font-black text-slate-200">
                                {{ $comp->name ?? 'Competición' }}
                            </span>
                            <span class="block truncate font-mono text-[9px] text-slate-600">
                                {{ $comp?->code }}
                                @if ($p->final_position)
                                    · puesto {{ $p->final_position }}
                                @endif
                                @if ($comp?->completed_at)
                                    · {{ $comp->completed_at->format('d/m/Y') }}
                                @endif
                            </span>
                        </span>

                        <span class="shrink-0 font-mono text-[11px]">
                            <span class="text-emerald-300">{{ $ganadas }}</span><span class="text-slate-700">–</span><span class="text-rose-300">{{ $perdidas }}</span>
                        </span>

                        @if ($p->outcome)
                            <span class="shrink-0 rounded px-1.5 py-0.5 text-[8px] font-black
                                {{ $campeon ? 'bg-amber-500/20 text-amber-300' : 'bg-slate-800 text-slate-400' }}">
                                {{ $p->outcome_label ?? $p->outcome }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </div>
        </section>
    @empty
        <p class="rounded-2xl border border-dashed border-slate-700 px-4 py-10 text-center text-[11px] leading-relaxed text-slate-600">
            Todavía no ha competido en ninguna edición.
        </p>
    @endforelse


    {{-- ============ POR TIPO DE FASE ============ --}}

    @if (count($byEngine))
        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <div class="flex items-center gap-2 border-b border-slate-800 bg-cyan-500/10 px-4 py-2">
                <span class="text-[11px]">⧉</span>
                <h2 class="text-[11px] font-black uppercase tracking-wider text-cyan-300">
                    Cómo le va en cada tipo de fase
                </h2>
            </div>

            <div class="grid gap-1.5 p-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($byEngine as $fila)
                    @php
                        $tipos = [
                            'SINGLE_ELIMINATION' => 'Eliminación directa',
                            'ROUND_ROBIN' => 'Todos contra todos',
                            'GROUP_STAGE' => 'Fase de grupos',
                            'SWISS' => 'Suizo',
                        ];
                        $jugados = (int) ($fila['matches'] ?? 0);
                        $ganados = (int) ($fila['wins'] ?? 0);
                        $pct = $jugados > 0 ? (int) round($ganados / $jugados * 100) : null;
                    @endphp

                    <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-2.5">
                        <p class="truncate text-[10px] font-black text-slate-300">
                            {{ $tipos[$fila['phase_type']] ?? $fila['phase_type'] }}
                        </p>

                        <p class="font-mono text-[18px] font-black leading-none
                            {{ $pct === null ? 'text-slate-700' : ($pct >= 50 ? 'text-emerald-300' : 'text-slate-400') }}">
                            {{ $pct === null ? '—' : $pct . '%' }}
                        </p>

                        <p class="font-mono text-[9px] text-slate-600">
                            {{ $fila['phases'] }} {{ (int) $fila['phases'] === 1 ? 'fase' : 'fases' }}
                            · {{ $ganados }}–{{ (int) ($fila['losses'] ?? 0) }}
                            @if ((int) ($fila['draws'] ?? 0) > 0)
                                ({{ $fila['draws'] }} emp.)
                            @endif
                        </p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
