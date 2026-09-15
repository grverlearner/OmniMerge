@php
    /*
     * El directorio de creadores.
     *
     * Cinco formas de mirar a la misma gente, y una de ellas es la que no se
     * podía hacer en ninguna parte: el mapa, que parte la comunidad en tres
     * —solo biblioteca, completos, solo torneos— y enseña de un vistazo cómo
     * está repartida.
     *
     * Ver docs/md/78-Comunidad.md
     */

    $hayFiltros = $q !== '' || $tipo !== 'todos';
@endphp

<x-community-layout surface="dark">

    <x-slot name="header">Creadores</x-slot>

    <div x-data="directorioDeCreadores()" class="space-y-3">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-end gap-4">
            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600">Comunidad · Personas</p>
                <h1 class="mt-1 text-xl font-black tracking-tight text-white">Quién hace la comunidad</h1>
                <p class="mt-0.5 max-w-2xl text-[11px] text-slate-500">
                    Todos los que han publicado algo, de la biblioteca o de torneos. Cada uno con su tipo:
                    <span style="color: {{ $tipos['completo'][1] }}">completo</span> si publica en los dos lados,
                    <span style="color: {{ $tipos['biblioteca'][1] }}">de biblioteca</span> o
                    <span style="color: {{ $tipos['torneos'][1] }}">de torneos</span> si solo en uno.
                </p>
            </div>

            <a href="{{ route('profiles.show', auth()->user()->username) }}"
                class="flex items-center gap-1.5 rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-3 py-2 text-[11px] font-black text-emerald-300 transition hover:bg-emerald-500 hover:text-slate-950">
                <x-omni-icon name="chispa" size="h-3.5 w-3.5" />
                Cómo me ven
            </a>
        </header>


        {{-- ===================================================== --}}
        {{-- LOS CUATRO TIPOS, QUE TAMBIÉN SON EL FILTRO --}}
        {{-- ===================================================== --}}

        <section class="grid grid-cols-2 gap-2 lg:grid-cols-4">
            @foreach ([['todos', 'Todos', '#94a3b8', 'publican algo'], ['completo', 'Completos', $tipos['completo'][1], 'biblioteca y torneos'], ['biblioteca', 'De biblioteca', $tipos['biblioteca'][1], 'solo entidades y demás'], ['torneos', 'De torneos', $tipos['torneos'][1], 'solo plantillas']] as [$clave, $texto, $tono, $pie])
                <a href="{{ route('community.creators.index', array_filter(['tipo' => $clave === 'todos' ? null : $clave, 'q' => $q ?: null, 'orden' => $orden !== 'publicado' ? $orden : null])) }}"
                    class="rounded-xl border px-3 py-2 transition hover:-translate-y-0.5"
                    style="{{ $tipo === $clave ? "border-color: {$tono}; background-color: {$tono}14" : 'border-color: #1e293b' }}">
                    <span class="block font-mono text-2xl font-black leading-none"
                        style="color: {{ $cuentas[$clave] > 0 ? $tono : '#475569' }}">{{ $cuentas[$clave] }}</span>
                    <span class="mt-1 block text-[9px] font-black uppercase tracking-wider text-slate-500">{{ $texto }}</span>
                    <span class="block truncate text-[9px] text-slate-600">{{ $pie }}</span>
                </a>
            @endforeach
        </section>


        {{-- ===================================================== --}}
        {{-- BUSCAR, ORDENAR Y FORMA DE MIRAR --}}
        {{-- ===================================================== --}}

        <section class="sticky top-2 z-20 rounded-2xl border border-slate-800 bg-slate-900/95 p-2 backdrop-blur">
            <div class="flex flex-wrap items-center gap-2">

                <form method="GET" action="{{ route('community.creators.index') }}" class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
                    @if ($tipo !== 'todos')
                        <input type="hidden" name="tipo" value="{{ $tipo }}">
                    @endif

                    <label class="relative min-w-[160px] flex-1">
                        <span class="sr-only">Buscar un creador</span>
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                            <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                        </span>
                        <input type="search" name="q" value="{{ $q }}" placeholder="Nombre, usuario o presentación…"
                            class="w-full rounded-xl border-slate-800 bg-slate-950 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-emerald-500 focus:ring-emerald-500">
                    </label>

                    <select name="orden" onchange="this.form.submit()"
                        class="rounded-xl border-slate-800 bg-slate-950 py-2 text-[11px] font-bold text-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                        @foreach (['publicado' => 'Los que más han publicado', 'copiado' => 'Los más copiados', 'reciente' => 'Los que publicaron antes', 'nombre' => 'Por nombre'] as $valor => $texto)
                            <option value="{{ $valor }}" @selected($orden === $valor)>{{ $texto }}</option>
                        @endforeach
                    </select>

                    <button type="submit"
                        class="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-emerald-500 hover:text-emerald-300">
                        Buscar
                    </button>

                    @if ($hayFiltros)
                        <a href="{{ route('community.creators.index') }}"
                            class="rounded-xl px-2 py-2 text-[10px] font-black text-slate-500 underline transition hover:text-slate-300">
                            Quitar filtros
                        </a>
                    @endif
                </form>

                <span class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-950 p-1">
                    @foreach ([['mapa', 'orbita', 'Mapa: la comunidad partida en tres'], ['galeria', 'galeria', 'Galería: cada creador con sus caras'], ['cuadricula', 'cuadricula', 'Cuadrícula: solo las caras'], ['lista', 'panel', 'Lista: una línea por creador'], ['tabla', 'capas', 'Tabla: todas las cifras']] as [$modo, $icono, $ayuda])
                        <button type="button" @click="vista = '{{ $modo }}'" title="{{ $ayuda }}"
                            :aria-pressed="vista === '{{ $modo }}'"
                            :class="vista === '{{ $modo }}' ? 'bg-emerald-500 text-slate-950' : 'text-slate-500 hover:text-slate-200'"
                            class="rounded-lg px-2 py-1.5 transition">
                            <x-omni-icon :name="$icono" size="h-4 w-4" />
                        </button>
                    @endforeach
                </span>

                <span x-show="vista === 'cuadricula'" x-cloak class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-950 p-1">
                    <button type="button" @click="columnas = Math.max(3, columnas - 1)" :disabled="columnas === 3"
                        class="rounded-lg px-2 py-1.5 text-slate-500 transition hover:text-slate-200 disabled:opacity-30">
                        <x-omni-icon name="chevron-izquierda" size="h-3.5 w-3.5" />
                    </button>
                    <span class="w-3 text-center font-mono text-[10px] font-black text-slate-500" x-text="columnas"></span>
                    <button type="button" @click="columnas = Math.min(8, columnas + 1)" :disabled="columnas === 8"
                        class="rounded-lg px-2 py-1.5 text-slate-500 transition hover:text-slate-200 disabled:opacity-30">
                        <x-omni-icon name="chevron-derecha" size="h-3.5 w-3.5" />
                    </button>
                </span>
            </div>
        </section>


        @if ($creadores->isEmpty())

            <section class="rounded-2xl border border-dashed border-slate-800 py-14 text-center">
                <span class="inline-flex text-slate-700"><x-omni-icon name="usuario" size="h-9 w-9" /></span>
                <p class="mt-2 text-[13px] font-black text-white">
                    {{ $hayFiltros ? 'Nadie encaja con eso' : 'Todavía nadie ha publicado nada' }}
                </p>
                <p class="mx-auto mt-1 max-w-md text-[11px] leading-relaxed text-slate-500">
                    @if ($hayFiltros)
                        Puede que no haya creadores de ese tipo todavía, o que el nombre no coincida.
                    @else
                        En cuanto alguien publique una entidad, una colección, un atributo o una plantilla,
                        aparecerá aquí.
                    @endif
                </p>
            </section>

        @else

            {{-- ---------- MAPA ---------- --}}

            <section x-show="vista === 'mapa'" class="grid gap-2 lg:grid-cols-3">
                @foreach (['biblioteca', 'completo', 'torneos'] as $clave)
                    @php
                        [$etiquetaTipo, $tonoTipo, $ayudaTipo] = $tipos[$clave];
                        $grupo = $creadores->where('tipo_creador', $clave)->values();
                    @endphp

                    <div class="overflow-hidden rounded-2xl border bg-slate-900/40"
                        style="border-color: {{ $tonoTipo }}{{ $clave === 'completo' ? '66' : '33' }}">

                        <div class="flex items-center gap-2 px-3 py-2.5" style="background: linear-gradient(120deg, {{ $tonoTipo }}22, transparent 70%)">
                            <span class="h-7 w-1.5 shrink-0 rounded-full" style="background-color: {{ $tonoTipo }}"></span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-[13px] font-black" style="color: {{ $tonoTipo }}">{{ $etiquetaTipo }}</span>
                                <span class="block text-[9px] text-slate-500">{{ $ayudaTipo }}</span>
                            </span>
                            <span class="font-mono text-xl font-black" style="color: {{ $grupo->isNotEmpty() ? $tonoTipo : '#475569' }}">{{ $grupo->count() }}</span>
                        </div>

                        @if ($grupo->isEmpty())
                            <p class="px-3 py-6 text-center text-[11px] text-slate-600">
                                Nadie {{ $clave === 'completo' ? 'publica en los dos lados' : ($clave === 'biblioteca' ? 'publica solo en la biblioteca' : 'publica solo torneos') }}
                                {{ $hayFiltros ? 'con este filtro' : 'todavía' }}.
                            </p>
                        @else
                            <div class="flex flex-wrap gap-2 p-3">
                                @foreach ($grupo as $creador)
                                    <a href="{{ route('profiles.show', $creador->username) }}"
                                        class="group flex w-20 flex-col items-center" title="{{ $creador->name }} · {{ $creador->pub_total }} publicadas">
                                        <span class="block h-16 w-16 overflow-hidden rounded-2xl border-2 bg-slate-950 transition group-hover:-translate-y-0.5"
                                            style="border-color: {{ $tonoTipo }}88">
                                            @if ($creador->avatar_url)
                                                <img src="{{ $creador->avatar_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                            @else
                                                <span class="flex h-full w-full items-center justify-center text-[15px] font-black text-slate-500">{{ $creador->initials }}</span>
                                            @endif
                                        </span>
                                        <span class="mt-1 block w-full truncate text-center text-[10px] font-black text-slate-300">{{ $creador->name }}</span>
                                        <span class="block font-mono text-[9px] text-slate-600">{{ $creador->pub_total }} publ.</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </section>


            {{-- ---------- GALERÍA ---------- --}}

            <section x-show="vista === 'galeria'" x-cloak class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($creadores as $creador)
                    @include('community.creators.partials.tarjeta')
                @endforeach
            </section>


            {{-- ---------- CUADRÍCULA ---------- --}}

            <section x-show="vista === 'cuadricula'" x-cloak class="grid gap-2" :class="rejilla">
                @foreach ($creadores as $creador)
                    @php [$etiquetaTipo, $tonoTipo] = $tipos[$creador->tipo_creador]; @endphp
                    <a href="{{ route('profiles.show', $creador->username) }}" title="{{ $creador->name }} · {{ $etiquetaTipo }}"
                        class="group block overflow-hidden rounded-xl border bg-slate-900/50 transition hover:-translate-y-0.5"
                        style="border-color: {{ $tonoTipo }}44">
                        <span class="relative block aspect-square overflow-hidden bg-slate-950">
                            @if ($creador->avatar_url)
                                <img src="{{ $creador->avatar_url }}" alt="" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-[20px] font-black text-slate-600">{{ $creador->initials }}</span>
                            @endif
                            <span class="absolute left-1.5 top-1.5 h-2.5 w-2.5 rounded-full ring-2 ring-slate-950" style="background-color: {{ $tonoTipo }}"></span>
                        </span>
                        <span class="block truncate px-2 py-1.5 text-center text-[11px] font-black text-slate-300">{{ $creador->name }}</span>
                    </a>
                @endforeach
            </section>


            {{-- ---------- LISTA ---------- --}}

            <section x-show="vista === 'lista'" x-cloak class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">
                @foreach ($creadores as $creador)
                    @php
                        [$etiquetaTipo, $tonoTipo] = $tipos[$creador->tipo_creador];
                        $susCaras = $caras[$creador->id] ?? collect();
                    @endphp
                    <div class="flex flex-wrap items-center gap-3 border-b border-slate-800/70 px-3 py-2 transition hover:bg-slate-950/50">
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background-color: {{ $tonoTipo }}" title="{{ $etiquetaTipo }}"></span>

                        <a href="{{ route('profiles.show', $creador->username) }}" class="h-11 w-11 shrink-0 overflow-hidden rounded-xl border bg-slate-950" style="border-color: {{ $tonoTipo }}55">
                            @if ($creador->avatar_url)
                                <img src="{{ $creador->avatar_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-[13px] font-black text-slate-500">{{ $creador->initials }}</span>
                            @endif
                        </a>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <a href="{{ route('profiles.show', $creador->username) }}" class="truncate text-[13px] font-black text-white transition hover:text-emerald-300">{{ $creador->name }}</a>
                                <span class="font-mono text-[9px] text-slate-600">&#64;{{ $creador->username }}</span>
                                <span class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider" style="color: {{ $tonoTipo }}; background-color: {{ $tonoTipo }}1f">{{ $etiquetaTipo }}</span>
                            </div>
                            @if ($susCaras->isNotEmpty())
                                <div class="mt-1 flex -space-x-1.5">
                                    @foreach ($susCaras as $cara)
                                        <span class="h-5 w-5 shrink-0 overflow-hidden rounded-full border border-slate-900 bg-slate-950">
                                            <img src="{{ $cara->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <span class="hidden shrink-0 items-center gap-3 font-mono text-[10px] sm:flex">
                            @foreach ([[$creador->pub_biblioteca, 'biblio', '#818cf8'], [$creador->pub_torneos, 'torneos', '#fbbf24'], [$creador->copias_total, 'copias', '#34d399']] as [$valor, $etiqueta, $tono])
                                <span class="text-center">
                                    <span class="block font-black" style="color: {{ $valor > 0 ? $tono : '#475569' }}">{{ $valor }}</span>
                                    <span class="block text-[8px] uppercase tracking-wider text-slate-700">{{ $etiqueta }}</span>
                                </span>
                            @endforeach
                        </span>

                        <a href="{{ route('profiles.show', $creador->username) }}"
                            class="shrink-0 rounded-lg border border-slate-800 px-2.5 py-1.5 text-[10px] font-black text-slate-400 transition hover:border-emerald-500 hover:text-emerald-300">
                            Ver perfil
                        </a>
                    </div>
                @endforeach
            </section>


            {{-- ---------- TABLA ---------- --}}

            <section x-show="vista === 'tabla'" x-cloak class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[860px]">
                        <thead class="border-b border-slate-800 text-left">
                            <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                <th class="px-4 py-2.5">Creador</th>
                                <th class="px-3 py-2.5">Tipo</th>
                                <th class="px-3 py-2.5 text-right">Entidades</th>
                                <th class="px-3 py-2.5 text-right">Colecciones</th>
                                <th class="px-3 py-2.5 text-right">Atributos</th>
                                <th class="px-3 py-2.5 text-right">Torneos</th>
                                <th class="px-3 py-2.5 text-right">Fases</th>
                                <th class="px-3 py-2.5 text-right">Copias</th>
                                <th class="px-3 py-2.5 text-right">Última publicación</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/70">
                            @foreach ($creadores as $creador)
                                @php [$etiquetaTipo, $tonoTipo] = $tipos[$creador->tipo_creador]; @endphp
                                <tr class="transition hover:bg-slate-950/50">
                                    <td class="px-4 py-2">
                                        <a href="{{ route('profiles.show', $creador->username) }}" class="flex items-center gap-2">
                                            <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border bg-slate-950" style="border-color: {{ $tonoTipo }}55">
                                                @if ($creador->avatar_url)
                                                    <img src="{{ $creador->avatar_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                                @else
                                                    <span class="flex h-full w-full items-center justify-center text-[11px] font-black text-slate-500">{{ $creador->initials }}</span>
                                                @endif
                                            </span>
                                            <span class="min-w-0">
                                                <span class="block truncate text-[12px] font-black text-white">{{ $creador->name }}</span>
                                                <span class="block font-mono text-[9px] text-slate-600">&#64;{{ $creador->username }}</span>
                                            </span>
                                        </a>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider" style="color: {{ $tonoTipo }}; background-color: {{ $tonoTipo }}1f">{{ $etiquetaTipo }}</span>
                                    </td>
                                    @foreach ([[$creador->pub_entidades_count, '#818cf8'], [$creador->pub_colecciones_count, '#34d399'], [$creador->pub_atributos_count, '#22d3ee'], [$creador->pub_torneos_count, '#fbbf24'], [$creador->pub_fases_count, '#f472b6'], [$creador->copias_total, '#34d399']] as [$valor, $tono])
                                        <td class="px-3 py-2 text-right font-mono text-[11px] font-black" style="color: {{ $valor > 0 ? $tono : '#475569' }}">{{ $valor }}</td>
                                    @endforeach
                                    <td class="px-3 py-2 text-right font-mono text-[10px] text-slate-500">
                                        {{ $creador->ultima_publicacion ? $creador->ultima_publicacion->diffForHumans() : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif


        @if ($sinPublicar > 0 && ! $hayFiltros)
            <p class="px-1 text-[10px] leading-4 text-slate-600">
                Hay {{ $sinPublicar }} {{ $sinPublicar === 1 ? 'perfil visible que no ha' : 'perfiles visibles que no han' }}
                publicado nada todavía, así que no {{ $sinPublicar === 1 ? 'aparece' : 'aparecen' }} en el directorio.
            </p>
        @endif
    </div>


    <script>
        function directorioDeCreadores() {
            return {
                vista: 'mapa',
                columnas: 6,

                init() {
                    try {
                        const g = JSON.parse(localStorage.getItem('omnimerge.creadores') ?? '{}');
                        if (['mapa', 'galeria', 'cuadricula', 'lista', 'tabla'].includes(g.vista)) this.vista = g.vista;
                        if (g.columnas >= 3 && g.columnas <= 8) this.columnas = g.columnas;
                    } catch (e) {
                        /* sin memoria, valores de fábrica */
                    }

                    ['vista', 'columnas'].forEach((campo) => this.$watch(campo, () => {
                        localStorage.setItem('omnimerge.creadores', JSON.stringify({ vista: this.vista, columnas: this.columnas }));
                    }));
                },

                /* Literales: Tailwind no genera clases compuestas al vuelo */
                get rejilla() {
                    return {
                        3: 'grid-cols-2 sm:grid-cols-3',
                        4: 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4',
                        5: 'grid-cols-3 sm:grid-cols-4 lg:grid-cols-5',
                        6: 'grid-cols-3 sm:grid-cols-4 lg:grid-cols-6',
                        7: 'grid-cols-3 sm:grid-cols-5 lg:grid-cols-7',
                        8: 'grid-cols-4 sm:grid-cols-6 lg:grid-cols-8',
                    }[this.columnas];
                },
            };
        }
    </script>

</x-community-layout>
