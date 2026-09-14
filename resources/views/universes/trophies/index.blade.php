@php
    /*
     * Los trofeos de un universo.
     *
     * Un trofeo es lo que se lleva quien gana. Lo que había: la lista de
     * trofeos y las doce últimas entregas. Correcto, y sin las dos preguntas
     * que una vitrina existe para contestar:
     *
     *   · quién tiene más trofeos de este mundo
     *   · quién ha ganado cada uno
     *
     * Ahora las dos tienen su propia vista, con caras. Y los trofeos que nunca
     * ha ganado nadie se dicen, porque un trofeo sin entregar es una copa
     * cogiendo polvo.
     */

    $tonoNivel = [
        'GOLD' => ['#fbbf24', 'Oro'],
        'SILVER' => ['#cbd5e1', 'Plata'],
        'BRONZE' => ['#f59e0b', 'Bronce'],
        'SPECIAL' => ['#a78bfa', 'Especial'],
    ];

    $hayFiltros = $search || $tier || $scope || $awarded;

    $maximoEntregas = $trophies->max('awards_count') ?: 1;

    $maximoPalmares = $palmares->max(fn($fila) => $fila['entregas']->count()) ?: 1;

    $puedeEditar = auth()->user()?->can('update', $universe) ?? false;
@endphp

