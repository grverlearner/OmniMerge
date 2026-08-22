@php
    $tierSkin = [
        'GOLD' => 'from-amber-400 to-amber-600 shadow-amber-500/30',
        'SILVER' => 'from-slate-300 to-slate-500 shadow-slate-500/30',
        'BRONZE' => 'from-orange-400 to-orange-700 shadow-orange-600/30',
        'SPECIAL' => 'from-violet-400 to-violet-600 shadow-violet-500/30',
    ];

    $medal = [1 => '🥇', 2 => '🥈', 3 => '🥉'];
@endphp


{{-- ============================================ --}}
{{-- PALMARÉS --}}
{{-- ============================================ --}}

<section class="rounded-3xl border border-slate-200 bg-white p-6">

    <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-600">
        Lo conseguido en {{ $universe->name }}
    </p>

    <h2 class="mt-2 text-2xl font-black text-slate-900">🏆 Palmarés</h2>


    {{-- VITRINA DE CIFRAS --}}

    <div class="mt-6 grid grid-cols-2 gap-px overflow-hidden rounded-2xl bg-slate-100 sm:grid-cols-4">

        <div class="bg-white px-4 py-4 text-center">
            <p class="text-3xl font-black text-amber-500">{{ $palmares['first_places'] }}</p>
            <p class="mt-0.5 text-[10px] font-black uppercase tracking-wider text-slate-400">Títulos</p>
        </div>

        <div class="bg-white px-4 py-4 text-center">
            <p class="text-3xl font-black text-slate-400">{{ $palmares['second_places'] }}</p>
            <p class="mt-0.5 text-[10px] font-black uppercase tracking-wider text-slate-400">Finales</p>
        </div>

        <div class="bg-white px-4 py-4 text-center">
            <p class="text-3xl font-black text-orange-500">{{ $palmares['third_places'] }}</p>
            <p class="mt-0.5 text-[10px] font-black uppercase tracking-wider text-slate-400">Terceros</p>
        </div>

        <div class="bg-white px-4 py-4 text-center">
            <p class="text-3xl font-black text-violet-600">{{ $palmares['trophies'] }}</p>
            <p class="mt-0.5 text-[10px] font-black uppercase tracking-wider text-slate-400">Trofeos</p>
        </div>

    </div>


    {{-- TROFEOS --}}

    @if ($trophyAwards->isNotEmpty())

        <div class="mt-6">

            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Vitrina</p>

            <div class="mt-3 flex flex-wrap gap-3">

                @foreach ($trophyAwards as $award)

                    @php
                        $skin = $tierSkin[$award->trophy?->tier ?? 'GOLD'] ?? $tierSkin['GOLD'];
                    @endphp

                    <div class="group relative">

                        <div
                            class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br {{ $skin }} text-3xl shadow-lg">
                            @if ($award->trophy?->image_url)
                                <img src="{{ $award->trophy->image_url }}" alt=""
                                    class="h-full w-full rounded-2xl object-cover">
                            @else
                                {{ $award->trophy?->display_icon ?? '🏆' }}
                            @endif
                        </div>

                        <div
                            class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 hidden w-48 -translate-x-1/2 rounded-xl bg-slate-900 p-3 text-center shadow-xl group-hover:block">

                            <p class="text-xs font-black text-white">{{ $award->trophy?->name }}</p>

                            <p class="mt-1 text-[10px] text-slate-300">
                                {{ $award->tournamentInstance?->name }}
                                @if ($award->season)
                                    · Temporada {{ $award->season->number }}
                                @endif
                            </p>

                        </div>

                    </div>
                @endforeach

            </div>

        </div>
    @endif


    {{-- PODIOS --}}

    @if ($podiums->isNotEmpty())

        <div class="mt-6">

            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Podios</p>

            <div class="mt-3 divide-y divide-slate-100">

                @foreach ($podiums as $podium)
                    <div class="flex items-center gap-3 py-3">

                        <span class="text-2xl">{{ $medal[$podium->placement] ?? '·' }}</span>

                        <div class="min-w-0 flex-1">

                            <a href="{{ route('universes.competitions.show', [$universe, $podium->tournament_instance_id]) }}"
                                class="block truncate text-sm font-black text-slate-900 hover:text-violet-600">
                                {{ $podium->tournamentInstance?->name }}
                            </a>

                            <p class="text-[10px] text-slate-400">
                                @if ($podium->tournamentInstance?->season)
                                    Temporada {{ $podium->tournamentInstance->season->number }} ·
                                @endif
                                {{ $podium->tournamentInstance?->completed_at?->format('d/m/Y') }}
                            </p>

                        </div>

                    </div>
                @endforeach

            </div>

        </div>
    @endif


    @if ($palmares['tournaments'] === 0)
        <p class="mt-6 text-center text-sm text-slate-400">
            Todavía no ha terminado ninguna competición.
        </p>
    @endif

