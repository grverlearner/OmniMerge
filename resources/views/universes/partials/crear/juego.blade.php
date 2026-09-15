@php
    /*
     * Con que se resuelven los enfrentamientos.
     *
     * No es un detalle tecnico: el motor decide COMO se gana en este mundo, y
     * por tanto que clase de competidor triunfa. Elegirlo al crear evita que
     * los primeros torneos salgan con el que venia de fabrica sin haberlo
     * mirado.
     *
     * Las tarjetas salen del GameRegistry, asi que cuando haya mas juegos
     * aparecen aqui solos.
     */

    $tonosAcento = [
        'emerald' => '#34d399',
        'violet' => '#a78bfa',
        'sky' => '#38bdf8',
        'amber' => '#fbbf24',
        'rose' => '#fb7185',
        'cyan' => '#22d3ee',
    ];
@endphp

<section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <header class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-2.5">

        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-300">
            <x-omni-icon name="dado" size="h-4 w-4" />
        </span>

        <div class="min-w-0 flex-1">
            <h2 class="text-[13px] font-black text-white">Con qué se juega</h2>
            <p class="text-[10px] text-slate-500">
                El motor que resuelve cada enfrentamiento. Los torneos nuevos lo propondrán
                primero, y cada uno puede usar otro si quiere.
            </p>
        </div>
    </header>

    <div class="grid gap-2 p-4 sm:grid-cols-2 xl:grid-cols-3">

        @foreach ($juegos as $definicion)
            @php
                $tonoJ = $tonosAcento[$definicion['accent'] ?? ''] ?? '#94a3b8';
                $clave = $definicion['key'];
            @endphp

            <label class="block cursor-pointer overflow-hidden rounded-xl border transition"
                :style="juego === '{{ $clave }}'
                    ? 'border-color: {{ $tonoJ }}; background-color: {{ $tonoJ }}12'
                    : 'border-color: #1e293b'">

                <input type="radio" name="game_key" value="{{ $clave }}" x-model="juego" class="sr-only">

                <span class="block p-3">

                    <span class="flex items-start gap-2">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                            style="background-color: {{ $tonoJ }}22; color: {{ $tonoJ }}">
                            <x-omni-icon name="dado" size="h-4 w-4" />
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[13px] font-black"
                                :class="juego === '{{ $clave }}' ? 'text-white' : 'text-slate-300'">
                                {{ $definicion['name'] }}
                            </span>

                            <span class="block font-mono text-[9px]" style="color: {{ $tonoJ }}99">
                                {{ $definicion['type_label'] ?? '' }}
                                · desde {{ $definicion['minimum_participants'] ?? 2 }} competidores
                            </span>
                        </span>

                        <span class="shrink-0 rounded-full border-2 transition"
                            :style="juego === '{{ $clave }}'
                                ? 'border-color: {{ $tonoJ }}; background-color: {{ $tonoJ }}'
                                : 'border-color: #334155'"
                            style="height: 14px; width: 14px"></span>
                    </span>

                    <span class="mt-1.5 block text-[10px] leading-4 text-slate-500">
                        {{ $definicion['tagline'] ?? '' }}
                    </span>

                    @if (! empty($definicion['win_condition']))
                        <span class="mt-1.5 flex items-start gap-1.5 rounded-lg border border-slate-800 bg-slate-950 px-2 py-1.5">
                            <span class="shrink-0 text-[8px] font-black uppercase leading-3 tracking-wider text-slate-600">
                                Gana
                            </span>
                            <span class="text-[9px] leading-3 text-slate-400">
                                {{ $definicion['win_condition'] }}
                            </span>
                        </span>
                    @endif

                    <span class="mt-1.5 flex flex-wrap gap-1">
                        @if (! empty($definicion['allows_draws']))
                            <span class="rounded bg-slate-800 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-slate-400">
                                admite empates
                            </span>
                        @endif

                        @if (! empty($definicion['tracks_points']))
                            <span class="rounded bg-slate-800 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-slate-400">
                                lleva {{ mb_strtolower($definicion['points_label'] ?? 'puntos') }}
                            </span>
                        @endif
                    </span>
                </span>
            </label>
        @endforeach
    </div>

    <p class="border-t border-slate-800 px-4 py-2 text-[9px] leading-3 text-slate-600">
        Por ahora hay {{ $juegos->count() }}
        {{ $juegos->count() === 1 ? 'motor disponible' : 'motores disponibles' }}. Los que
        vengan aparecerán aquí solos, sin tocar esta pantalla.
    </p>

    <x-input-error :messages="$errors->get('game_key')" class="mx-4 mb-3" />
</section>
