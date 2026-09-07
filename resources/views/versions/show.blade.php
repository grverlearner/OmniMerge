@php
    /*
     * La ficha de una definición de versión.
     *
     * Era una pantalla de lectura: para tocar las reglas de catálogo había que
     * irse al formulario completo —que las borra todas y las vuelve a crear— y
     * para asociar una entidad, al aplicador en lote.
     *
     * Ahora las dos cosas se hacen aquí, y las dos se hacen con imágenes:
     *
     *   · las reglas se montan eligiendo el catálogo y su valor por la cara,
     *     no buscando texto en dos desplegables encadenados
     *   · las entidades se asocian desde la propia ficha, copiando su imagen
     *
     * Y se enseña lo que antes había que deducir: qué significa exactamente la
     * regla montada, cuántas entidades tiene cada valor al que apunta, y qué
     * tienen ya en común las entidades que la llevan —que es de donde sale la
     * regla que habría que escribir—.
     */

    $tonoEstado = [
        'ACTIVE' => 'bg-emerald-500/15 text-emerald-300',
        'INACTIVE' => 'bg-amber-500/15 text-amber-300',
        'ARCHIVED' => 'bg-slate-800 text-slate-500',
    ];

    $tiposDeRelacion = [
        'ACTIVATES' => ['Activa la versión', 'border-cyan-500/30 bg-cyan-500/10 text-cyan-300'],
        'CONTEXT' => ['Contexto', 'border-indigo-500/30 bg-indigo-500/10 text-indigo-300'],
        'RELATED' => ['Relacionada', 'border-slate-700 bg-slate-800/60 text-slate-400'],
    ];

    $modoCobertura = [
        'AUTO' => 'Por catálogo',
        'MANUAL' => 'Alcance libre',
        'EXCLUSIVE' => 'Exclusiva',
    ];

    /* Los rasgos de la propia definición, que ahora van al lado de su cara. */
    $rasgos = [
        ['Clase', $version->kind_label, 'capas', 'De qué tipo de cambio habla: una era, una edad, una forma…'],
        [
            'Ámbito',
            $version->scope_label,
            'orbita',
            $version->isExclusive()
                ? 'Reservada para una sola entidad.'
                : 'La puede llevar cualquier entidad.',
        ],
        [
            'Activación',
            $version->activation_label,
            'chispa',
            $version->canAutoActivate()
                ? 'Puede salir elegida sola según el catálogo activo.'
                : 'Solo se elige a mano; nunca sale sola.',
        ],
        [
            'Estado',
            $version->status_label,
            'controles',
            $version->status === 'ACTIVE'
                ? 'Se tiene en cuenta en todas partes.'
                : 'Fuera de circulación: no se ofrece ni se resuelve.',
        ],
        ['Prioridad', $version->priority, 'barras', 'Cuando dos versiones encajan a la vez, gana la de número más alto.'],
        ['Orden', $version->sort_order, 'menu', 'Solo coloca la ficha en los listados; no cambia nada del motor.'],
    ];
@endphp