</section>


{{-- ============================================ --}}
{{-- HISTORIAL DE PROGRESIÓN --}}
{{-- ============================================ --}}

@if ($statHistory->isNotEmpty())

    <section class="rounded-3xl border border-slate-200 bg-white p-6">

        <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-600">
            Por qué es lo que es hoy
        </p>

        <h2 class="mt-2 text-2xl font-black text-slate-900">📈 Progresión</h2>

        <p class="mt-2 text-sm text-slate-500">
            Cada cambio en sus estadísticas, con el motivo que lo provocó.
        </p>


        <div class="mt-6 space-y-2">

            @foreach ($statHistory as $change)

                <details class="group rounded-2xl border border-slate-200 bg-slate-50/60 open:bg-white">

                    <summary class="flex cursor-pointer items-center gap-3 p-4">

                        <span class="text-xl">
                            {{ $change->source_type === 'RANKING' ? '📊' : '🏆' }}
                        </span>

                        <div class="min-w-0 flex-1">

                            <div class="flex flex-wrap items-center gap-2">

                                <span class="text-sm font-black text-slate-900">
                                    {{ $change->reason ?: 'Recompensa' }}
                                </span>

                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-black
                                        {{ $change->delta >= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                    {{ $change->delta_label }} {{ $change->stat_key }}
                                </span>

                            </div>

                            <p class="mt-0.5 truncate text-[10px] text-slate-400">
                                {{ $change->tournamentInstance?->name ?? 'Ajuste del Universo' }}
                                @if ($change->season)
                                    · Temporada {{ $change->season->number }}
                                @endif
                            </p>

                        </div>

                        <span class="shrink-0 font-mono text-xs font-black text-slate-500">
                            {{ rtrim(rtrim(number_format((float) $change->value_before, 2, '.', ''), '0'), '.') }}
                            →
                            {{ rtrim(rtrim(number_format((float) $change->value_after, 2, '.', ''), '0'), '.') }}
                        </span>

                    </summary>

                    <div class="border-t border-slate-100 px-4 py-3 text-xs text-slate-500">

                        <dl class="grid gap-2 sm:grid-cols-2">

                            <div>
                                <dt class="text-[10px] font-black uppercase tracking-wider text-slate-400">Juego</dt>
                                <dd class="font-bold text-slate-700">{{ $change->game_key }}</dd>
                            </div>

                            <div>
                                <dt class="text-[10px] font-black uppercase tracking-wider text-slate-400">Cuándo</dt>
                                <dd class="font-bold text-slate-700">{{ $change->created_at?->format('d/m/Y H:i') }}</dd>
                            </div>

                            @if ($change->reward?->trophy)
                                <div>
                                    <dt class="text-[10px] font-black uppercase tracking-wider text-slate-400">Trofeo</dt>
                                    <dd class="font-bold text-slate-700">
                                        {{ $change->reward->trophy->display_icon }}
                                        {{ $change->reward->trophy->name }}
                                    </dd>
                                </div>
                            @endif

                        </dl>

                    </div>

                </details>
            @endforeach

        </div>

    </section>
@endif
