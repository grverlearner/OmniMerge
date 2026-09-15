{{--
    CLASIFICACIÓN — cómo se decide quién manda.

    Los puntos de cada resultado, cuántas competiciones hay que jugar para
    aparecer y cómo se deshace un empate a puntos. Todo lo aplica
    UniverseRankingService, así que la clasificación se recalcula sola.
--}}

@php
    $puntos = [
        'points_champion' => ['Por título', 'Ganar una competición', 'medalla', '#fbbf24'],
        'points_win' => ['Por victoria', 'Cada enfrentamiento ganado', 'espadas', '#34d399'],
        'points_draw' => ['Por empate', 'Cada enfrentamiento empatado', 'capas', '#38bdf8'],
        'points_loss' => ['Por derrota', 'Cada enfrentamiento perdido', 'cerrar', '#f87171'],
        'points_participation' => ['Por participar', 'Cada competición jugada', 'usuario', '#a78bfa'],
    ];
@endphp

<section id="clasificacion" data-seccion class="scroll-mt-24 overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <header class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-3"
        style="background: linear-gradient(120deg, #fbbf241a, transparent 60%)">
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500/15 text-amber-300">
            <x-omni-icon name="barras" size="h-4 w-4" />
        </span>
        <div class="min-w-0 flex-1">
            <h2 class="text-[14px] font-black text-white">Clasificación</h2>
            <p class="text-[10px] text-slate-500">Puntos, mínimo para aparecer y desempates. La clasificación se recalcula sola.</p>
        </div>
        @if (\App\Support\Universes\UniverseSettings::NAV['ranking'] ?? false)
            <a href="{{ route('universes.ranking', $universe) }}" target="_blank" rel="noopener"
                class="rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-500 transition hover:border-amber-500 hover:text-amber-300">Ver la clasificación</a>
        @endif
        <button type="button" @click="restablecer(['points_champion', 'points_win', 'points_draw', 'points_loss', 'points_participation', 'ranking_min_competitions', 'ranking_tiebreaks'])"
            class="flex items-center gap-1 rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-500 transition hover:border-slate-600 hover:text-white">
            <x-omni-icon name="deshacer" size="h-3 w-3" />
            Restablecer
        </button>
    </header>

    <div class="space-y-5 p-4">

        {{-- ============ LOS PUNTOS ============ --}}

        <div>
            <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">Puntos</p>

            <div class="mt-2 grid gap-2 sm:grid-cols-2 xl:grid-cols-5">
                @foreach ($puntos as $clave => [$titulo, $ayuda, $icono, $tono])
                    <div class="rounded-xl border bg-slate-950/60 p-2.5" style="border-color: {{ $tono }}33">
                        <span class="flex items-center gap-1.5 text-[11px] font-black" style="color: {{ $tono }}">
                            <x-omni-icon :name="$icono" size="h-3.5 w-3.5" />
                            {{ $titulo }}
                        </span>
                        <span class="block text-[9px] text-slate-600">{{ $ayuda }}</span>

                        <div class="mt-2 flex items-center gap-1">
                            <button type="button" @click="s.{{ $clave }} = Math.max(0, (Number(s.{{ $clave }}) || 0) - 1)"
                                class="h-7 w-7 rounded-lg border border-slate-700 text-[14px] font-black text-slate-400 hover:text-white">−</button>
                            <input type="number" min="0" max="1000" name="settings[{{ $clave }}]" x-model.number="s.{{ $clave }}"
                                class="w-full min-w-0 rounded-lg border-slate-700 bg-slate-900 py-1 text-center font-mono text-[15px] font-black text-white focus:border-amber-500 focus:ring-amber-500">
                            <button type="button" @click="s.{{ $clave }} = Math.min(1000, (Number(s.{{ $clave }}) || 0) + 1)"
                                class="h-7 w-7 rounded-lg border border-slate-700 text-[14px] font-black text-slate-400 hover:text-white">+</button>
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="mt-2 rounded-xl border border-amber-500/20 bg-amber-500/5 px-3 py-2 text-[11px] leading-5 text-slate-300">
                Ejemplo: un campeón que ganó 4, empató 1 y perdió 1 a lo largo de 2 competiciones suma
                <span class="font-mono text-[15px] font-black text-amber-300" x-text="puntosEjemplo"></span> puntos.
            </p>
        </div>


        {{-- ============ EL MÍNIMO ============ --}}

        <div>
            <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">Mínimo para aparecer</p>
            <p class="text-[10px] text-slate-600">Quien haya jugado menos competiciones no sale en la clasificación. Sus resultados se conservan.</p>

            <div class="mt-2 flex items-center gap-3">
                <input type="range" min="0" max="20" step="1" name="settings[ranking_min_competitions]" x-model.number="s.ranking_min_competitions"
                    class="flex-1 accent-amber-500">
                <span class="w-40 text-right text-[12px] font-black text-amber-200"
                    x-text="s.ranking_min_competitions == 0 ? 'Aparece todo el que jugó' : 'Al menos ' + s.ranking_min_competitions + (s.ranking_min_competitions == 1 ? ' competición' : ' competiciones')"></span>
            </div>
        </div>


        {{-- ============ LOS DESEMPATES ============ --}}

        <div>
            <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">Si dos empatan a puntos</p>
            <p class="text-[10px] text-slate-600">Se miran en este orden, hasta que uno quede por delante.</p>

            <div class="mt-2 space-y-1">
                <template x-for="(d, i) in s.ranking_tiebreaks" :key="'tb' + d">
                    <div class="flex items-center gap-2 rounded-xl border border-amber-500/30 bg-slate-950 px-2.5 py-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-amber-500 font-mono text-[11px] font-black text-slate-950" x-text="i + 1"></span>
                        <span class="min-w-0 flex-1 truncate text-[12px] font-black text-slate-100" x-text="desempates[d]"></span>
                        <button type="button" @click="mover('ranking_tiebreaks', i, -1)" class="p-1 text-slate-500 hover:text-white" title="Antes">
                            <x-omni-icon name="chevron-izquierda" size="h-3.5 w-3.5" class="rotate-90" />
                        </button>
                        <button type="button" @click="mover('ranking_tiebreaks', i, 1)" class="p-1 text-slate-500 hover:text-white" title="Después">
                            <x-omni-icon name="chevron-derecha" size="h-3.5 w-3.5" class="rotate-90" />
                        </button>
                        <button type="button" @click="alternar('ranking_tiebreaks', d)" class="p-1 text-slate-500 hover:text-rose-300" title="Quitar">
                            <x-omni-icon name="cerrar" size="h-3.5 w-3.5" />
                        </button>
                    </div>
                </template>

                <p x-show="! s.ranking_tiebreaks.length" class="rounded-xl border border-dashed border-slate-700 px-3 py-2 text-[10px] text-slate-500">
                    Sin desempates: quien empata a puntos queda en el orden en que llegó.
                </p>
            </div>

            <div x-show="desempatesInactivos.length" class="mt-2 flex flex-wrap gap-1">
                <template x-for="d in desempatesInactivos" :key="'tbi' + d">
                    <button type="button" @click="alternar('ranking_tiebreaks', d)"
                        class="rounded-full border border-slate-700 px-2.5 py-1 text-[10px] font-bold text-slate-400 transition hover:border-amber-400 hover:text-amber-200">
                        + <span x-text="desempates[d]"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>
</section>
