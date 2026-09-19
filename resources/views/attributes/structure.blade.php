@php
    /*
     * La estructura de los atributos.
     *
     * Aquí se decide cuándo un atributo aparece en la ficha de una entidad,
     * cuándo es obligatorio, y qué valores de un catálogo quedan disponibles
     * según lo que se haya elegido en otro. Es el motor que hace que la ficha
     * de una entidad no sea siempre el mismo formulario.
     *
     * Lo que había era correcto y prácticamente ilegible: tres pestañas de
     * desplegables de texto sobre fondo blanco, sin una sola imagen, sin decir
     * en ningún sitio qué hacía cada cosa ni qué pasaba después de guardarla.
     *
     * Tres cosas importantes de cómo funciona esto por dentro, que la pantalla
     * ahora dice en voz alta porque antes había que adivinarlas:
     *
     *   · El mapa de dependencias NO se edita. Sale solo de las reglas: cada
     *     condición que escribes registra una flecha, y de las flechas sale el
     *     nivel de cada atributo. Por eso su pestaña no tiene formulario.
     *
     *   · Una regla sobre un atributo que no tiene ninguna entidad es correcta
     *     y no hace nada. Ahora se avisa.
     *
     *   · Dos reglas que dicen «mostrar» y «ocultar» sobre el mismo atributo se
     *     pueden guardar las dos. Gana la de más prioridad, y eso deja de ser
     *     evidente. Ahora se avisa también.
     */

    $accionTono = [
        'SHOW' => ['#10b981', 'Mostrar', 'aparece en la ficha'],
        'HIDE' => ['#f43f5e', 'Ocultar', 'desaparece de la ficha'],
        'REQUIRE' => ['#f59e0b', 'Exigir', 'pasa a ser obligatorio'],
    ];

    $operadorTexto = [
        'EQUALS' => 'es',
        'NOT_EQUALS' => 'no es',
        'EXISTS' => 'tiene algún valor',
        'NOT_EXISTS' => 'está vacío',
    ];

    $hayAvisos = $stats['conflictos'] > 0 || $stats['inertes'] > 0;
@endphp

