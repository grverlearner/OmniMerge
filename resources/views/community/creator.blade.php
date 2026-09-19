@php
    /*
     * El perfil de biblioteca de un creador.
     *
     * Antes: seis entidades, seis colecciones, seis atributos y se acabó. Un
     * escaparate sin fondo, y sin manera de explorar de verdad lo que alguien ha
     * hecho.
     *
     * Ahora es su biblioteca entera, con sus pestañas, sus filtros y sus formas
     * de mirar, y con las cuatro cosas que un perfil debería contestar y no
     * contestaba:
     *
     *   · cuánto le han copiado          → si su trabajo le sirve a alguien más
     *   · qué le has copiado tú          → para no copiar dos veces lo mismo
     *   · de quién se inspira él         → la atribución también va hacia atrás
     *   · de qué está hecha su biblioteca → reparto por tipo, no un número
     *
     * Solo biblioteca. Sus plantillas de torneo viven en otra comunidad y aquí
     * hay un enlace, porque mezclar las dos convierte el perfil en un cajón.
     */

    $nombre = $user->username ?? $user->name;

    $pestanas = [
        'overview' => ['Resumen', 'panel', null],
        'entities' => ['Entidades', 'chispa', $statistics['entities']],
        'collections' => ['Colecciones', 'capas', $statistics['collections']],
        'attributes' => ['Atributos', 'controles', $statistics['attributes']],
        'catalogs' => ['Catálogos', 'cuadricula', $statistics['catalogs']],
    ];

    $comunes = array_filter([
        'search' => $search,
        'sort' => $sort !== 'newest' ? $sort : null,
        'image' => $image,
        'per_page' => $perPage !== 24 ? $perPage : null,
    ]);

    $hayFiltros = $search || $image || $entityTypeId || $dataType || $attributeId;

    $totalPublico = $statistics['entities'] + $statistics['collections'] + $statistics['attributes'];

    $modos = [
        'entities' => [
            ['gallery', 'galeria', 'Galería: solo las caras'],
            ['grid', 'cuadricula', 'Cuadrícula: la ficha completa'],
            ['list', 'menu', 'Lista: una línea cada una'],
            ['table', 'controles', 'Tabla: para comparar'],
            ['special', 'usuario', 'Por creador: de quién viene cada una'],
        ],
        'collections' => [
            ['gallery', 'galeria', 'Galería: solo las portadas'],
            ['grid', 'cuadricula', 'Cuadrícula: la ficha completa'],
            ['list', 'menu', 'Lista: una línea cada una'],
            ['table', 'controles', 'Tabla: para comparar'],
            ['special', 'capas', 'Contenido: las caras de lo que hay dentro'],
        ],
        'attributes' => [
            ['gallery', 'galeria', 'Galería: solo las caras'],
            ['grid', 'cuadricula', 'Cuadrícula: la ficha completa'],
            ['list', 'menu', 'Lista: una línea cada uno'],
            ['table', 'controles', 'Tabla: para comparar'],
            ['special', 'capas', 'Valores: qué trae dentro cada catálogo'],
        ],
        'catalogs' => [
            ['gallery', 'galeria', 'Galería: solo las caras'],
            ['grid', 'cuadricula', 'Cuadrícula: la ficha completa'],
            ['list', 'menu', 'Lista: una línea cada uno'],
            ['table', 'controles', 'Tabla: para comparar'],
            ['special', 'grafo', 'Por catálogo: agrupados por a cuál pertenecen'],
        ],
    ];

    $maximoTipo = $repartoPorTipo->max('total') ?: 1;
    $maximoCopias = $loMasCopiado->max('clones_count') ?: 1;
@endphp

