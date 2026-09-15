@php
    /*
     * Un universo en la estanteria.
     *
     * La tarjeta grande: mosaico de su gente de fondo, su imagen, lo que tiene
     * dentro y -lo que no habia- si algo esta atascado ahi.
     *
     * Un mundo se reconoce por su gente antes que por su nombre, asi que las
     * caras no son decoracion: son la forma mas rapida de saber cual de los
     * tres es este.
     */

    $caras = $carasPorUniverso[$mundo->id] ?? collect();
    $campeon = $campeonPorUniverso[$mundo->id] ?? null;
    $temporada = $temporadaPorUniverso[$mundo->id] ?? null;

    [$tono, $textoEstado] = $tonosEstado[$mundo->status] ?? ['#94a3b8', $mundo->status];

    $atascado = $mundo->atascadas_count > 0;
@endphp

<article class="group relative overflow-hidden rounded-2xl border bg-slate-900/50 transition duration-300 hover:-translate-y-0.5"
    style="border-color: {{ $atascado ? '#fb718566' : $mundo->accent . '55' }}">

    {{-- Su color --}}
    <span class="pointer-events-none absolute inset-x-0 top-0 z-10 h-1" style="background-color: {{ $mundo->accent }}"></span>

    {{-- ---------- LA PORTADA ---------- --}}

    <a href="{{ $mundo->home_url }}" class="relative block h-32 overflow-hidden bg-slate-950">

        {{-- Su gente de fondo --}}
        @if ($caras->isNotEmpty())
            <span class="absolute inset-0 grid grid-cols-6 opacity-30">
                @foreach ($caras as $cara)
                    <span class="block aspect-square overflow-hidden">
                        <img src="{{ $cara->image_url }}" alt="" loading="lazy"
                            class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                    </span>
                @endforeach
            </span>
        @endif

        <span class="absolute inset-0"
            style="background: linear-gradient(120deg, #020617 18%, {{ $mundo->accent }}26 65%, #02061788 100%)"></span>

        {{-- Su cara --}}
        <span class="absolute left-3 top-3 h-20 w-20 overflow-hidden rounded-xl border-2 bg-slate-950 shadow-lg"
            style="border-color: {{ $mundo->accent }}">
            @if ($mundo->image_url)
                <img src="{{ $mundo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover" style="object-position: {{ $mundo->ajustes()->coverPosition() }}">
            @else
                <span class="flex h-full w-full items-center justify-center" style="color: {{ $mundo->accent }}; background-color: {{ $mundo->accent }}1a">
                    <x-omni-icon :name="$mundo->ajustes()->icon()" size="h-8 w-8" />
                </span>
            @endif
        </span>

        {{-- Etiquetas --}}
        <span class="absolute right-2 top-2 flex flex-wrap justify-end gap-1">
            <span class="rounded-lg px-2 py-0.5 text-[9px] font-black uppercase tracking-wider backdrop-blur"
                style="color: {{ $tono }}; background-color: {{ $tono }}26">{{ $textoEstado }}</span>

            @if ($mundo->vivas_count > 0)
                <span class="flex items-center gap-1 rounded-lg bg-emerald-500/25 px-2 py-0.5 text-[9px] font-black text-emerald-200 backdrop-blur">
                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400"></span>
                    {{ $mundo->vivas_count }} en juego
                </span>
            @endif

            @if ($atascado)
                <span class="rounded-lg bg-rose-500/30 px-2 py-0.5 text-[9px] font-black text-rose-200 backdrop-blur"
                    title="{{ $mundo->atascadas_count }} competiciones paradas esperando una decisión">
                    {{ $mundo->atascadas_count }} atascada{{ $mundo->atascadas_count === 1 ? '' : 's' }}
                </span>
            @endif
        </span>

        {{-- El último campeón, en su esquina --}}
        @if ($campeon?->universeEntity)
            <span class="absolute bottom-2 right-2 flex items-center gap-1.5 rounded-xl bg-slate-950/80 px-1.5 py-1 backdrop-blur"
                title="Último campeón: {{ $campeon->universeEntity->display_label }} en {{ $campeon->la_competicion }}">

                <span class="h-7 w-7 overflow-hidden rounded-lg border border-amber-500/50 bg-slate-900">
                    @if ($campeon->universeEntity->image_url)
                        <img src="{{ $campeon->universeEntity->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                    @else
                        <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                    @endif
                </span>

                <span class="pr-1">
                    <span class="block text-[8px] font-black uppercase leading-3 tracking-wider text-amber-400/70">
                        Último campeón
                    </span>
                    <span class="block max-w-[100px] truncate text-[10px] font-black leading-3 text-amber-100">
                        {{ $campeon->universeEntity->display_label }}
                    </span>
                </span>
            </span>
        @endif
    </a>


    {{-- ---------- LO QUE HAY DENTRO ---------- --}}

    <div class="p-3">

        <div class="flex items-start gap-2">
            <div class="min-w-0 flex-1">
                <a href="{{ $mundo->home_url }}"
                    class="block truncate text-[14px] font-black leading-tight text-white transition hover:text-violet-300">
                    {{ $mundo->name }}
                </a>

                <p class="flex flex-wrap items-center gap-x-1.5 font-mono text-[9px] text-slate-600">
                    <span>{{ $mundo->code }}</span>
                    @if ($temporada)
                        <span class="text-violet-400">· T{{ $temporada->number }} {{ $temporada->name }}</span>
                    @elseif ($mundo->seasons_count > 0)
                        <span class="text-amber-500">· sin temporada en marcha</span>
                    @endif
                </p>
            </div>

            @if ($mundo->activities_max_occurred_at)
                <span class="shrink-0 text-right">
                    <span class="block text-[8px] font-black uppercase leading-3 tracking-wider text-slate-700">
                        Se movió
                    </span>
                    <span class="block font-mono text-[9px] text-slate-500">
                        {{ \Illuminate\Support\Carbon::parse($mundo->activities_max_occurred_at)->diffForHumans(null, true) }}
                    </span>
                </span>
            @endif
        </div>

        @if ($mundo->ajustes()->tagline())
            <p class="mt-1 truncate text-[11px] font-black" style="color: {{ $mundo->accent }}">{{ $mundo->ajustes()->tagline() }}</p>
        @endif

        @if ($mundo->description)
            <p class="mt-1 line-clamp-2 text-[10px] leading-relaxed text-slate-500">{{ $mundo->description }}</p>
        @endif


        {{-- Las cifras que lo describen --}}
        <div class="mt-2 grid grid-cols-4 gap-1">
            @foreach ([['Gente', $mundo->entities_count, '#a78bfa'], ['Torneos', $mundo->universe_tournaments_count, '#22d3ee'], ['Jugadas', $mundo->tournament_instances_count, '#34d399'], ['Trofeos', $mundo->trophies_count, '#fbbf24']] as [$etiqueta, $valor, $tonoC])
                <span class="rounded-lg border border-slate-800 bg-slate-950 py-1 text-center">
                    <span class="block font-mono text-[13px] font-black"
                        style="color: {{ $valor > 0 ? $tonoC : '#475569' }}">{{ $valor }}</span>
                    <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">{{ $etiqueta }}</span>
                </span>
            @endforeach
        </div>

        {{--
            Lo que pide atención, si pide algo. Es el mismo criterio del
            Resumen de cada universo, para que la estantería no diga una cosa
            y el mundo otra.
        --}}
        @if ($atascado || $mundo->listas_count > 0)
            <div class="mt-2 flex flex-wrap gap-1">
                @if ($atascado)
                    <a href="{{ route('universes.competitions.index', $mundo) }}"
                        class="rounded-lg border border-rose-500/40 bg-rose-500/10 px-2 py-1 text-[10px] font-black text-rose-300 transition hover:bg-rose-500 hover:text-white">
                        {{ $mundo->atascadas_count }} esperan una decisión
                    </a>
                @endif

                @if ($mundo->listas_count > 0)
                    <a href="{{ route('universes.competitions.index', $mundo) }}?status=DRAFT"
                        class="rounded-lg border border-sky-500/40 bg-sky-500/10 px-2 py-1 text-[10px] font-black text-sky-300 transition hover:bg-sky-500 hover:text-white">
                        {{ $mundo->listas_count }} sin empezar
                    </a>
                @endif
            </div>
        @endif

        @if ($mundo->tournament_instances_count === 0)
            <p class="mt-2 rounded-lg border border-dashed border-slate-800 px-2 py-1.5 text-[10px] text-slate-600">
                @if ($mundo->entities_count === 0)
                    Vacío: todavía no tiene habitantes.
                @else
                    Tiene gente, pero aquí no se ha jugado nada aún.
                @endif
            </p>
        @endif
    </div>


    {{-- ---------- ATAJOS ---------- --}}

    <div class="flex items-center gap-1 border-t border-slate-800/70 px-2 py-1.5">
        @foreach ([['universes.explorer', 'globo', 'El mapa de su gente'], ['universes.competitions.index', 'espadas', 'Competiciones'], ['universes.ranking', 'barras', 'Clasificación'], ['universes.trophies.index', 'trofeo', 'Trofeos'], ['universes.history', 'historial', 'Historial']] as [$ruta, $icono, $ayuda])
            <a href="{{ route($ruta, $mundo) }}" title="{{ $ayuda }}"
                class="rounded-lg px-1.5 py-1.5 text-slate-600 transition hover:bg-slate-800 hover:text-violet-300">
                <x-omni-icon :name="$icono" size="h-3.5 w-3.5" />
            </a>
        @endforeach

        <span class="flex-1"></span>

        <a href="{{ $mundo->home_url }}"
            class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-500 transition hover:text-violet-300">
            Entrar
        </a>
    </div>
</article>
