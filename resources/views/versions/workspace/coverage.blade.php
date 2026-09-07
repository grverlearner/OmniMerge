@php
    /*
     * Cobertura: a quién le falta cada molde.
     *
     * Antes esta pantalla era una lista de barras: decía que a «Naruto
     * clásico» le faltaban 16 entidades y ahí se acababa. Un número que no se
     * puede tocar no sirve de nada.
     *
     * Ahora cada fila se abre y enseña POR NOMBRE Y CON FOTO a quién le falta,
     * con el botón que lleva a aplicársela. Y se mide de tres maneras
     * distintas, porque no todas las definiciones significan lo mismo:
     *
     *   AUTO       tiene reglas de catálogo: solo cuentan las entidades que
     *              cumplen esas reglas
     *   MANUAL     compartida sin reglas: la puede aplicar cualquiera, así
     *              que la base es la biblioteca entera
     *   EXCLUSIVA  reservada para una sola entidad: medirla contra la
     *              biblioteca no significaría nada, así que no se mide
     */

    $modos = [
        'AUTO' => ['Por catálogo', 'border-cyan-500/30 bg-cyan-500/10 text-cyan-300'],
        'MANUAL' => ['Alcance libre', 'border-violet-500/30 bg-violet-500/10 text-violet-300'],
        'EXCLUSIVE' => ['Exclusiva', 'border-sky-500/30 bg-sky-500/10 text-sky-300'],
    ];

    $estados = [
        '' => 'Todas las definiciones',
        'INCOMPLETE' => 'Solo las que tienen huecos',
        'COMPLETE' => 'Solo las completas',
        'AUTO' => 'Solo las de catálogo',
        'MANUAL' => 'Solo las de alcance libre',
    ];

    $ordenes = [
        'missing' => 'Las que más faltan',
        'worst' => 'Menor cobertura primero',
        'best' => 'Mayor cobertura primero',
        'name' => 'Por nombre (A–Z)',
    ];

    $filtrando = $search !== '' || $estado !== '';
@endphp

