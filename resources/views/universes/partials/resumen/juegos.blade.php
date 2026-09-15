@php
    /*
     * Con que se juega este mundo.
     *
     * El motor que resuelve los enfrentamientos no es un detalle tecnico: es lo
     * que decide como se gana. Un universo que solo usa Rounded Number es un
     * mundo distinto de uno que reparte entre varios juegos, y eso no se veia
     * en ninguna parte.
     */

    $techoJuego = max(1, $juegos->max('cuantas') ?? 1);

    $tonosJuego = ['#a78bfa', '#34d399', '#22d3ee', '#fbbf24', '#fb7185', '#60a5fa'];
@endphp

@if ($juegos->isNotEmpty())

    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

        <header class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-2.5">

            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-800 text-slate-300">
                <x-omni-icon name="dado" size="h-4 w-4" />
            </span>

            <div class="min-w-0 flex-1">
                <h2 class="text-[13px] font-black text-white">Con qué se juega</h2>
                <p class="text-[10px] text-slate-500">Qué motor ha resuelto cada competición.</p>
            </div>

            <a href="{{ route('universes.games.index', $universe) }}"
                class="shrink-0 text-[11px] font-black text-slate-400 underline transition hover:text-white">
                Juegos
            </a>
        </header>

        <div class="space-y-2 p-3">

            @foreach ($juegos as $indice => $juego)
                @php
                    $tonoJ = $juego['clave'] === 'SIN_JUEGO'
                        ? '#475569'
                        : ($tonosJuego[$indice % count($tonosJuego)]);

                    $anchoJ = max(3, (int) round($juego['cuantas'] / $techoJuego * 100));
                @endphp

                <div>
                    <div class="flex items-baseline justify-between gap-2">
                        <span class="truncate text-[11px] font-black"
                            style="color: {{ $tonoJ }}">{{ $juego['nombre'] }}</span>

                        <span class="shrink-0 font-mono text-[11px] font-black text-slate-400">
                            {{ $juego['cuantas'] }}
                        </span>
                    </div>

                    <div class="mt-1 h-2 overflow-hidden rounded-full bg-slate-950">
                        <div class="h-full rounded-full" style="width: {{ $anchoJ }}%; background-color: {{ $tonoJ }}"></div>
                    </div>

                    @if ($juego['clave'] === 'SIN_JUEGO')
                        {{--
                            Honestidad: no es un juego, es la ausencia de uno.
                            Suele ser una competicion antigua, de antes de que
                            el motor se guardase en cada edicion.
                        --}}
                        <p class="mt-0.5 text-[9px] leading-3 text-slate-600">
                            No consta con qué se resolvió.
                        </p>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
@endif


{{-- ---------- EL MUNDO EN CARAS ---------- --}}

@if ($mosaico->isNotEmpty())

    <a href="{{ route('universes.explorer', $universe) }}"
        class="group block overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50 transition hover:border-violet-500/50">

        <div class="grid grid-cols-6 gap-px bg-slate-800">
            @foreach ($mosaico->take(12) as $cara)
                <span class="block aspect-square overflow-hidden bg-slate-950">
                    <img src="{{ $cara->image_url }}" alt="" loading="lazy"
                        class="h-full w-full object-cover opacity-70 transition duration-300 group-hover:opacity-100">
                </span>
            @endforeach
        </div>

        <div class="flex items-center gap-2 px-3 py-2.5">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                <x-omni-icon name="globo" size="h-4 w-4" />
            </span>

            <div class="min-w-0 flex-1">
                <p class="text-[12px] font-black text-white">El mapa del mundo</p>
                <p class="text-[10px] text-slate-500">
                    Los {{ $statistics['entities'] }} repartidos por lo que quieras ver.
                </p>
            </div>

            <span class="shrink-0 text-slate-600 transition group-hover:text-violet-400">
                <x-omni-icon name="chevron-derecha" size="h-4 w-4" />
            </span>
        </div>
    </a>
@endif