<x-app-layout title="Estructura de atributos" surface="dark">

    <x-slot name="header">Atributos</x-slot>

    <div x-data="constructorDeEstructura({ atributos: @js($attributePayload) })" class="space-y-4">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-center gap-3">

            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-cyan-500/40 bg-cyan-500/10 text-cyan-300">
                <x-omni-icon name="grafo" size="h-5 w-5" />
            </span>

            <div class="min-w-0 flex-1">
                <a href="{{ route('attributes.index') }}"
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-cyan-400">
                    ← Atributos
                </a>

                <h1 class="mt-0.5 text-xl font-black tracking-tight text-white">Estructura</h1>

                <p class="text-[11px] leading-relaxed text-slate-500">
                    Cuándo aparece cada atributo, cuándo es obligatorio, y qué valores de un catálogo
                    quedan disponibles según otro.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
                <a href="{{ route('attributes.index') }}"
                    class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2 text-[11px] font-black text-slate-400 transition hover:border-slate-700 hover:text-white">
                    Volver
                </a>

                <button type="button" @click="tab = 'RULES'"
                    class="flex items-center gap-1.5 rounded-xl bg-violet-500 px-3 py-2 text-[11px] font-black text-white transition hover:bg-violet-400">
                    <x-omni-icon name="mas" size="h-3.5 w-3.5" />
                    Nueva regla
                </button>
            </div>
        </header>


        @if (session('success'))
            <div class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-2.5 text-[12px] font-bold text-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3">
                <p class="text-[12px] font-black text-rose-200">No se pudo guardar:</p>
                <ul class="mt-1 space-y-0.5 text-[11px] leading-relaxed text-rose-200/80">
                    @foreach ($errors->all() as $error)
                        <li>· {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- ===================================================== --}}
        {{-- QUÉ ES ESTO --}}
        {{-- ===================================================== --}}

        <section x-data="{ abierto: {{ $stats['rules'] === 0 ? 'true' : 'false' }} }"
            class="overflow-hidden rounded-2xl border border-cyan-500/25 bg-cyan-500/5">

            <button type="button" @click="abierto = !abierto"
                class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition hover:bg-cyan-500/5">

                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-cyan-500/15 text-cyan-300">
                    <x-omni-icon name="chispa" size="h-3.5 w-3.5" />
                </span>

                <span class="min-w-0 flex-1 text-[12px] font-black text-white">
                    Qué se hace en esta pantalla
                    <span class="font-bold text-slate-500">— las tres piezas, en un dibujo</span>
                </span>

                <span class="shrink-0 text-slate-500 transition" :class="abierto ? 'rotate-90' : ''">
                    <x-omni-icon name="chevron-derecha" size="h-4 w-4" />
                </span>
            </button>

            <div x-show="abierto" x-cloak x-collapse class="border-t border-cyan-500/20 p-4">

                <div class="grid gap-3 lg:grid-cols-3">

                    {{-- Una regla --}}
                    <div class="rounded-2xl border border-slate-800 bg-slate-950 p-3">

                        <svg viewBox="0 0 220 96" class="h-auto w-full text-violet-400" fill="none"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">

                            <rect x="6" y="30" width="62" height="34" rx="5" />
                            <circle cx="22" cy="47" r="7" opacity=".65" />
                            <path d="M34 43h24M34 51h14" opacity=".45" />
                            <text x="37" y="24" text-anchor="middle" fill="currentColor" stroke="none"
                                font-size="7.5" font-weight="700">Si «Anime» es Naruto</text>

                            <path d="M74 47h26M100 47l-6-4M100 47l-6 4" opacity=".8" />

                            <rect x="106" y="18" width="62" height="26" rx="5" opacity=".95" />
                            <path d="M116 31h42" opacity=".45" />
                            <text x="137" y="12" text-anchor="middle" fill="currentColor" stroke="none"
                                font-size="7.5" font-weight="700">entonces «Clan»</text>

                            <rect x="106" y="52" width="62" height="26" rx="5" stroke-dasharray="4 4" opacity=".4" />
                            <path d="M122 65h30" opacity=".3" />
                            <text x="137" y="90" text-anchor="middle" fill="currentColor" stroke="none"
                                font-size="7" font-weight="700" opacity=".5">si no, no está</text>

                            <path d="M176 31h12M188 31l-4-3M188 31l-4 3" opacity=".55" />
                            <circle cx="202" cy="31" r="8" opacity=".8" />
                            <path d="M198 31l3 3 5-6" opacity=".9" />
                        </svg>

                        <p class="mt-2 text-[11px] font-black text-white">1 · Una regla</p>
                        <p class="mt-0.5 text-[10px] leading-relaxed text-slate-500">
                            Mira lo que ya tiene la entidad y decide si otro atributo
                            <strong class="text-emerald-300">aparece</strong>,
                            <strong class="text-rose-300">desaparece</strong> o
                            <strong class="text-amber-300">pasa a ser obligatorio</strong>.
                        </p>
                    </div>


                    {{-- Una dependencia de catálogo --}}
                    <div class="rounded-2xl border border-slate-800 bg-slate-950 p-3">

                        <svg viewBox="0 0 220 96" class="h-auto w-full text-fuchsia-400" fill="none"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">

                            <rect x="6" y="34" width="58" height="26" rx="5" />
                            <circle cx="20" cy="47" r="6" opacity=".7" />
                            <path d="M32 47h22" opacity=".45" />
                            <text x="35" y="28" text-anchor="middle" fill="currentColor" stroke="none"
                                font-size="7.5" font-weight="700">País = Perú</text>

                            <path d="M70 47h24M94 47l-6-4M94 47l-6 4" opacity=".8" />

                            <rect x="100" y="10" width="58" height="20" rx="4" opacity=".95" />
                            <circle cx="112" cy="20" r="5" opacity=".7" />
                            <path d="M122 20h26" opacity=".45" />

                            <rect x="100" y="36" width="58" height="20" rx="4" opacity=".95" />
                            <circle cx="112" cy="46" r="5" opacity=".7" />
                            <path d="M122 46h26" opacity=".45" />

                            <rect x="100" y="62" width="58" height="20" rx="4" stroke-dasharray="4 3" opacity=".3" />
                            <path d="M104 82l50-20" opacity=".35" />

                            <text x="129" y="94" text-anchor="middle" fill="currentColor" stroke="none"
                                font-size="7" font-weight="700" opacity=".5">las demás se caen</text>

                            <path d="M166 20h12M178 20l-4-3M178 20l-4 3" opacity=".5" />
                            <path d="M166 46h12M178 46l-4-3M178 46l-4 3" opacity=".5" />
                            <text x="196" y="36" text-anchor="middle" fill="currentColor" stroke="none"
                                font-size="7" font-weight="700" opacity=".6">Región</text>
                        </svg>

                        <p class="mt-2 text-[11px] font-black text-white">2 · Una dependencia de catálogo</p>
                        <p class="mt-0.5 text-[10px] leading-relaxed text-slate-500">
                            Un valor de un catálogo <strong class="text-emerald-300">permite</strong> o
                            <strong class="text-rose-300">bloquea</strong> valores de otro. Se elige
                            entre menos, y sin equivocarse.
                        </p>
                    </div>


                    {{-- El mapa --}}
                    <div class="rounded-2xl border border-slate-800 bg-slate-950 p-3">

                        <svg viewBox="0 0 220 96" class="h-auto w-full text-cyan-400" fill="none"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">

                            <text x="26" y="12" text-anchor="middle" fill="currentColor" stroke="none"
                                font-size="7" font-weight="700" opacity=".55">nivel 0</text>
                            <text x="110" y="12" text-anchor="middle" fill="currentColor" stroke="none"
                                font-size="7" font-weight="700" opacity=".55">nivel 1</text>
                            <text x="194" y="12" text-anchor="middle" fill="currentColor" stroke="none"
                                font-size="7" font-weight="700" opacity=".55">nivel 2</text>

                            <rect x="6" y="22" width="40" height="22" rx="4" />
                            <rect x="6" y="52" width="40" height="22" rx="4" opacity=".6" />

                            <path d="M50 33h32M82 33l-5-3M82 33l-5 3" opacity=".7" />
                            <path d="M50 63h32M82 63l-5-3M82 63l-5 3" opacity=".45" />

                            <rect x="88" y="22" width="44" height="22" rx="4" />
                            <rect x="88" y="52" width="44" height="22" rx="4" opacity=".6" />

                            <path d="M136 33h34M170 33l-5-3M170 33l-5 3" opacity=".7" />
                            <path d="M136 63l30-26M166 37l1-6M166 37l-6-1" opacity=".4" />

                            <rect x="174" y="22" width="40" height="22" rx="4" />

                            <path d="M6 88h208" opacity=".15" />
                            <text x="110" y="95" text-anchor="middle" fill="currentColor" stroke="none"
                                font-size="6.5" font-weight="700" opacity=".5">el nivel no se elige: sale de las reglas</text>
                        </svg>

                        <p class="mt-2 text-[11px] font-black text-white">3 · El mapa</p>
                        <p class="mt-0.5 text-[10px] leading-relaxed text-slate-500">
                            No se edita: se calcula. Cada condición que escribes deja una flecha, y de las
                            flechas sale el nivel de cada atributo.
                        </p>
                    </div>

                </div>
            </div>
        </section>


        {{-- ===================================================== --}}
        {{-- CIFRAS --}}
        {{-- ===================================================== --}}

        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">

            @foreach ([['Atributos', $stats['attributes'], '#6366f1', 'STRUCTURE'], ['Reglas', $stats['rules'], '#8b5cf6', 'RULES'], ['Flechas', $stats['relationships'], '#06b6d4', 'STRUCTURE'], ['Catálogos atados', $stats['option_relationships'], '#d946ef', 'CATALOGS'], ['Niveles', $stats['niveles'], '#0ea5e9', 'STRUCTURE'], ['Sueltos', $stats['sueltos'], '#64748b', 'STRUCTURE']] as [$etiqueta, $valor, $tono, $destino])
                <button type="button" @click="tab = '{{ $destino }}'"
                    class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2 text-left transition hover:border-slate-700">
                    <p class="font-mono text-xl font-black"
                        style="color: {{ $valor > 0 ? $tono : '#475569' }}">{{ $valor }}</p>
                    <p class="truncate text-[9px] font-black uppercase tracking-wider text-slate-600">{{ $etiqueta }}</p>
                </button>
            @endforeach
        </div>


        {{-- ===================================================== --}}
        {{-- AVISOS --}}
        {{-- ===================================================== --}}

        {{--
            Lo que hoy se puede guardar sin que nadie diga nada. Ninguna de las
            dos cosas rompe la aplicación, pero las dos hacen que el resultado
            deje de ser evidente, así que se dicen aquí arriba.
        --}}

        @if ($hayAvisos)
            <section class="space-y-2">

                @if ($conflictosDeReglas->isNotEmpty())
                    <div class="rounded-2xl border border-rose-500/30 bg-rose-500/5 p-3.5">

                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-rose-500/15 text-rose-300">
                                <x-omni-icon name="chispa" size="h-3.5 w-3.5" />
                            </span>
                            <p class="text-[12px] font-black text-rose-200">
                                {{ $conflictosDeReglas->count() }}
                                {{ $conflictosDeReglas->count() === 1 ? 'atributo tiene reglas que se contradicen' : 'atributos tienen reglas que se contradicen' }}
                            </p>
                        </div>

                        <p class="mt-1 text-[10px] leading-relaxed text-rose-200/60">
                            Una regla dice «mostrar» y otra «ocultar» sobre el mismo atributo. Se guardan
                            las dos y gana la de más prioridad, así que el resultado deja de leerse de un
                            vistazo. Vale la pena borrar una.
                        </p>

                        <div class="mt-2.5 space-y-1.5">
                            @foreach ($conflictosDeReglas as $objetivoId => $grupo)
                                @php $objetivo = $grupo->first()->targetAttribute; @endphp

                                <div class="flex flex-wrap items-center gap-2 rounded-xl border border-rose-500/20 bg-slate-950 p-2">

                                    @include('attributes.partials.cara', [
                                        'cosa' => $objetivo,
                                        'tamano' => 'h-8 w-8',
                                        'respaldo' => '◍',
                                    ])

                                    <span class="min-w-0 flex-1 truncate text-[11px] font-black text-white">
                                        {{ $objetivo?->name ?? 'Atributo borrado' }}
                                    </span>

                                    @foreach ($grupo as $regla)
                                        @php [$tono, $etiqueta] = $accionTono[$regla->action] ?? ['#64748b', $regla->action]; @endphp
                                        <span class="rounded-lg px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider"
                                            style="color: {{ $tono }}; background-color: {{ $tono }}22"
                                            title="Prioridad {{ $regla->priority }}">
                                            {{ $etiqueta }} · p{{ $regla->priority }}
                                        </span>
                                    @endforeach

                                    <button type="button" @click="tab = 'RULES'; filtroRegla = '{{ $objetivoId }}'"
                                        class="shrink-0 rounded-lg border border-rose-500/30 px-2 py-1 text-[10px] font-black text-rose-300 transition hover:bg-rose-500 hover:text-white">
                                        Ver las dos →
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif


                @if ($conflictosDeCatalogo->isNotEmpty())
                    <div class="rounded-2xl border border-rose-500/30 bg-rose-500/5 p-3.5">

                        <p class="text-[12px] font-black text-rose-200">
                            {{ $conflictosDeCatalogo->count() }}
                            {{ $conflictosDeCatalogo->count() === 1 ? 'par de valores está a la vez permitido y bloqueado' : 'pares de valores están a la vez permitidos y bloqueados' }}
                        </p>

                        <div class="mt-2 space-y-1.5">
                            @foreach ($conflictosDeCatalogo as $grupo)
                                @php $muestra = $grupo->first(); @endphp

                                <div class="flex flex-wrap items-center gap-2 rounded-xl border border-rose-500/20 bg-slate-950 p-2">
                                    @include('attributes.partials.cara', ['cosa' => $muestra->sourceOption, 'tamano' => 'h-7 w-7'])
                                    <span class="truncate text-[10px] font-black text-white">{{ $muestra->sourceOption?->name }}</span>
                                    <span class="text-slate-700">→</span>
                                    @include('attributes.partials.cara', ['cosa' => $muestra->targetOption, 'tamano' => 'h-7 w-7'])
                                    <span class="truncate text-[10px] font-black text-white">{{ $muestra->targetOption?->name }}</span>

                                    <button type="button" @click="tab = 'CATALOGS'"
                                        class="ml-auto shrink-0 rounded-lg border border-rose-500/30 px-2 py-1 text-[10px] font-black text-rose-300 transition hover:bg-rose-500 hover:text-white">
                                        Arreglar →
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif


                @if ($reglasInertes->isNotEmpty())
                    <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-amber-500/25 bg-amber-500/5 px-4 py-2.5">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 text-amber-300">
                            <x-omni-icon name="controles" size="h-3.5 w-3.5" />
                        </span>

                        <p class="min-w-0 flex-1 text-[11px] leading-relaxed text-amber-200/80">
                            <strong class="text-amber-200">{{ $reglasInertes->count() }}
                                {{ $reglasInertes->count() === 1 ? 'regla no puede hacer nada todavía' : 'reglas no pueden hacer nada todavía' }}</strong>:
                            apuntan a un atributo que no tiene ninguna entidad. La regla es correcta, pero
                            no hay a quién aplicársela hasta que alguna entidad use ese atributo.
                        </p>
                    </div>
                @endif

            </section>
        @endif


        {{-- ===================================================== --}}
        {{-- PESTAÑAS --}}
        {{-- ===================================================== --}}

        <nav class="flex flex-wrap gap-1.5 rounded-2xl border border-slate-800 bg-slate-900/50 p-1.5">

            @foreach ([['STRUCTURE', 'Mapa', 'grafo', '#06b6d4'], ['RULES', 'Reglas', 'chispa', '#8b5cf6'], ['CATALOGS', 'Catálogos', 'capas', '#d946ef']] as [$clave, $etiqueta, $icono, $tono])
                <button type="button" @click="tab = '{{ $clave }}'"
                    :aria-pressed="tab === '{{ $clave }}'"
                    :style="tab === '{{ $clave }}' ? 'background-color: {{ $tono }}; color: #020617' : ''"
                    :class="tab === '{{ $clave }}' ? '' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white'"
                    class="flex flex-1 items-center justify-center gap-1.5 rounded-xl px-4 py-2.5 text-[12px] font-black transition sm:flex-none">
                    <x-omni-icon :name="$icono" size="h-4 w-4" />
                    {{ $etiqueta }}
                </button>
            @endforeach
        </nav>


        {{-- ===================================================== --}}
        {{-- MAPA --}}
        {{-- ===================================================== --}}

        <section x-show="tab === 'STRUCTURE'" class="space-y-4">

            <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-cyan-500/25 bg-cyan-500/5 px-4 py-2.5">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-cyan-500/15 text-cyan-300">
                    <x-omni-icon name="grafo" size="h-3.5 w-3.5" />
                </span>

                <p class="min-w-0 flex-1 text-[11px] leading-relaxed text-cyan-100/70">
                    <strong class="text-cyan-200">Esto no se edita aquí: se calcula.</strong>
                    Cada condición que escribes en una regla deja una flecha entre dos atributos, y de las
                    flechas sale el nivel de cada uno. Si quieres cambiar el mapa, cambia las reglas.
                </p>

                <button type="button" @click="tab = 'RULES'"
                    class="shrink-0 rounded-xl border border-cyan-500/30 px-3 py-1.5 text-[10px] font-black text-cyan-300 transition hover:bg-cyan-500 hover:text-slate-950">
                    Ir a las reglas →
                </button>
            </div>


            {{-- ---------- POR NIVELES ---------- --}}

            <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-3">
                    <div class="min-w-0 flex-1">
                        <h2 class="text-[13px] font-black text-white">El recorrido, por niveles</h2>
                        <p class="text-[10px] leading-relaxed text-slate-500">
                            El <strong class="text-slate-400">nivel 0</strong> no depende de nadie: siempre
                            se ve. Cada nivel siguiente solo aparece cuando el anterior tiene el valor que
                            pide la regla.
                        </p>
                    </div>
                </div>

                <div class="space-y-3 p-4">
                    @foreach ($niveles as $nivel => $delNivel)
                        <div class="rounded-2xl border border-slate-800 bg-slate-950 p-3">

                            <div class="mb-2 flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-lg font-mono text-[11px] font-black"
                                    style="background-color: {{ $nivel === 0 ? '#06b6d422' : '#8b5cf622' }}; color: {{ $nivel === 0 ? '#22d3ee' : '#a78bfa' }}">
                                    {{ $nivel }}
                                </span>

                                <span class="text-[11px] font-black text-white">Nivel {{ $nivel }}</span>

                                <span class="text-[10px] text-slate-600">
                                    @if ($nivel === 0)
                                        no dependen de nada
                                    @else
                                        aparecen cuando se cumple una regla
                                    @endif
                                </span>

                                <span class="ml-auto font-mono text-[10px] text-slate-600">{{ $delNivel->count() }}</span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5">
                                @foreach ($delNivel as $atributo)
                                    @php
                                        $reglasDelAtributo = $comoObjetivo->get($atributo->id, collect());
                                        $vecesCondicion = $comoCondicion->get($atributo->id, 0);
                                        $tonoAtributo = $atributo->color ?: '#6366f1';
                                    @endphp

                                    <a href="{{ route('attributes.show', $atributo) }}"
                                        class="group flex items-center gap-2 rounded-xl border bg-slate-900/60 p-2 transition hover:-translate-y-0.5"
                                        style="border-color: {{ $tonoAtributo }}40">

                                        @include('attributes.partials.cara', [
                                            'cosa' => $atributo,
                                            'tamano' => 'h-9 w-9',
                                            'respaldo' => '◍',
                                        ])

                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-[11px] font-black text-white">
                                                {{ $atributo->name }}
                                            </span>

                                            <span class="block truncate text-[9px] text-slate-500">
                                                @if ($reglasDelAtributo->isNotEmpty())
                                                    {{ $reglasDelAtributo->count() }}
                                                    {{ $reglasDelAtributo->count() === 1 ? 'regla decide si sale' : 'reglas deciden si sale' }}
                                                @elseif ($vecesCondicion > 0)
                                                    manda en {{ $vecesCondicion }}
                                                    {{ $vecesCondicion === 1 ? 'regla' : 'reglas' }}
                                                @else
                                                    siempre se ve
                                                @endif
                                            </span>
                                        </span>

                                        @if ($vecesCondicion > 0)
                                            <span class="shrink-0 font-mono text-[10px] font-black text-cyan-400"
                                                title="Es la condición de {{ $vecesCondicion }} reglas">
                                                →{{ $vecesCondicion }}
                                            </span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    @if ($niveles->isEmpty())
                        <p class="py-10 text-center text-[11px] text-slate-600">
                            No hay atributos activos que colocar en el mapa.
                        </p>
                    @endif
                </div>
            </div>


            {{-- ---------- LAS FLECHAS ---------- --}}

            <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-cyan-500/15 text-cyan-300">
                        <x-omni-icon name="orbita" size="h-4 w-4" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <h2 class="text-[13px] font-black text-white">Quién manda sobre quién</h2>
                        <p class="text-[10px] leading-relaxed text-slate-500">
                            Una flecha por cada pareja que alguna regla ha puesto en contacto.
                        </p>
                    </div>

                    <span class="shrink-0 rounded-xl border border-slate-800 px-3 py-1.5 font-mono text-[11px] font-black text-cyan-300">
                        {{ $relationships->count() }}
                    </span>
                </div>

                @if ($relationships->isEmpty())
                    <div class="p-10 text-center">
                        <span class="inline-flex text-slate-700"><x-omni-icon name="orbita" size="h-9 w-9" /></span>
                        <p class="mt-2 text-[13px] font-black text-white">Ningún atributo depende de otro</p>
                        <p class="mx-auto mt-1 max-w-md text-[11px] leading-relaxed text-slate-500">
                            Todos se ven siempre. En cuanto escribas la primera regla, aquí aparecerá su
                            flecha sola.
                        </p>
                        <button type="button" @click="tab = 'RULES'"
                            class="mt-3 rounded-xl bg-violet-500 px-4 py-2 text-[11px] font-black text-white transition hover:bg-violet-400">
                            Escribir la primera regla
                        </button>
                    </div>
                @else
                    <div class="grid gap-2 p-4 lg:grid-cols-2">
                        @foreach ($relationships as $relacion)
                            <article class="flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-950 p-2.5">

                                <a href="{{ $relacion->sourceAttribute ? route('attributes.show', $relacion->sourceAttribute) : '#' }}"
                                    class="flex min-w-0 flex-1 items-center gap-2">
                                    @include('attributes.partials.cara', [
                                        'cosa' => $relacion->sourceAttribute,
                                        'tamano' => 'h-9 w-9',
                                        'respaldo' => '◍',
                                    ])
                                    <span class="min-w-0">
                                        <span class="block truncate text-[11px] font-black text-white">
                                            {{ $relacion->sourceAttribute?->name ?? '—' }}
                                        </span>
                                        <span class="block text-[9px] text-slate-600">
                                            nivel {{ $relacion->sourceAttribute?->hierarchy_level ?? '?' }}
                                        </span>
                                    </span>
                                </a>

                                <span class="shrink-0 text-center">
                                    <span class="block font-mono text-[10px] font-black text-cyan-400">→</span>
                                    <span class="block text-[8px] font-black uppercase tracking-wider text-slate-700">manda</span>
                                </span>

                                <a href="{{ $relacion->targetAttribute ? route('attributes.show', $relacion->targetAttribute) : '#' }}"
                                    class="flex min-w-0 flex-1 items-center gap-2">
                                    @include('attributes.partials.cara', [
                                        'cosa' => $relacion->targetAttribute,
                                        'tamano' => 'h-9 w-9',
                                        'respaldo' => '◍',
                                    ])
                                    <span class="min-w-0">
                                        <span class="block truncate text-[11px] font-black text-white">
                                            {{ $relacion->targetAttribute?->name ?? '—' }}
                                        </span>
                                        <span class="block text-[9px] text-slate-600">
                                            nivel {{ $relacion->targetAttribute?->hierarchy_level ?? '?' }}
                                        </span>
                                    </span>
                                </a>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>


            {{-- ---------- SUELTOS ---------- --}}

            @if ($sueltos->isNotEmpty())
                <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                    <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-800 text-slate-400">
                            <x-omni-icon name="panel" size="h-4 w-4" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <h2 class="text-[13px] font-black text-white">Los que se ven siempre</h2>
                            <p class="text-[10px] leading-relaxed text-slate-500">
                                No aparecen en ninguna regla, ni mandando ni obedeciendo. No es un problema
                                —la mayoría de atributos son así—, pero contesta de un vistazo a «¿por qué
                                este sale siempre?».
                            </p>
                        </div>

                        <span class="shrink-0 rounded-xl border border-slate-800 px-3 py-1.5 font-mono text-[11px] font-black text-slate-400">
                            {{ $sueltos->count() }}
                        </span>
                    </div>

                    <div class="grid grid-cols-3 gap-2 p-4 sm:grid-cols-6 lg:grid-cols-9">
                        @foreach ($sueltos as $atributo)
                            <a href="{{ route('attributes.show', $atributo) }}" title="{{ $atributo->name }}"
                                class="group overflow-hidden rounded-xl border border-slate-800 bg-slate-950 transition hover:border-slate-600">
                                <span class="block aspect-square overflow-hidden bg-slate-900">
                                    @if ($atributo->image_url)
                                        <img src="{{ $atributo->image_url }}" alt="" loading="lazy"
                                            class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-lg"
                                            style="color: {{ ($atributo->color ?: '#6366f1') . '77' }}">
                                            {{ $atributo->icon ?: $atributo->data_type_icon }}
                                        </span>
                                    @endif
                                </span>
                                <span class="block truncate px-1.5 py-1 text-center text-[9px] font-black text-slate-400">
                                    {{ $atributo->name }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

        </section>


        {{-- ===================================================== --}}
        {{-- REGLAS --}}
        {{-- ===================================================== --}}

        <section x-show="tab === 'RULES'" x-cloak class="space-y-4">

            @include('attributes.partials.rule-builder')


            {{-- ---------- LAS QUE YA HAY ---------- --}}

            <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                        <x-omni-icon name="chispa" size="h-4 w-4" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <h2 class="text-[13px] font-black text-white">Las reglas que ya hay</h2>
                        <p class="text-[10px] text-slate-500">
                            Se leen de arriba abajo por prioridad: la de número más alto manda.
                        </p>
                    </div>

                    @if ($rules->isNotEmpty())
                        <label class="relative min-w-[140px]">
                            <span class="sr-only">Buscar regla</span>
                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                                <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                            </span>
                            <input type="search" x-model="buscarRegla" placeholder="Buscar…"
                                class="w-full rounded-xl border-slate-800 bg-slate-900 py-2 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                        </label>

                        <select x-model="filtroRegla"
                            class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                            <option value="">Cualquier atributo</option>
                            @foreach ($rules->pluck('targetAttribute')->filter()->unique('id')->sortBy('name') as $objetivo)
                                <option value="{{ $objetivo->id }}">{{ $objetivo->name }}</option>
                            @endforeach
                        </select>

                        <span class="shrink-0 rounded-xl border border-slate-800 px-3 py-1.5 font-mono text-[11px] font-black text-violet-300">
                            {{ $rules->count() }}
                        </span>
                    @endif
                </div>

                @if ($rules->isEmpty())
                    <div class="p-10 text-center">
                        <span class="inline-flex text-slate-700"><x-omni-icon name="chispa" size="h-9 w-9" /></span>
                        <p class="mt-2 text-[13px] font-black text-white">Todavía no hay ninguna regla</p>
                        <p class="mx-auto mt-1 max-w-md text-[11px] leading-relaxed text-slate-500">
                            Sin reglas, todos los atributos activos salen siempre en la ficha de todas las
                            entidades. Que es un punto de partida perfectamente razonable.
                        </p>
                    </div>
                @else
                    <div class="divide-y divide-slate-800/70">
                        @foreach ($rules as $regla)
                            @php
                                [$tonoAccion, $etiquetaAccion, $explicaAccion] = $accionTono[$regla->action] ?? ['#64748b', $regla->action, ''];

                                $objetivo = $regla->targetAttribute;

                                $usoObjetivo = (int) ($usoPorAtributo[$regla->target_attribute_id] ?? 0);

                                $textoBusqueda = mb_strtolower(
                                    trim(
                                        ($regla->name ?? '') . ' ' .
                                        ($objetivo?->name ?? '') . ' ' .
                                        $regla->conditions->map(fn($c) => ($c->sourceAttribute?->name ?? '') . ' ' . ($c->sourceOption?->name ?? ''))->implode(' ')
                                    ),
                                );
                            @endphp

                            {{--
                                Se oculta con x-show y no con x-for: dentro de cada
                                ficha hay un formulario de borrado, y desmontarlo al
                                filtrar dejaría el token fuera del documento.
                            --}}
                            <article x-data="{ abierta: false }"
                                x-show="(! filtroRegla || filtroRegla === '{{ $regla->target_attribute_id }}')
                                        && (! buscarRegla || @js($textoBusqueda).includes(buscarRegla.toLowerCase()))"
                                class="p-3 transition hover:bg-slate-950/40">

                                {{-- La línea resumida --}}
                                <div class="flex flex-wrap items-center gap-2">

                                    @include('attributes.partials.cara', [
                                        'cosa' => $objetivo,
                                        'tamano' => 'h-10 w-10',
                                        'respaldo' => '◍',
                                    ])

                                    <div class="min-w-0 flex-1">
                                        <p class="flex flex-wrap items-center gap-1.5 text-[12px] leading-relaxed text-slate-300">
                                            <span class="text-slate-500">Si</span>

                                            @foreach ($regla->conditions as $indice => $condicion)
                                                @if ($indice > 0)
                                                    <span class="text-[9px] font-black uppercase tracking-wider"
                                                        style="color: {{ $regla->match_mode === 'ALL' ? '#22d3ee' : '#f59e0b' }}">
                                                        {{ $regla->match_mode === 'ALL' ? 'y' : 'o' }}
                                                    </span>
                                                @endif

                                                <span class="inline-flex items-center gap-1 rounded-lg border border-slate-800 bg-slate-950 py-0.5 pl-0.5 pr-1.5">
                                                    @include('attributes.partials.cara', [
                                                        'cosa' => $condicion->sourceAttribute,
                                                        'tamano' => 'h-5 w-5',
                                                        'respaldo' => '◍',
                                                    ])
                                                    <span class="text-[10px] font-black text-white">
                                                        {{ $condicion->sourceAttribute?->name ?? '—' }}
                                                    </span>
                                                    <span class="text-[10px] text-slate-500">
                                                        {{ $operadorTexto[$condicion->operator] ?? $condicion->operator }}
                                                    </span>
                                                    @if ($condicion->sourceOption)
                                                        @include('attributes.partials.cara', [
                                                            'cosa' => $condicion->sourceOption,
                                                            'tamano' => 'h-5 w-5',
                                                        ])
                                                        <span class="text-[10px] font-black text-violet-300">
                                                            {{ $condicion->sourceOption->name }}
                                                        </span>
                                                    @endif
                                                </span>
                                            @endforeach

                                            <span class="text-slate-500">entonces</span>

                                            <span class="rounded-lg px-1.5 py-0.5 text-[10px] font-black uppercase tracking-wider"
                                                style="color: {{ $tonoAccion }}; background-color: {{ $tonoAccion }}22">
                                                {{ $etiquetaAccion }}
                                            </span>

                                            <span class="font-black text-white">{{ $objetivo?->name ?? 'Atributo borrado' }}</span>
                                        </p>

                                        <p class="mt-0.5 flex flex-wrap items-center gap-2 text-[9px] text-slate-600">
                                            @if ($regla->name)
                                                <span class="font-black text-slate-500">{{ $regla->name }}</span>
                                            @endif
                                            <span>prioridad {{ $regla->priority }}</span>
                                            <span>·</span>
                                            <span>{{ $regla->match_mode === 'ALL' ? 'se cumplen todas' : 'basta con una' }}</span>

                                            @if ($usoObjetivo === 0)
                                                <span class="rounded bg-amber-500/15 px-1.5 py-0.5 font-black text-amber-300"
                                                    title="Ninguna entidad tiene este atributo, así que la regla no se aplica a nadie todavía">
                                                    sin efecto aún
                                                </span>
                                            @else
                                                <span>· afecta a {{ $usoObjetivo }}
                                                    {{ $usoObjetivo === 1 ? 'entidad' : 'entidades' }}</span>
                                            @endif
                                        </p>
                                    </div>

                                    <button type="button" @click="abierta = ! abierta"
                                        class="shrink-0 rounded-lg border border-slate-800 px-2 py-1.5 text-[10px] font-black text-slate-400 transition hover:border-slate-600 hover:text-white">
                                        <span x-text="abierta ? 'Cerrar' : 'Detalle'"></span>
                                    </button>

                                    <form method="POST" action="{{ route('attributes.structure.rules.destroy', $regla) }}"
                                        data-omni-confirm data-confirm-variant="danger" data-confirm-title="Eliminar la regla" data-confirm-message="El atributo volverá a comportarse según las demás reglas que le queden." data-confirm-subject="{{ $objetivo?->name }}" data-confirm-action="Sí, eliminarla"
                                        class="shrink-0">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="rounded-lg border border-rose-500/30 px-2 py-1.5 text-[10px] font-black text-rose-300 transition hover:bg-rose-500 hover:text-white">
                                            Borrar
                                        </button>
                                    </form>
                                </div>


                                {{-- El detalle --}}
                                <div x-show="abierta" x-cloak x-collapse class="mt-2.5">
                                    <div class="grid gap-2 rounded-xl border border-slate-800 bg-slate-950 p-3 lg:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)]">

                                        <div class="space-y-1.5">
                                            <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                                Lo que mira
                                            </p>

                                            @foreach ($regla->conditions as $condicion)
                                                <div class="flex items-center gap-2 rounded-lg border border-slate-800 bg-slate-900/60 p-1.5">
                                                    @include('attributes.partials.cara', [
                                                        'cosa' => $condicion->sourceAttribute,
                                                        'tamano' => 'h-8 w-8',
                                                        'respaldo' => '◍',
                                                    ])

                                                    <span class="min-w-0 flex-1">
                                                        <span class="block truncate text-[11px] font-black text-white">
                                                            {{ $condicion->sourceAttribute?->name ?? '—' }}
                                                        </span>
                                                        <span class="block truncate text-[9px] text-slate-500">
                                                            {{ $operadorTexto[$condicion->operator] ?? $condicion->operator }}
                                                            {{ $condicion->sourceOption?->name }}
                                                        </span>
                                                    </span>

                                                    @if ($condicion->sourceOption)
                                                        @include('attributes.partials.cara', [
                                                            'cosa' => $condicion->sourceOption,
                                                            'tamano' => 'h-8 w-8',
                                                        ])
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>

                                        <div class="flex items-center justify-center px-2">
                                            <span class="text-center">
                                                <span class="block font-mono text-lg font-black"
                                                    style="color: {{ $tonoAccion }}">→</span>
                                                <span class="block text-[8px] font-black uppercase tracking-wider text-slate-700">
                                                    entonces
                                                </span>
                                            </span>
                                        </div>

                                        <div class="rounded-lg border p-2.5"
                                            style="border-color: {{ $tonoAccion }}40; background-color: {{ $tonoAccion }}0d">
                                            <p class="text-[9px] font-black uppercase tracking-wider"
                                                style="color: {{ $tonoAccion }}">{{ $etiquetaAccion }}</p>

                                            <div class="mt-1.5 flex items-center gap-2">
                                                @include('attributes.partials.cara', [
                                                    'cosa' => $objetivo,
                                                    'tamano' => 'h-9 w-9',
                                                    'respaldo' => '◍',
                                                ])
                                                <span class="min-w-0">
                                                    <span class="block truncate text-[11px] font-black text-white">
                                                        {{ $objetivo?->name ?? '—' }}
                                                    </span>
                                                    <span class="block text-[9px] text-slate-500">{{ $explicaAccion }}</span>
                                                </span>
                                            </div>

                                            @if ($objetivo)
                                                <a href="{{ route('attributes.show', $objetivo) }}"
                                                    class="mt-2 block text-[10px] font-black underline"
                                                    style="color: {{ $tonoAccion }}">
                                                    Ver el atributo →
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- CATÁLOGOS --}}
        {{-- ===================================================== --}}

        <section x-show="tab === 'CATALOGS'" x-cloak class="space-y-4">

            @include('attributes.partials.catalog-link-builder')


            {{-- ---------- LOS QUE YA HAY ---------- --}}

            <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-fuchsia-500/15 text-fuchsia-300">
                        <x-omni-icon name="capas" size="h-4 w-4" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <h2 class="text-[13px] font-black text-white">Los catálogos que ya están atados</h2>
                        <p class="text-[10px] text-slate-500">
                            Agrupados por el valor que manda.
                        </p>
                    </div>

                    @if ($optionRelationships->isNotEmpty())
                        <label class="relative min-w-[140px]">
                            <span class="sr-only">Buscar</span>
                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                                <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                            </span>
                            <input type="search" x-model="buscarMapeo" placeholder="Buscar valor…"
                                class="w-full rounded-xl border-slate-800 bg-slate-900 py-2 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-fuchsia-500 focus:ring-fuchsia-500">
                        </label>

                        <span class="shrink-0 rounded-xl border border-slate-800 px-3 py-1.5 font-mono text-[11px] font-black text-fuchsia-300">
                            {{ $optionRelationships->count() }}
                        </span>
                    @endif
                </div>

                @if ($optionRelationships->isEmpty())
                    <div class="p-10 text-center">
                        <span class="inline-flex text-slate-700"><x-omni-icon name="capas" size="h-9 w-9" /></span>
                        <p class="mt-2 text-[13px] font-black text-white">Ningún catálogo depende de otro</p>
                        <p class="mx-auto mt-1 max-w-md text-[11px] leading-relaxed text-slate-500">
                            Al rellenar una entidad se ofrecen todos los valores de cada catálogo. Eso está
                            bien hasta que un catálogo crece: entonces conviene que elegir «Perú» deje sólo
                            las regiones del Perú.
                        </p>
                    </div>
                @else
                    <div class="space-y-2 p-4">
                        @foreach ($optionRelationships->groupBy('source_option_id') as $fuenteId => $grupo)
                            @php
                                $fuente = $grupo->first()->sourceOption;

                                $textoGrupo = mb_strtolower(
                                    ($fuente?->name ?? '') . ' ' .
                                    ($fuente?->attribute?->name ?? '') . ' ' .
                                    $grupo->map(fn($r) => ($r->targetOption?->name ?? '') . ' ' . ($r->targetOption?->attribute?->name ?? ''))->implode(' '),
                                );
                            @endphp

                            <article x-show="! buscarMapeo || @js($textoGrupo).includes(buscarMapeo.toLowerCase())"
                                class="rounded-2xl border border-slate-800 bg-slate-950 p-3">

                                <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 pb-2.5">

                                    @include('attributes.partials.cara', [
                                        'cosa' => $fuente,
                                        'tamano' => 'h-10 w-10',
                                    ])

                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-[12px] font-black text-white">
                                            Cuando se elige {{ $fuente?->name ?? '—' }}
                                        </p>
                                        <p class="truncate text-[9px] text-slate-600">
                                            en {{ $fuente?->attribute?->name ?? '—' }}
                                        </p>
                                    </div>

                                    <span class="shrink-0 font-mono text-[10px] text-slate-600">
                                        {{ $grupo->count() }}
                                        {{ $grupo->count() === 1 ? 'consecuencia' : 'consecuencias' }}
                                    </span>
                                </div>

                                <div class="mt-2.5 grid gap-1.5 sm:grid-cols-2">
                                    @foreach ($grupo as $relacion)
                                        @php
                                            $permite = $relacion->relationship_type === 'ALLOWS';
                                            $tonoRelacion = $permite ? '#10b981' : '#f43f5e';
                                        @endphp

                                        <div class="flex items-center gap-2 rounded-xl border p-2"
                                            style="border-color: {{ $tonoRelacion }}33">

                                            <span class="shrink-0 rounded-lg px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider"
                                                style="color: {{ $tonoRelacion }}; background-color: {{ $tonoRelacion }}22">
                                                {{ $permite ? 'permite' : 'bloquea' }}
                                            </span>

                                            @include('attributes.partials.cara', [
                                                'cosa' => $relacion->targetOption,
                                                'tamano' => 'h-8 w-8',
                                            ])

                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-[11px] font-black text-white">
                                                    {{ $relacion->targetOption?->name ?? '—' }}
                                                </span>
                                                <span class="block truncate text-[9px] text-slate-600">
                                                    en {{ $relacion->targetOption?->attribute?->name ?? '—' }}
                                                </span>
                                            </span>

                                            <form method="POST"
                                                action="{{ route('attributes.structure.options.destroy', $relacion) }}"
                                                data-omni-confirm data-confirm-variant="danger" data-confirm-title="Quitar la dependencia" data-confirm-message="Las dos opciones dejan de depender una de la otra." data-confirm-action="Sí, quitarla"
                                                class="shrink-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="Quitar"
                                                    class="rounded-lg px-1.5 py-1 text-[11px] font-black text-slate-600 transition hover:text-rose-300">
                                                    ×
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>

        </section>

    </div>


    <script>
        function constructorDeEstructura(config) {

            return {

                tab: 'STRUCTURE',

                atributos: config.atributos ?? [],

                /* ---------- constructor de reglas ---------- */

                objetivo: '',
                accion: 'SHOW',
                modo: 'ALL',
                prioridad: 0,
                nombreRegla: '',
                buscarObjetivo: '',

                condiciones: [],

                /* ---------- constructor de catálogos ---------- */

                catFuente: '',
                catValorFuente: '',
                catDestino: '',
                catValorDestino: '',
                catTipo: 'ALLOWS',
                catPrioridad: 0,

                /* ---------- filtros de las listas ---------- */

                filtroRegla: '',
                buscarRegla: '',
                buscarMapeo: '',


                init() {
                    this.anadirCondicion();

                    try {
                        const guardada = localStorage.getItem('omnimerge.structure.tab');
                        if (['STRUCTURE', 'RULES', 'CATALOGS'].includes(guardada)) {
                            this.tab = guardada;
                        }
                    } catch (e) {}

                    this.$watch('tab', (valor) => {
                        try {
                            localStorage.setItem('omnimerge.structure.tab', valor);
                        } catch (e) {}
                    });
                },


                /* ---------- consultas sobre el material ---------- */

                atributo(id) {
                    return this.atributos.find(
                        item => String(item.id) === String(id)
                    ) ?? null;
                },

                opcionesDe(id) {
                    return this.atributo(id)?.options ?? [];
                },

                opcion(atributoId, opcionId) {
                    return this.opcionesDe(atributoId).find(
                        item => String(item.id) === String(opcionId)
                    ) ?? null;
                },

                get catalogos() {
                    return this.atributos.filter(
                        item => item.data_type === 'OPTION'
                    );
                },


                /* ---------- condiciones ---------- */

                anadirCondicion() {
                    this.condiciones.push({
                        key: `${Date.now()}-${Math.random()}`,
                        source_attribute_id: '',
                        operator: 'EQUALS',
                        source_option_id: '',
                    });
                },

                quitarCondicion(indice) {
                    this.condiciones.splice(indice, 1);
                },

                /*
                 * Al cambiar el atributo de una condicion, el valor que hubiera
                 * elegido antes pertenece a otro catalogo: se tira.
                 */
                elegirFuente(condicion, id) {
                    condicion.source_attribute_id = String(id);
                    condicion.source_option_id = '';
                },

                /*
                 * «Tiene algun valor» y «esta vacio» no miran un valor concreto,
                 * asi que el que estuviera elegido deja de tener sentido.
                 */
                cambiarOperador(condicion, operador) {
                    condicion.operator = operador;

                    if (!['EQUALS', 'NOT_EQUALS'].includes(operador)) {
                        condicion.source_option_id = '';
                    }
                },


                /* ---------- catálogos ---------- */

                elegirCatalogoFuente(id) {
                    this.catFuente = String(id);
                    this.catValorFuente = '';
                },

                elegirCatalogoDestino(id) {
                    this.catDestino = String(id);
                    this.catValorDestino = '';
                },


                /* ---------- si se puede guardar ---------- */

                get reglaCompleta() {
                    if (!this.objetivo) return false;

                    return this.condiciones.every(condicion => {
                        if (!condicion.source_attribute_id) return false;

                        if (['EQUALS', 'NOT_EQUALS'].includes(condicion.operator)) {
                            return !!condicion.source_option_id;
                        }

                        return true;
                    });
                },

                get mapeoCompleto() {
                    return !!this.catValorFuente && !!this.catValorDestino;
                },
            };
        }
    </script>

</x-app-layout>
