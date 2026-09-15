@php
    /*
     * Los cuatro modulos, con lo que hay dentro de cada uno.
     *
     * Antes eran seis tarjetas iguales con un emoji y una descripcion generica.
     * Un modulo se reconoce por lo que contiene: por eso cada tarjeta lleva sus
     * caras de verdad -las entidades de la Biblioteca, las portadas de tus
     * mundos, tus plantillas de torneo- y sus cifras, no un adjetivo.
     *
     * Y por eso ya no esta la tarjeta de «Rankings y analitica, proximamente»:
     * la clasificacion existe, se usa, y vive dentro de cada universo.
     */
@endphp

<section class="grid gap-3 lg:grid-cols-2">

    {{-- ===================================================== --}}
    {{-- BIBLIOTECA --}}
    {{-- ===================================================== --}}

    <article class="group overflow-hidden rounded-2xl border border-indigo-500/25 bg-slate-900/50 transition hover:border-indigo-500/50">

        <a href="{{ route('dashboard') }}" class="block">

            <div class="flex items-center gap-3 border-b border-indigo-500/15 px-4 py-3">

                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-500/15 text-indigo-300">
                    <x-omni-icon name="libro" size="h-5 w-5" />
                </span>

                <div class="min-w-0 flex-1">
                    <h2 class="text-[15px] font-black leading-tight text-white">Biblioteca</h2>
                    <p class="text-[10px] leading-3 text-slate-500">
                        Lo que existe: entidades, cómo se describen y cómo se agrupan.
                    </p>
                </div>

                <span class="shrink-0 text-slate-600 transition group-hover:text-indigo-400">
                    <x-omni-icon name="chevron-derecha" size="h-4 w-4" />
                </span>
            </div>
        </a>

        {{-- Sus caras --}}
        @if ($carasBiblioteca->isNotEmpty())
            <div class="grid grid-cols-10 gap-px bg-slate-800">
                @foreach ($carasBiblioteca as $cara)
                    <a href="{{ route('entities.show', $cara) }}" title="{{ $cara->name }}"
                        class="block aspect-square overflow-hidden bg-slate-950">
                        <img src="{{ $cara->image_url }}" alt="" loading="lazy"
                            class="h-full w-full object-cover opacity-75 transition duration-300 hover:opacity-100 hover:scale-110">
                    </a>
                @endforeach
            </div>
        @endif

        <div class="grid grid-cols-3 gap-px bg-slate-800 sm:grid-cols-5">
            @foreach ([['Entidades', $statistics['entities'], route('entities.index')], ['Tipos', $statistics['entity_types'], route('entity-types.index')], ['Atributos', $statistics['attributes'], route('attributes.index')], ['Catálogo', $statistics['catalog_values'], route('attribute-options.index')], ['Colecciones', $statistics['collections'], route('collections.index')]] as [$etiqueta, $valor, $destino])
                <a href="{{ $destino }}"
                    class="bg-slate-900/60 px-2 py-2 text-center transition hover:bg-slate-800">
                    <span class="block font-mono text-[15px] font-black"
                        style="color: {{ $valor > 0 ? '#818cf8' : '#475569' }}">{{ $valor }}</span>
                    <span class="block truncate text-[8px] font-black uppercase tracking-wider text-slate-600">
                        {{ $etiqueta }}
                    </span>
                </a>
            @endforeach
        </div>
    </article>


    {{-- ===================================================== --}}
    {{-- UNIVERSOS --}}
    {{-- ===================================================== --}}

    <article class="group overflow-hidden rounded-2xl border border-violet-500/25 bg-slate-900/50 transition hover:border-violet-500/50">

        <a href="{{ route('universes.dashboard') }}" class="block">

            <div class="flex items-center gap-3 border-b border-violet-500/15 px-4 py-3">

                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-500/15 text-violet-300">
                    <x-omni-icon name="globo" size="h-5 w-5" />
                </span>

                <div class="min-w-0 flex-1">
                    <h2 class="text-[15px] font-black leading-tight text-white">Universos</h2>
                    <p class="text-[10px] leading-3 text-slate-500">
                        Mundos con su propia gente, su calendario y su clasificación.
                    </p>
                </div>

                @if ($statistics['live'] > 0)
                    <span class="flex shrink-0 items-center gap-1 rounded-lg bg-emerald-500/15 px-2 py-0.5 text-[10px] font-black text-emerald-300">
                        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400"></span>
                        {{ $statistics['live'] }}
                    </span>
                @endif

                <span class="shrink-0 text-slate-600 transition group-hover:text-violet-400">
                    <x-omni-icon name="chevron-derecha" size="h-4 w-4" />
                </span>
            </div>
        </a>

        {{-- Tus mundos, con su portada --}}
        @if ($carasUniversos->isNotEmpty())
            <div class="grid grid-cols-2 gap-1.5 p-2 sm:grid-cols-4">
                @foreach ($carasUniversos as $mundo)
                    <a href="{{ route('universes.show', $mundo) }}"
                        class="group/m overflow-hidden rounded-xl border border-slate-800 bg-slate-950 transition hover:border-violet-500/50">

                        <span class="relative block h-16 overflow-hidden bg-slate-900">
                            @if ($mundo->image_url)
                                <img src="{{ $mundo->image_url }}" alt="" loading="lazy"
                                    class="h-full w-full object-cover transition duration-300 group-hover/m:scale-105">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-slate-800">
                                    <x-omni-icon name="globo" size="h-5 w-5" />
                                </span>
                            @endif

                            @if ($mundo->vivas_count > 0)
                                <span class="absolute right-1 top-1 rounded bg-emerald-500/30 px-1 font-mono text-[9px] font-black text-emerald-100 backdrop-blur">
                                    {{ $mundo->vivas_count }}
                                </span>
                            @endif
                        </span>

                        <span class="block px-1.5 py-1">
                            <span class="block truncate text-[10px] font-black text-slate-300">{{ $mundo->name }}</span>
                            <span class="block font-mono text-[8px] text-slate-600">
                                {{ $mundo->entities_count }} habitantes
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif

        <div class="grid grid-cols-4 gap-px bg-slate-800">
            @foreach ([['Mundos', $statistics['universes'], route('universes.index')], ['Habitantes', $statistics['inhabitants'], route('universes.index')], ['Jugadas', $statistics['competitions'], route('universes.dashboard')], ['En juego', $statistics['live'], route('universes.dashboard')]] as [$etiqueta, $valor, $destino])
                <a href="{{ $destino }}" class="bg-slate-900/60 px-2 py-2 text-center transition hover:bg-slate-800">
                    <span class="block font-mono text-[15px] font-black"
                        style="color: {{ $valor > 0 ? '#a78bfa' : '#475569' }}">{{ $valor }}</span>
                    <span class="block truncate text-[8px] font-black uppercase tracking-wider text-slate-600">
                        {{ $etiqueta }}
                    </span>
                </a>
            @endforeach
        </div>
    </article>


    {{-- ===================================================== --}}
    {{-- TORNEOS --}}
    {{-- ===================================================== --}}

    <article class="group overflow-hidden rounded-2xl border border-amber-500/25 bg-slate-900/50 transition hover:border-amber-500/50">

        <a href="{{ route('tournaments.dashboard') }}" class="block">

            <div class="flex items-center gap-3 border-b border-amber-500/15 px-4 py-3">

                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/15 text-amber-300">
                    <x-omni-icon name="trofeo" size="h-5 w-5" />
                </span>

                <div class="min-w-0 flex-1">
                    <h2 class="text-[15px] font-black leading-tight text-white">Torneos</h2>
                    <p class="text-[10px] leading-3 text-slate-500">
                        La forma de la competición: fases, recorridos y salidas.
                    </p>
                </div>

                <span class="shrink-0 text-slate-600 transition group-hover:text-amber-400">
                    <x-omni-icon name="chevron-derecha" size="h-4 w-4" />
                </span>
            </div>
        </a>

        @if ($carasTorneos->isNotEmpty())
            <div class="grid grid-cols-3 gap-1.5 p-2 sm:grid-cols-6">
                @foreach ($carasTorneos as $plantilla)
                    <a href="{{ route('tournaments.templates.show', $plantilla) }}"
                        title="{{ $plantilla->name }}"
                        class="group/t overflow-hidden rounded-lg border border-slate-800 bg-slate-950 transition hover:border-amber-500/50">

                        <span class="block h-12 overflow-hidden bg-slate-900">
                            @if ($plantilla->image_url)
                                <img src="{{ $plantilla->image_url }}" alt="" loading="lazy"
                                    class="h-full w-full object-cover transition duration-300 group-hover/t:scale-105">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-slate-800">
                                    <x-omni-icon name="trofeo" size="h-4 w-4" />
                                </span>
                            @endif
                        </span>

                        <span class="block truncate px-1 py-1 text-center text-[9px] font-black text-slate-400">
                            {{ $plantilla->name }}
                        </span>
                    </a>
                @endforeach
            </div>
        @endif

        <div class="grid grid-cols-2 gap-px bg-slate-800">
            @foreach ([['Plantillas de torneo', $statistics['tournaments'], route('tournaments.templates.index')], ['Fases diseñadas', $statistics['phases'], route('tournaments.phase-templates.index')]] as [$etiqueta, $valor, $destino])
                <a href="{{ $destino }}" class="bg-slate-900/60 px-2 py-2 text-center transition hover:bg-slate-800">
                    <span class="block font-mono text-[15px] font-black"
                        style="color: {{ $valor > 0 ? '#fbbf24' : '#475569' }}">{{ $valor }}</span>
                    <span class="block truncate text-[8px] font-black uppercase tracking-wider text-slate-600">
                        {{ $etiqueta }}
                    </span>
                </a>
            @endforeach
        </div>
    </article>


    {{-- ===================================================== --}}
    {{-- COMUNIDAD --}}
    {{-- ===================================================== --}}

    <article class="group overflow-hidden rounded-2xl border border-emerald-500/25 bg-slate-900/50 transition hover:border-emerald-500/50">

        <a href="{{ route('community.home') }}" class="block">

            <div class="flex items-center gap-3 border-b border-emerald-500/15 px-4 py-3">

                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-300">
                    <x-omni-icon name="orbita" size="h-5 w-5" />
                </span>

                <div class="min-w-0 flex-1">
                    <h2 class="text-[15px] font-black leading-tight text-white">Comunidad</h2>
                    <p class="text-[10px] leading-3 text-slate-500">
                        Lo que otros comparten y lo que tú dejas ver. Copiar trae una copia
                        independiente.
                    </p>
                </div>

                <span class="shrink-0 text-slate-600 transition group-hover:text-emerald-400">
                    <x-omni-icon name="chevron-derecha" size="h-4 w-4" />
                </span>
            </div>
        </a>

        @if ($carasComunidad->isNotEmpty())
            <div class="flex flex-wrap gap-1.5 p-2">
                @foreach ($carasComunidad as $cara)
                    <a href="{{ route('entities.show', $cara) }}" title="{{ $cara->name }} · pública"
                        class="block h-12 w-12 overflow-hidden rounded-lg border border-emerald-500/30 bg-slate-950">
                        <img src="{{ $cara->image_url }}" alt="" loading="lazy"
                            class="h-full w-full object-cover transition duration-300 hover:scale-110">
                    </a>
                @endforeach
            </div>
        @else
            <p class="px-4 py-3 text-[10px] leading-4 text-slate-600">
                No has hecho pública ninguna entidad todavía. Lo que publiques podrá verse y
                copiarse desde la comunidad, siempre con tu nombre al lado.
            </p>
        @endif

        <div class="grid grid-cols-3 gap-px bg-slate-800">
            @foreach ([['Lo tuyo público', $statistics['public'], 'Entidades, colecciones y atributos'], ['Traído de otros', $statistics['brought'], 'Copias que te has llevado'], ['Te han copiado', $statistics['copied_from_me'], 'Veces que alguien se llevó algo tuyo']] as [$etiqueta, $valor, $ayuda])
                <span class="bg-slate-900/60 px-2 py-2 text-center" title="{{ $ayuda }}">
                    <span class="block font-mono text-[15px] font-black"
                        style="color: {{ $valor > 0 ? '#34d399' : '#475569' }}">{{ $valor }}</span>
                    <span class="block truncate text-[8px] font-black uppercase tracking-wider text-slate-600">
                        {{ $etiqueta }}
                    </span>
                </span>
            @endforeach
        </div>
    </article>
</section>
