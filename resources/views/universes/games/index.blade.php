@php
    /*
     * El catálogo de juegos del Universo.
     *
     * El torneo decide quiénes se enfrentan. El juego decide quién gana. Esta
     * pantalla existe para entender lo segundo antes de elegirlo.
     *
     * Lo que tenía: dos tarjetas claras con el texto de cada motor. Correcto y
     * mudo sobre lo único que hace falta para decidir:
     *
     *   · cómo funciona de verdad   → un párrafo, no un dibujo
     *   · con qué valores se juega  → la configuración vivía solo en su ficha
     *   · si se usa                 → un número de enfrentamientos sin contexto
     *
     * Los juegos viven en código (GameRegistry), así que no tienen imagen que
     * subir: su «foto» es el diagrama que se dibuja desde su propia definición.
     * Uno nuevo aparece aquí sin tocar esta plantilla.
     *
     * Tailwind solo genera las clases que encuentra escritas enteras, así que
     * el acento de cada juego viaja como color en `style`, no como clase
     * compuesta.
     */

    $tonos = [
        'emerald' => '#34d399',
        'amber' => '#fbbf24',
        'violet' => '#a78bfa',
        'cyan' => '#22d3ee',
        'rose' => '#fb7185',
        'blue' => '#60a5fa',
    ];

    $porDefecto = $games->firstWhere(fn($j) => $j['record']->is_default);

    $totalPartidas = $games->sum('encounters');

    $activos = $games->filter(fn($j) => $j['record']->is_enabled)->count();
@endphp