<x-community-layout :title="$user->name" surface="dark">

    <x-slot name="header">Comunidad</x-slot>

    @include('community.partials.perfil-pestanas', ['persona' => $user, 'activa' => 'biblioteca'])

    <div x-data="perfilDeCreador({ pestana: @js($tab) })" class="space-y-4">

        {{-- ===================================================== --}}
        {{-- QUIÉN ES --}}
        {{-- ===================================================== --}}

        <a href="{{ route('community.index') }}"
            class="inline-block text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-violet-400">
            ← Comunidad
        </a>

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            {{--
                El mosaico de sus caras como portada. No es un adorno: es lo
                primero que dice qué clase de biblioteca es esta, y a la vez la
                forma más rápida de entrar en cualquiera de ellas.
            --}}

            @if ($mosaico->isNotEmpty())
                <div class="relative">
                    <div class="grid grid-cols-6 gap-px bg-slate-800 sm:grid-cols-9 lg:grid-cols-[repeat(18,minmax(0,1fr))]">
                        @foreach ($mosaico as $pieza)
                            <a href="{{ route('community.entities.show', $pieza) }}" title="{{ $pieza->name }}"
                                class="group relative block aspect-square overflow-hidden bg-slate-950">
                                <img src="{{ $pieza->public_image_url ?: $pieza->image_url }}" alt=""
                                    loading="lazy"
                                    class="h-full w-full object-cover opacity-60 transition duration-500 group-hover:scale-110 group-hover:opacity-100">
                            </a>
                        @endforeach
                    </div>

                    <span class="pointer-events-none absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-slate-900 to-transparent"></span>
                </div>
            @endif

            <div class="flex flex-wrap items-end gap-4 p-4 {{ $mosaico->isNotEmpty() ? '-mt-8 relative' : '' }}">

                <span class="h-16 w-16 shrink-0 overflow-hidden rounded-2xl border-2 border-slate-800 bg-slate-950 shadow-xl">
                    @if ($user->avatar_url)
                        <img src="{{ $user->avatar_url }}" alt="" class="h-full w-full object-cover">
                    @else
                        <span class="flex h-full w-full items-center justify-center text-2xl font-black text-slate-500">
                            {{ mb_strtoupper(mb_substr($nombre, 0, 1)) }}
                        </span>
                    @endif
                </span>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="truncate text-2xl font-black tracking-tight text-white">{{ $user->name }}</h1>
                        <x-creator-badge :user="$user" class="mt-1" />

                        @if ($isOwner)
                            <span class="rounded bg-violet-500/15 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider text-violet-300">
                                eres tú
                            </span>
                        @endif

                        @if (! $user->isPublicProfile())
                            <span class="rounded bg-amber-500/15 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider text-amber-300"
                                title="Solo tú puedes ver esto: tu perfil está en privado">
                                perfil privado
                            </span>
                        @endif
                    </div>

                    <p class="text-[12px] font-bold text-violet-400">{{ '@' . $nombre }}</p>

                    @if ($user->bio ?? null)
                        <p class="mt-1 max-w-2xl text-[11px] leading-relaxed text-slate-400">{{ $user->bio }}</p>
                    @endif
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-1.5">
                    {{--
                        Esta pantalla es su biblioteca. Quien quiera saber quien
                        es -las dos mitades a la vez- tiene el perfil entero.
                    --}}
                    <a href="{{ route('profiles.show', $user->username) }}"
                        class="rounded-xl border border-violet-500/40 bg-violet-500/10 px-3 py-2 text-[11px] font-black text-violet-300 transition hover:bg-violet-500 hover:text-white">
                        Su perfil completo
                    </a>

                    <a href="{{ route('tournaments.community.creator', $user) }}"
                        class="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-[11px] font-black text-slate-400 transition hover:border-amber-500 hover:text-amber-300"
                        title="Sus plantillas de torneo están en la otra comunidad">
                        Sus torneos →
                    </a>

                    @if ($isOwner)
                        <a href="{{ route('entities.index') }}"
                            class="rounded-xl bg-violet-500 px-3 py-2 text-[11px] font-black text-white transition hover:bg-violet-400">
                            Editar mi biblioteca
                        </a>
                    @endif
                </div>
            </div>


            {{-- Las cifras que dicen algo --}}
            <div class="grid grid-cols-2 gap-px border-t border-slate-800 bg-slate-800 sm:grid-cols-3 lg:grid-cols-6">

                @foreach (['entities' => ['Entidades', '#a78bfa'], 'collections' => ['Colecciones', '#22d3ee'], 'attributes' => ['Atributos', '#34d399'], 'catalogs' => ['Catálogos', '#fbbf24']] as $clave => [$etiqueta, $tono])
                    <a href="{{ route('community.creators.show', array_merge(['user' => $user->username], $comunes, ['tab' => $clave])) }}"
                        class="bg-slate-900/50 px-3 py-2.5 transition hover:bg-slate-950">
                        <span class="block font-mono text-xl font-black"
                            style="color: {{ $statistics[$clave] > 0 ? $tono : '#475569' }}">
                            {{ $statistics[$clave] }}
                        </span>
                        <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">{{ $etiqueta }}</span>
                    </a>
                @endforeach

                <div class="bg-slate-900/50 px-3 py-2.5"
                    title="Veces que alguien se ha llevado algo suyo a su biblioteca">
                    <span class="block font-mono text-xl font-black {{ $vecesCopiado > 0 ? 'text-fuchsia-300' : 'text-slate-600' }}">
                        ↺{{ $vecesCopiado }}
                    </span>
                    <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">Le han copiado</span>
                </div>

                <div class="bg-slate-900/50 px-3 py-2.5"
                    title="{{ $isOwner ? 'Es tu propia biblioteca' : 'Cosas suyas que ya están en tu biblioteca' }}">
                    <span class="block font-mono text-xl font-black {{ $loQueTengoSuyo > 0 ? 'text-emerald-300' : 'text-slate-600' }}">
                        {{ $isOwner ? '—' : $loQueTengoSuyo }}
                    </span>
                    <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">
                        {{ $isOwner ? 'Es tuya' : 'Ya tienes suyo' }}
                    </span>
                </div>
            </div>
        </section>


        @if (session('success'))
            <div class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-2.5 text-[12px] font-bold text-emerald-200">
                {{ session('success') }}
            </div>
        @endif


        {{-- ===================================================== --}}
        {{-- PESTAÑAS --}}
        {{-- ===================================================== --}}

        <nav class="flex flex-wrap items-center gap-1.5">
            @foreach ($pestanas as $clave => [$etiqueta, $icono, $cuantos])
                <a href="{{ route('community.creators.show', array_merge(['user' => $user->username], $comunes, ['tab' => $clave])) }}"
                    class="flex items-center gap-1.5 rounded-xl border px-3 py-2 text-[11px] font-black transition {{ $tab === $clave ? 'border-violet-500 bg-violet-500/15 text-white' : 'border-slate-800 bg-slate-900/50 text-slate-400 hover:border-slate-700 hover:text-slate-200' }}">
                    <x-omni-icon :name="$icono" size="h-3.5 w-3.5" />
                    {{ $etiqueta }}
                    @if ($cuantos !== null)
                        <span class="font-mono text-[10px] {{ $tab === $clave ? 'text-violet-300' : 'text-slate-600' }}">
                            {{ $cuantos }}
                        </span>
                    @endif
                </a>
            @endforeach
        </nav>


        @if ($tab === 'overview')

            {{-- ================================================= --}}
            {{-- RESUMEN --}}
            {{-- ================================================= --}}

            @if ($totalPublico === 0)

                <section class="rounded-2xl border border-dashed border-slate-800 py-14 text-center">
                    <span class="inline-flex text-slate-700"><x-omni-icon name="libro" size="h-9 w-9" /></span>

                    <p class="mt-2 text-[13px] font-black text-white">
                        {{ $isOwner ? 'Todavía no has publicado nada' : 'Todavía no ha publicado nada' }}
                    </p>

                    <p class="mx-auto mt-1 max-w-md text-[11px] leading-relaxed text-slate-500">
                        @if ($isOwner)
                            Tu biblioteca puede estar llena y aun así no verse aquí: solo aparece lo que
                            marcas como público en cada ficha.
                        @else
                            Su biblioteca puede estar llena y no verse: aquí solo sale lo que ha marcado
                            como público.
                        @endif
                    </p>
                </section>

            @else

                <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">

                    <div class="space-y-4">

                        {{-- Lo más copiado --}}
                        @if ($loMasCopiado->isNotEmpty())
                            <section class="overflow-hidden rounded-2xl border border-fuchsia-500/25 bg-fuchsia-500/5">

                                <div class="flex items-center gap-3 border-b border-fuchsia-500/20 px-4 py-2.5">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-fuchsia-500/15 text-fuchsia-300">
                                        <x-omni-icon name="barras" size="h-4 w-4" />
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <h2 class="text-[13px] font-black text-white">Lo que más le han copiado</h2>
                                        <p class="text-[10px] leading-relaxed text-fuchsia-200/60">
                                            De todo lo que ha publicado, esto es lo que se ha llevado la gente.
                                        </p>
                                    </div>
                                </div>

                                <div class="space-y-1.5 p-3">
                                    @foreach ($loMasCopiado as $pieza)
                                        <a href="{{ route('community.entities.show', $pieza) }}"
                                            class="flex items-center gap-2.5 rounded-xl border border-slate-800 bg-slate-950 p-2 transition hover:border-fuchsia-500/40">

                                            <span class="h-9 w-9 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-900">
                                                @if ($pieza->public_image_url ?: $pieza->image_url)
                                                    <img src="{{ $pieza->public_image_url ?: $pieza->image_url }}" alt=""
                                                        loading="lazy" class="h-full w-full object-cover">
                                                @else
                                                    <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                                                @endif
                                            </span>

                                            <span class="w-32 shrink-0 truncate text-[11px] font-black text-white">{{ $pieza->name }}</span>

                                            <span class="h-1.5 min-w-0 flex-1 overflow-hidden rounded-full bg-slate-900">
                                                <span class="block h-full rounded-full bg-fuchsia-400"
                                                    style="width: {{ max((int) round(($pieza->clones_count / $maximoCopias) * 100), 3) }}%"></span>
                                            </span>

                                            <span class="shrink-0 font-mono text-[11px] font-black text-fuchsia-300">
                                                ↺{{ $pieza->clones_count }}
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                            </section>
                        @endif


                        {{-- Sus entidades, en pequeño --}}
                        @if ($entities && $entities->isNotEmpty())
                            <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                                <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-2.5">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                                        <x-omni-icon name="chispa" size="h-4 w-4" />
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <h2 class="text-[13px] font-black text-white">Sus entidades</h2>
                                        <p class="text-[10px] text-slate-500">Lo último que ha publicado.</p>
                                    </div>
                                    <a href="{{ route('community.creators.show', ['user' => $user->username, 'tab' => 'entities']) }}"
                                        class="shrink-0 rounded-xl border border-slate-800 px-2.5 py-2 text-[10px] font-black text-slate-400 transition hover:border-violet-500 hover:text-violet-300">
                                        Ver las {{ $statistics['entities'] }} →
                                    </a>
                                </div>

                                <div class="grid grid-cols-3 gap-2 p-3 sm:grid-cols-6">
                                    @foreach ($entities as $entidad)
                                        @php $cara = $entidad->public_image_url ?: $entidad->image_url; @endphp

                                        <a href="{{ route('community.entities.show', $entidad) }}" title="{{ $entidad->name }}"
                                            class="group relative overflow-hidden rounded-xl border border-slate-800 bg-slate-950 transition hover:-translate-y-0.5">
                                            <span class="block aspect-square overflow-hidden bg-slate-900">
                                                @if ($cara)
                                                    <img src="{{ $cara }}" alt="" loading="lazy"
                                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                                @else
                                                    <span class="flex h-full w-full items-center justify-center text-xl text-slate-800">◍</span>
                                                @endif
                                            </span>

                                            @if ($entidad->clones->isNotEmpty())
                                                <span class="absolute right-1 top-1 rounded bg-emerald-500 px-1 text-[8px] font-black text-emerald-950">✓</span>
                                            @endif

                                            <span class="block truncate px-1 py-1 text-center text-[9px] font-black text-slate-400">
                                                {{ $entidad->name }}
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                            </section>
                        @endif


                        {{-- Sus colecciones --}}
                        @if ($collections && $collections->isNotEmpty())
                            <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                                <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-2.5">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-cyan-500/15 text-cyan-300">
                                        <x-omni-icon name="capas" size="h-4 w-4" />
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <h2 class="text-[13px] font-black text-white">Sus colecciones</h2>
                                        <p class="text-[10px] text-slate-500">Copiarlas se lleva lo que tienen dentro.</p>
                                    </div>
                                    <a href="{{ route('community.creators.show', ['user' => $user->username, 'tab' => 'collections']) }}"
                                        class="shrink-0 rounded-xl border border-slate-800 px-2.5 py-2 text-[10px] font-black text-slate-400 transition hover:border-cyan-500 hover:text-cyan-300">
                                        Ver las {{ $statistics['collections'] }} →
                                    </a>
                                </div>

                                <div class="grid gap-2.5 p-3 sm:grid-cols-2">
                                    @foreach ($collections as $coleccion)
                                        @include('community.partials.tarjeta-coleccion', ['coleccion' => $coleccion])
                                    @endforeach
                                </div>
                            </section>
                        @endif


                        {{-- Sus atributos --}}
                        @if ($attributes && $attributes->isNotEmpty())
                            <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                                <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-2.5">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-300">
                                        <x-omni-icon name="controles" size="h-4 w-4" />
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <h2 class="text-[13px] font-black text-white">Sus atributos</h2>
                                        <p class="text-[10px] text-slate-500">Con sus catálogos, si los tienen.</p>
                                    </div>
                                    <a href="{{ route('community.creators.show', ['user' => $user->username, 'tab' => 'attributes']) }}"
                                        class="shrink-0 rounded-xl border border-slate-800 px-2.5 py-2 text-[10px] font-black text-slate-400 transition hover:border-emerald-500 hover:text-emerald-300">
                                        Ver los {{ $statistics['attributes'] }} →
                                    </a>
                                </div>

                                <div class="grid gap-2.5 p-3 sm:grid-cols-2">
                                    @foreach ($attributes as $atributo)
                                        @include('community.partials.tarjeta-atributo', ['atributo' => $atributo])
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    </div>


                    {{-- ---------- LA COLUMNA DE AL LADO ---------- --}}

                    <aside class="space-y-3">

                        {{-- De qué está hecha --}}
                        @if ($repartoPorTipo->isNotEmpty())
                            <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-3.5">
                                <h2 class="text-[10px] font-black uppercase tracking-wider text-slate-600">
                                    De qué está hecha
                                </h2>

                                <div class="mt-2 space-y-1.5">
                                    @foreach ($repartoPorTipo as $fila)
                                        <a href="{{ route('community.creators.show', ['user' => $user->username, 'tab' => 'entities', 'entity_type' => $fila->entity_type_id]) }}"
                                            class="block">
                                            <div class="flex items-center justify-between text-[10px]">
                                                <span class="truncate font-bold text-slate-300">
                                                    {{ $fila->tipo?->name ?? 'Sin tipo' }}
                                                </span>
                                                <span class="font-mono font-black text-violet-300">{{ $fila->total }}</span>
                                            </div>
                                            <div class="mt-0.5 h-1.5 overflow-hidden rounded-full bg-slate-950">
                                                <div class="h-full rounded-full bg-violet-500"
                                                    style="width: {{ max((int) round(($fila->total / $maximoTipo) * 100), 3) }}%"></div>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </section>
                        @endif


                        {{-- Sus catálogos --}}
                        @if ($suscatalogos->isNotEmpty())
                            <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">
                                <div class="border-b border-slate-800 px-3.5 py-2">
                                    <h2 class="text-[10px] font-black uppercase tracking-wider text-slate-600">
                                        Sus catálogos
                                    </h2>
                                </div>

                                <div class="divide-y divide-slate-800/70">
                                    @foreach ($suscatalogos as $catalogo)
                                        @php $tono = $catalogo->color ?: '#6366f1'; @endphp

                                        <a href="{{ route('community.creators.show', ['user' => $user->username, 'tab' => 'catalogs', 'attribute' => $catalogo->id]) }}"
                                            class="flex items-center gap-2 px-3 py-1.5 transition hover:bg-slate-950/50">

                                            @include('attributes.partials.cara', [
                                                'cosa' => $catalogo,
                                                'tamano' => 'h-7 w-7',
                                                'respaldo' => '◫',
                                                'tono' => $tono,
                                            ])

                                            <span class="min-w-0 flex-1 truncate text-[11px] font-black text-slate-300">
                                                {{ $catalogo->name }}
                                            </span>

                                            <span class="shrink-0 font-mono text-[10px] font-black"
                                                style="color: {{ $catalogo->options_count > 0 ? $tono : '#f43f5e' }}">
                                                {{ $catalogo->options_count }}
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                            </section>
                        @endif


                        {{-- De quién se inspira --}}
                        @if ($inspiraciones->isNotEmpty())
                            <section class="rounded-2xl border border-amber-500/25 bg-amber-500/5 p-3.5">
                                <h2 class="text-[10px] font-black uppercase tracking-wider text-amber-300/70">
                                    De quién se inspira
                                </h2>

                                <p class="mt-1 text-[10px] leading-relaxed text-amber-200/60">
                                    Parte de su biblioteca sale de la de otros. La atribución también va
                                    hacia atrás.
                                </p>

                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach ($inspiraciones as $fuente)
                                        <a href="{{ route('community.creators.show', $fuente->username) }}"
                                            class="flex items-center gap-1.5 rounded-lg border border-slate-800 bg-slate-950 py-0.5 pl-0.5 pr-2 transition hover:border-amber-500">
                                            <span class="h-6 w-6 shrink-0 overflow-hidden rounded-full border border-slate-800 bg-slate-900">
                                                @if ($fuente->avatar_url)
                                                    <img src="{{ $fuente->avatar_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                                @else
                                                    <span class="flex h-full w-full items-center justify-center text-[9px] font-black text-slate-500">
                                                        {{ mb_strtoupper(mb_substr($fuente->username ?? $fuente->name, 0, 1)) }}
                                                    </span>
                                                @endif
                                            </span>
                                            <span class="truncate text-[10px] font-black text-slate-300">
                                                {{ '@' . $fuente->username }}
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                            </section>
                        @endif


                        {{-- Lo último --}}
                        @if ($actividad->isNotEmpty())
                            <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">
                                <div class="border-b border-slate-800 px-3.5 py-2">
                                    <h2 class="text-[10px] font-black uppercase tracking-wider text-slate-600">
                                        Lo último que publicó
                                    </h2>
                                </div>

                                <div class="divide-y divide-slate-800/70">
                                    @foreach ($actividad as $fila)
                                        @php
                                            $cosa = $fila['cosa'];

                                            [$ruta, $tono] = match ($fila['clase']) {
                                                'entidad' => [route('community.entities.show', $cosa), '#a78bfa'],
                                                'coleccion' => [route('community.collections.show', $cosa), '#22d3ee'],
                                                default => [route('community.attributes.show', $cosa), '#34d399'],
                                            };
                                        @endphp

                                        <a href="{{ $ruta }}" class="flex items-center gap-2 px-3 py-1.5 transition hover:bg-slate-950/50">

                                            <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background-color: {{ $tono }}"></span>

                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-[11px] font-black text-slate-300">{{ $cosa->name }}</span>
                                                <span class="block text-[9px] text-slate-600">{{ $fila['clase'] }}</span>
                                            </span>

                                            <span class="shrink-0 font-mono text-[9px] text-slate-600">
                                                {{ $fila['fecha']->diffForHumans(null, true) }}
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                            </section>
                        @endif

                    </aside>
                </div>

            @endif

        @else

            {{-- ================================================= --}}
            {{-- UNA CLASE, A FONDO --}}
            {{-- ================================================= --}}

            <section class="sticky top-2 z-20 rounded-2xl border border-slate-800 bg-slate-900/95 p-2 backdrop-blur">

                <div class="flex flex-wrap items-center gap-2">

                    <form method="GET" action="{{ route('community.creators.show', $user->username) }}"
                        class="flex min-w-0 flex-1 flex-wrap items-center gap-2">

                        <input type="hidden" name="tab" value="{{ $tab }}">

                        <label class="relative min-w-[150px] flex-1">
                            <span class="sr-only">Buscar</span>
                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                                <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                            </span>
                            <input type="search" name="search" value="{{ $search }}"
                                placeholder="Buscar en su biblioteca…"
                                class="w-full rounded-xl border-slate-800 bg-slate-950 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                        </label>

                        <select name="sort" onchange="this.form.submit()"
                            class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                            @foreach (['newest' => 'Lo más nuevo', 'oldest' => 'Lo más antiguo', 'popular' => 'Lo más copiado', 'name_asc' => 'Nombre (A–Z)', 'name_desc' => 'Nombre (Z–A)'] as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected($sort === $valor)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>

                        @if (in_array($tab, ['entities', 'catalogs'], true))
                            <select name="image" onchange="this.form.submit()"
                                class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                                @foreach (['' => 'Con o sin imagen', 'yes' => 'Solo con imagen', 'no' => 'Sin imagen'] as $valor => $etiqueta)
                                    <option value="{{ $valor }}" @selected($image === $valor)>{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                        @endif

                        @if ($tab === 'entities' && $entityTypes->isNotEmpty())
                            <select name="entity_type" onchange="this.form.submit()"
                                class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                                <option value="">Cualquier tipo</option>
                                @foreach ($entityTypes as $tipo)
                                    <option value="{{ $tipo->id }}" @selected($entityTypeId === $tipo->id)>{{ $tipo->name }}</option>
                                @endforeach
                            </select>
                        @endif

                        @if ($tab === 'attributes')
                            <select name="data_type" onchange="this.form.submit()"
                                class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                                @foreach (['' => 'Cualquier tipo', 'OPTION' => 'Catálogo', 'TEXT' => 'Texto', 'LONG_TEXT' => 'Texto largo', 'INTEGER' => 'Número entero', 'DECIMAL' => 'Número decimal', 'BOOLEAN' => 'Sí o no', 'DATE' => 'Fecha', 'COLOR' => 'Color'] as $valor => $etiqueta)
                                    <option value="{{ $valor }}" @selected($dataType === $valor)>{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                        @endif

                        @if ($tab === 'catalogs' && $suscatalogos->isNotEmpty())
                            <select name="attribute" onchange="this.form.submit()"
                                class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                                <option value="">Cualquier catálogo</option>
                                @foreach ($suscatalogos as $catalogo)
                                    <option value="{{ $catalogo->id }}" @selected($attributeId === $catalogo->id)>
                                        {{ $catalogo->name }}
                                    </option>
                                @endforeach
                            </select>
                        @endif

                        <select name="per_page" onchange="this.form.submit()"
                            class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                            @foreach ([12, 24, 48, 96] as $cuantos)
                                <option value="{{ $cuantos }}" @selected($perPage === $cuantos)>{{ $cuantos }}</option>
                            @endforeach
                        </select>

                        <button type="submit"
                            class="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                            Buscar
                        </button>

                        @if ($hayFiltros)
                            <a href="{{ route('community.creators.show', ['user' => $user->username, 'tab' => $tab]) }}"
                                class="rounded-xl px-2 py-2 text-[10px] font-black text-slate-500 underline transition hover:text-slate-300">
                                Quitar filtros
                            </a>
                        @endif
                    </form>


                    <span class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-950 p-1">
                        @foreach ($modos[$tab] as [$modo, $icono, $ayuda])
                            <button type="button" @click="vista = '{{ $modo }}'" title="{{ $ayuda }}"
                                :aria-pressed="vista === '{{ $modo }}'"
                                :class="vista === '{{ $modo }}' ? 'bg-violet-500 text-white' : 'text-slate-500 hover:text-slate-200'"
                                class="rounded-lg px-2 py-1.5 transition">
                                <x-omni-icon :name="$icono" size="h-4 w-4" />
                            </button>
                        @endforeach
                    </span>

                    <span x-show="['gallery', 'grid'].includes(vista)" x-cloak
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


            @php
                $listado = match ($tab) {
                    'entities' => $entities,
                    'collections' => $collections,
                    'attributes' => $attributes,
                    'catalogs' => $catalogs,
                };

                $partial = match ($tab) {
                    'entities' => 'community.partials.resultados-entidades',
                    'collections' => 'community.partials.resultados-colecciones',
                    'attributes' => 'community.partials.resultados-atributos',
                    'catalogs' => 'community.partials.resultados-catalogos',
                };
            @endphp

            @if ($listado->isEmpty())

                <section class="rounded-2xl border border-dashed border-slate-800 py-14 text-center">
                    <span class="inline-flex text-slate-700"><x-omni-icon name="brujula" size="h-9 w-9" /></span>

                    <p class="mt-2 text-[13px] font-black text-white">
                        {{ $hayFiltros ? 'Nada encaja con lo que has filtrado' : 'No tiene ' . mb_strtolower($pestanas[$tab][0]) . ' públicas' }}
                    </p>

                    <p class="mx-auto mt-1 max-w-md text-[11px] leading-relaxed text-slate-500">
                        {{ $hayFiltros ? 'Prueba a quitar algún filtro.' : 'Puede tenerlas y no haberlas marcado como públicas.' }}
                    </p>

                    @if ($hayFiltros)
                        <a href="{{ route('community.creators.show', ['user' => $user->username, 'tab' => $tab]) }}"
                            class="mt-3 inline-block rounded-xl border border-slate-800 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                            Quitar filtros
                        </a>
                    @endif
                </section>

            @else

                <p class="px-1 text-[10px] font-black uppercase tracking-wider text-slate-600">
                    {{ $listado->total() }} {{ mb_strtolower($pestanas[$tab][0]) }} de {{ '@' . $nombre }}
                </p>

                @include($partial, ['items' => $listado])

                <div>{{ $listado->links() }}</div>

            @endif

        @endif

    </div>


    <script>
        function perfilDeCreador(config) {

            return {

                pestana: config.pestana ?? 'overview',

                vista: 'grid',
                tamano: 6,

                /*
                 * En el perfil no se copia en lote —eso vive en el explorador—,
                 * pero las piezas compartidas de resultados esperan encontrar
                 * esta lista, así que existe vacía.
                 */
                seleccionadas: [],

                init() {
                    try {
                        const guardado = JSON.parse(
                            localStorage.getItem('omnimerge.creator.view') ?? '{}'
                        );

                        const mio = guardado[this.pestana] ?? {};

                        if (['gallery', 'grid', 'list', 'table', 'special'].includes(mio.vista)) {
                            this.vista = mio.vista;
                        }

                        if (mio.tamano >= 4 && mio.tamano <= 9) {
                            this.tamano = mio.tamano;
                        }
                    } catch (e) {}

                    this.$watch('vista', () => this.recordar());
                    this.$watch('tamano', () => this.recordar());
                },

                recordar() {
                    try {
                        const guardado = JSON.parse(
                            localStorage.getItem('omnimerge.creator.view') ?? '{}'
                        );

                        guardado[this.pestana] = {
                            vista: this.vista,
                            tamano: this.tamano,
                        };

                        localStorage.setItem(
                            'omnimerge.creator.view',
                            JSON.stringify(guardado)
                        );
                    } catch (e) {}
                },

                get columnas() {
                    return {
                        4: 'grid-cols-2 sm:grid-cols-4',
                        5: 'grid-cols-2 sm:grid-cols-5',
                        6: 'grid-cols-3 sm:grid-cols-4 lg:grid-cols-6',
                        7: 'grid-cols-3 sm:grid-cols-5 lg:grid-cols-7',
                        8: 'grid-cols-4 sm:grid-cols-6 lg:grid-cols-8',
                        9: 'grid-cols-4 sm:grid-cols-6 lg:grid-cols-9',
                    }[this.tamano];
                },

                get columnasAnchas() {
                    return {
                        4: 'sm:grid-cols-2',
                        5: 'sm:grid-cols-2 lg:grid-cols-3',
                        6: 'sm:grid-cols-2 lg:grid-cols-3',
                        7: 'sm:grid-cols-3 lg:grid-cols-4',
                        8: 'sm:grid-cols-3 lg:grid-cols-4',
                        9: 'sm:grid-cols-4 lg:grid-cols-5',
                    }[this.tamano];
                },
            };
        }
    </script>

</x-community-layout>
