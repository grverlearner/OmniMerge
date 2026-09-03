@php
    /*
     * La ficha de una entidad.
     *
     * Una entidad es la suma de cuatro cosas, y esta pantalla las enseña las
     * cuatro con su cara: QUÉ ES (su tipo), CÓMO ES (sus características, con
     * la imagen de cada valor de catálogo), DÓNDE VIVE (sus colecciones) y
     * EN QUÉ SE CONVIERTE (sus versiones).
     *
     * Lo que más confunde de este modelo es que la entidad tiene DOS caras: la
     * original —la que se escribió el primer día— y la de su Base activa, que
     * es la que el resto de la aplicación enseña. Aquí eso no se esconde: hay
     * un interruptor arriba, siempre visible, que dice cuál se está viendo y
     * deja saltar a la otra. `?view=original` es lo que lo controla, y ya
     * existía; lo que faltaba era decirlo.
     *
     * Los colores de tipos, colecciones y opciones son datos del usuario
     * -hexadecimales elegidos por él- y por eso van en `style`: no son tokens
     * del diseño, son contenido.
     */

    $tipo = $entity->entityType;

    $colorTipo = $tipo?->color ?: '#6366f1';

    $estadoTono = [
        'ACTIVE' => 'bg-emerald-500/15 text-emerald-300',
        'INACTIVE' => 'bg-amber-500/15 text-amber-300',
        'ARCHIVED' => 'bg-slate-800 text-slate-500',
    ];

    /*
     * Las características que se enseñan dependen de qué cara se esté
     * mirando. La Base activa trae las suyas ya RESUELTAS por el resolver
     * -con herencia aplicada-, así que llegan en otra forma; se normalizan
     * aquí para que el resto de la pantalla no tenga que saberlo.
     */
    if ($usingActiveBase) {
        $caracteristicas = $activeBaseEffectiveAttributes->map(fn($item) => [
            'attribute' => $item['attribute'] ?? null,
            'values' => collect($item['values'] ?? []),
            'grupo' => optional(optional($item['attribute'])->groups)->first()?->name ?? 'Otros',
        ]);
    } else {
        $caracteristicas = $entity->entityAttributes->map(fn($asignacion) => [
            'attribute' => $asignacion->attribute,
            'values' => $asignacion->values,
            'grupo' => $asignacion->attribute?->groups->first()?->name ?? 'Otros',
        ]);
    }

    $porGrupo = $caracteristicas->filter(fn($c) => $c['attribute'])->groupBy('grupo');
@endphp

