@php
    /*
     * La ficha de un juego dentro de un universo.
     *
     * Lo que tenía: las reglas, los últimos enfrentamientos, la configuración y
     * un «quién destaca» de ocho nombres. Bien, y sin lo único que hace que un
     * juego se entienda de verdad:
     *
     *   con qué números lo juega cada competidor
     *
     * Las reglas explican el mecanismo; los números explican el mundo. Quién es
     * fiable, quién es una lotería, y quién sigue con los valores de partida
     * porque nadie le ha tocado nada.
     *
     * El acento del juego viene del motor, así que viaja como color en `style`:
     * Tailwind solo genera las clases que encuentra escritas enteras.
     */

    $tonos = [
        'emerald' => '#34d399',
        'amber' => '#fbbf24',
        'violet' => '#a78bfa',
        'cyan' => '#22d3ee',
        'rose' => '#fb7185',
        'blue' => '#60a5fa',
    ];

    $tono = $tonos[$definition['accent'] ?? 'violet'] ?? '#a78bfa';

    $esDefecto = (bool) ($record?->is_default);

    $claves = collect($definition['stats'] ?? [])->pluck('key')->all();

    /* Con dos estadísticas numéricas se puede dibujar el rango de cada uno. */
    $esRango = count($claves) === 2
        && in_array('min_value', $claves, true)
        && in_array('max_value', $claves, true);

    $totalCompetidores = $competidores->count();
    $conPropias = $competidores->where('propias', true)->count();
    $jugadosTotal = $competidores->sum('jugados');
@endphp