<x-universe-layout :universe="$universe" surface="dark">

    <x-slot name="header">Trofeos</x-slot>

    <div x-data="trofeosDelUniverso()" class="space-y-4">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-end gap-4">

            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600">
                    {{ $universe->name }} · Trofeos
                </p>

                <h1 class="mt-1 text-xl font-black tracking-tight text-white">
                    La vitrina de este mundo
                </h1>

                <p class="mt-0.5 max-w-2xl text-[11px] text-slate-500">
                    Lo que se lleva quien gana. Un trofeo se crea aquí y después se engancha a la
                    recompensa de un torneo; al terminar una competición, se entrega solo.
                </p>
            </div>

            @if ($puedeEditar)
                <button type="button" @click="abrirNuevo = ! abrirNuevo"
                    class="flex items-center gap-1.5 rounded-xl bg-amber-500 px-3 py-2 text-[11px] font-black text-slate-950 transition hover:bg-amber-400">
                    <x-omni-icon name="mas" size="h-3.5 w-3.5" />
                    Nuevo trofeo
                </button>
            @endif
        </header>


        @if (session('success'))
            <div class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-2.5 text-[12px] font-bold text-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-2.5 text-[12px] font-bold text-rose-200">
                <ul class="space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>· {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- ===================================================== --}}
        {{-- CIFRAS --}}
        {{-- ===================================================== --}}

        <section class="grid grid-cols-2 gap-2 sm:grid-cols-5">
            @foreach ([['Trofeos', $statistics['trofeos'], null, '#fbbf24'], ['Entregados', $statistics['entregados'], null, '#34d399'], ['Premiados', $statistics['premiados'], null, '#a78bfa'], ['Sin entregar', $statistics['sin_entregar'], ['awarded' => 'no'], '#fb7185'], ['De una edición', $statistics['de_edicion'], ['scope' => 'edition'], '#22d3ee']] as [$etiqueta, $valor, $filtro, $tono])

                @if ($filtro)
                    <a href="{{ route('universes.trophies.index', array_merge(['universe' => $universe], $filtro)) }}"
                        class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2 transition hover:border-slate-700">
                        <span class="block font-mono text-xl font-black"
                            style="color: {{ $valor > 0 ? $tono : '#475569' }}">{{ $valor }}</span>
                        <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">{{ $etiqueta }}</span>
                    </a>
                @else
                    <div class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2">
                        <span class="block font-mono text-xl font-black"
                            style="color: {{ $valor > 0 ? $tono : '#475569' }}">{{ $valor }}</span>
                        <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">{{ $etiqueta }}</span>
                    </div>
                @endif
            @endforeach
        </section>


        @if ($statistics['sin_entregar'] > 0 && $awarded !== 'no')
            <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-rose-500/25 bg-rose-500/5 px-4 py-2.5">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-rose-500/15 text-rose-300">
                    <x-omni-icon name="trofeo" size="h-3.5 w-3.5" />
                </span>

                <p class="min-w-0 flex-1 text-[11px] leading-relaxed text-rose-200/80">
                    <strong class="text-rose-200">{{ $statistics['sin_entregar'] }}</strong>
                    {{ $statistics['sin_entregar'] === 1 ? 'trofeo no lo ha ganado nadie todavía' : 'trofeos no los ha ganado nadie todavía' }}.
                    Crearlo no lo reparte: hay que engancharlo a la recompensa de un torneo.
                </p>

                <a href="{{ route('universes.trophies.index', ['universe' => $universe, 'awarded' => 'no']) }}"
                    class="shrink-0 rounded-xl border border-rose-500/40 px-2.5 py-1.5 text-[10px] font-black text-rose-300 transition hover:bg-rose-500 hover:text-white">
                    Verlos →
                </a>
            </div>
        @endif


        {{-- ===================================================== --}}
        {{-- NUEVO TROFEO --}}
        {{-- ===================================================== --}}

        @if ($puedeEditar)
            @include('universes.trophies.partials.crear', [
                'universe' => $universe,
                'ediciones' => $ediciones,
                'tonoNivel' => $tonoNivel,
            ])
        @endif


        {{-- ===================================================== --}}
        {{-- FILTROS Y FORMA DE MIRAR --}}
        {{-- ===================================================== --}}

        <section class="sticky top-2 z-20 rounded-2xl border border-slate-800 bg-slate-900/95 p-2 backdrop-blur">

            <div class="flex flex-wrap items-center gap-2">

                <form method="GET" action="{{ route('universes.trophies.index', $universe) }}"
                    class="flex min-w-0 flex-1 flex-wrap items-center gap-2">

                    <label class="relative min-w-[150px] flex-1">
                        <span class="sr-only">Buscar trofeo</span>
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                            <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                        </span>
                        <input type="search" name="search" value="{{ $search }}"
                            placeholder="Buscar trofeo…"
                            class="w-full rounded-xl border-slate-800 bg-slate-950 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                    </label>

                    <select name="tier" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        <option value="">Cualquier nivel</option>
                        @foreach ($tonoNivel as $clave => [$tono, $etiqueta])
                            <option value="{{ $clave }}" @selected($tier === $clave)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>

                    <select name="scope" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        @foreach (['' => 'De todo el mundo', 'universe' => 'Del universo', 'edition' => 'De una edición'] as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected($scope === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>

                    <select name="awarded" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        @foreach (['' => 'Entregados o no', 'yes' => 'Ya conquistados', 'no' => 'Sin entregar nunca'] as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected($awarded === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>

                    <select name="sort" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                        @foreach (['name' => 'Por nombre', 'awards' => 'Los más conquistados', 'tier' => 'Por nivel', 'newest' => 'Los más nuevos'] as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected($sort === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>

                    <button type="submit"
                        class="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                        Buscar
                    </button>

                    @if ($hayFiltros)
                        <a href="{{ route('universes.trophies.index', $universe) }}"
                            class="rounded-xl px-2 py-2 text-[10px] font-black text-slate-500 underline transition hover:text-slate-300">
                            Quitar filtros
                        </a>
                    @endif
                </form>


                <span class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-950 p-1">
                    @foreach ([['vitrina', 'trofeo', 'Vitrina: los trofeos en grande'], ['grid', 'cuadricula', 'Cuadrícula: con su palmarés'], ['holders', 'medalla', 'Quién tiene qué: la vitrina de cada competidor'], ['recent', 'historial', 'Lo último conquistado'], ['table', 'controles', 'Tabla: para comparar']] as [$modo, $icono, $ayuda])
                        <button type="button" @click="vista = '{{ $modo }}'" title="{{ $ayuda }}"
                            :aria-pressed="vista === '{{ $modo }}'"
                            :class="vista === '{{ $modo }}' ? 'bg-amber-500 text-slate-950' : 'text-slate-500 hover:text-slate-200'"
                            class="rounded-lg px-2 py-1.5 transition">
                            <x-omni-icon :name="$icono" size="h-4 w-4" />
                        </button>
                    @endforeach
                </span>

                <span x-show="['vitrina', 'grid'].includes(vista)" x-cloak
                    class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-950 p-1">
                    <button type="button" @click="tamano = Math.max(4, tamano - 1)" :disabled="tamano === 4"
                        class="rounded-lg px-2 py-1.5 text-slate-500 transition hover:text-slate-200 disabled:opacity-30">
                        <x-omni-icon name="chevron-izquierda" size="h-3.5 w-3.5" />
                    </button>
                    <span class="w-3 text-center font-mono text-[10px] font-black text-slate-500" x-text="tamano"></span>
                    <button type="button" @click="tamano = Math.min(9, tamano + 1)" :disabled="tamano === 9"
                        class="rounded-lg px-2 py-1.5 text-slate-500 transition hover:text-slate-200 disabled:opacity-30">
                        <x-omni-icon name="chevron-derecha" size="h-3.5 w-3.5" />
                    </button>
                </span>
            </div>
        </section>


        @if ($trophies->isEmpty())

            <section class="rounded-2xl border border-dashed border-slate-800 py-14 text-center">

                <span class="inline-flex text-slate-700"><x-omni-icon name="trofeo" size="h-9 w-9" /></span>

                <p class="mt-2 text-[13px] font-black text-white">
                    {{ $hayFiltros ? 'Ningún trofeo encaja con lo que has filtrado' : 'Esta vitrina está vacía' }}
                </p>

                <p class="mx-auto mt-1 max-w-md text-[11px] leading-relaxed text-slate-500">
                    @if ($hayFiltros)
                        Prueba a quitar algún filtro.
                    @else
                        Un trofeo se crea aquí, se engancha a la recompensa de un torneo, y se entrega solo
                        cuando una competición termina.
                    @endif
                </p>

                @if ($puedeEditar && ! $hayFiltros)
                    <button type="button" @click="abrirNuevo = true"
                        class="mt-3 rounded-xl bg-amber-500 px-4 py-2 text-[11px] font-black text-slate-950 transition hover:bg-amber-400">
                        Crear el primero
                    </button>
                @endif
            </section>

        @else

            {{-- ===================================================== --}}
            {{-- VITRINA --}}
            {{-- ===================================================== --}}

            {{--
                Los trofeos en grande, que es como se mira una vitrina. Los que
                nadie ha ganado salen apagados: no es lo mismo una copa
                conquistada que una recién hecha.
            --}}

            <section x-show="vista === 'vitrina'" class="grid gap-3" :class="columnas">
                @foreach ($trophies as $trofeo)
                    @php
                        [$tono, $nivel] = $tonoNivel[$trofeo->tier] ?? ['#94a3b8', $trofeo->tier];
                        $conquistado = $trofeo->awards_count > 0;
                    @endphp

                    <article class="group overflow-hidden rounded-2xl border bg-slate-900/50 transition duration-300 hover:-translate-y-1"
                        style="border-color: {{ $conquistado ? $tono . '55' : '#1e293b' }}">

                        <div class="relative aspect-square overflow-hidden"
                            style="background: radial-gradient(120% 110% at 50% 10%, {{ $tono }}{{ $conquistado ? '28' : '10' }}, #020617 70%)">

                            @if ($trofeo->image_url)
                                <img src="{{ $trofeo->image_url }}" alt="{{ $trofeo->name }}" loading="lazy"
                                    class="h-full w-full object-contain p-4 transition duration-500 group-hover:scale-110 {{ $conquistado ? '' : 'opacity-40 grayscale' }}">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-6xl transition duration-500 group-hover:scale-110"
                                    style="color: {{ $tono }}{{ $conquistado ? '' : '55' }}">
                                    {{ $trofeo->display_icon }}
                                </span>
                            @endif

                            <span class="absolute left-2 top-2 rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider"
                                style="background-color: {{ $tono }}22; color: {{ $tono }}">
                                {{ $nivel }}
                            </span>

                            @if ($trofeo->tournament_instance_id)
                                <span class="absolute right-2 top-2 rounded bg-slate-950/85 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-cyan-300"
                                    title="Nació dentro de una edición concreta">
                                    de edición
                                </span>
                            @endif

                            @if ($conquistado)
                                <span class="absolute bottom-2 right-2 rounded-lg px-2 py-0.5 font-mono text-[11px] font-black"
                                    style="background-color: {{ $tono }}; color: #020617">
                                    ×{{ $trofeo->awards_count }}
                                </span>
                            @endif
                        </div>

                        <div class="p-2.5">
                            <p class="truncate text-[13px] font-black text-white">{{ $trofeo->name }}</p>

                            <p class="line-clamp-2 text-[10px] leading-relaxed text-slate-500">
                                {{ $trofeo->description ?: 'Sin descripción.' }}
                            </p>

                            @php $suyas = $porTrofeo[$trofeo->id] ?? collect(); @endphp

                            @if ($suyas->isNotEmpty())
                                <div class="mt-2 flex -space-x-2">
                                    @foreach ($suyas->take(6) as $entrega)
                                        <span class="h-7 w-7 shrink-0 overflow-hidden rounded-lg border-2 border-slate-900 bg-slate-950"
                                            title="{{ $entrega->universeEntity?->display_label }}">
                                            @if ($entrega->universeEntity?->image_url)
                                                <img src="{{ $entrega->universeEntity->image_url }}" alt=""
                                                    loading="lazy" class="h-full w-full object-cover">
                                            @else
                                                <span class="flex h-full w-full items-center justify-center text-[9px] text-slate-700">◍</span>
                                            @endif
                                        </span>
                                    @endforeach

                                    @if ($suyas->count() > 6)
                                        <span class="flex h-7 shrink-0 items-center rounded-lg border-2 border-slate-900 bg-slate-950 px-1.5 font-mono text-[9px] font-black"
                                            style="color: {{ $tono }}">
                                            +{{ $suyas->count() - 6 }}
                                        </span>
                                    @endif
                                </div>
                            @else
                                <p class="mt-2 rounded-lg border border-dashed border-slate-800 px-2 py-1 text-center text-[9px] text-slate-600">
                                    Nadie lo ha ganado todavía.
                                </p>
                            @endif
                        </div>

                        @if ($puedeEditar)
                            @include('universes.trophies.partials.acciones', [
                                'universe' => $universe,
                                'trofeo' => $trofeo,
                            ])
                        @endif
                    </article>
                @endforeach
            </section>


            {{-- ===================================================== --}}
            {{-- CUADRÍCULA CON PALMARÉS --}}
            {{-- ===================================================== --}}

            <section x-show="vista === 'grid'" x-cloak class="grid gap-3" :class="columnas">
                @foreach ($trophies as $trofeo)
                    @php
                        [$tono, $nivel] = $tonoNivel[$trofeo->tier] ?? ['#94a3b8', $trofeo->tier];
                        $suyas = $porTrofeo[$trofeo->id] ?? collect();
                        $ancho = max((int) round(($trofeo->awards_count / $maximoEntregas) * 100), 3);
                    @endphp

                    <article class="overflow-hidden rounded-2xl border bg-slate-900/50"
                        style="border-color: {{ $trofeo->awards_count > 0 ? $tono . '40' : '#1e293b' }}">

                        <div class="flex items-center gap-3 border-b border-slate-800 p-3">

                            <span class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-xl border"
                                style="border-color: {{ $tono }}40; background: radial-gradient(120% 110% at 50% 10%, {{ $tono }}22, #020617 70%)">
                                @if ($trofeo->image_url)
                                    <img src="{{ $trofeo->image_url }}" alt="" loading="lazy" class="h-full w-full object-contain p-1">
                                @else
                                    <span class="text-2xl" style="color: {{ $tono }}">{{ $trofeo->display_icon }}</span>
                                @endif
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[13px] font-black text-white">{{ $trofeo->name }}</p>

                                <div class="flex flex-wrap items-center gap-1">
                                    <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider"
                                        style="background-color: {{ $tono }}22; color: {{ $tono }}">{{ $nivel }}</span>

                                    @if ($trofeo->competition)
                                        <span class="truncate rounded border border-slate-800 px-1.5 py-0.5 text-[8px] font-bold text-cyan-300"
                                            title="Solo existe dentro de esta edición">
                                            {{ $trofeo->competition->name }}
                                        </span>
                                    @endif
                                </div>

                                <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-950">
                                    <div class="h-full rounded-full"
                                        style="width: {{ $trofeo->awards_count > 0 ? $ancho : 0 }}%; background-color: {{ $tono }}"></div>
                                </div>
                            </div>

                            <span class="shrink-0 text-right">
                                <span class="block font-mono text-xl font-black"
                                    style="color: {{ $trofeo->awards_count > 0 ? $tono : '#475569' }}">
                                    {{ $trofeo->awards_count }}
                                </span>
                                <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">
                                    {{ $trofeo->awards_count === 1 ? 'entrega' : 'entregas' }}
                                </span>
                            </span>
                        </div>

                        {{-- Su palmarés: quién lo ha ganado, y en qué --}}
                        @if ($suyas->isNotEmpty())
                            <div class="divide-y divide-slate-800/70">
                                @foreach ($suyas->take(5) as $entrega)
                                    <div class="flex items-center gap-2 px-3 py-1.5">

                                        <span class="w-5 shrink-0 text-center font-mono text-[10px] font-black"
                                            style="color: {{ $entrega->position === 1 ? $tono : '#475569' }}">
                                            {{ $entrega->position ? $entrega->position . 'º' : '—' }}
                                        </span>

                                        <span class="h-7 w-7 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                            @if ($entrega->universeEntity?->image_url)
                                                <img src="{{ $entrega->universeEntity->image_url }}" alt=""
                                                    loading="lazy" class="h-full w-full object-cover">
                                            @else
                                                <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                                            @endif
                                        </span>

                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-[11px] font-black text-slate-200">
                                                {{ $entrega->universeEntity?->display_label ?? 'Sin competidor' }}
                                            </span>
                                            <span class="block truncate text-[9px] text-slate-600">
                                                {{ $entrega->tournamentInstance?->name ?? 'sin competición' }}
                                            </span>
                                        </span>

                                        <span class="shrink-0 font-mono text-[9px] text-slate-700">
                                            {{ $entrega->awarded_at?->format('d/m/y') }}
                                        </span>
                                    </div>
                                @endforeach

                                @if ($suyas->count() > 5)
                                    <p class="px-3 py-1.5 text-[9px] text-slate-600">
                                        Y {{ $suyas->count() - 5 }} entregas más.
                                    </p>
                                @endif
                            </div>
                        @else
                            <p class="px-3 py-4 text-center text-[10px] text-slate-600">
                                Nadie lo ha ganado todavía. Engánchalo a la recompensa de un torneo.
                            </p>
                        @endif

                        @if ($puedeEditar)
                            @include('universes.trophies.partials.acciones', [
                                'universe' => $universe,
                                'trofeo' => $trofeo,
                            ])
                        @endif
                    </article>
                @endforeach
            </section>


            {{-- ===================================================== --}}
            {{-- QUIÉN TIENE QUÉ --}}
            {{-- ===================================================== --}}

            <section x-show="vista === 'holders'" x-cloak class="space-y-2.5">

                @if ($palmares->isEmpty())
                    <p class="rounded-2xl border border-dashed border-slate-800 py-10 text-center text-[11px] leading-relaxed text-slate-600">
                        Todavía no se ha entregado ningún trofeo, así que nadie tiene vitrina.
                    </p>
                @else
                    <p class="text-[10px] leading-relaxed text-slate-500">
                        La vitrina de cada competidor. Se cuenta sobre la historia entera del mundo, no
                        sobre lo que haya filtrado arriba.
                    </p>

                    @foreach ($palmares as $posicion => $fila)
                        @php
                            $competidor = $fila['competidor'];
                            $cuantos = $fila['entregas']->count();
                            $ancho = max((int) round(($cuantos / $maximoPalmares) * 100), 8);
                            $esPrimero = $posicion === 0;
                        @endphp

                        <article class="overflow-hidden rounded-2xl border bg-slate-900/50"
                            style="border-color: {{ $esPrimero ? '#fbbf24' : '#1e293b' }}">

                            <div class="flex flex-wrap items-center gap-3 p-3">

                                <span class="w-6 shrink-0 text-center font-mono text-[14px] font-black {{ $esPrimero ? 'text-amber-300' : 'text-slate-700' }}">
                                    {{ $posicion + 1 }}
                                </span>

                                <span class="h-12 w-12 shrink-0 overflow-hidden rounded-xl border bg-slate-950"
                                    style="border-color: {{ $esPrimero ? '#fbbf2455' : '#1e293b' }}">
                                    @if ($competidor?->image_url)
                                        <img src="{{ $competidor->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                                    @endif
                                </span>

                                <div class="min-w-0 flex-1">
                                    @if ($competidor)
                                        <a href="{{ route('universes.entities.show', [$universe, $competidor]) }}"
                                            class="block truncate text-[14px] font-black text-white transition hover:underline">
                                            {{ $fila['nombre'] }}
                                        </a>
                                    @else
                                        <p class="truncate text-[14px] font-black text-slate-400">{{ $fila['nombre'] }}</p>
                                    @endif

                                    <p class="text-[10px] text-slate-600">
                                        {{ $fila['primeros'] }}
                                        {{ $fila['primeros'] === 1 ? 'como primero' : 'como primeros' }}
                                    </p>

                                    <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-950">
                                        <div class="h-full rounded-full"
                                            style="width: {{ $ancho }}%; background-color: {{ $esPrimero ? '#fbbf24' : '#64748b' }}"></div>
                                    </div>
                                </div>

                                <span class="shrink-0 text-right">
                                    <span class="block font-mono text-2xl font-black {{ $esPrimero ? 'text-amber-300' : 'text-slate-400' }}">
                                        {{ $cuantos }}
                                    </span>
                                    <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">
                                        {{ $cuantos === 1 ? 'trofeo' : 'trofeos' }}
                                    </span>
                                </span>
                            </div>

                            <div class="flex flex-wrap gap-1.5 border-t border-slate-800 px-3 py-2">
                                @foreach ($fila['entregas'] as $entrega)
                                    @php
                                        $suTrofeo = $entrega->trophy;
                                        [$tonoT] = $tonoNivel[$suTrofeo?->tier] ?? ['#94a3b8'];
                                    @endphp

                                    <span class="flex items-center gap-1.5 rounded-lg border border-slate-800 bg-slate-950 py-0.5 pl-0.5 pr-2"
                                        title="{{ $entrega->tournamentInstance?->name }}">

                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center overflow-hidden rounded-md border border-slate-800"
                                            style="background-color: {{ $tonoT }}12">
                                            @if ($suTrofeo?->image_url)
                                                <img src="{{ $suTrofeo->image_url }}" alt="" loading="lazy" class="h-full w-full object-contain p-0.5">
                                            @else
                                                <span class="text-[11px]" style="color: {{ $tonoT }}">{{ $suTrofeo?->display_icon ?? '🏆' }}</span>
                                            @endif
                                        </span>

                                        <span class="min-w-0">
                                            <span class="block max-w-[150px] truncate text-[10px] font-black text-slate-300">
                                                {{ $suTrofeo?->name ?? 'Trofeo' }}
                                            </span>
                                            <span class="block text-[8px] text-slate-600">
                                                {{ $entrega->position ? $entrega->position . 'º' : '—' }}
                                                @if ($entrega->season)
                                                    · T{{ $entrega->season->number }}
                                                @endif
                                            </span>
                                        </span>
                                    </span>
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                @endif
            </section>


            {{-- ===================================================== --}}
            {{-- LO ÚLTIMO CONQUISTADO --}}
            {{-- ===================================================== --}}

            <section x-show="vista === 'recent'" x-cloak
                class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <div class="border-b border-slate-800 px-4 py-2.5">
                    <h2 class="text-[13px] font-black text-white">Lo último conquistado</h2>
                    <p class="text-[10px] text-slate-500">Las doce entregas más recientes de este mundo.</p>
                </div>

                @if ($recentAwards->isEmpty())
                    <p class="px-4 py-10 text-center text-[11px] text-slate-600">
                        Todavía no se ha entregado ningún trofeo.
                    </p>
                @else
                    <div class="divide-y divide-slate-800/70">
                        @foreach ($recentAwards as $entrega)
                            @php
                                $suTrofeo = $entrega->trophy;
                                [$tonoT, $nivelT] = $tonoNivel[$suTrofeo?->tier] ?? ['#94a3b8', '—'];
                            @endphp

                            <div class="flex flex-wrap items-center gap-3 px-4 py-2.5">

                                <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl border"
                                    style="border-color: {{ $tonoT }}40; background: radial-gradient(120% 110% at 50% 10%, {{ $tonoT }}22, #020617 70%)">
                                    @if ($suTrofeo?->image_url)
                                        <img src="{{ $suTrofeo->image_url }}" alt="" loading="lazy" class="h-full w-full object-contain p-1">
                                    @else
                                        <span class="text-xl" style="color: {{ $tonoT }}">{{ $suTrofeo?->display_icon ?? '🏆' }}</span>
                                    @endif
                                </span>

                                <span class="shrink-0 text-slate-700">→</span>

                                <span class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                    @if ($entrega->universeEntity?->image_url)
                                        <img src="{{ $entrega->universeEntity->image_url }}" alt=""
                                            loading="lazy" class="h-full w-full object-cover">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                                    @endif
                                </span>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[12px] font-black text-white">
                                        {{ $entrega->universeEntity?->display_label ?? 'Sin competidor' }}
                                        <span class="font-normal text-slate-500">ganó</span>
                                        <span style="color: {{ $tonoT }}">{{ $suTrofeo?->name ?? 'un trofeo' }}</span>
                                    </p>

                                    <p class="truncate text-[10px] text-slate-500">
                                        @if ($entrega->position)
                                            <span class="font-mono" style="color: {{ $tonoT }}">{{ $entrega->position }}º</span>
                                            <span class="text-slate-700">·</span>
                                        @endif
                                        {{ $entrega->tournamentInstance?->name ?? 'sin competición' }}
                                        @if ($entrega->season)
                                            <span class="text-slate-700">·</span>
                                            <span class="font-mono text-violet-400">T{{ $entrega->season->number }}</span>
                                        @endif
                                    </p>
                                </div>

                                <span class="shrink-0 font-mono text-[9px] text-slate-600">
                                    {{ $entrega->awarded_at?->format('d/m/Y') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>


            {{-- ===================================================== --}}
            {{-- TABLA --}}
            {{-- ===================================================== --}}

            <section x-show="vista === 'table'" x-cloak
                class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px]">
                        <thead class="border-b border-slate-800 text-left">
                            <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                <th class="px-4 py-2.5">Trofeo</th>
                                <th class="px-3 py-2.5">Nivel</th>
                                <th class="px-3 py-2.5">Alcance</th>
                                <th class="px-3 py-2.5 text-right">Entregas</th>
                                <th class="px-3 py-2.5">Último en ganarlo</th>
                                <th class="px-3 py-2.5">Cuándo</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-800/70">
                            @foreach ($trophies as $trofeo)
                                @php
                                    [$tono, $nivel] = $tonoNivel[$trofeo->tier] ?? ['#94a3b8', $trofeo->tier];
                                    $suyas = $porTrofeo[$trofeo->id] ?? collect();
                                    $ultima = $suyas->first();
                                @endphp

                                <tr class="transition hover:bg-slate-950/50">
                                    <td class="px-4 py-2">
                                        <span class="flex items-center gap-2">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-lg border"
                                                style="border-color: {{ $tono }}40">
                                                @if ($trofeo->image_url)
                                                    <img src="{{ $trofeo->image_url }}" alt="" loading="lazy" class="h-full w-full object-contain p-0.5">
                                                @else
                                                    <span class="text-[13px]" style="color: {{ $tono }}">{{ $trofeo->display_icon }}</span>
                                                @endif
                                            </span>
                                            <span class="truncate text-[12px] font-black text-white">{{ $trofeo->name }}</span>
                                        </span>
                                    </td>

                                    <td class="px-3 py-2 text-[11px]" style="color: {{ $tono }}">{{ $nivel }}</td>

                                    <td class="px-3 py-2 text-[11px] text-slate-500">
                                        {{ $trofeo->competition?->name ?? 'todo el universo' }}
                                    </td>

                                    <td class="px-3 py-2 text-right font-mono text-[11px]"
                                        style="color: {{ $trofeo->awards_count > 0 ? $tono : '#475569' }}">
                                        {{ $trofeo->awards_count }}
                                    </td>

                                    <td class="px-3 py-2 text-[11px] text-slate-400">
                                        {{ $ultima?->universeEntity?->display_label ?? '—' }}
                                    </td>

                                    <td class="px-3 py-2 font-mono text-[10px] text-slate-500">
                                        {{ $ultima?->awarded_at?->format('d/m/Y') ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

        @endif

    </div>


    <script>
        function trofeosDelUniverso() {

            return {

                vista: 'vitrina',
                tamano: 6,
                abrirNuevo: {{ $errors->any() ? 'true' : 'false' }},

                /* Qué trofeo se está corrigiendo, o null. El editor vive dentro
                   de la propia tarjeta: corregir una copa no debería sacar de
                   la vitrina. */
                editando: null,

                init() {
                    try {
                        const g = JSON.parse(localStorage.getItem('omnimerge.trophies.view') ?? '{}');
                        if (['vitrina', 'grid', 'holders', 'recent', 'table'].includes(g.vista)) {
                            this.vista = g.vista;
                        }
                        if (g.tamano >= 4 && g.tamano <= 9) this.tamano = g.tamano;
                    } catch (e) {}

                    this.$watch('vista', () => this.recordar());
                    this.$watch('tamano', () => this.recordar());
                },

                recordar() {
                    try {
                        localStorage.setItem('omnimerge.trophies.view',
                            JSON.stringify({ vista: this.vista, tamano: this.tamano }));
                    } catch (e) {}
                },

                get columnas() {
                    return {
                        4: 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4',
                        5: 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4',
                        6: 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-5',
                        7: 'grid-cols-2 sm:grid-cols-4 lg:grid-cols-5',
                        8: 'grid-cols-3 sm:grid-cols-4 lg:grid-cols-6',
                        9: 'grid-cols-3 sm:grid-cols-5 lg:grid-cols-7',
                    }[this.tamano];
                },
            };
        }
    </script>

</x-universe-layout>