<x-app-layout :title="$version->name" surface="dark">

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

                <h1 class="mt-1 truncate text-xl font-black tracking-tight text-white">
                    {{ $version->name }}
                </h1>

                <p class="mt-0.5 font-mono text-[10px] text-slate-600">{{ $version->code }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
                @can('update', $version)
                    <a href="{{ route('versions.edit', $version) }}"
                        class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-amber-500 hover:text-amber-300">
                        ✎ Editar la definición
                    </a>

                    <a href="{{ route('versions.entities.bulk.create', $version) }}"
                        class="rounded-xl bg-violet-500 px-3 py-2 text-[11px] font-black text-white transition hover:bg-violet-400">
                        Aplicar en lote
                    </a>
                @endcan
            </div>

        </header>


        @include('versions.partials.workspace-navigation')


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
        {{-- QUÉ ES ESTE MOLDE --}}
        {{-- ===================================================== --}}

        {{--
            La cara a la izquierda y TODO lo demás a su derecha: sus rasgos, sus
            cifras, su descripción y dónde encaja. Antes los rasgos colgaban
            debajo de la imagen, en una columna estrecha que los dejaba en una
            lista larga y fina mientras media pantalla quedaba vacía.
        --}}

        <section class="grid gap-4 lg:grid-cols-[280px_minmax(0,1fr)]">

            <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <div class="relative aspect-square overflow-hidden bg-slate-950">
                    @if ($version->image_url)
                        <img src="{{ $version->image_url }}" alt="{{ $version->name }}"
                            class="h-full w-full object-cover">
                    @else
                        <span class="flex h-full w-full items-center justify-center text-6xl text-violet-500/20"
                            style="background: radial-gradient(120% 90% at 50% 0%, rgba(139,92,246,0.14), transparent 70%)">◈</span>
                    @endif

                    <span class="absolute left-2 top-2 rounded-lg border border-violet-500/30 bg-slate-950/85 px-2 py-1 text-[9px] font-black uppercase tracking-wider text-violet-300">
                        {{ $version->kind_label }}
                    </span>

                    <span class="absolute right-2 top-2 rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $tonoEstado[$version->status] ?? 'bg-slate-800 text-slate-500' }}">
                        {{ $version->status_label }}
                    </span>
                </div>

                <p class="border-t border-slate-800 px-3 py-2 text-center font-mono text-[10px] text-slate-600">
                    {{ $version->code }}
                </p>

            </div>


            <div class="space-y-3">

                {{-- ---------- SUS RASGOS ---------- --}}

                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                    @foreach ($rasgos as [$etiqueta, $valor, $icono, $ayuda])
                        <div class="rounded-xl border border-slate-800 bg-slate-900/50 p-2.5">

                            <div class="flex items-center gap-1.5">
                                <span class="text-slate-600"><x-omni-icon :name="$icono" size="h-3.5 w-3.5" /></span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">{{ $etiqueta }}</span>
                            </div>

                            <p class="mt-0.5 truncate text-[13px] font-black text-white">{{ $valor }}</p>
                            <p class="mt-0.5 text-[10px] leading-4 text-slate-500">{{ $ayuda }}</p>
                        </div>
                    @endforeach
                </div>


                {{-- ---------- LAS CIFRAS ---------- --}}

                <div class="grid grid-cols-3 gap-2 sm:grid-cols-5">
                    @foreach ([['La aplican', $appliedStats['total'], 'text-violet-300'], ['Base activa', $appliedStats['default'], 'text-amber-300'], ['Con cambios', $appliedStats['with_overrides'], 'text-indigo-300'], ['Con imágenes', $appliedStats['with_media'], 'text-fuchsia-300'], ['Subversiones', $version->children_count, 'text-cyan-300']] as [$etiqueta, $valor, $tono])
                        <div class="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2">
                            <p class="font-mono text-xl font-black {{ $valor > 0 ? $tono : 'text-slate-700' }}">{{ $valor }}</p>
                            <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">{{ $etiqueta }}</p>
                        </div>
                    @endforeach
                </div>


                {{-- ---------- DESCRIPCIÓN ---------- --}}

                <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-3.5">
                    <h2 class="text-[10px] font-black uppercase tracking-wider text-slate-600">Qué es</h2>

                    <p class="mt-1 text-[12px] leading-relaxed {{ $version->description ? 'text-slate-300' : 'text-slate-600' }}">
                        {{ $version->description ?: 'Sin descripción. Merece la pena escribirla: dentro de un año, «Otra» y «Forma» no van a distinguirse solas.' }}
                    </p>
                </div>


                {{-- ---------- JERARQUÍA ---------- --}}

                @if ($ancestros->isNotEmpty() || $version->children->isNotEmpty())
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-3.5">

                        <h2 class="text-[10px] font-black uppercase tracking-wider text-slate-600">Dónde encaja</h2>

                        <div class="mt-1.5 flex flex-wrap items-center gap-1.5 text-[11px]">
                            @foreach ($ancestros as $ancestro)
                                <a href="{{ route('versions.show', $ancestro) }}"
                                    class="rounded-lg border border-slate-800 px-2 py-1 font-bold text-slate-400 transition hover:border-violet-500 hover:text-violet-300">
                                    {{ $ancestro->name }}
                                </a>
                                <span class="text-slate-700">→</span>
                            @endforeach

                            <span class="rounded-lg border border-violet-500/40 bg-violet-500/10 px-2 py-1 font-black text-violet-200">
                                {{ $version->name }}
                            </span>
                        </div>

                        @if ($version->children->isNotEmpty())
                            <div class="mt-3 border-t border-slate-800 pt-3">
                                <p class="text-[10px] font-black uppercase tracking-wider text-slate-600">Cuelgan de esta</p>

                                <div class="mt-1.5 flex flex-wrap gap-1.5">
                                    @foreach ($version->children as $hija)
                                        <a href="{{ route('versions.show', $hija) }}"
                                            class="flex items-center gap-1.5 rounded-lg border border-slate-800 bg-slate-950 px-2 py-1 transition hover:border-violet-500/50">
                                            <span class="h-5 w-5 shrink-0 overflow-hidden rounded border border-slate-800">
                                                @if ($hija->image_url)
                                                    <img src="{{ $hija->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                                @endif
                                            </span>
                                            <span class="text-[11px] font-black text-slate-300">{{ $hija->name }}</span>
                                            <span class="font-mono text-[9px] {{ $hija->entity_versions_count > 0 ? 'text-violet-300' : 'text-slate-700' }}">
                                                {{ $hija->entity_versions_count }}
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                    </div>
                @endif

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- CUÁNDO SE ACTIVA --}}
        {{-- ===================================================== --}}

        <section x-data="{
            anadiendo: false,

            catalogo: null,
            catalogoNombre: '',

            opcion: null,
            opcionNombre: '',
            opcionImagen: null,
            opcionUsos: 0,

            tipo: 'ACTIVATES',
            grupo: 1,
            operador: 'AND',

            buscar: '',

            elegirCatalogo(id, nombre) {
                this.catalogo = id;
                this.catalogoNombre = nombre;
                this.buscar = '';
                this.opcion = null;
                this.opcionNombre = '';
                this.opcionImagen = null;
                this.opcionUsos = 0;
            },

            elegirOpcion(id, nombre, imagen, usos) {
                this.opcion = id;
                this.opcionNombre = nombre;
                this.opcionImagen = imagen;
                this.opcionUsos = usos;
            },

            reiniciar() {
                this.elegirCatalogo(null, '');
            },
        }" class="overflow-hidden rounded-2xl border border-cyan-500/25 bg-slate-900/50">

            <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-3">

                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-cyan-500/15 text-cyan-300">
                    <x-omni-icon name="brujula" size="h-4 w-4" />
                </span>

                <div class="min-w-0 flex-1">
                    <h2 class="text-[13px] font-black text-white">Cuándo se activa sola</h2>
                    <p class="text-[10px] text-slate-500">
                        Las reglas de catálogo que hacen que este molde salga elegido sin que nadie lo pida.
                    </p>
                </div>

                <span class="shrink-0 rounded-lg border border-slate-800 px-2 py-1 font-mono text-[10px] font-black text-slate-400">
                    {{ $version->catalog_links_count }}
                    {{ $version->catalog_links_count === 1 ? 'regla' : 'reglas' }}
                </span>

                @can('update', $version)
                    <button type="button" @click="anadiendo = !anadiendo"
                        class="shrink-0 rounded-xl bg-cyan-500/15 px-3 py-2 text-[11px] font-black text-cyan-300 transition hover:bg-cyan-500 hover:text-slate-950">
                        <span x-text="anadiendo ? 'Cancelar' : '+ Añadir regla'"></span>
                    </button>
                @endcan
            </div>


            @can('update', $version)
                @include('versions.partials.rule-picker')
            @endcan


            {{-- ---------- LA REGLA, EN CASTELLANO ---------- --}}

            @if ($activationGroups->isEmpty())

                <div class="p-5 text-center">
                    <p class="text-[12px] font-black text-white">Este molde no se activa solo.</p>
                    <p class="mx-auto mt-1 max-w-lg text-[11px] leading-relaxed text-slate-500">
                        No tiene ninguna regla de las que activan, así que nunca lo elegirá el motor por su
                        cuenta: solo se aplica a mano. Eso está bien si es lo que quieres —muchos moldes son
                        así—; si no, añade arriba una regla del tipo <strong class="text-cyan-300">activa la
                        versión</strong>.
                    </p>
                </div>

            @else

                <div class="space-y-2 p-4">

                    <p class="text-[11px] text-slate-400">
                        Se activa cuando se cumple
                        <strong class="text-cyan-300">
                            {{ $activationGroups->count() === 1 ? 'esta condición' : 'cualquiera de estos ' . $activationGroups->count() . ' grupos' }}
                        </strong>:
                    </p>

                    @foreach ($activationGroups as $numeroGrupo => $grupo)
                        <div class="rounded-xl border border-cyan-500/20 bg-slate-950/50 p-3">

                            @if ($activationGroups->count() > 1)
                                <p class="mb-2 text-[9px] font-black uppercase tracking-wider text-cyan-500/70">
                                    Grupo {{ $numeroGrupo }}
                                </p>
                            @endif

                            <div class="space-y-1.5">
                                @foreach ($grupo->values() as $indice => $enlace)
                                    @php $usos = (int) ($optionUsage[$enlace->attribute_option_id] ?? 0); @endphp

                                    <div x-data="{ corrigiendo: false }"
                                        class="rounded-lg border border-slate-800 bg-slate-900/60">

                                        <div class="flex flex-wrap items-center gap-2 px-2.5 py-2">

                                            @if ($indice > 0)
                                                <span class="shrink-0 rounded px-1.5 py-0.5 text-[9px] font-black {{ $enlace->logical_operator === 'OR' ? 'bg-amber-500/15 text-amber-300' : 'bg-slate-800 text-slate-400' }}">
                                                    {{ $enlace->logical_operator === 'OR' ? 'O' : 'Y' }}
                                                </span>
                                            @endif

                                            {{-- La cara del valor: es como se reconoce, no por su nombre --}}
                                            <span class="h-9 w-9 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                                @if ($enlace->option?->image_url)
                                                    <img src="{{ $enlace->option->image_url }}" alt="" loading="lazy"
                                                        class="h-full w-full object-cover">
                                                @else
                                                    <span class="flex h-full w-full items-center justify-center text-slate-700">◇</span>
                                                @endif
                                            </span>

                                            <span class="min-w-0 flex-1 text-[12px]">
                                                <span class="block truncate text-[9px] font-black uppercase tracking-wider text-slate-600">
                                                    {{ $enlace->option?->attribute?->name ?? $enlace->attribute?->name }}
                                                </span>
                                                <span class="block truncate font-black text-white">{{ $enlace->option?->name ?? '—' }}</span>
                                            </span>

                                            <span class="shrink-0 rounded-lg border px-2 py-1 font-mono text-[10px] font-black {{ $usos > 0 ? 'border-cyan-500/25 text-cyan-300' : 'border-rose-500/30 text-rose-300' }}"
                                                title="{{ $usos > 0 ? $usos . ' entidades tuyas tienen este valor' : 'Ninguna entidad tuya tiene este valor, así que esta regla no puede cumplirse' }}">
                                                {{ $usos }}
                                            </span>

                                            @can('update', $version)
                                                <button type="button" @click="corrigiendo = !corrigiendo"
                                                    class="shrink-0 rounded-lg px-1.5 py-1 text-[10px] font-black text-slate-500 transition hover:text-amber-300">✎</button>

                                                <form method="POST"
                                                    action="{{ route('versions.catalog-links.destroy', [$version, $enlace]) }}"
                                                    onsubmit="return confirm('Se quita esta regla. ¿Seguro?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="shrink-0 rounded-lg px-1.5 py-1 text-[10px] font-black text-slate-600 transition hover:text-rose-300">✕</button>
                                                </form>
                                            @endcan
                                        </div>

                                        @can('update', $version)
                                            <form method="POST"
                                                action="{{ route('versions.catalog-links.update', [$version, $enlace]) }}"
                                                x-show="corrigiendo" x-cloak x-collapse
                                                class="grid gap-2 border-t border-slate-800 p-2.5 sm:grid-cols-4">
                                                @csrf
                                                @method('PATCH')

                                                <select name="relation_type"
                                                    class="rounded-lg border-slate-800 bg-slate-950 text-[11px] text-slate-200 focus:border-cyan-500 focus:ring-cyan-500">
                                                    @foreach (['ACTIVATES' => 'Activa la versión', 'CONTEXT' => 'Es su contexto', 'RELATED' => 'Solo relacionada'] as $valor => $etiqueta)
                                                        <option value="{{ $valor }}" @selected($enlace->relation_type === $valor)>{{ $etiqueta }}</option>
                                                    @endforeach
                                                </select>

                                                <input type="number" name="condition_group" value="{{ $enlace->condition_group }}"
                                                    min="1" max="100" title="Grupo"
                                                    class="rounded-lg border-slate-800 bg-slate-950 text-[11px] text-slate-200 focus:border-cyan-500 focus:ring-cyan-500">

                                                <select name="logical_operator"
                                                    class="rounded-lg border-slate-800 bg-slate-950 text-[11px] text-slate-200 focus:border-cyan-500 focus:ring-cyan-500">
                                                    <option value="AND" @selected($enlace->logical_operator === 'AND')>Y — también hace falta</option>
                                                    <option value="OR" @selected($enlace->logical_operator === 'OR')>O — basta con esta</option>
                                                </select>

                                                <button type="submit"
                                                    class="rounded-lg bg-cyan-500/15 py-1.5 text-[11px] font-black text-cyan-300 transition hover:bg-cyan-500 hover:text-slate-950">
                                                    Guardar
                                                </button>
                                            </form>
                                        @endcan

                                    </div>
                                @endforeach
                            </div>

                        </div>
                    @endforeach

                    @if (collect($activationGroups->flatten())->contains(fn($e) => (int) ($optionUsage[$e->attribute_option_id] ?? 0) === 0))
                        <p class="rounded-xl border border-rose-500/25 bg-rose-500/5 px-3 py-2 text-[10px] leading-relaxed text-rose-200">
                            Alguna regla apunta a un valor que <strong>ninguna de tus entidades tiene</strong>
                            (el contador en rojo). Mientras siga así, esa condición no se cumplirá nunca.
                        </p>
                    @endif

                </div>

            @endif


            {{-- ---------- LAS OTRAS RELACIONES ---------- --}}

            @if ($contextLinks->isNotEmpty())
                <div class="border-t border-slate-800 p-4">

                    <h3 class="text-[10px] font-black uppercase tracking-wider text-slate-600">
                        Otras relaciones de catálogo
                    </h3>

                    <p class="mt-0.5 text-[10px] leading-relaxed text-slate-500">
                        Estas <strong class="text-slate-400">no activan nada</strong>: son documentación. Sirven
                        para dejar dicho dónde ocurre esta versión o con qué tiene que ver.
                    </p>

                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @foreach ($contextLinks as $enlace)
                            @php [$etiquetaTipo, $tonoTipo] = $tiposDeRelacion[$enlace->relation_type] ?? $tiposDeRelacion['RELATED']; @endphp

                            <span class="flex items-center gap-2 rounded-xl border py-1 pl-1 pr-2 {{ $tonoTipo }}">
                                <span class="h-7 w-7 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                    @if ($enlace->option?->image_url)
                                        <img src="{{ $enlace->option->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-[10px] text-slate-700">◇</span>
                                    @endif
                                </span>

                                <span class="min-w-0">
                                    <span class="block text-[8px] font-black uppercase tracking-wider opacity-70">{{ $etiquetaTipo }}</span>
                                    <span class="block truncate text-[11px] font-bold">{{ $enlace->option?->name }}</span>
                                </span>

                                @can('update', $version)
                                    <form method="POST" action="{{ route('versions.catalog-links.destroy', [$version, $enlace]) }}"
                                        onsubmit="return confirm('Se quita esta relación. ¿Seguro?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-[10px] font-black opacity-50 transition hover:opacity-100">✕</button>
                                    </form>
                                @endcan
                            </span>
                        @endforeach
                    </div>

                </div>
            @endif

        </section>


        {{-- ===================================================== --}}
        {{-- LO QUE YA COMPARTEN LAS QUE LA LLEVAN --}}
        {{-- ===================================================== --}}

        {{--
            El revés de una regla. Una regla dice «actívate con esto»; esto dice
            «las que ya la llevan tienen esto en común», que es justo de donde
            sale la regla que habría que escribir. Si las tres que llevan
            «Shippuden» tienen las tres Anime = Naruto: Shippūden, la regla se
            escribe sola —y por eso hay un botón para escribirla de un clic—.
        --}}

        @if ($sharedTraits->isNotEmpty())
            <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-3">

                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-300">
                        <x-omni-icon name="grafo" size="h-4 w-4" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <h2 class="text-[13px] font-black text-white">Qué tienen en común las que la llevan</h2>
                        <p class="text-[10px] leading-relaxed text-slate-500">
                            Las características de catálogo de las
                            {{ $appliedStats['total'] }}
                            {{ $appliedStats['total'] === 1 ? 'entidad que ya la aplica' : 'entidades que ya la aplican' }}.
                            Lo que comparten <strong class="text-emerald-300">todas</strong> es el mejor
                            candidato a regla de activación.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 p-4 sm:grid-cols-4 lg:grid-cols-6">

                    @foreach ($sharedTraits->take(24) as $rasgo)
                        @php $yaEsRegla = $linkedOptionIds->contains($rasgo->id); @endphp

                        <div class="overflow-hidden rounded-xl border bg-slate-950 {{ $rasgo->todas ? 'border-emerald-500/40' : 'border-slate-800' }}">

                            <div class="relative aspect-[4/3] overflow-hidden bg-slate-900">
                                @if ($rasgo->image_url)
                                    <img src="{{ $rasgo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-xl text-slate-700">◇</span>
                                @endif

                                <span class="absolute bottom-1 right-1 rounded px-1 font-mono text-[9px] font-black {{ $rasgo->todas ? 'bg-emerald-500 text-slate-950' : 'bg-slate-950/85 text-slate-300' }}"
                                    title="{{ $rasgo->cuantas }} de {{ $appliedStats['total'] }} la tienen">
                                    {{ $rasgo->cuantas }}/{{ $appliedStats['total'] }}
                                </span>

                                @if ($yaEsRegla)
                                    <span class="absolute left-1 top-1 rounded bg-cyan-500 px-1 text-[8px] font-black text-slate-950">YA ES REGLA</span>
                                @endif
                            </div>

                            <div class="p-1.5">
                                <p class="truncate text-[9px] font-black uppercase tracking-wider text-slate-600">
                                    {{ $rasgo->attribute?->name }}
                                </p>
                                <p class="truncate text-[11px] font-black text-white">{{ $rasgo->name }}</p>

                                @can('update', $version)
                                    @if (! $yaEsRegla && $rasgo->todas)
                                        <form method="POST" action="{{ route('versions.catalog-links.store', $version) }}">
                                            @csrf
                                            <input type="hidden" name="attribute_id" value="{{ $rasgo->attribute_id }}">
                                            <input type="hidden" name="attribute_option_id" value="{{ $rasgo->id }}">
                                            <input type="hidden" name="relation_type" value="ACTIVATES">
                                            <input type="hidden" name="condition_group" value="1">
                                            <input type="hidden" name="logical_operator" value="AND">

                                            <button type="submit"
                                                title="Crea la regla «{{ $rasgo->attribute?->name }} = {{ $rasgo->name }}» que activa esta versión"
                                                class="mt-1 w-full rounded-lg bg-cyan-500/15 py-1 text-[9px] font-black text-cyan-300 transition hover:bg-cyan-500 hover:text-slate-950">
                                                Convertir en regla
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                            </div>

                        </div>
                    @endforeach

                </div>

                @if ($sharedTraits->count() > 24)
                    <p class="border-t border-slate-800 px-4 py-2 text-[10px] text-slate-500">
                        Y {{ $sharedTraits->count() - 24 }} características más.
                    </p>
                @endif

            </section>
        @endif


        {{-- ===================================================== --}}
        {{-- LAS ENTIDADES QUE LA LLEVAN --}}
        {{-- ===================================================== --}}

        <section x-data="{
            view: 'grid',
            asociando: false,
            elegidas: [],

            alternar(id) {
                const i = this.elegidas.indexOf(id);
                if (i === -1) this.elegidas.push(id); else this.elegidas.splice(i, 1);
            },

            todas(ids) { this.elegidas = [...ids]; },
            ninguna() { this.elegidas = []; },
        }" class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-3">

                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                    <x-omni-icon name="chispa" size="h-4 w-4" />
                </span>

                <div class="min-w-0 flex-1">
                    <h2 class="text-[13px] font-black text-white">Entidades que la llevan</h2>
                    <p class="text-[10px] text-slate-500">
                        Cada una tiene su propia cara, su nombre y sus características: la definición es el
                        molde, esto son las piezas.
                    </p>
                </div>

                <span class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-950 p-1">
                    @foreach ([['grid', 'cuadricula'], ['list', 'capas']] as [$modo, $icono])
                        <button type="button" @click="view = '{{ $modo }}'"
                            :class="view === '{{ $modo }}' ? 'bg-violet-500 text-white' : 'text-slate-500 hover:text-slate-200'"
                            class="rounded-lg px-2 py-1.5 transition">
                            <x-omni-icon :name="$icono" size="h-3.5 w-3.5" />
                        </button>
                    @endforeach
                </span>

                @can('update', $version)
                    <button type="button" @click="asociando = !asociando"
                        class="shrink-0 rounded-xl bg-violet-500/15 px-3 py-2 text-[11px] font-black text-violet-300 transition hover:bg-violet-500 hover:text-white">
                        <span x-text="asociando ? 'Cancelar' : '+ Asociar entidades'"></span>
                    </button>
                @endcan
            </div>


            {{-- ---------- ASOCIAR, AQUÍ MISMO ---------- --}}

            {{--
                Una versión de entidad necesita imagen sí o sí. En vez de pedirla
                —que es lo que obliga a irse al aplicador en lote—, se copia la de
                la propia entidad: es justo lo que se quiere de partida cuando la
                versión todavía no tiene cara propia. Se dice, y se puede cambiar
                después.
            --}}

            @can('update', $version)
                <form method="POST" action="{{ route('versions.entities.store', $version) }}"
                    x-show="asociando" x-cloak x-collapse
                    class="border-b border-slate-800 bg-slate-950/50">
                    @csrf

                    @if ($candidateEntities->isEmpty())

                        <p class="p-5 text-center text-[11px] text-slate-500">
                            Todas tus entidades activas ya llevan esta definición.
                        </p>

                    @else

                        <div class="flex flex-wrap items-center gap-2 border-b border-slate-800/70 px-4 py-2.5">
                            <p class="min-w-0 flex-1 text-[11px] text-slate-400">
                                Elige a quién se la pones. Se creará su versión copiando la imagen de cada
                                entidad, y podrás cambiarla después.
                            </p>

                            <button type="button" @click="todas({{ Illuminate\Support\Js::from($candidateEntities->pluck('id')) }})"
                                class="rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-white">
                                Todas
                            </button>

                            <button type="button" @click="ninguna()"
                                class="rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-white">
                                Ninguna
                            </button>

                            <span class="rounded-lg border border-violet-500/30 bg-violet-500/10 px-2 py-1 font-mono text-[10px] font-black text-violet-300"
                                x-text="elegidas.length + ' elegidas'"></span>
                        </div>

                        <div class="grid max-h-80 grid-cols-2 gap-2 overflow-y-auto p-4 sm:grid-cols-4 lg:grid-cols-6">

                            @foreach ($candidateEntities as $candidata)
                                <label class="group relative cursor-pointer overflow-hidden rounded-xl border bg-slate-950 transition"
                                    :class="elegidas.includes({{ $candidata->id }})
                                        ? 'border-violet-500 ring-1 ring-violet-500'
                                        : 'border-slate-800 hover:border-slate-700'">

                                    <input type="checkbox" name="entity_ids[]" value="{{ $candidata->id }}"
                                        class="sr-only" :checked="elegidas.includes({{ $candidata->id }})"
                                        @change="alternar({{ $candidata->id }})">

                                    <span class="relative block aspect-square overflow-hidden bg-slate-900">
                                        @if ($candidata->image_url)
                                            <img src="{{ $candidata->image_url }}" alt="" loading="lazy"
                                                class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                        @else
                                            <span class="flex h-full w-full items-center justify-center text-2xl text-slate-800">◍</span>
                                        @endif

                                        @if ($candidata->cumple_reglas)
                                            <span class="absolute left-1 top-1 rounded bg-cyan-500 px-1 text-[8px] font-black text-slate-950"
                                                title="Cumple las reglas de catálogo de este molde">✓ ENCAJA</span>
                                        @endif

                                        <span x-show="elegidas.includes({{ $candidata->id }})" x-cloak
                                            class="absolute inset-0 flex items-center justify-center bg-violet-500/30 text-lg font-black text-white">✓</span>
                                    </span>

                                    <span class="block truncate px-1.5 py-1 text-center text-[10px] font-black text-slate-300">
                                        {{ $candidata->name }}
                                    </span>
                                </label>
                            @endforeach

                        </div>

                        <div class="flex flex-wrap items-center gap-3 border-t border-slate-800/70 px-4 py-3">
                            <button type="submit" x-bind:disabled="elegidas.length === 0"
                                class="rounded-xl bg-violet-500 px-4 py-2 text-[11px] font-black text-white transition hover:bg-violet-400 disabled:opacity-40">
                                Asociar las elegidas
                            </button>

                            <a href="{{ route('versions.entities.bulk.create', $version) }}"
                                class="text-[10px] font-black text-slate-500 underline transition hover:text-violet-300">
                                ¿Quieres poner nombre e imagen distintos a cada una? Usa el aplicador en lote →
                            </a>
                        </div>

                    @endif
                </form>
            @endcan


            {{-- ---------- LAS QUE YA LA LLEVAN ---------- --}}

            @if ($version->entityVersions->isEmpty())

                <div class="p-8 text-center">
                    <span class="inline-flex text-slate-700"><x-omni-icon name="chispa" size="h-9 w-9" /></span>

                    <p class="mt-2 text-[13px] font-black text-white">Todavía no la lleva nadie</p>

                    <p class="mx-auto mt-1 max-w-md text-[11px] leading-relaxed text-slate-500">
                        Este molde existe, pero no hace nada hasta que alguna entidad lo aplique. Pulsa
                        <strong class="text-violet-300">Asociar entidades</strong> aquí arriba.
                    </p>
                </div>

            @else

                <div x-show="view === 'grid'" class="grid gap-3 p-4 sm:grid-cols-3 lg:grid-cols-5">

                    @foreach ($version->entityVersions as $aplicada)
                        <article class="group overflow-hidden rounded-xl border border-slate-800 bg-slate-950 transition hover:-translate-y-0.5 hover:border-violet-500/50">

                            <a href="{{ route('entity-versions.show', [$aplicada->entity, $aplicada]) }}"
                                class="relative block aspect-square overflow-hidden">
                                @if ($aplicada->image_url)
                                    <img src="{{ $aplicada->image_url }}" alt="" loading="lazy"
                                        class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-2xl text-slate-800">◈</span>
                                @endif

                                @if ($aplicada->is_default)
                                    <span class="absolute right-1.5 top-1.5 rounded bg-amber-400 px-1.5 py-0.5 text-[9px] font-black text-amber-950"
                                        title="Base activa de su entidad">★</span>
                                @endif
                            </a>

                            <div class="p-2">
                                <a href="{{ route('entity-versions.show', [$aplicada->entity, $aplicada]) }}"
                                    class="block truncate text-[11px] font-black text-white transition hover:text-violet-300">
                                    {{ $aplicada->name }}
                                </a>

                                <a href="{{ route('entities.show', $aplicada->entity) }}"
                                    class="block truncate text-[9px] text-slate-500 transition hover:text-indigo-300">
                                    {{ $aplicada->entity->name }}
                                </a>

                                <div class="mt-1.5 flex gap-1 text-[9px]">
                                    <span class="rounded border px-1 py-0.5 font-bold {{ $aplicada->version_attributes_count > 0 ? 'border-violet-500/25 text-violet-300' : 'border-slate-800 text-slate-700' }}"
                                        title="Características propias">{{ $aplicada->version_attributes_count }} ✎</span>

                                    <span class="rounded border px-1 py-0.5 font-bold {{ $aplicada->images_count > 0 ? 'border-fuchsia-500/25 text-fuchsia-300' : 'border-slate-800 text-slate-700' }}"
                                        title="Imágenes de galería">{{ $aplicada->images_count }} ◫</span>
                                </div>
                            </div>
                        </article>
                    @endforeach

                </div>

                <div x-show="view === 'list'" x-cloak class="divide-y divide-slate-800/70">
                    @foreach ($version->entityVersions as $aplicada)
                        <div class="flex items-center gap-3 px-4 py-2 transition hover:bg-slate-950/50">

                            <a href="{{ route('entity-versions.show', [$aplicada->entity, $aplicada]) }}"
                                class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                @if ($aplicada->image_url)
                                    <img src="{{ $aplicada->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-slate-700">◈</span>
                                @endif
                            </a>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <a href="{{ route('entity-versions.show', [$aplicada->entity, $aplicada]) }}"
                                        class="truncate text-[12px] font-black text-white transition hover:text-violet-300">
                                        {{ $aplicada->name }}
                                    </a>

                                    @if ($aplicada->is_default)
                                        <span class="rounded bg-amber-400 px-1 text-[8px] font-black text-amber-950">★ BASE</span>
                                    @endif
                                </div>

                                <p class="truncate text-[10px] text-slate-500">
                                    {{ $aplicada->entity->name }}
                                    @if ($aplicada->entity->entityType)
                                        <span class="text-slate-700">·</span> {{ $aplicada->entity->entityType->name }}
                                    @endif
                                </p>
                            </div>

                            <span class="hidden shrink-0 gap-1.5 sm:flex">
                                <span class="rounded-lg border px-2 py-1 font-mono text-[10px] font-black {{ $aplicada->version_attributes_count > 0 ? 'border-violet-500/25 text-violet-300' : 'border-slate-800 text-slate-700' }}">{{ $aplicada->version_attributes_count }}</span>
                                <span class="rounded-lg border px-2 py-1 font-mono text-[10px] font-black {{ $aplicada->images_count > 0 ? 'border-fuchsia-500/25 text-fuchsia-300' : 'border-slate-800 text-slate-700' }}">{{ $aplicada->images_count }}</span>
                            </span>

                            @can('update', $aplicada)
                                <a href="{{ route('entity-versions.edit', [$aplicada->entity, $aplicada]) }}"
                                    class="shrink-0 rounded-lg px-2 py-1 text-[10px] font-black text-slate-500 transition hover:text-amber-300">✎</a>
                            @endcan
                        </div>
                    @endforeach
                </div>

            @endif

        </section>


        {{-- ===================================================== --}}
        {{-- A QUIÉN LE FALTA --}}
        {{-- ===================================================== --}}

        @if ($coverageMode !== 'EXCLUSIVE')
            <section x-data="{ abierto: false }"
                class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <div class="flex flex-wrap items-center gap-3 px-4 py-3">

                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 text-amber-300">
                        <x-omni-icon name="barras" size="h-4 w-4" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <h2 class="text-[13px] font-black text-white">Cobertura</h2>
                            <span class="rounded-lg border border-slate-800 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider text-slate-500">
                                {{ $modoCobertura[$coverageMode] }}
                            </span>
                        </div>

                        @if ($eligibleEntities->isEmpty())
                            <p class="text-[10px] leading-relaxed text-slate-500">
                                Ninguna entidad cumple sus reglas, así que no hay a quién aplicársela
                                automáticamente. Revisa las reglas de arriba, o asóciala a mano.
                            </p>
                        @else
                            <div class="mt-1 flex items-center gap-2.5">
                                <span class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-950">
                                    <span class="block h-full rounded-full {{ $coveragePercentage >= 90 ? 'bg-emerald-500' : ($coveragePercentage >= 50 ? 'bg-cyan-500' : 'bg-amber-500') }}"
                                        style="width: {{ max($coveragePercentage, 2) }}%"></span>
                                </span>
                                <span class="shrink-0 font-mono text-[10px] font-black text-slate-500">
                                    {{ $eligibleEntities->count() - $missingEntities->count() }}/{{ $eligibleEntities->count() }}
                                </span>
                            </div>

                            <p class="mt-1 text-[10px] text-slate-500">
                                @if ($missingEntities->isEmpty())
                                    <span class="font-black text-emerald-300">Sin huecos.</span>
                                    Todas las entidades que pueden llevarla ya la llevan.
                                @else
                                    Le falta a <strong class="text-amber-300">{{ $missingEntities->count() }}</strong>
                                    {{ $missingEntities->count() === 1 ? 'entidad' : 'entidades' }}
                                    @if ($coverageMode === 'AUTO')
                                        que cumplen sus reglas.
                                    @else
                                        de tu biblioteca. Nada la restringe, así que cuentan todas.
                                    @endif
                                @endif
                            </p>
                        @endif
                    </div>

                    @if ($coveragePercentage !== null && $eligibleEntities->isNotEmpty())
                        <span class="shrink-0 font-mono text-2xl font-black {{ $coveragePercentage >= 90 ? 'text-emerald-300' : ($coveragePercentage >= 50 ? 'text-cyan-300' : 'text-amber-300') }}">
                            {{ $coveragePercentage }}%
                        </span>
                    @endif

                    @if ($missingEntities->isNotEmpty())
                        <button type="button" @click="abierto = !abierto"
                            class="shrink-0 rounded-xl border border-slate-800 px-2.5 py-2 text-[10px] font-black text-slate-300 transition hover:border-amber-500 hover:text-amber-300">
                            <span x-text="abierto ? 'Ocultar' : 'Ver a quién le falta'"></span>
                        </button>
                    @endif
                </div>

                <div x-show="abierto" x-cloak x-collapse class="border-t border-slate-800 bg-slate-950/40">
                    <div class="grid grid-cols-3 gap-2 p-4 sm:grid-cols-5 lg:grid-cols-8">
                        @foreach ($missingEntities->take(24) as $entidad)
                            <a href="{{ route('entities.show', $entidad) }}" title="{{ $entidad->name }}"
                                class="group overflow-hidden rounded-xl border border-slate-800 bg-slate-950 transition hover:border-amber-500/40">
                                <span class="block aspect-square overflow-hidden bg-slate-900">
                                    @if ($entidad->image_url)
                                        <img src="{{ $entidad->image_url }}" alt="" loading="lazy"
                                            class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-xl text-slate-800">◍</span>
                                    @endif
                                </span>
                                <span class="block truncate px-1.5 py-1 text-center text-[9px] font-black text-slate-400">
                                    {{ $entidad->name }}
                                </span>
                            </a>
                        @endforeach
                    </div>

                    @if ($missingEntities->count() > 24)
                        <p class="border-t border-slate-800/70 px-4 py-2 text-[10px] text-slate-500">
                            Y {{ $missingEntities->count() - 24 }} más.
                        </p>
                    @endif
                </div>

            </section>
        @endif


        {{-- ===================================================== --}}
        {{-- ZONA DE PELIGRO --}}
        {{-- ===================================================== --}}

        @can('delete', $version)
            <section class="rounded-2xl border border-rose-500/25 bg-rose-500/5 p-4">

                <div class="flex flex-wrap items-center gap-3">

                    <div class="min-w-0 flex-1">
                        <h2 class="text-[12px] font-black text-rose-200">Eliminar esta definición</h2>

                        <p class="mt-0.5 text-[10px] leading-relaxed text-rose-200/60">
                            @if ($version->entity_versions_count > 0)
                                La llevan <strong>{{ $version->entity_versions_count }}</strong>
                                {{ $version->entity_versions_count === 1 ? 'entidad' : 'entidades' }}
                                y tiene <strong>{{ $version->catalog_links_count }}</strong>
                                {{ $version->catalog_links_count === 1 ? 'regla' : 'reglas' }} de catálogo.
                                Borrarla afecta a todo eso.
                            @else
                                No la lleva ninguna entidad, así que borrarla no rompe nada.
                            @endif
                        </p>
                    </div>

                    <form method="POST" action="{{ route('versions.destroy', $version) }}"
                        onsubmit="return confirm('Se elimina la definición «{{ $version->name }}». ¿Seguro?')">
                        @csrf
                        @method('DELETE')

                        <button type="submit"
                            class="rounded-xl border border-rose-500/40 px-4 py-2 text-[11px] font-black text-rose-300 transition hover:bg-rose-500 hover:text-white">
                            Eliminar
                        </button>
                    </form>

                </div>

            </section>
        @endcan

    </div>

</x-app-layout>