<x-universe-layout :universe="$universe" surface="dark">

    <x-slot name="header">{{ $definition['name'] }}</x-slot>

    <div x-data="fichaDeJuego()" class="space-y-4">

        {{-- ===================================================== --}}
        {{-- QUIÉN ES --}}
        {{-- ===================================================== --}}

        <a href="{{ route('universes.games.index', $universe) }}"
            class="inline-block text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-violet-400">
            ← Juegos
        </a>

        <section class="overflow-hidden rounded-2xl border bg-slate-900/50"
            style="border-color: {{ $tono }}40">

            <div class="flex flex-wrap items-start gap-4 p-4"
                style="background: radial-gradient(110% 160% at 0% 0%, {{ $tono }}18, transparent 60%)">

                <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl border text-3xl"
                    style="border-color: {{ $tono }}55; background-color: {{ $tono }}18">
                    {{ $definition['icon'] ?? '◈' }}
                </span>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="truncate text-2xl font-black tracking-tight text-white">
                            {{ $definition['name'] }}
                        </h1>

                        @if ($esDefecto)
                            <span class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider"
                                style="background-color: {{ $tono }}; color: #020617">
                                por defecto
                            </span>
                        @endif
                    </div>

                    <p class="mt-0.5 max-w-2xl text-[12px] leading-relaxed text-slate-400">
                        {{ $definition['description'] ?? $definition['tagline'] }}
                    </p>

                    <div class="mt-2 flex flex-wrap items-center gap-1">
                        <span class="rounded border border-slate-800 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider"
                            style="color: {{ $tono }}">{{ $definition['type_label'] ?? 'Motor' }}</span>

                        <span class="rounded border border-slate-800 px-1.5 py-0.5 text-[9px] font-bold text-slate-500">
                            {{ $definition['interaction_label'] ?? 'Enfrentamiento' }}
                        </span>

                        @if ($definition['allows_draws'] ?? false)
                            <span class="rounded border border-slate-800 px-1.5 py-0.5 text-[9px] font-bold text-slate-500">
                                admite empate
                            </span>
                        @endif

                        @if ($definition['tracks_points'] ?? false)
                            <span class="rounded border border-slate-800 px-1.5 py-0.5 text-[9px] font-bold text-slate-500">
                                {{ $definition['points_label'] ?? 'Puntúa' }}
                            </span>
                        @endif

                        <span class="rounded border border-slate-800 px-1.5 py-0.5 font-mono text-[9px] text-slate-600">
                            {{ $definition['key'] }}
                        </span>
                    </div>
                </div>
            </div>


            {{-- Cómo funciona, dibujado --}}
            <div class="border-y border-slate-800 bg-slate-950/40 p-4">
                <p class="mb-2 text-[9px] font-black uppercase tracking-wider text-slate-600">Cómo funciona</p>

                <div class="mx-auto max-w-2xl">
                    @include('universes.games.partials.diagrama', [
                        'definicion' => $definition,
                        'tono' => $tono,
                    ])
                </div>
            </div>


            <div class="grid grid-cols-2 gap-px bg-slate-800 sm:grid-cols-4">
                @foreach ([['Competidores', $totalCompetidores, 'Los que hay en este universo'], ['Con números propios', $conPropias, 'Los demás juegan con los valores de partida'], ['Enfrentamientos', $jugadosTotal, 'Veces que este motor ha resuelto algo aquí'], ['Estado', $record?->is_enabled ? 'Activo' : 'Apagado', 'Si se puede elegir al crear una competición']] as [$etiqueta, $valor, $ayuda])
                    <div class="bg-slate-900/50 px-3 py-2.5" title="{{ $ayuda }}">
                        <p class="font-mono text-lg font-black {{ is_numeric($valor) && $valor == 0 ? 'text-slate-600' : '' }}"
                            style="{{ is_numeric($valor) && $valor > 0 ? 'color: ' . $tono : '' }}{{ ! is_numeric($valor) ? ($record?->is_enabled ? 'color:#34d399' : 'color:#64748b') : '' }}">
                            {{ $valor }}
                        </p>
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">{{ $etiqueta }}</p>
                    </div>
                @endforeach
            </div>
        </section>


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
        {{-- CON QUÉ NÚMEROS JUEGA CADA UNO --}}
        {{-- ===================================================== --}}

        {{--
            La sección que faltaba. Las reglas explican el mecanismo; esto
            explica el mundo. Cinco formas de mirarlo, y la especial —los
            rangos, todos a la misma escala— es la que de verdad contesta
            «¿quién da miedo aquí?».
        --}}

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-2.5">

                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                    style="background-color: {{ $tono }}22; color: {{ $tono }}">
                    <x-omni-icon name="espadas" size="h-4 w-4" />
                </span>

                <div class="min-w-0 flex-1">
                    <h2 class="text-[13px] font-black text-white">Con qué números juega cada uno</h2>
                    <p class="text-[10px] leading-relaxed text-slate-500">
                        Los {{ $totalCompetidores }} competidores del universo, con sus valores en este
                        juego.
                        @if ($conPropias < $totalCompetidores)
                            <strong class="text-amber-300">{{ $totalCompetidores - $conPropias }}</strong>
                            {{ $totalCompetidores - $conPropias === 1 ? 'sigue' : 'siguen' }} con los de
                            partida.
                        @endif
                    </p>
                </div>

                <label class="flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-800 bg-slate-950 px-2.5 py-1.5">
                    <input type="checkbox" x-model="soloJugados"
                        class="rounded border-slate-700 bg-slate-900 text-violet-500 focus:ring-violet-500">
                    <span class="text-[10px] font-black text-slate-400">Solo los que han jugado</span>
                </label>

                <span class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-950 p-1">
                    @foreach ([['ranges', 'barras', 'Rangos: todos a la misma escala'], ['gallery', 'galeria', 'Galería: solo las caras'], ['grid', 'cuadricula', 'Cuadrícula: con sus números'], ['list', 'menu', 'Lista: una línea cada uno'], ['table', 'controles', 'Tabla: para comparar']] as [$modo, $icono, $ayuda])
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


            @if ($competidores->isEmpty())

                <div class="p-10 text-center">
                    <span class="inline-flex text-slate-700"><x-omni-icon name="usuario" size="h-9 w-9" /></span>
                    <p class="mt-2 text-[13px] font-black text-white">Este universo no tiene competidores</p>
                    <p class="mx-auto mt-1 max-w-md text-[11px] leading-relaxed text-slate-500">
                        Un juego sin nadie que lo juegue es una regla escrita. Trae entidades de tu
                        biblioteca y aparecerán aquí con sus números.
                    </p>
                    <a href="{{ route('universes.entities.index', $universe) }}"
                        class="mt-3 inline-block rounded-xl px-4 py-2 text-[11px] font-black"
                        style="background-color: {{ $tono }}22; color: {{ $tono }}">
                        Traer competidores →
                    </a>
                </div>

            @else

                {{-- ---------- RANGOS ---------- --}}

                <div x-show="vista === 'ranges'" class="space-y-1.5 p-4">

                    @if ($esRango)
                        <p class="mb-1 text-[10px] leading-relaxed text-slate-500">
                            Cada barra es lo que puede sacar ese competidor, todas a la misma escala hasta
                            <strong class="text-slate-400">{{ rtrim(rtrim(number_format($techo, 1, ',', ''), '0'), ',') }}</strong>.
                            Una barra corta y a la derecha es un competidor fiable; una larga, una lotería.
                        </p>
                    @endif

                    @foreach ($competidores as $fila)
                        @php
                            $entidad = $fila['entidad'];
                            $min = (float) ($fila['stats']['min_value'] ?? 0);
                            $max = (float) ($fila['stats']['max_value'] ?? 0);
                            $izquierda = (int) round(($min / $techo) * 100);
                            $ancho = max((int) round((($max - $min) / $techo) * 100), 2);
                        @endphp

                        <div x-show="! soloJugados || {{ $fila['jugados'] }} > 0"
                            class="flex items-center gap-2.5 rounded-xl border border-slate-800 bg-slate-950 p-2">

                            <a href="{{ route('universes.entities.show', [$universe, $entidad]) }}"
                                class="h-9 w-9 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-900">
                                @if ($entidad->image_url)
                                    <img src="{{ $entidad->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                                @endif
                            </a>

                            <a href="{{ route('universes.entities.show', [$universe, $entidad]) }}"
                                class="w-28 shrink-0 truncate text-[11px] font-black text-white">
                                {{ $entidad->display_label }}
                            </a>

                            @if ($esRango)
                                <span class="relative h-2.5 min-w-0 flex-1 overflow-hidden rounded-full bg-slate-900">
                                    <span class="absolute inset-y-0 rounded-full"
                                        style="left: {{ min($izquierda, 98) }}%; width: {{ $ancho }}%; background-color: {{ $fila['propias'] ? $tono : '#475569' }}"></span>
                                </span>

                                <span class="w-16 shrink-0 text-right font-mono text-[10px] font-black"
                                    style="color: {{ $fila['propias'] ? $tono : '#64748b' }}">
                                    {{ rtrim(rtrim(number_format($min, 1, ',', ''), '0'), ',') }}–{{ rtrim(rtrim(number_format($max, 1, ',', ''), '0'), ',') }}
                                </span>
                            @else
                                <span class="min-w-0 flex-1 truncate font-mono text-[10px] text-slate-500">
                                    @foreach ($fila['stats'] as $clave => $valor)
                                        {{ $clave }}={{ rtrim(rtrim(number_format((float) $valor, 1, ',', ''), '0'), ',') }}@if (! $loop->last) · @endif
                                    @endforeach
                                </span>
                            @endif

                            @if (! $fila['propias'])
                                <span class="shrink-0 rounded border border-slate-800 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-slate-600"
                                    title="Nadie le ha tocado los números: juega con los de partida">
                                    de partida
                                </span>
                            @endif

                            <span class="w-14 shrink-0 text-right font-mono text-[10px] {{ $fila['jugados'] > 0 ? 'text-slate-400' : 'text-slate-700' }}"
                                title="Ganados de jugados">
                                {{ $fila['ganados'] }}/{{ $fila['jugados'] }}
                            </span>
                        </div>
                    @endforeach
                </div>


                {{-- ---------- GALERÍA ---------- --}}

                <div x-show="vista === 'gallery'" x-cloak class="grid gap-2 p-4" :class="columnas">
                    @foreach ($competidores as $fila)
                        @php $entidad = $fila['entidad']; @endphp

                        <a href="{{ route('universes.entities.show', [$universe, $entidad]) }}"
                            title="{{ $entidad->display_label }}"
                            x-show="! soloJugados || {{ $fila['jugados'] }} > 0"
                            class="group overflow-hidden rounded-xl border bg-slate-950 transition hover:-translate-y-0.5"
                            style="border-color: {{ $fila['propias'] ? $tono . '40' : '#1e293b' }}">

                            <span class="block aspect-square overflow-hidden bg-slate-900">
                                @if ($entidad->image_url)
                                    <img src="{{ $entidad->image_url }}" alt="" loading="lazy"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-xl text-slate-800">◍</span>
                                @endif
                            </span>

                            <span class="block truncate px-1.5 pt-1 text-center text-[10px] font-black text-slate-300">
                                {{ $entidad->display_label }}
                            </span>

                            <span class="block truncate px-1.5 pb-1 text-center font-mono text-[9px]"
                                style="color: {{ $fila['propias'] ? $tono : '#475569' }}">
                                @if ($esRango)
                                    {{ rtrim(rtrim(number_format((float) ($fila['stats']['min_value'] ?? 0), 1, ',', ''), '0'), ',') }}–{{ rtrim(rtrim(number_format((float) ($fila['stats']['max_value'] ?? 0), 1, ',', ''), '0'), ',') }}
                                @else
                                    {{ $fila['ganados'] }}/{{ $fila['jugados'] }}
                                @endif
                            </span>
                        </a>
                    @endforeach
                </div>


                {{-- ---------- CUADRÍCULA ---------- --}}

                <div x-show="vista === 'grid'" x-cloak class="grid gap-2.5 p-4" :class="columnas">
                    @foreach ($competidores as $fila)
                        @php
                            $entidad = $fila['entidad'];
                            $ratio = $fila['jugados'] > 0
                                ? (int) round(($fila['ganados'] / $fila['jugados']) * 100)
                                : 0;
                        @endphp

                        <article x-show="! soloJugados || {{ $fila['jugados'] }} > 0"
                            class="group overflow-hidden rounded-xl border bg-slate-950 transition hover:-translate-y-0.5"
                            style="border-color: {{ $fila['propias'] ? $tono . '40' : '#1e293b' }}">

                            <a href="{{ route('universes.entities.show', [$universe, $entidad]) }}"
                                class="relative block aspect-square overflow-hidden bg-slate-900">
                                @if ($entidad->image_url)
                                    <img src="{{ $entidad->image_url }}" alt="" loading="lazy"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-2xl text-slate-800">◍</span>
                                @endif

                                @if (! $fila['propias'])
                                    <span class="absolute right-1 top-1 rounded bg-slate-950/85 px-1 text-[8px] font-black uppercase tracking-wider text-slate-500">
                                        de partida
                                    </span>
                                @endif
                            </a>

                            <div class="p-1.5">
                                <a href="{{ route('universes.entities.show', [$universe, $entidad]) }}"
                                    class="block truncate text-[11px] font-black text-white">
                                    {{ $entidad->display_label }}
                                </a>

                                @foreach ($fila['stats'] as $clave => $valor)
                                    <p class="flex items-center justify-between font-mono text-[9px]">
                                        <span class="truncate text-slate-600">{{ $clave }}</span>
                                        <span style="color: {{ $tono }}">
                                            {{ rtrim(rtrim(number_format((float) $valor, 1, ',', ''), '0'), ',') }}
                                        </span>
                                    </p>
                                @endforeach

                                @if ($fila['jugados'] > 0)
                                    <div class="mt-1 h-1 overflow-hidden rounded-full bg-slate-900" title="{{ $ratio }}% ganados">
                                        <div class="h-full rounded-full" style="width: {{ $ratio }}%; background-color: {{ $tono }}"></div>
                                    </div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>


                {{-- ---------- LISTA ---------- --}}

                <div x-show="vista === 'list'" x-cloak class="divide-y divide-slate-800/70">
                    @foreach ($competidores as $fila)
                        @php $entidad = $fila['entidad']; @endphp

                        <div x-show="! soloJugados || {{ $fila['jugados'] }} > 0"
                            class="flex items-center gap-3 px-4 py-2 transition hover:bg-slate-950/50">

                            <a href="{{ route('universes.entities.show', [$universe, $entidad]) }}"
                                class="h-9 w-9 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                @if ($entidad->image_url)
                                    <img src="{{ $entidad->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                                @endif
                            </a>

                            <a href="{{ route('universes.entities.show', [$universe, $entidad]) }}"
                                class="min-w-0 flex-1 truncate text-[12px] font-black text-white">
                                {{ $entidad->display_label }}
                            </a>

                            @foreach ($fila['stats'] as $clave => $valor)
                                <span class="hidden shrink-0 rounded-lg border border-slate-800 px-2 py-0.5 font-mono text-[10px] sm:block"
                                    style="color: {{ $fila['propias'] ? $tono : '#64748b' }}" title="{{ $clave }}">
                                    {{ rtrim(rtrim(number_format((float) $valor, 1, ',', ''), '0'), ',') }}
                                </span>
                            @endforeach

                            <span class="w-14 shrink-0 text-right font-mono text-[10px] {{ $fila['jugados'] > 0 ? 'text-slate-400' : 'text-slate-700' }}">
                                {{ $fila['ganados'] }}/{{ $fila['jugados'] }}
                            </span>
                        </div>
                    @endforeach
                </div>


                {{-- ---------- TABLA ---------- --}}

                <div x-show="vista === 'table'" x-cloak class="overflow-x-auto">
                    <table class="w-full min-w-[620px]">
                        <thead class="border-b border-slate-800 text-left">
                            <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                <th class="px-4 py-2.5">Competidor</th>
                                @foreach ($definition['stats'] ?? [] as $esquema)
                                    <th class="px-3 py-2.5 text-right">{{ $esquema['label'] }}</th>
                                @endforeach
                                <th class="px-3 py-2.5 text-right">Jugados</th>
                                <th class="px-3 py-2.5 text-right">Ganados</th>
                                <th class="px-3 py-2.5 text-right">%</th>
                                <th class="px-3 py-2.5">Números</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-800/70">
                            @foreach ($competidores as $fila)
                                @php
                                    $entidad = $fila['entidad'];
                                    $ratio = $fila['jugados'] > 0
                                        ? (int) round(($fila['ganados'] / $fila['jugados']) * 100)
                                        : null;
                                @endphp

                                <tr x-show="! soloJugados || {{ $fila['jugados'] }} > 0"
                                    class="transition hover:bg-slate-950/50">

                                    <td class="px-4 py-2">
                                        <a href="{{ route('universes.entities.show', [$universe, $entidad]) }}"
                                            class="flex items-center gap-2">
                                            <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                                @if ($entidad->image_url)
                                                    <img src="{{ $entidad->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                                @else
                                                    <span class="flex h-full w-full items-center justify-center text-[11px] text-slate-700">◍</span>
                                                @endif
                                            </span>
                                            <span class="truncate text-[12px] font-black text-white">{{ $entidad->display_label }}</span>
                                        </a>
                                    </td>

                                    @foreach ($definition['stats'] ?? [] as $esquema)
                                        <td class="px-3 py-2 text-right font-mono text-[11px]"
                                            style="color: {{ $fila['propias'] ? $tono : '#64748b' }}">
                                            {{ rtrim(rtrim(number_format((float) ($fila['stats'][$esquema['key']] ?? 0), 1, ',', ''), '0'), ',') }}
                                        </td>
                                    @endforeach

                                    <td class="px-3 py-2 text-right font-mono text-[11px] {{ $fila['jugados'] > 0 ? 'text-slate-300' : 'text-slate-700' }}">
                                        {{ $fila['jugados'] }}
                                    </td>

                                    <td class="px-3 py-2 text-right font-mono text-[11px] {{ $fila['ganados'] > 0 ? 'text-emerald-300' : 'text-slate-700' }}">
                                        {{ $fila['ganados'] }}
                                    </td>

                                    <td class="px-3 py-2 text-right font-mono text-[11px] text-slate-400">
                                        {{ $ratio === null ? '—' : $ratio . '%' }}
                                    </td>

                                    <td class="px-3 py-2 text-[10px] {{ $fila['propias'] ? 'text-slate-400' : 'text-slate-600' }}">
                                        {{ $fila['propias'] ? 'propios' : 'de partida' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @endif
        </section>


        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_360px]">

            <div class="space-y-4">

                {{-- ===================================================== --}}
                {{-- QUIÉN DESTACA --}}
                {{-- ===================================================== --}}

                <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                    <div class="flex items-center gap-3 border-b border-slate-800 px-4 py-2.5">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 text-amber-300">
                            <x-omni-icon name="medalla" size="h-4 w-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <h2 class="text-[13px] font-black text-white">Quién destaca</h2>
                            <p class="text-[10px] text-slate-500">Se deriva de lo jugado, no de sus números.</p>
                        </div>
                    </div>

                    @if ($leaders->isEmpty())
                        <p class="px-4 py-8 text-center text-[11px] leading-relaxed text-slate-600">
                            Nadie ha jugado todavía a {{ $definition['name'] }} en este universo.
                        </p>
                    @else
                        <div class="space-y-1.5 p-3">
                            @foreach ($leaders as $indice => $lider)
                                @php
                                    $ratio = $lider->encounters_played > 0
                                        ? (int) round(($lider->encounters_won / $lider->encounters_played) * 100)
                                        : 0;
                                @endphp

                                <a href="{{ route('universes.entities.show', [$universe, $lider]) }}"
                                    class="flex items-center gap-2.5 rounded-xl border border-slate-800 bg-slate-950 p-2 transition hover:border-amber-500/40">

                                    <span class="w-5 shrink-0 text-center font-mono text-[11px] font-black {{ $indice === 0 ? 'text-amber-300' : 'text-slate-700' }}">
                                        {{ $indice + 1 }}
                                    </span>

                                    <span class="h-9 w-9 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-900">
                                        @if ($lider->image_url)
                                            <img src="{{ $lider->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                        @else
                                            <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                                        @endif
                                    </span>

                                    <span class="w-28 shrink-0 truncate text-[11px] font-black text-white">
                                        {{ $lider->display_label }}
                                    </span>

                                    <span class="h-1.5 min-w-0 flex-1 overflow-hidden rounded-full bg-slate-900">
                                        <span class="block h-full rounded-full bg-amber-400" style="width: {{ max($ratio, 2) }}%"></span>
                                    </span>

                                    <span class="shrink-0 font-mono text-[10px] font-black text-amber-300">
                                        {{ $lider->encounters_won }}/{{ $lider->encounters_played }}
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </section>


                {{-- ===================================================== --}}
                {{-- ÚLTIMOS ENFRENTAMIENTOS --}}
                {{-- ===================================================== --}}

                <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                    <div class="flex items-center gap-3 border-b border-slate-800 px-4 py-2.5">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-cyan-500/15 text-cyan-300">
                            <x-omni-icon name="historial" size="h-4 w-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <h2 class="text-[13px] font-black text-white">Últimos enfrentamientos</h2>
                            <p class="text-[10px] text-slate-500">Lo que ha resuelto este motor, con lo que sacó cada uno.</p>
                        </div>
                    </div>

                    @if ($recentEncounters->isEmpty())
                        <p class="px-4 py-8 text-center text-[11px] leading-relaxed text-slate-600">
                            Todavía no se ha jugado nada con este motor aquí.
                        </p>
                    @else
                        <div class="divide-y divide-slate-800/70">
                            @foreach ($recentEncounters as $enfrentamiento)
                                <div class="px-4 py-2.5">

                                    <div class="mb-1.5 flex flex-wrap items-center gap-1.5 text-[9px]">
                                        @if ($enfrentamiento->tournamentInstance)
                                            <a href="{{ route('universes.competitions.show', [$universe, $enfrentamiento->tournamentInstance]) }}"
                                                class="rounded border border-slate-800 px-1.5 py-0.5 font-black text-cyan-300 transition hover:underline">
                                                {{ $enfrentamiento->tournamentInstance->name }}
                                            </a>
                                        @else
                                            <span class="rounded border border-slate-800 px-1.5 py-0.5 font-black text-slate-600">
                                                simulador
                                            </span>
                                        @endif

                                        @if ($enfrentamiento->phase_name)
                                            <span class="text-slate-600">{{ $enfrentamiento->phase_name }}</span>
                                        @endif

                                        <span class="ml-auto font-mono text-slate-700">
                                            {{ $enfrentamiento->created_at?->diffForHumans(null, true) }}
                                        </span>
                                    </div>

                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($enfrentamiento->participants as $participante)
                                            <span class="flex items-center gap-1.5 rounded-lg border py-0.5 pl-0.5 pr-2 {{ $participante->is_winner ? 'bg-emerald-500/10' : 'bg-slate-950' }}"
                                                style="border-color: {{ $participante->is_winner ? '#34d39955' : '#1e293b' }}">

                                                <span class="h-6 w-6 shrink-0 overflow-hidden rounded-md border border-slate-800 bg-slate-900">
                                                    @if ($participante->universeEntity?->image_url)
                                                        <img src="{{ $participante->universeEntity->image_url }}" alt=""
                                                            loading="lazy" class="h-full w-full object-cover">
                                                    @else
                                                        <span class="flex h-full w-full items-center justify-center text-[9px] text-slate-600">
                                                            {{ $participante->is_winner ? '✓' : '·' }}
                                                        </span>
                                                    @endif
                                                </span>

                                                <span class="max-w-[130px] truncate text-[10px] font-black {{ $participante->is_winner ? 'text-emerald-200' : 'text-slate-400' }}">
                                                    {{ $participante->universeEntity?->display_label ?? $participante->name ?? 'Participante' }}
                                                </span>

                                                @if ($participante->display_value !== null || $participante->value !== null)
                                                    <span class="shrink-0 font-mono text-[10px] font-black"
                                                        style="color: {{ $participante->is_winner ? '#34d399' : '#64748b' }}">
                                                        {{ $participante->display_value
                                                            ?? rtrim(rtrim(number_format((float) $participante->value, 2, ',', ''), '0'), ',') }}
                                                    </span>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>


                {{-- ===================================================== --}}
                {{-- LAS REGLAS --}}
                {{-- ===================================================== --}}

                @if (! empty($definition['rules']))
                    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                        <div class="border-b border-slate-800 px-4 py-2.5">
                            <h2 class="text-[13px] font-black text-white">Las reglas, paso a paso</h2>
                        </div>

                        <ol class="space-y-1.5 p-4">
                            @foreach ($definition['rules'] as $indice => $regla)
                                <li class="flex gap-2">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded font-mono text-[10px] font-black"
                                        style="background-color: {{ $tono }}22; color: {{ $tono }}">
                                        {{ $indice + 1 }}
                                    </span>
                                    <span class="text-[11px] leading-relaxed text-slate-400">{{ $regla }}</span>
                                </li>
                            @endforeach
                        </ol>

                        <div class="space-y-2 border-t border-slate-800 p-4">
                            @if ($definition['win_condition'] ?? null)
                                <p class="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-[11px] leading-relaxed text-slate-400">
                                    <strong style="color: {{ $tono }}">Gana:</strong>
                                    {{ $definition['win_condition'] }}
                                </p>
                            @endif

                            @if ($definition['tiebreak'] ?? null)
                                <p class="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-[11px] leading-relaxed text-slate-400">
                                    <strong class="text-slate-200">Si empatan:</strong>
                                    {{ $definition['tiebreak'] }}
                                </p>
                            @endif
                        </div>
                    </section>
                @endif
            </div>


            {{-- ---------- LA CONFIGURACIÓN ---------- --}}

            <aside class="lg:sticky lg:top-2 lg:self-start">

                <form method="POST"
                    action="{{ route('universes.games.configuration', [$universe, $definition['key']]) }}"
                    class="overflow-hidden rounded-2xl border bg-slate-900/50"
                    style="border-color: {{ $tono }}40">
                    @csrf
                    @method('PUT')

                    <div class="border-b border-slate-800 px-4 py-2.5">
                        <h2 class="text-[13px] font-black text-white">Con qué entra un competidor nuevo</h2>
                        <p class="mt-0.5 text-[10px] leading-relaxed text-slate-500">
                            El motor dice qué estadísticas existen; este universo decide con qué valores se
                            empieza y hasta dónde se puede llegar. Un mundo de recién llegados puede
                            repartir 0–3; uno de veteranos, 5–20.
                        </p>
                    </div>

                    <div class="space-y-3 p-4">
                        @foreach ($configuration->stats() as $stat)
                            <div class="rounded-xl border border-slate-800 bg-slate-950 p-3">

                                <div class="flex items-center gap-1.5">
                                    <span class="min-w-0 flex-1 truncate text-[11px] font-black text-slate-200">
                                        {{ $stat['label'] }}
                                    </span>

                                    @if ($stat['is_customised'])
                                        <span class="shrink-0 rounded px-1 text-[8px] font-black uppercase tracking-wider"
                                            style="background-color: {{ $tono }}22; color: {{ $tono }}"
                                            title="Este universo lo ha ajustado; no es el valor que trae el motor">
                                            a medida
                                        </span>
                                    @endif
                                </div>

                                @if ($stat['help'])
                                    <p class="mt-0.5 text-[9px] leading-3 text-slate-600">{{ $stat['help'] }}</p>
                                @endif

                                <div class="mt-2 grid grid-cols-3 gap-1.5">
                                    @foreach ([['default', 'De partida'], ['min', 'Mínimo'], ['max', 'Máximo']] as [$campo, $etiqueta])
                                        <label class="block">
                                            <span class="mb-0.5 block text-[8px] font-black uppercase tracking-wider text-slate-600">
                                                {{ $etiqueta }}
                                            </span>
                                            <input type="number" step="{{ $stat['step'] }}"
                                                name="stats[{{ $stat['key'] }}][{{ $campo }}]"
                                                value="{{ $stat[$campo] }}"
                                                class="w-full rounded-lg border-slate-800 bg-slate-900 px-2 py-1 text-center font-mono text-[11px] text-slate-200 focus:border-violet-500 focus:ring-violet-500">
                                        </label>
                                    @endforeach
                                </div>

                                <p class="mt-1.5 font-mono text-[8px] text-slate-700">
                                    el motor admite de {{ rtrim(rtrim(number_format($stat['engine_min'], 1, ',', ''), '0'), ',') }}
                                    a {{ rtrim(rtrim(number_format($stat['engine_max'], 1, ',', ''), '0'), ',') }}
                                </p>
                            </div>
                        @endforeach
                    </div>

                    @if ($conEstadisticas > 0)
                        <label class="mx-4 mb-3 flex cursor-pointer items-start gap-2 rounded-xl border border-amber-500/30 bg-amber-500/5 p-3">
                            <input type="checkbox" name="apply_to_existing" value="1"
                                class="mt-0.5 rounded border-amber-500/40 bg-slate-900 text-amber-500 focus:ring-amber-500">
                            <span class="min-w-0">
                                <span class="block text-[10px] font-black text-amber-200">
                                    Reajustar también a los {{ $conEstadisticas }} que ya están
                                </span>
                                <span class="mt-0.5 block text-[9px] leading-3 text-amber-200/60">
                                    Sin marcar, esto solo afecta a quien entre a partir de ahora. Marcado,
                                    <strong class="text-amber-200">se les reescriben las estadísticas</strong>
                                    y se pierde lo que hayan ganado.
                                </span>
                            </span>
                        </label>
                    @endif

                    <div class="border-t border-slate-800 p-3">
                        <button type="submit"
                            class="w-full rounded-xl px-4 py-2.5 text-[12px] font-black text-slate-950 transition"
                            style="background-color: {{ $tono }}">
                            Guardar configuración
                        </button>
                    </div>
                </form>

            </aside>
        </div>

    </div>


    <script>
        function fichaDeJuego() {

            return {

                vista: 'ranges',
                tamano: 6,
                soloJugados: false,

                init() {
                    try {
                        const g = JSON.parse(localStorage.getItem('omnimerge.gameShow.view') ?? '{}');
                        if (['ranges', 'gallery', 'grid', 'list', 'table'].includes(g.vista)) this.vista = g.vista;
                        if (g.tamano >= 4 && g.tamano <= 9) this.tamano = g.tamano;
                    } catch (e) {}

                    this.$watch('vista', () => this.recordar());
                    this.$watch('tamano', () => this.recordar());
                },

                recordar() {
                    try {
                        localStorage.setItem('omnimerge.gameShow.view',
                            JSON.stringify({ vista: this.vista, tamano: this.tamano }));
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
            };
        }
    </script>

</x-universe-layout>