<x-app-layout title="Cobertura de versiones" surface="dark">

    <x-slot name="header">Versiones</x-slot>

    <div class="space-y-4">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-end gap-4">

            <div class="min-w-0 flex-1">
                <a href="{{ route('versions.index') }}"
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-violet-400">
                    ← Definiciones
                </a>

                <h1 class="mt-1 text-xl font-black tracking-tight text-white">Cobertura</h1>

                <p class="mt-0.5 text-[11px] text-slate-500">
                    Qué entidades ya llevan cada molde, y —lo importante— a cuáles les falta.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
                @foreach ([['Definiciones', $resumen['definiciones'], 'text-white'], ['Completas', $resumen['completas'], 'text-emerald-300'], ['Huecos', $resumen['faltantes'], 'text-amber-300'], ['Media', $resumen['media'] . '%', 'text-cyan-300'], ['Entidades', $resumen['entidades'], 'text-indigo-300']] as [$etiqueta, $valor, $tono])
                    <span class="flex items-baseline gap-1.5 rounded-xl border border-slate-800 bg-slate-900/50 px-2.5 py-1.5">
                        <span class="font-mono text-base font-black {{ $tono }}">{{ $valor }}</span>
                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">{{ $etiqueta }}</span>
                    </span>
                @endforeach
            </div>

        </header>


        @include('versions.partials.workspace-navigation')


        {{-- ===================================================== --}}
        {{-- FILTROS --}}
        {{-- ===================================================== --}}

        <div class="sticky top-20 z-20 rounded-2xl border border-slate-800 bg-slate-950/95 backdrop-blur">

            <form method="GET" action="{{ route('versions.coverage') }}"
                class="flex flex-wrap items-center gap-2 px-4 py-3">

                <label class="relative min-w-[180px] flex-1">
                    <span class="sr-only">Buscar definición</span>
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                        <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                    </span>
                    <input type="search" name="search" value="{{ $search }}" placeholder="Buscar definición..."
                        class="w-full rounded-xl border-slate-800 bg-slate-900 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-cyan-500 focus:ring-cyan-500">
                </label>

                @foreach ([['state', $estados, $estado], ['sort', $ordenes, $sort]] as [$campo, $opciones, $actual])
                    <select name="{{ $campo }}" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-cyan-500 focus:ring-cyan-500">
                        @foreach ($opciones as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected((string) $actual === (string) $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                @endforeach

                <button type="submit"
                    class="rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-cyan-500 hover:text-cyan-300">
                    Aplicar
                </button>

                @if ($filtrando)
                    <a href="{{ route('versions.coverage') }}"
                        class="rounded-xl border border-rose-500/30 px-3 py-2 text-[11px] font-black text-rose-300 transition hover:bg-rose-500/10">
                        Quitar filtros
                    </a>
                @endif

                @if ($resumen['faltantes'] > 0)
                    <span class="ml-auto rounded-xl border border-amber-500/25 bg-amber-500/5 px-3 py-2 text-[10px] font-black text-amber-300">
                        {{ $resumen['faltantes'] }} aplicaciones pendientes en total
                    </span>
                @endif

            </form>

        </div>


        {{-- ===================================================== --}}
        {{-- LAS FILAS --}}
        {{-- ===================================================== --}}

        @if ($coverageRows->isEmpty())

            <div class="rounded-2xl border border-dashed border-slate-800 py-16 text-center">
                <span class="inline-flex text-slate-700"><x-omni-icon name="barras" size="h-10 w-10" /></span>

                <h2 class="mt-3 text-lg font-black text-white">
                    {{ $filtrando ? 'Ninguna definición encaja con el filtro' : 'Todavía no hay nada que medir' }}
                </h2>

                <p class="mx-auto mt-1.5 max-w-md text-xs leading-relaxed text-slate-500">
                    {{ $filtrando
                        ? 'Prueba con «Todas las definiciones»: puede que ya no queden huecos del tipo que estás buscando.'
                        : 'La cobertura compara los moldes activos con tus entidades. Crea una definición y esta pantalla empezará a decirte a quién le falta.' }}
                </p>
            </div>

        @else

            <div class="space-y-2.5">

                @foreach ($coverageRows as $fila)
                    @php
                        $version = $fila['version'];
                        [$modoEtiqueta, $modoTono] = $modos[$fila['mode']];
                        $pct = $fila['percentage'];

                        /*
                         * «Sin huecos» y «no hay nadie que pueda llevarla» dan
                         * los dos cero faltantes, y son cosas opuestas: la
                         * primera es un trabajo terminado y la segunda una
                         * regla de catálogo que no encaja con ninguna entidad.
                         * Decirle «sin huecos» a la segunda sería mentir.
                         */
                        $nadieEncaja = $fila['eligible'] === 0;

                        $completa = $fila['missing'] === 0 && !$nadieEncaja;

                        $tonoBarra = match (true) {
                            $pct === null => 'bg-slate-700',
                            $pct >= 90 => 'bg-emerald-500',
                            $pct >= 50 => 'bg-cyan-500',
                            $pct >= 20 => 'bg-amber-500',
                            default => 'bg-rose-500',
                        };

                        $tonoCifra = match (true) {
                            $pct === null => 'text-slate-600',
                            $pct >= 90 => 'text-emerald-300',
                            $pct >= 50 => 'text-cyan-300',
                            $pct >= 20 => 'text-amber-300',
                            default => 'text-rose-300',
                        };
                    @endphp

                    <article x-data="{ abierto: false }"
                        class="overflow-hidden rounded-2xl border bg-slate-900/50 transition {{ $completa ? 'border-emerald-500/25' : 'border-slate-800' }}">

                        {{-- ---------- LA LÍNEA ---------- --}}

                        <div class="flex flex-wrap items-center gap-3 p-3">

                            <a href="{{ route('versions.show', $version) }}"
                                class="h-12 w-12 shrink-0 overflow-hidden rounded-xl border border-slate-800 bg-slate-950">
                                @if ($version->image_url)
                                    <img src="{{ $version->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-violet-400">◈</span>
                                @endif
                            </a>

                            <div class="min-w-0 flex-1">

                                <div class="flex flex-wrap items-center gap-1.5">
                                    <a href="{{ route('versions.show', $version) }}"
                                        class="truncate text-[13px] font-black text-white transition hover:text-violet-300">
                                        {{ $version->name }}
                                    </a>

                                    <span class="rounded-lg border px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $modoTono }}">
                                        {{ $modoEtiqueta }}
                                    </span>

                                    <span class="rounded-lg border border-slate-800 px-1.5 py-0.5 text-[9px] font-bold text-slate-500">
                                        {{ $version->kind_label }}
                                    </span>
                                </div>

                                @if ($fila['mode'] === 'EXCLUSIVE')

                                    <p class="mt-1 text-[10px] text-slate-500">
                                        Reservada para una sola entidad, así que no se mide contra la biblioteca.
                                        Ahora mismo está aplicada en
                                        <strong class="text-sky-300">{{ $fila['covered'] }}</strong>.
                                    </p>

                                @elseif ($nadieEncaja)

                                    <p class="mt-1 text-[10px] leading-relaxed text-slate-500">
                                        <span class="font-black text-amber-300">Ninguna entidad cumple sus reglas.</span>
                                        Sus reglas de catálogo no coinciden con nada de tu biblioteca, así que no
                                        hay a quién aplicársela automáticamente. Revisa las reglas, o aplícala a mano.
                                    </p>

                                @else

                                    <div class="mt-1.5 flex items-center gap-2.5">
                                        <span class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-950">
                                            <span class="block h-full rounded-full {{ $tonoBarra }} transition-all"
                                                style="width: {{ max($pct, 2) }}%"></span>
                                        </span>

                                        <span class="shrink-0 font-mono text-[10px] font-black text-slate-500">
                                            {{ $fila['covered'] }}/{{ $fila['eligible'] }}
                                        </span>
                                    </div>

                                    <p class="mt-1 text-[10px] text-slate-500">
                                        @if ($completa)
                                            <span class="font-black text-emerald-300">Sin huecos.</span>
                                            Todas las entidades que pueden llevarla ya la llevan.
                                        @else
                                            Le falta a
                                            <strong class="text-amber-300">{{ $fila['missing'] }}</strong>
                                            {{ $fila['missing'] === 1 ? 'entidad' : 'entidades' }}
                                            @if ($fila['mode'] === 'AUTO')
                                                que cumplen sus reglas de catálogo.
                                            @else
                                                de tu biblioteca. Nada la restringe, así que cuentan todas.
                                            @endif
                                        @endif
                                    </p>

                                @endif

                            </div>

                            @if ($pct !== null && ! $nadieEncaja)
                                <span class="shrink-0 font-mono text-2xl font-black {{ $tonoCifra }}">{{ $pct }}%</span>
                            @endif

                            <div class="flex shrink-0 items-center gap-1.5">

                                @if (!$completa && $fila['missing_entities']->isNotEmpty())
                                    <button type="button" @click="abierto = !abierto"
                                        class="flex items-center gap-1.5 rounded-xl border border-slate-800 px-2.5 py-2 text-[10px] font-black text-slate-300 transition hover:border-amber-500 hover:text-amber-300">
                                        <span x-text="abierto ? 'Ocultar' : 'Ver a quién le falta'"></span>
                                        <span class="transition" :class="abierto ? 'rotate-90' : ''">
                                            <x-omni-icon name="chevron-derecha" size="h-3 w-3" />
                                        </span>
                                    </button>
                                @endif

                                @can('update', $version)
                                    <a href="{{ route('versions.entities.bulk.create', $version) }}"
                                        class="rounded-xl bg-violet-500/15 px-3 py-2 text-[10px] font-black text-violet-300 transition hover:bg-violet-500 hover:text-white">
                                        Aplicar en lote
                                    </a>
                                @endcan

                                <a href="{{ route('versions.entities.index', ['version' => $version->id]) }}"
                                    title="Ver las que ya la llevan"
                                    class="rounded-xl border border-slate-800 px-2.5 py-2 text-[10px] font-black text-slate-400 transition hover:border-violet-500 hover:text-violet-300">
                                    Ya la llevan
                                </a>
                            </div>

                        </div>


                        {{-- ---------- A QUIÉN LE FALTA ---------- --}}

                        <div x-show="abierto" x-cloak x-collapse class="border-t border-slate-800 bg-slate-950/40">

                            @if ($fila['mode'] === 'AUTO' && $fila['options']->isNotEmpty())
                                <div class="flex flex-wrap items-center gap-1.5 border-b border-slate-800/70 px-3 py-2">
                                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                        La activan
                                    </span>

                                    @foreach ($fila['options'] as $enlace)
                                        @if ($enlace->option)
                                            <span class="rounded-lg border border-cyan-500/25 bg-cyan-500/5 px-2 py-0.5 text-[10px] font-bold text-cyan-300">
                                                {{ $enlace->option->attribute?->name }}: {{ $enlace->option->name }}
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            @endif

                            <div class="grid grid-cols-2 gap-2 p-3 sm:grid-cols-3 lg:grid-cols-6">

                                @foreach ($fila['missing_entities'] as $entidad)
                                    <div class="group overflow-hidden rounded-xl border border-slate-800 bg-slate-950 transition hover:border-amber-500/40">

                                        <a href="{{ route('entities.show', $entidad) }}"
                                            class="relative block aspect-square overflow-hidden bg-slate-900">
                                            @if ($entidad->image_url)
                                                <img src="{{ $entidad->image_url }}" alt="" loading="lazy"
                                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                            @else
                                                <span class="flex h-full w-full items-center justify-center text-2xl text-slate-800">◍</span>
                                            @endif
                                        </a>

                                        <div class="p-1.5">
                                            <p class="truncate text-[10px] font-black text-white">{{ $entidad->name }}</p>

                                            @can('update', $entidad)
                                                <a href="{{ route('versions.entities.bulk.create', ['version' => $version, 'search' => $entidad->name]) }}"
                                                    class="mt-1 block rounded-lg bg-violet-500/15 py-1 text-center text-[9px] font-black text-violet-300 transition hover:bg-violet-500 hover:text-white">
                                                    Aplicársela
                                                </a>
                                            @endcan
                                        </div>

                                    </div>
                                @endforeach

                            </div>

                            @if ($fila['missing'] > $fila['missing_entities']->count())
                                <p class="border-t border-slate-800/70 px-3 py-2 text-[10px] text-slate-500">
                                    Y {{ $fila['missing'] - $fila['missing_entities']->count() }} más.
                                    <a href="{{ route('versions.entities.bulk.create', $version) }}"
                                        class="font-black text-violet-300 underline transition hover:text-violet-200">
                                        Aplicarla a varias a la vez →
                                    </a>
                                </p>
                            @endif

                        </div>

                    </article>
                @endforeach

            </div>

            <p class="px-1 text-[10px] leading-relaxed text-slate-600">
                Solo se miden las definiciones <strong class="text-slate-400">activas</strong>. Una
                definición <strong class="text-slate-400">por catálogo</strong> se compara con las
                entidades que cumplen sus reglas; una de <strong class="text-slate-400">alcance
                libre</strong> no tiene nada que la restrinja, así que se compara con la biblioteca
                entera —y por eso es normal que su porcentaje sea bajo—.
            </p>

        @endif

    </div>

</x-app-layout>
