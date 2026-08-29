@php
    /*
     * PALMARÉS — lo que ha ganado, y lo que eso le ha dado.
     *
     * Todo derivado: los títulos salen de las posiciones resueltas y los
     * trofeos de lo concedido. Aquí no se guarda nada, se lee.
     */
@endphp

<div x-show="tab === 'palmares'" x-cloak class="space-y-2">

    {{-- ============ EL PODIO EN CIFRAS ============ --}}

    <div class="grid grid-cols-2 gap-1.5 sm:grid-cols-4">
        @foreach ([
            ['🥇', 'Primeros', $palmares['first_places'] ?? 0, 'text-amber-300', 'border-amber-500/30 bg-amber-500/5'],
            ['🥈', 'Segundos', $palmares['second_places'] ?? 0, 'text-slate-300', 'border-slate-700 bg-slate-900/50'],
            ['🥉', 'Terceros', $palmares['third_places'] ?? 0, 'text-orange-300', 'border-orange-500/30 bg-orange-500/5'],
            ['🏆', 'Trofeos', $palmares['trophies'] ?? 0, 'text-violet-300', 'border-violet-500/30 bg-violet-500/5'],
        ] as [$icono, $label, $cifra, $tono, $marco])
            <div class="rounded-2xl border {{ $marco }} px-3 py-2 text-center">
                <p class="text-[15px]">{{ $icono }}</p>
                <p class="font-mono text-[20px] font-black leading-none {{ $tono }}">{{ $cifra }}</p>
                <p class="text-[8px] uppercase tracking-wider text-slate-600">{{ $label }}</p>
            </div>
        @endforeach
    </div>


    {{-- ============ SUS TROFEOS ============ --}}

    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

        <div class="flex items-center gap-2 border-b border-slate-800 bg-violet-500/10 px-4 py-2">
            <span class="text-[11px]">🏆</span>
            <h2 class="text-[11px] font-black uppercase tracking-wider text-violet-300">Vitrina</h2>
            <span class="ml-auto font-mono text-[10px] text-slate-600">{{ count($trophyAwards) }}</span>
        </div>

        @if (count($trophyAwards) === 0)
            <p class="px-4 py-6 text-center text-[11px] leading-relaxed text-slate-600">
                Todavía no ha ganado ningún trofeo.
            </p>
        @else
            <div class="grid gap-1.5 p-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($trophyAwards as $award)
                    @php $t = $award->trophy ?? null; @endphp

                    <div class="flex items-center gap-2 rounded-xl border border-violet-500/25 bg-violet-500/5 p-2">

                        <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-950 text-xl">
                            @if ($t?->image_url)
                                <img src="{{ $t->image_url }}" alt="" class="h-full w-full object-cover">
                            @else
                                {{ $t?->icon ?: '🏆' }}
                            @endif
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[11px] font-black text-slate-100">
                                {{ $t?->name ?? 'Trofeo' }}
                            </span>
                            <span class="block truncate text-[9px] text-slate-500">
                                {{ $award->tournamentInstance?->name ?? 'Sin competición' }}
                            </span>
                            <span class="block font-mono text-[8px] text-slate-600">
                                {{ $award->created_at?->format('d/m/Y') }}
                            </span>
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </section>


    {{-- ============ SUS PODIOS ============ --}}

    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

        <div class="flex items-center gap-2 border-b border-slate-800 bg-amber-500/10 px-4 py-2">
            <span class="text-[11px]">◆</span>
            <h2 class="text-[11px] font-black uppercase tracking-wider text-amber-300">Podios</h2>
            <span class="ml-auto font-mono text-[10px] text-slate-600">{{ count($podiums) }}</span>
        </div>

        @if (count($podiums) === 0)
            <p class="px-4 py-6 text-center text-[11px] leading-relaxed text-slate-600">
                Todavía no ha subido a ningún podio.
            </p>
        @else
            <div class="divide-y divide-slate-800/60">
                @foreach ($podiums as $p)
                    <div class="flex flex-wrap items-center gap-2 px-3 py-2">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-950 font-mono text-[11px] font-black
                            {{ ($p['position'] ?? 0) === 1 ? 'text-amber-300' : (($p['position'] ?? 0) === 2 ? 'text-slate-300' : 'text-orange-300') }}">
                            {{ $p['position'] ?? '?' }}
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[11px] font-black text-slate-200">
                                {{ $p['tournament'] ?? $p['name'] ?? 'Competición' }}
                            </span>
                            <span class="block font-mono text-[9px] text-slate-600">
                                {{ $p['date'] ?? $p['season'] ?? '' }}
                            </span>
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </section>


    {{-- ============ CÓMO HAN CAMBIADO SUS STATS ============ --}}

    @if (count($statHistory))
        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <div class="flex items-center gap-2 border-b border-slate-800 bg-emerald-500/10 px-4 py-2">
                <span class="text-[11px]">↗</span>
                <h2 class="text-[11px] font-black uppercase tracking-wider text-emerald-300">
                    Lo que le han dado los torneos
                </h2>
                <span class="ml-auto font-mono text-[10px] text-slate-600">{{ count($statHistory) }}</span>
            </div>

            <div class="divide-y divide-slate-800/60">
                @foreach ($statHistory as $cambio)
                    <div class="flex flex-wrap items-center gap-2 px-3 py-1.5">

                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[10px] font-black text-slate-300">
                                {{ $cambio->stat_key ?? $cambio['stat_key'] ?? '—' }}
                            </span>
                            <span class="block truncate text-[9px] text-slate-600">
                                {{ $cambio->reason ?? $cambio['reason'] ?? '' }}
                            </span>
                        </span>

                        @php
                            $antes = $cambio->value_before ?? $cambio['value_before'] ?? null;
                            $despues = $cambio->value_after ?? $cambio['value_after'] ?? null;
                            $sube = $antes !== null && $despues !== null && $despues > $antes;
                        @endphp

                        <span class="shrink-0 font-mono text-[10px]">
                            <span class="text-slate-600">{{ $antes ?? '—' }}</span>
                            <span class="mx-1 text-slate-700">→</span>
                            <span class="{{ $sube ? 'text-emerald-300' : 'text-rose-300' }}">{{ $despues ?? '—' }}</span>
                        </span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