<x-app-layout :title="$displayName" surface="dark">

    <x-slot name="header">{{ $displayName }}</x-slot>

    <div class="space-y-4">

        {{-- ===================================================== --}}
        {{-- LA PORTADA --}}
        {{-- ===================================================== --}}

        <header class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50"
            style="border-color: {{ $colorTipo }}44">

            <div class="grid gap-0 lg:grid-cols-[300px_minmax(0,1fr)]">

                {{-- La cara --}}
                <div class="relative aspect-[4/5] overflow-hidden bg-slate-950 lg:aspect-auto lg:min-h-[340px]">

                    @if ($displayImageUrl)
                        <img src="{{ $displayImageUrl }}" alt="{{ $displayName }}"
                            class="h-full w-full object-cover">
                    @else
                        <span class="flex h-full w-full items-center justify-center text-7xl font-black"
                            style="color: {{ $colorTipo }}33; background:
                                radial-gradient(120% 90% at 50% 0%, {{ $colorTipo }}22, transparent 70%)">
                            {{ $tipo?->icon ?: mb_strtoupper(mb_substr($displayName, 0, 1)) }}
                        </span>
                    @endif

                    {{-- Qué cara es esta --}}
                    <span class="absolute bottom-2 left-2 rounded-lg px-2 py-1 text-[9px] font-black uppercase tracking-wider backdrop-blur"
                        style="background-color: {{ $usingActiveBase ? '#8b5cf6' : '#0f172a' }}dd; color: #fff">
                        {{ $usingActiveBase ? '★ ' . $activeBaseEntityVersion->name : 'Original' }}
                    </span>
                </div>


                {{-- Quién es --}}
                <div class="min-w-0 p-5">

                    <a href="{{ route('entities.index') }}"
                        class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-indigo-400">
                        ← Entidades
                    </a>

                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        @if ($tipo)
                            <a href="{{ route('entity-types.show', $tipo) }}"
                                class="flex items-center gap-2 rounded-lg border px-2 py-1 transition hover:bg-slate-950"
                                style="border-color: {{ $colorTipo }}55">

                                @if ($tipo->image_url)
                                    <img src="{{ $tipo->image_url }}" alt="" class="h-4 w-4 rounded object-cover">
                                @else
                                    <span class="text-[11px]">{{ $tipo->icon }}</span>
                                @endif

                                <span class="text-[9px] font-black uppercase tracking-wider"
                                    style="color: {{ $colorTipo }}">{{ $tipo->name }}</span>
                            </a>
                        @else
                            <a href="{{ route('entities.edit', $entity) }}"
                                class="rounded-lg bg-amber-500/15 px-2 py-1 text-[9px] font-black uppercase tracking-wider text-amber-300 transition hover:bg-amber-500/25">
                                sin tipo · ponerle uno
                            </a>
                        @endif

                        <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $estadoTono[$entity->status] ?? 'bg-slate-800 text-slate-500' }}">
                            {{ $entity->status_label }}
                        </span>

                        <span class="rounded bg-slate-800 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-slate-400">
                            {{ $entity->visibility_label }}
                        </span>

                        <span class="font-mono text-[10px] text-slate-600">{{ $entity->code }}</span>
                    </div>

                    <h1 class="mt-2 text-3xl font-black tracking-tight text-white">{{ $displayName }}</h1>

                    <p class="mt-2 max-w-2xl text-[12px] leading-relaxed text-slate-400">
                        {{ $displayDescription ?: 'Sin descripción.' }}
                    </p>


                    {{-- ============ QUÉ CARA SE ESTÁ VIENDO ============ --}}

                    {{--
                        El interruptor que faltaba. La entidad tiene dos caras
                        y hasta ahora había que saberlo para encontrarlas.
                    --}}

                    @if ($activeBaseEntityVersion)
                        <div class="mt-4 inline-flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-950 p-1">

                            <a href="{{ route('entities.show', $entity) }}"
                                class="rounded-lg px-3 py-1.5 text-[10px] font-black transition {{ $usingActiveBase ? 'bg-violet-500 text-white' : 'text-slate-500 hover:text-slate-200' }}">
                                ★ {{ $activeBaseEntityVersion->name }}
                            </a>

                            <a href="{{ route('entities.show', [$entity, 'view' => 'original']) }}"
                                class="rounded-lg px-3 py-1.5 text-[10px] font-black transition {{ $usingActiveBase ? 'text-slate-500 hover:text-slate-200' : 'bg-slate-700 text-white' }}">
                                Original
                            </a>

                            <span class="px-2 text-[9px] text-slate-600">
                                {{ $usingActiveBase
                                    ? 'esto es lo que ve el resto de la aplicación'
                                    : 'lo que escribiste el primer día' }}
                            </span>
                        </div>
                    @endif


                    {{-- ============ SUS CIFRAS ============ --}}

                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ([['Características', $displayCharacteristicsCount, 'text-violet-300'], ['Catálogos', $catalogValuesCount, 'text-cyan-300'], ['Colecciones', $entity->collections_count, 'text-emerald-300'], ['Versiones', $entity->entity_versions_count, 'text-amber-300'], ['Vistas', $entity->views_count, 'text-slate-300'], ['Copias', $entity->clones_count, 'text-sky-300']] as [$etiqueta, $valor, $color])
                            <span class="flex items-baseline gap-2 rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2">
                                <span class="font-mono text-lg font-black {{ $valor > 0 ? $color : 'text-slate-700' }}">
                                    {{ $valor }}
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                    {{ $etiqueta }}
                                </span>
                            </span>
                        @endforeach
                    </div>


                    {{-- ============ QUÉ SE PUEDE HACER ============ --}}

                    @can('update', $entity)
                        <div class="mt-4 flex flex-wrap gap-2">

                            @if ($usingActiveBase)
                                <a href="{{ route('entity-versions.edit', [$entity, $activeBaseEntityVersion]) }}"
                                    class="flex items-center gap-2 rounded-xl bg-violet-500 px-4 py-2.5 text-xs font-black text-white transition hover:bg-violet-400">
                                    <x-omni-icon name="controles" size="h-4 w-4" />
                                    Editar esta versión
                                </a>

                                <a href="{{ route('entity-versions.attributes.edit', [$entity, $activeBaseEntityVersion]) }}"
                                    class="flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-950/60 px-4 py-2.5 text-xs font-black text-slate-200 transition hover:border-violet-500/60 hover:text-violet-300">
                                    <x-omni-icon name="capas" size="h-4 w-4" />
                                    Sus características
                                </a>
                            @else
                                <a href="{{ route('entities.edit', $entity) }}"
                                    class="flex items-center gap-2 rounded-xl bg-indigo-500 px-4 py-2.5 text-xs font-black text-white transition hover:bg-indigo-400">
                                    <x-omni-icon name="controles" size="h-4 w-4" />
                                    Editar la original
                                </a>

                                <a href="{{ route('entities.attributes.edit', $entity) }}"
                                    class="flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-950/60 px-4 py-2.5 text-xs font-black text-slate-200 transition hover:border-violet-500/60 hover:text-violet-300">
                                    <x-omni-icon name="capas" size="h-4 w-4" />
                                    Sus características
                                </a>
                            @endif

                            <a href="{{ route('entity-versions.index', $entity) }}"
                                class="flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-950/60 px-4 py-2.5 text-xs font-black text-slate-200 transition hover:border-amber-500/60 hover:text-amber-300">
                                <x-omni-icon name="chispa" size="h-4 w-4" />
                                Versiones
                            </a>

                            <a href="{{ route('entities.presentation.edit', $entity) }}"
                                class="flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-950/60 px-4 py-2.5 text-xs font-black text-slate-200 transition hover:border-sky-500/60 hover:text-sky-300">
                                <x-omni-icon name="galeria" size="h-4 w-4" />
                                Presentación
                            </a>
                        </div>
                    @endcan

                </div>

            </div>

        </header>


        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_340px] xl:items-start">

            {{-- ============================================================= --}}
            {{-- COLUMNA PRINCIPAL --}}
            {{-- ============================================================= --}}

            <div class="space-y-4">

                {{-- ===================================================== --}}
                {{-- CÓMO ES --}}
                {{-- ===================================================== --}}

                <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                    <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                            <x-omni-icon name="controles" size="h-4 w-4" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <h2 class="text-sm font-black text-white">Cómo es</h2>
                            <p class="text-[10px] text-slate-500">
                                {{ $usingActiveBase
                                    ? 'Las características de ' . $activeBaseEntityVersion->name . ', con lo que hereda ya aplicado.'
                                    : 'Las características de la entidad original.' }}
                            </p>
                        </div>

                        @can('update', $entity)
                            <a href="{{ $usingActiveBase
                                ? route('entity-versions.attributes.edit', [$entity, $activeBaseEntityVersion])
                                : route('entities.attributes.edit', $entity) }}"
                                class="shrink-0 text-[10px] font-black text-slate-500 transition hover:text-violet-300">
                                Editar →
                            </a>
                        @endcan
                    </header>

                    @if ($porGrupo->isEmpty())

                        <div class="px-5 py-10 text-center">
                            <span class="inline-flex text-slate-700">
                                <x-omni-icon name="controles" size="h-9 w-9" />
                            </span>

                            <p class="mt-2 text-sm font-black text-white">Todavía no tiene características</p>

                            <p class="mx-auto mt-1 max-w-sm text-[11px] leading-relaxed text-slate-500">
                                Una característica es cualquier cosa que quieras registrar de ella: su
                                fuerza, su continente, su afiliación. Se eligen de tus atributos.
                            </p>

                            @can('update', $entity)
                                <a href="{{ route('entities.attributes.edit', $entity) }}"
                                    class="mt-4 inline-block rounded-xl bg-violet-500 px-4 py-2 text-[11px] font-black text-white transition hover:bg-violet-400">
                                    + Ponerle características
                                </a>
                            @endcan
                        </div>

                    @else

                        <div class="space-y-4 p-5">
                            @foreach ($porGrupo as $nombreGrupo => $delGrupo)

                                <div>
                                    <p class="mb-2 flex items-center gap-2 text-[9px] font-black uppercase tracking-wider text-slate-500">
                                        {{ $nombreGrupo }}
                                        <span class="h-px flex-1 bg-slate-800"></span>
                                        <span class="font-mono text-slate-700">{{ $delGrupo->count() }}</span>
                                    </p>

                                    <div class="grid gap-2 sm:grid-cols-2">
                                        @foreach ($delGrupo as $caracteristica)
                                            @php
                                                $atributo = $caracteristica['attribute'];
                                                $colorAtributo = $atributo->color ?: '#64748b';
                                                $valores = collect($caracteristica['values']);
                                            @endphp

                                            <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-2.5">

                                                {{-- Qué característica es --}}
                                                <p class="flex items-center gap-2">
                                                    @if ($atributo->image_url)
                                                        <img src="{{ $atributo->image_url }}" alt="" loading="lazy"
                                                            class="h-5 w-5 shrink-0 rounded object-cover">
                                                    @elseif ($atributo->icon)
                                                        <span class="text-[13px]">{{ $atributo->icon }}</span>
                                                    @else
                                                        <span class="h-2 w-2 shrink-0 rounded-full"
                                                            style="background-color: {{ $colorAtributo }}"></span>
                                                    @endif

                                                    <span class="min-w-0 flex-1 truncate text-[11px] font-black text-slate-300">
                                                        {{ $atributo->name }}
                                                    </span>

                                                    @if ($atributo->unit)
                                                        <span class="shrink-0 font-mono text-[9px] text-slate-600">
                                                            {{ $atributo->unit }}
                                                        </span>
                                                    @endif
                                                </p>

                                                {{-- Y con qué valor --}}
                                                @if ($valores->isEmpty())
                                                    <p class="mt-1.5 text-[11px] italic text-slate-700">Sin valor.</p>
                                                @else
                                                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                                                        @foreach ($valores as $valor)
                                                            @php
                                                                $opcion = is_object($valor) ? $valor->option ?? null : null;
                                                                $texto = is_object($valor) && method_exists($valor, 'displayValue')
                                                                    ? $valor->displayValue()
                                                                    : (string) (is_array($valor) ? $valor['display'] ?? '' : $valor);
                                                            @endphp

                                                            @if ($opcion)
                                                                {{-- Un valor de catálogo tiene cara propia --}}
                                                                <a href="{{ route('attribute-options.show', $opcion) }}"
                                                                    class="flex items-center gap-1.5 rounded-lg border bg-slate-900 px-2 py-1 transition hover:bg-slate-800"
                                                                    style="border-color: {{ $opcion->color ?: '#334155' }}66">

                                                                    @if ($opcion->image_url)
                                                                        <img src="{{ $opcion->image_url }}" alt=""
                                                                            loading="lazy"
                                                                            class="h-4 w-4 rounded object-cover">
                                                                    @elseif ($opcion->icon)
                                                                        <span class="text-[11px]">{{ $opcion->icon }}</span>
                                                                    @else
                                                                        <span class="h-2 w-2 rounded-full"
                                                                            style="background-color: {{ $opcion->color ?: '#64748b' }}"></span>
                                                                    @endif

                                                                    <span class="text-[11px] font-bold text-white">
                                                                        {{ $opcion->name }}
                                                                    </span>
                                                                </a>
                                                            @elseif ($texto !== '')
                                                                <span class="rounded-lg border border-slate-800 bg-slate-900 px-2 py-1 text-[11px] font-bold text-white">
                                                                    {{ $texto }}
                                                                </span>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @endif

                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                            @endforeach
                        </div>

                    @endif

                </section>


                {{-- ===================================================== --}}
                {{-- EN QUÉ SE CONVIERTE --}}
                {{-- ===================================================== --}}

                <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                    <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500/15 text-amber-300">
                            <x-omni-icon name="chispa" size="h-4 w-4" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <h2 class="text-sm font-black text-white">En qué se convierte</h2>
                            <p class="text-[10px] text-slate-500">
                                Sus versiones: la misma entidad en otro momento, otro traje u otra
                                realidad.
                            </p>
                        </div>

                        @can('update', $entity)
                            <a href="{{ route('entity-versions.create', $entity) }}"
                                class="shrink-0 rounded-lg bg-amber-500/15 px-2.5 py-1 text-[10px] font-black text-amber-300 transition hover:bg-amber-500 hover:text-slate-950">
                                + Nueva
                            </a>
                        @endcan
                    </header>

                    @if ($entity->entityVersions->isEmpty())

                        <div class="px-5 py-10 text-center">
                            <span class="inline-flex text-slate-700">
                                <x-omni-icon name="chispa" size="h-9 w-9" />
                            </span>

                            <p class="mt-2 text-sm font-black text-white">Solo existe la original</p>

                            <p class="mx-auto mt-1 max-w-sm text-[11px] leading-relaxed text-slate-500">
                                Una versión sirve para tener a la misma entidad en dos estados sin
                                duplicarla: «Naruto niño» y «Naruto Hokage» son la misma persona.
                            </p>

                            @can('update', $entity)
                                <a href="{{ route('entity-versions.create', $entity) }}"
                                    class="mt-4 inline-block rounded-xl bg-amber-500 px-4 py-2 text-[11px] font-black text-slate-950 transition hover:bg-amber-400">
                                    + Crear la primera versión
                                </a>
                            @endcan
                        </div>

                    @else

                        <div class="grid gap-2 p-4 sm:grid-cols-3 lg:grid-cols-4">
                            @foreach ($entity->entityVersions as $version)
                                @php
                                    $esBase = $activeBaseEntityVersion && $activeBaseEntityVersion->id === $version->id;
                                @endphp

                                <a href="{{ route('entity-versions.show', [$entity, $version]) }}"
                                    class="group relative overflow-hidden rounded-xl border bg-slate-950/60 transition hover:bg-slate-900 {{ $esBase ? 'border-violet-500/50' : 'border-slate-800 hover:border-slate-700' }}">

                                    <span class="relative block aspect-[4/3] overflow-hidden bg-slate-950">
                                        @if ($version->image_url)
                                            <img src="{{ $version->image_url }}" alt="" loading="lazy"
                                                class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                        @else
                                            <span class="flex h-full w-full items-center justify-center text-2xl opacity-20"
                                                style="color: {{ $colorTipo }}">★</span>
                                        @endif

                                        @if ($esBase)
                                            <span class="absolute right-1.5 top-1.5 rounded bg-violet-500 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-white">
                                                ★ base
                                            </span>
                                        @endif

                                        @if ($version->status !== 'ACTIVE')
                                            <span class="absolute left-1.5 top-1.5 rounded bg-slate-950/85 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-slate-400 backdrop-blur">
                                                {{ $version->status }}
                                            </span>
                                        @endif
                                    </span>

                                    <span class="block p-2">
                                        <span class="block truncate text-[11px] font-black text-white">
                                            {{ $version->name }}
                                        </span>

                                        <span class="block truncate text-[9px] text-slate-500">
                                            {{ $version->version?->name ?? 'Sin definición' }}
                                        </span>
                                    </span>
                                </a>
                            @endforeach
                        </div>

                        <a href="{{ route('entity-versions.index', $entity) }}"
                            class="block border-t border-slate-800 px-5 py-2.5 text-center text-[10px] font-black text-slate-500 transition hover:text-amber-300">
                            Gestionar todas sus versiones →
                        </a>

                    @endif

                </section>

            </div>


            {{-- ============================================================= --}}
            {{-- COLUMNA LATERAL --}}
            {{-- ============================================================= --}}

            <aside class="space-y-4">

                {{-- ===================================================== --}}
                {{-- DÓNDE VIVE --}}
                {{-- ===================================================== --}}

                <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">

                    <p class="flex items-center gap-2 text-[9px] font-black uppercase tracking-wider text-slate-500">
                        <x-omni-icon name="capas" size="h-3.5 w-3.5" />
                        Dónde vive
                    </p>

                    @if ($entity->collections->isEmpty())
                        <p class="mt-2 text-[11px] leading-4 text-slate-600">
                            No está en ninguna colección. Las colecciones agrupan entidades que
                            compiten juntas o que quieres tener a mano.
                        </p>

                        @can('update', $entity)
                            <a href="{{ route('entities.edit', $entity) }}"
                                class="mt-2.5 block rounded-xl border border-slate-800 px-3 py-2 text-center text-[11px] font-black text-slate-400 transition hover:border-emerald-500/60 hover:text-emerald-300">
                                Añadirla a una
                            </a>
                        @endcan
                    @else
                        <ul class="mt-2.5 space-y-1.5">
                            @foreach ($entity->collections as $coleccion)
                                <li>
                                    <a href="{{ route('collections.show', $coleccion) }}"
                                        class="flex items-center gap-2.5 rounded-xl border bg-slate-950/60 p-2 transition hover:bg-slate-900"
                                        style="border-color: {{ $coleccion->color ?: '#1e293b' }}66">

                                        <span class="h-9 w-9 shrink-0 overflow-hidden rounded-lg bg-slate-950">
                                            @if ($coleccion->image_url)
                                                <img src="{{ $coleccion->image_url }}" alt="" loading="lazy"
                                                    class="h-full w-full object-cover">
                                            @else
                                                <span class="flex h-full w-full items-center justify-center text-sm"
                                                    style="color: {{ $coleccion->color ?: '#64748b' }}">
                                                    {{ $coleccion->icon ?: '◈' }}
                                                </span>
                                            @endif
                                        </span>

                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-[11px] font-black text-slate-200">
                                                {{ $coleccion->name }}
                                            </span>
                                            <span class="block font-mono text-[9px] text-slate-600">
                                                {{ $coleccion->code }}
                                            </span>
                                        </span>

                                        <span class="shrink-0 text-slate-700">
                                            <x-omni-icon name="chevron-derecha" size="h-3 w-3" />
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                </section>


                {{-- ===================================================== --}}
                {{-- LA FICHA TÉCNICA --}}
                {{-- ===================================================== --}}

                <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">

                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Ficha técnica</p>

                    <dl class="mt-2.5 space-y-2 text-[11px]">
                        @foreach ([['Código', $entity->code], ['Enlace', $entity->slug], ['Tipo', $tipo?->name ?? 'Sin tipo'], ['Estado', $entity->status_label], ['Visibilidad', $entity->visibility_label], ['Creada', $entity->created_at?->locale('es')->isoFormat('D [de] MMMM [de] YYYY')], ['Última edición', $entity->updated_at?->locale('es')->diffForHumans()]] as [$etiqueta, $valor])
                            <div class="flex items-baseline justify-between gap-3">
                                <dt class="shrink-0 text-slate-600">{{ $etiqueta }}</dt>
                                <dd class="truncate text-right font-bold text-slate-200">{{ $valor }}</dd>
                            </div>
                        @endforeach
                    </dl>

                </section>


                {{-- ===================================================== --}}
                {{-- LAS DOS CARAS, EXPLICADAS --}}
                {{-- ===================================================== --}}

                <section class="rounded-2xl border border-violet-500/30 bg-violet-500/5 p-4">

                    <p class="text-[9px] font-black uppercase tracking-wider text-violet-300">
                        Qué cara enseña esta entidad
                    </p>

                    @if ($activeBaseEntityVersion)
                        <p class="mt-2 text-[11px] leading-4 text-slate-400">
                            Tiene una <strong class="text-white">Base activa</strong>:
                            <strong class="text-violet-300">{{ $activeBaseEntityVersion->name }}</strong>.
                            Eso significa que en torneos, listas y comunidad se la ve así, no como la
                            escribiste al principio.
                        </p>

                        <a href="{{ route('entity-versions.show', [$entity, $activeBaseEntityVersion]) }}"
                            class="mt-3 block rounded-xl border border-violet-500/40 bg-slate-950 px-3 py-2 text-center text-[11px] font-black text-violet-300 transition hover:bg-slate-900">
                            Abrir esa versión →
                        </a>
                    @else
                        <p class="mt-2 text-[11px] leading-4 text-slate-400">
                            No tiene Base activa, así que en todas partes se la ve como la
                            <strong class="text-white">original</strong>. Si le pones una versión como
                            base, esa pasa a ser su cara.
                        </p>

                        @can('update', $entity)
                            <a href="{{ route('entity-versions.index', $entity) }}"
                                class="mt-3 block rounded-xl border border-violet-500/40 bg-slate-950 px-3 py-2 text-center text-[11px] font-black text-violet-300 transition hover:bg-slate-900">
                                Elegir una base →
                            </a>
                        @endcan
                    @endif

                </section>


                {{-- ===================================================== --}}
                {{-- LO QUE NO SE DESHACE --}}
                {{-- ===================================================== --}}

                @can('delete', $entity)
                    <section class="rounded-2xl border border-rose-500/25 bg-rose-500/5 p-4">

                        <p class="text-[9px] font-black uppercase tracking-wider text-rose-300">
                            Lo que no se deshace
                        </p>

                        <p class="mt-2 text-[11px] leading-4 text-slate-500">
                            Al borrarla se van con ella sus características, sus versiones y su sitio
                            en las colecciones.
                        </p>

                        {{-- El modal global: mismos atributos que el resto de la aplicación --}}
                        <form method="POST" action="{{ route('entities.destroy', $entity) }}" class="mt-3"
                            data-omni-confirm data-confirm-variant="danger" data-confirm-icon="×"
                            data-confirm-title="Borrar esta entidad"
                            data-confirm-subject="{{ $entity->name }}"
                            data-confirm-message="Se van con ella sus características, sus versiones y su sitio en las colecciones."
                            data-confirm-detail="Esto no se puede deshacer."
                            data-confirm-action="Borrarla">
                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                class="w-full rounded-xl border border-rose-500/40 px-3 py-2 text-[11px] font-black text-rose-300 transition hover:bg-rose-500/15">
                                Borrar esta entidad
                            </button>
                        </form>

                    </section>
                @endcan

            </aside>

        </div>

    </div>

</x-app-layout>