<x-universe-layout :universe="$universe" surface="dark">

    <x-slot name="header">Juegos</x-slot>

    <div class="space-y-4">

        {{-- ===================================================== --}}
        {{-- QUÉ ES ESTO --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-end gap-4">

            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600">
                    {{ $universe->name }} · Juegos
                </p>

                <h1 class="mt-1 text-xl font-black tracking-tight text-white">
                    Cómo se decide una batalla
                </h1>

                <p class="mt-0.5 max-w-2xl text-[11px] text-slate-500">
                    El torneo decide <strong class="text-slate-400">quiénes</strong> se enfrentan. El juego
                    decide <strong class="text-slate-400">quién gana</strong>. Cada competición de este
                    universo puede usar uno distinto.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2 text-right">
                    <p class="font-mono text-lg font-black {{ $totalPartidas > 0 ? 'text-violet-300' : 'text-slate-600' }}">
                        {{ $totalPartidas }}
                    </p>
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                        enfrentamientos
                    </p>
                </div>

                <div class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2 text-right">
                    <p class="font-mono text-lg font-black {{ $competidores > 0 ? 'text-cyan-300' : 'text-slate-600' }}">
                        {{ $competidores }}
                    </p>
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                        competidores
                    </p>
                </div>
            </div>
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
        {{-- DÓNDE ENCAJA UN JUEGO --}}
        {{-- ===================================================== --}}

        <section x-data="{ abierto: false }"
            class="overflow-hidden rounded-2xl border border-violet-500/25 bg-violet-500/5">

            <button type="button" @click="abierto = !abierto"
                class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition hover:bg-violet-500/5">

                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                    <x-omni-icon name="dado" size="h-3.5 w-3.5" />
                </span>

                <span class="min-w-0 flex-1 text-[12px] font-black text-white">
                    Dónde encaja un juego
                    <span class="font-bold text-slate-500">— torneo, juego y competidor, en un dibujo</span>
                </span>

                <span class="shrink-0 text-slate-500 transition" :class="abierto ? 'rotate-90' : ''">
                    <x-omni-icon name="chevron-derecha" size="h-4 w-4" />
                </span>
            </button>

            <div x-show="abierto" x-cloak x-collapse class="border-t border-violet-500/20 p-4">
                <div class="grid gap-4 lg:grid-cols-[380px_minmax(0,1fr)]">

                    <svg viewBox="0 0 300 140" class="h-auto w-full text-violet-400" fill="none"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">

                        {{-- El torneo empareja --}}
                        <rect x="6" y="34" width="76" height="62" rx="6" stroke-dasharray="5 4" />
                        <path d="M18 50h20v14h-20z" opacity=".7" />
                        <path d="M18 74h20v14h-20z" opacity=".7" />
                        <path d="M40 57h10v24h-10" opacity=".45" />
                        <path d="M50 69h14" opacity=".45" />
                        <text x="44" y="28" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="8" font-weight="700">El torneo</text>
                        <text x="44" y="110" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="7" font-weight="700" opacity=".55">decide quiénes se enfrentan</text>

                        {{-- El juego resuelve --}}
                        <path d="M88 65h20M108 65l-6-4M108 65l-6 4" opacity=".7" />
                        <rect x="114" y="34" width="76" height="62" rx="6" stroke-width="2" />
                        <circle cx="140" cy="58" r="9" opacity=".8" />
                        <circle cx="166" cy="58" r="9" opacity=".8" />
                        <path d="M128 80h48" opacity=".35" />
                        <text x="152" y="28" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="8" font-weight="700">El juego</text>
                        <text x="152" y="110" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="7" font-weight="700">decide quién gana</text>

                        {{-- El competidor lleva sus números --}}
                        <path d="M196 65h20M216 65l-6-4M216 65l-6 4" opacity=".7" />
                        <rect x="222" y="34" width="72" height="62" rx="6" />
                        <circle cx="240" cy="54" r="8" opacity=".7" />
                        <path d="M254 50h30M254 60h18" opacity=".4" />
                        <rect x="232" y="72" width="52" height="12" rx="3" opacity=".7" />
                        <text x="258" y="28" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="8" font-weight="700">El competidor</text>
                        <text x="258" y="110" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="7" font-weight="700" opacity=".55">lleva sus números</text>

                        <path d="M6 124h288" opacity=".15" />
                        <text x="150" y="135" text-anchor="middle" fill="currentColor" stroke="none"
                            font-size="7" font-weight="700" opacity=".5">un mismo competidor juega distinto en cada juego</text>
                    </svg>

                    <div class="space-y-2 text-[11px] leading-relaxed text-slate-400">
                        <p>
                            Un <strong class="text-white">juego</strong> es el motor que resuelve un
                            enfrentamiento. El torneo monta los cruces; cuando llega el momento de decir
                            quién pasa, pregunta al juego.
                        </p>

                        <p>
                            Cada competidor guarda <strong class="text-violet-300">sus propias
                            estadísticas por juego</strong> dentro de este universo. El mismo personaje
                            puede ser temible en uno y del montón en otro, y su ficha de la biblioteca no
                            se entera de nada.
                        </p>

                        <p class="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-[10px] text-slate-400">
                            <strong class="text-slate-200">Los juegos no se crean aquí.</strong> Viven en
                            código, en el registro de motores. Cuando se añade uno nuevo aparece solo en
                            esta pantalla, en la ficha de cada competidor y en el simulador, sin migración
                            ni configuración previa.
                        </p>

                        <p class="border-t border-slate-800 pt-2 text-[10px] text-slate-500">
                            Lo que sí decide este universo es <strong class="text-slate-300">cuál se usa por
                            defecto</strong> y <strong class="text-slate-300">con qué valores</strong> entra
                            un competidor nuevo.
                        </p>
                    </div>

                </div>
            </div>
        </section>


        {{-- ===================================================== --}}
        {{-- LOS JUEGOS --}}
        {{-- ===================================================== --}}

        <div class="grid gap-4 xl:grid-cols-2">

            @foreach ($games as $juego)

                @php
                    $definicion = $juego['definition'];
                    $registro = $juego['record'];
                    $configuracion = $juego['configuration'];

                    $tono = $tonos[$definicion['accent'] ?? 'violet'] ?? '#a78bfa';

                    $esDefecto = (bool) $registro->is_default;
                    $jugado = $juego['encounters'] > 0;
                @endphp

                <article class="overflow-hidden rounded-2xl border bg-slate-900/50 transition duration-300 hover:-translate-y-0.5"
                    style="border-color: {{ $esDefecto ? $tono : $tono . '40' }}">

                    {{-- ---------- QUIÉN ES ---------- --}}

                    <div class="flex flex-wrap items-start gap-3 border-b border-slate-800 p-4"
                        style="background: radial-gradient(120% 140% at 0% 0%, {{ $tono }}18, transparent 65%)">

                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border text-2xl"
                            style="border-color: {{ $tono }}55; background-color: {{ $tono }}18">
                            {{ $definicion['icon'] ?? '◈' }}
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="truncate text-[16px] font-black text-white">{{ $definicion['name'] }}</h2>

                                @if ($esDefecto)
                                    <span class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider"
                                        style="background-color: {{ $tono }}; color: #020617">
                                        por defecto
                                    </span>
                                @endif

                                @if (! $registro->is_enabled)
                                    <span class="rounded bg-slate-800 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider text-slate-500">
                                        apagado
                                    </span>
                                @endif
                            </div>

                            <p class="mt-0.5 text-[11px] leading-relaxed text-slate-400">
                                {{ $definicion['tagline'] }}
                            </p>

                            <div class="mt-1.5 flex flex-wrap items-center gap-1">
                                <span class="rounded border border-slate-800 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider"
                                    style="color: {{ $tono }}">
                                    {{ $definicion['type_label'] ?? 'Motor' }}
                                </span>

                                <span class="rounded border border-slate-800 px-1.5 py-0.5 text-[9px] font-bold text-slate-500">
                                    {{ $definicion['minimum_participants'] ?? 2 }}
                                    @if ($definicion['maximum_participants'] ?? null)
                                        –{{ $definicion['maximum_participants'] }}
                                    @else
                                        o más
                                    @endif
                                    participantes
                                </span>

                                @if ($definicion['allows_draws'] ?? false)
                                    <span class="rounded border border-slate-800 px-1.5 py-0.5 text-[9px] font-bold text-slate-500"
                                        title="Puede quedar en empate; que se repita o no lo decide la fase">
                                        admite empate
                                    </span>
                                @endif

                                @if ($definicion['tracks_points'] ?? false)
                                    <span class="rounded border border-slate-800 px-1.5 py-0.5 text-[9px] font-bold text-slate-500"
                                        title="Lo que saca cada uno cuenta como puntos en la clasificación">
                                        puntúa
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>


                    {{-- ---------- CÓMO FUNCIONA, DIBUJADO ---------- --}}

                    <div class="border-b border-slate-800 bg-slate-950/40 p-4">
                        <p class="mb-2 text-[9px] font-black uppercase tracking-wider text-slate-600">
                            Cómo funciona
                        </p>

                        @include('universes.games.partials.diagrama', [
                            'definicion' => $definicion,
                            'tono' => $tono,
                        ])
                    </div>


                    {{-- ---------- LAS REGLAS, EN ORDEN ---------- --}}

                    @if (! empty($definicion['rules']))
                        <div x-data="{ abierto: false }" class="border-b border-slate-800">

                            <button type="button" @click="abierto = !abierto"
                                class="flex w-full items-center gap-2.5 px-4 py-2 text-left transition hover:bg-slate-950/40">

                                <span class="text-[11px] font-black text-slate-300">
                                    Las reglas, paso a paso
                                </span>

                                <span class="font-mono text-[10px] text-slate-600">
                                    {{ count($definicion['rules']) }}
                                </span>

                                <span class="ml-auto shrink-0 text-slate-600 transition" :class="abierto ? 'rotate-90' : ''">
                                    <x-omni-icon name="chevron-derecha" size="h-3.5 w-3.5" />
                                </span>
                            </button>

                            <div x-show="abierto" x-cloak x-collapse class="px-4 pb-3">
                                <ol class="space-y-1.5">
                                    @foreach ($definicion['rules'] as $indice => $regla)
                                        <li class="flex gap-2">
                                            <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded font-mono text-[9px] font-black"
                                                style="background-color: {{ $tono }}22; color: {{ $tono }}">
                                                {{ $indice + 1 }}
                                            </span>
                                            <span class="text-[10px] leading-relaxed text-slate-400">{{ $regla }}</span>
                                        </li>
                                    @endforeach
                                </ol>

                                @if ($definicion['tiebreak'] ?? null)
                                    <p class="mt-2 rounded-xl border border-slate-800 bg-slate-950 px-2.5 py-1.5 text-[10px] leading-relaxed text-slate-500">
                                        <strong class="text-slate-300">Si empatan:</strong>
                                        {{ $definicion['tiebreak'] }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endif


                    {{-- ---------- CON QUÉ VALORES SE JUEGA AQUÍ ---------- --}}

                    <div class="border-b border-slate-800 p-4">

                        <div class="mb-2 flex flex-wrap items-center gap-2">
                            <p class="min-w-0 flex-1 text-[9px] font-black uppercase tracking-wider text-slate-600">
                                Con qué entra un competidor nuevo en {{ $universe->name }}
                            </p>

                            <a href="{{ route('universes.games.show', [$universe, $definicion['key']]) }}"
                                class="shrink-0 rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-white"
                                style="border-color: {{ $tono }}33">
                                Ajustar →
                            </a>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($configuracion->stats() as $stat)
                                <div class="rounded-xl border border-slate-800 bg-slate-950 p-2.5">

                                    <div class="flex items-center gap-1.5">
                                        <span class="min-w-0 flex-1 truncate text-[10px] font-black text-slate-300">
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

                                    <p class="mt-0.5 font-mono text-[15px] font-black" style="color: {{ $tono }}">
                                        {{ rtrim(rtrim(number_format($stat['default'], 1, ',', ''), '0'), ',') }}
                                    </p>

                                    {{-- Dónde cae el valor de partida dentro del recorrido permitido --}}
                                    @php
                                        $recorrido = max($stat['max'] - $stat['min'], 0.0001);
                                        $posicion = (int) round((($stat['default'] - $stat['min']) / $recorrido) * 100);
                                    @endphp

                                    <div class="relative mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-900">
                                        <div class="absolute inset-y-0 w-1 rounded-full"
                                            style="left: {{ min(max($posicion, 0), 98) }}%; background-color: {{ $tono }}"></div>
                                    </div>

                                    <div class="mt-0.5 flex items-center justify-between font-mono text-[8px] text-slate-600">
                                        <span>{{ rtrim(rtrim(number_format($stat['min'], 1, ',', ''), '0'), ',') }}</span>
                                        <span>{{ rtrim(rtrim(number_format($stat['max'], 1, ',', ''), '0'), ',') }}</span>
                                    </div>

                                    @if ($stat['help'])
                                        <p class="mt-1 text-[9px] leading-3 text-slate-600">{{ $stat['help'] }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>


                    {{-- ---------- CUÁNTO SE USA ---------- --}}

                    <div class="grid grid-cols-3 gap-px border-b border-slate-800 bg-slate-800">
                        @foreach ([['Enfrentamientos', $juego['encounters'], 'Veces que este motor ha resuelto algo'], ['Competiciones', $juego['competitions'], 'Torneos de este universo que lo han elegido'], ['Estado', $registro->is_enabled ? 'Activo' : 'Apagado', 'Si se puede elegir al crear una competición']] as [$etiqueta, $valor, $ayuda])
                            <div class="bg-slate-900/50 px-3 py-2" title="{{ $ayuda }}">
                                <p class="font-mono text-[15px] font-black {{ is_numeric($valor) && $valor == 0 ? 'text-slate-600' : '' }}"
                                    style="{{ is_numeric($valor) && $valor > 0 ? 'color: ' . $tono : '' }}{{ ! is_numeric($valor) ? ($registro->is_enabled ? 'color:#34d399' : 'color:#64748b') : '' }}">
                                    {{ $valor }}
                                </p>
                                <p class="text-[8px] font-black uppercase tracking-wider text-slate-600">{{ $etiqueta }}</p>
                            </div>
                        @endforeach
                    </div>

                    @if (! $jugado)
                        <p class="border-b border-slate-800 bg-slate-950/40 px-4 py-2 text-[10px] leading-relaxed text-slate-500">
                            @if ($competidores === 0)
                                Todavía no hay competidores en este universo, así que no ha podido jugarse
                                nada.
                            @else
                                Nadie lo ha jugado todavía. Se estrena en cuanto una competición lo elija,
                                o probándolo en el simulador.
                            @endif
                        </p>
                    @endif


                    {{-- ---------- QUÉ SE PUEDE HACER ---------- --}}

                    <div class="flex flex-wrap items-center gap-1.5 p-3">

                        <a href="{{ route('universes.games.show', [$universe, $definicion['key']]) }}"
                            class="rounded-xl px-3 py-2 text-[11px] font-black transition"
                            style="background-color: {{ $tono }}22; color: {{ $tono }}">
                            Ver la ficha
                        </a>

                        @can('update', $universe)
                            @if (! $esDefecto)
                                <form method="POST" action="{{ route('universes.games.default', $universe) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="game_key" value="{{ $definicion['key'] }}">

                                    <button type="submit"
                                        class="rounded-xl border border-slate-800 px-3 py-2 text-[11px] font-black text-slate-400 transition hover:border-slate-600 hover:text-white"
                                        title="Las competiciones nuevas lo propondrán primero">
                                        Usar por defecto
                                    </button>
                                </form>
                            @else
                                <span class="rounded-xl border border-slate-800 px-3 py-2 text-[11px] font-black text-slate-600"
                                    title="Ya es el que proponen las competiciones nuevas">
                                    Ya es el de partida
                                </span>
                            @endif
                        @endcan

                        <span class="ml-auto font-mono text-[9px] text-slate-700">{{ $definicion['key'] }}</span>
                    </div>

                </article>
            @endforeach


            {{-- ---------- LO QUE VENDRÁ ---------- --}}

            <article class="flex flex-col justify-center rounded-2xl border border-dashed border-slate-800 bg-slate-900/30 p-6 text-center">

                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-800 text-2xl text-slate-700">
                    ＋
                </span>

                <p class="mt-3 text-[13px] font-black text-white">Habrá más juegos</p>

                <p class="mx-auto mt-1 max-w-sm text-[11px] leading-relaxed text-slate-500">
                    Hoy hay {{ $games->count() }}
                    {{ $games->count() === 1 ? 'motor' : 'motores' }}, {{ $activos }}
                    {{ $activos === 1 ? 'activo' : 'activos' }}. Los juegos viven en código: cuando se
                    escriba uno nuevo aparecerá aquí solo, con su diagrama y su configuración, sin que
                    haya que tocar esta pantalla ni migrar nada.
                </p>

                <p class="mt-2 text-[10px] text-slate-600">
                    Un juego basado en atributos —fuerza, velocidad— es el siguiente paso natural.
                </p>
            </article>

        </div>


        {{-- ===================================================== --}}
        {{-- CUÁL SE USA SI NO SE DICE NADA --}}
        {{-- ===================================================== --}}

        @if ($porDefecto)
            @php $tonoDefecto = $tonos[$porDefecto['definition']['accent'] ?? 'violet'] ?? '#a78bfa'; @endphp

            <section class="flex flex-wrap items-center gap-3 rounded-2xl border bg-slate-900/50 px-4 py-3"
                style="border-color: {{ $tonoDefecto }}40">

                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-xl"
                    style="background-color: {{ $tonoDefecto }}18">
                    {{ $porDefecto['definition']['icon'] ?? '◈' }}
                </span>

                <p class="min-w-0 flex-1 text-[11px] leading-relaxed text-slate-400">
                    Si al crear una competición no se dice otra cosa, se juega a
                    <strong style="color: {{ $tonoDefecto }}">{{ $porDefecto['definition']['name'] }}</strong>.
                    Cada competición puede cambiarlo, y cambiarlo aquí no toca las que ya existen.
                </p>

                <a href="{{ route('universes.competitions.create', $universe) }}"
                    class="shrink-0 rounded-xl border border-slate-800 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                    Crear una competición →
                </a>
            </section>
        @endif

    </div>

</x-universe-layout>
