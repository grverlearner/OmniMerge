@php
    /*
     * Crear (o editar) la versión de una entidad.
     *
     * Esta pantalla hace algo que no decía: según lo que elijas en el paso 1,
     * puede crear UNA cosa o DOS.
     *
     *   · «Usar una que ya existe»  → crea solo la versión de esta entidad
     *   · «Nueva compartida»        → crea TAMBIÉN la definición (el molde)
     *   · «Nueva exclusiva»         → crea TAMBIÉN la definición, reservada
     *
     * Antes las tres eran tres botones de texto del mismo tamaño y nada
     * advertía de que dos de ellos dejan un molde nuevo en la biblioteca para
     * siempre. Ahora cada opción lleva su esquema dibujado y su aviso.
     *
     * El motor de Alpine (`entityVersionBuilder`, al final del archivo) no se
     * ha tocado: esto es solo el marcado que lo rodea. Sus propiedades y
     * métodos son el contrato — mode, selectedVersionId, versionSearch,
     * newVersion*, imageSource, imagePreview, autoParent, manualParentId,
     * advancedOpen, filteredVersions(), selectedDefinition(),
     * suggestedEntityVersionName(), suggestedParentId/Name(),
     * previewUploadedImage(), refreshImagePreview(), optionsFor().
     */

    $editing = $entityVersion !== null;

    $initialMode = old('definition_mode', $creationDefaults['definition_mode'] ?? 'EXISTING');

    $initialImageSource = old('image_source', $entity->image ? 'ENTITY' : 'UPLOAD');

    $initialSelectedVersion = old('version_id', $entityVersion?->version_id ?? '');

    $initialManualParent = old(
        'parent_entity_version_id',
        $entityVersion?->parent_entity_version_id ?? ($creationDefaults['parent_entity_version_id'] ?? ''),
    );

    /* Las clases de molde, con la frase que las distingue. */
    $clases = [
        'ERA' => ['Era', 'Un tramo de la historia: «Shippuden», «Boruto».'],
        'AGE' => ['Edad', 'Un momento de su vida: «Niño», «Adulto».'],
        'FORM' => ['Forma', 'Otro cuerpo o estado estable: «Modo Sabio».'],
        'TRANSFORMATION' => ['Transformación', 'Un cambio puntual que se activa y se acaba.'],
        'OUTFIT' => ['Apariencia', 'Misma persona, otra ropa o aspecto.'],
        'TIMELINE' => ['Línea temporal', 'Otra realidad o continuidad distinta.'],
        'OTHER' => ['Otra', 'Cuando ninguna de las anteriores encaja.'],
    ];

    $activaciones = [
        'BOTH' => ['Automática y manual', 'Puede salir sola por catálogo, y también elegirse a mano.'],
        'AUTO' => ['Solo automática', 'Sale sola cuando el catálogo encaja; no se ofrece a mano.'],
        'MANUAL' => ['Solo manual', 'Nunca sale sola: se elige a mano siempre.'],
    ];
@endphp

<x-app-layout :title="$editing ? 'Editar versión' : 'Nueva versión'" surface="dark">

    <x-slot name="header">Versiones</x-slot>

    <form method="POST" enctype="multipart/form-data"
        action="{{ $editing
            ? route('entity-versions.update', [$entity, $entityVersion])
            : route('entity-versions.store', $entity) }}"
        x-data="entityVersionBuilder({
            editing: @js($editing),
            entityName: @js($entity->name),
            entityImageUrl: @js($entity->image_url),
            versions: @js($versionPayload),
            entityVersions: @js($entityVersionPayload),
            catalogs: @js($catalogPayload),
            initialMode: @js($initialMode),
            initialVersionId: @js((string) $initialSelectedVersion),
            initialImageSource: @js($initialImageSource),
            initialImageUrl: @js($entityVersion?->image_url ?? $entity->image_url),
            initialParent: @js((string) $initialManualParent),
            initialNewParent: @js((string) old('new_version_parent_id', $creationDefaults['new_version_parent_id'] ?? '')),
        })" class="space-y-4">

        @csrf
        @if ($editing)
            @method('PUT')
        @endif


        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-center gap-3">

            <a href="{{ route('entity-versions.index', $entity) }}"
                class="h-12 w-12 shrink-0 overflow-hidden rounded-xl border border-slate-800 bg-slate-950">
                @if ($entity->image_url)
                    <img src="{{ $entity->image_url }}" alt="" class="h-full w-full object-cover">
                @else
                    <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                @endif
            </a>

            <div class="min-w-0 flex-1">
                <a href="{{ route('entity-versions.index', $entity) }}"
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-violet-400">
                    ← Versiones de {{ $entity->name }}
                </a>

                <h1 class="mt-0.5 text-xl font-black tracking-tight text-white">
                    {{ $editing ? 'Editar ' . $entityVersion->name : 'Nueva versión de ' . $entity->name }}
                </h1>
            </div>

            <a href="{{ route('entity-versions.index', $entity) }}"
                class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2 text-[11px] font-black text-slate-400 transition hover:border-slate-700 hover:text-white">
                Cancelar
            </a>
        </header>


        @include('versions.partials.workspace-navigation')


        @if ($errors->any())
            <div class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3">
                <p class="text-[12px] font-black text-rose-200">
                    Falta algo antes de poder guardar:
                </p>
                <ul class="mt-1 space-y-0.5 text-[11px] text-rose-200/80">
                    @foreach ($errors->all() as $error)
                        <li>· {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- ===================================================== --}}
        {{-- QUÉ ESTÁS CREANDO --}}
        {{-- ===================================================== --}}

        {{--
            La advertencia que faltaba, dibujada: dos de las tres opciones del
            paso 1 no crean una cosa, crean dos —y la segunda, el molde, se
            queda en la biblioteca para siempre—.
        --}}

        @unless ($editing)
            <section x-data="{ abierto: false }"
                class="overflow-hidden rounded-2xl border border-violet-500/25 bg-violet-500/5">

                <button type="button" @click="abierto = !abierto"
                    class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition hover:bg-violet-500/5">

                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                        <x-omni-icon name="grafo" size="h-3.5 w-3.5" />
                    </span>

                    <span class="min-w-0 flex-1 text-[12px] font-black text-white">
                        Qué se crea aquí
                        <span class="font-bold text-slate-500">— y por qué a veces son dos cosas</span>
                    </span>

                    <span class="shrink-0 text-slate-500 transition" :class="abierto ? 'rotate-90' : ''">
                        <x-omni-icon name="chevron-derecha" size="h-4 w-4" />
                    </span>
                </button>

                <div x-show="abierto" x-cloak x-collapse class="border-t border-violet-500/20 p-4">
                    <div class="grid gap-4 lg:grid-cols-[300px_minmax(0,1fr)]">

                        <svg viewBox="0 0 260 120" class="h-auto w-full text-violet-400" fill="none"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">

                            <rect x="6" y="14" width="66" height="34" rx="5" />
                            <circle cx="24" cy="31" r="7" opacity=".7" />
                            <path d="M38 27h22M38 36h14" opacity=".45" />
                            <text x="39" y="9" text-anchor="middle" fill="currentColor" stroke="none"
                                font-size="8" font-weight="700">La entidad</text>

                            <rect x="6" y="72" width="66" height="34" rx="5" stroke-dasharray="4 3" />
                            <path d="M16 84h46M16 94h30" opacity=".5" />
                            <text x="39" y="67" text-anchor="middle" fill="currentColor" stroke="none"
                                font-size="8" font-weight="700">La definición</text>

                            <path d="M78 34 108 56M78 92 108 66" opacity=".6" />
                            <path d="M112 60h20M132 60l-8-5M132 60l-8 5" opacity=".8" />

                            <rect x="140" y="40" width="76" height="40" rx="6" />
                            <circle cx="158" cy="60" r="8" opacity=".7" />
                            <path d="M174 54h30M174 66h20" opacity=".45" />
                            <text x="178" y="34" text-anchor="middle" fill="currentColor" stroke="none"
                                font-size="8" font-weight="700">La versión</text>
                        </svg>

                        <div class="space-y-2 text-[11px] leading-relaxed text-slate-400">
                            <p>
                                Una <strong class="text-white">versión</strong> es siempre la unión de dos
                                cosas: <strong class="text-slate-200">{{ $entity->name }}</strong> y un
                                <strong class="text-violet-300">molde</strong> —«Shippuden», «Niño», «Modo
                                Sabio»—.
                            </p>

                            <p>
                                Si el molde <strong class="text-slate-200">ya existe</strong>, aquí solo se
                                crea la versión y ya está.
                            </p>

                            <p class="rounded-xl border border-amber-500/25 bg-amber-500/5 px-3 py-2 text-amber-200">
                                Pero si eliges <strong>nueva compartida</strong> o <strong>nueva
                                exclusiva</strong>, se crean <strong>dos cosas</strong>: el molde, que se
                                queda en tu biblioteca para siempre y podrá aplicarse a otras entidades, y la
                                versión. Merece la pena mirar antes si el molde ya estaba.
                            </p>
                        </div>

                    </div>
                </div>

            </section>
        @endunless


        {{-- ===================================================== --}}
        {{-- PASO 1 — EL MOLDE --}}
        {{-- ===================================================== --}}

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-violet-500 font-mono text-[11px] font-black text-white">1</span>

                <div class="min-w-0 flex-1">
                    <h2 class="text-[13px] font-black text-white">¿Qué molde se aplica?</h2>
                    <p class="text-[10px] text-slate-500">
                        El molde dice de qué va el cambio; la entidad pone quién lo sufre.
                    </p>
                </div>
            </div>


            @unless ($editing)

                {{-- ---------- LAS TRES MANERAS ---------- --}}

                <input type="hidden" name="definition_mode" :value="mode">

                <div class="grid gap-2 border-b border-slate-800 p-4 lg:grid-cols-3">

                    {{-- EXISTENTE --}}
                    <button type="button" @click="mode = 'EXISTING'"
                        :class="mode === 'EXISTING'
                            ? 'border-violet-500 bg-violet-500/10'
                            : 'border-slate-800 bg-slate-950 hover:border-slate-700'"
                        class="rounded-xl border p-3 text-left transition">

                        <svg viewBox="0 0 120 46" class="h-12 w-full text-violet-400" fill="none"
                            stroke="currentColor" stroke-width="1.4" stroke-linecap="round" aria-hidden="true">
                            <rect x="4" y="14" width="34" height="20" rx="3" stroke-dasharray="3 2" />
                            <path d="M42 24h12M54 24l-5-3M54 24l-5 3" opacity=".7" />
                            <rect x="60" y="4" width="26" height="14" rx="3" opacity=".85" />
                            <rect x="60" y="26" width="26" height="14" rx="3" opacity=".85" />
                            <rect x="92" y="15" width="24" height="14" rx="3" />
                            <path d="M96 22h16" opacity=".5" />
                        </svg>

                        <p class="mt-2 text-[12px] font-black" :class="mode === 'EXISTING' ? 'text-violet-200' : 'text-slate-300'">
                            Usar un molde que ya existe
                        </p>
                        <p class="mt-0.5 text-[10px] leading-4 text-slate-500">
                            Lo normal. No crea nada nuevo en la biblioteca: solo aplica a
                            {{ $entity->name }} uno de tus {{ count($versionPayload) }} moldes.
                        </p>
                    </button>

                    {{-- NUEVA COMPARTIDA --}}
                    <button type="button" @click="mode = 'NEW_SHARED'"
                        :class="mode === 'NEW_SHARED'
                            ? 'border-cyan-500 bg-cyan-500/10'
                            : 'border-slate-800 bg-slate-950 hover:border-slate-700'"
                        class="rounded-xl border p-3 text-left transition">

                        <svg viewBox="0 0 120 46" class="h-12 w-full text-cyan-400" fill="none"
                            stroke="currentColor" stroke-width="1.4" stroke-linecap="round" aria-hidden="true">
                            <rect x="4" y="14" width="34" height="20" rx="3" />
                            <path d="M12 21h18M12 27h10" opacity=".5" />
                            <path d="M42 18 56 8M42 24h12M42 30 56 40" opacity=".7" />
                            <rect x="60" y="2" width="22" height="12" rx="3" opacity=".8" />
                            <rect x="60" y="18" width="22" height="12" rx="3" opacity=".8" />
                            <rect x="60" y="34" width="22" height="12" rx="3" opacity=".8" />
                            <text x="100" y="27" text-anchor="middle" fill="currentColor" stroke="none"
                                font-size="9" font-weight="700">+1</text>
                        </svg>

                        <p class="mt-2 text-[12px] font-black" :class="mode === 'NEW_SHARED' ? 'text-cyan-200' : 'text-slate-300'">
                            Crear un molde compartido
                        </p>
                        <p class="mt-0.5 text-[10px] leading-4 text-slate-500">
                            <strong class="text-amber-300">Crea dos cosas</strong>: el molde —que luego podrá
                            aplicarse a cualquier otra entidad— y esta versión.
                        </p>
                    </button>

                    {{-- NUEVA EXCLUSIVA --}}
                    <button type="button" @click="mode = 'NEW_EXCLUSIVE'"
                        :class="mode === 'NEW_EXCLUSIVE'
                            ? 'border-sky-500 bg-sky-500/10'
                            : 'border-slate-800 bg-slate-950 hover:border-slate-700'"
                        class="rounded-xl border p-3 text-left transition">

                        <svg viewBox="0 0 120 46" class="h-12 w-full text-sky-400" fill="none"
                            stroke="currentColor" stroke-width="1.4" stroke-linecap="round" aria-hidden="true">
                            <rect x="4" y="14" width="34" height="20" rx="3" />
                            <path d="M12 21h18M12 27h10" opacity=".5" />
                            <path d="M42 24h12M54 24l-5-3M54 24l-5 3" opacity=".7" />
                            <rect x="60" y="14" width="24" height="20" rx="3" />
                            <circle cx="72" cy="24" r="5" opacity=".7" />
                            <path d="M92 16v16M92 16h10M92 24h7" opacity=".45" />
                            <circle cx="104" cy="30" r="6" stroke-dasharray="2 2" opacity=".5" />
                        </svg>

                        <p class="mt-2 text-[12px] font-black" :class="mode === 'NEW_EXCLUSIVE' ? 'text-sky-200' : 'text-slate-300'">
                            Crear un molde exclusivo
                        </p>
                        <p class="mt-0.5 text-[10px] leading-4 text-slate-500">
                            <strong class="text-amber-300">Crea dos cosas</strong>, pero el molde queda
                            reservado a {{ $entity->name }}: ninguna otra entidad podrá aplicarlo.
                        </p>
                    </button>

                </div>

            @else
                <input type="hidden" name="definition_mode" value="EXISTING">
            @endunless


            {{-- ---------- ELEGIR UN MOLDE EXISTENTE ---------- --}}

            <div x-show="mode === 'EXISTING'" class="p-4">

                <input type="hidden" name="version_id" :value="selectedVersionId">

                <div class="mb-2.5 flex flex-wrap items-center gap-2">
                    <h3 class="text-[12px] font-black text-white">Tus moldes</h3>

                    <label class="relative ml-auto w-56">
                        <span class="sr-only">Buscar molde</span>
                        <input type="search" x-model="versionSearch" placeholder="Buscar por nombre, código o clase…"
                            class="w-full rounded-lg border-slate-800 bg-slate-900 py-1.5 text-[11px] text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                    </label>
                </div>

                <template x-if="filteredVersions().length === 0">
                    <p class="rounded-xl border border-dashed border-slate-800 py-8 text-center text-[11px] text-slate-600">
                        Ningún molde encaja con esa búsqueda.
                    </p>
                </template>

                <div class="grid max-h-96 grid-cols-2 gap-2 overflow-y-auto sm:grid-cols-4 lg:grid-cols-6">
                    <template x-for="def in filteredVersions()" :key="def.id">
                        <button type="button"
                            @click="if (! entityVersions.some(e => String(e.version_id) === String(def.id))) selectedVersionId = String(def.id)"
                            :disabled="entityVersions.some(e => String(e.version_id) === String(def.id))"
                            :title="entityVersions.some(e => String(e.version_id) === String(def.id))
                                ? entityName + ' ya tiene este molde aplicado'
                                : def.name"
                            :class="String(selectedVersionId) === String(def.id)
                                ? 'border-violet-500 ring-1 ring-violet-500'
                                : 'border-slate-800 hover:border-slate-600'"
                            class="group overflow-hidden rounded-xl border bg-slate-950 text-left transition disabled:cursor-not-allowed disabled:opacity-35">

                            <span class="relative block aspect-square overflow-hidden bg-slate-900">
                                <template x-if="def.image_url">
                                    <img :src="def.image_url" alt="" loading="lazy"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                </template>
                                <template x-if="! def.image_url">
                                    <span class="flex h-full w-full items-center justify-center text-xl text-violet-500/30">◈</span>
                                </template>

                                <span class="absolute left-1 top-1 rounded bg-slate-950/85 px-1 text-[8px] font-black uppercase tracking-wider text-violet-300"
                                    x-text="def.kind"></span>

                                <span class="absolute bottom-1 right-1 rounded bg-slate-950/85 px-1 font-mono text-[9px] font-black text-slate-400"
                                    x-text="def.usage_count"
                                    title="Entidades que ya lo aplican"></span>

                                <span x-show="entityVersions.some(e => String(e.version_id) === String(def.id))" x-cloak
                                    class="absolute inset-0 flex items-center justify-center bg-slate-950/70 text-[9px] font-black text-slate-400">
                                    YA LA TIENE
                                </span>

                                <span x-show="String(selectedVersionId) === String(def.id)" x-cloak
                                    class="absolute inset-0 flex items-center justify-center bg-violet-500/30 text-lg font-black text-white">✓</span>
                            </span>

                            <span class="block px-1.5 py-1">
                                <span class="block truncate text-[10px] font-black text-white" x-text="def.name"></span>
                                <span class="block truncate text-[9px] text-slate-600" x-text="def.scope"></span>
                            </span>
                        </button>
                    </template>
                </div>

                <template x-if="selectedDefinition()">
                    <p class="mt-2.5 rounded-xl border border-violet-500/25 bg-violet-500/5 px-3 py-2 text-[11px] text-slate-300">
                        Se aplicará <strong class="text-violet-300" x-text="selectedDefinition().name"></strong>
                        a <strong class="text-white">{{ $entity->name }}</strong>.
                        <span class="text-slate-500" x-show="selectedDefinition().parent_name">
                            Cuelga de <span x-text="selectedDefinition().parent_name"></span>.
                        </span>
                    </p>
                </template>

            </div>


            {{-- ---------- CREAR UN MOLDE NUEVO ---------- --}}

            @unless ($editing)
                <div x-show="mode === 'NEW_SHARED' || mode === 'NEW_EXCLUSIVE'" x-cloak x-collapse
                    class="space-y-4 border-t border-slate-800 bg-slate-950/40 p-4">

                    <p class="rounded-xl border border-amber-500/25 bg-amber-500/5 px-3 py-2 text-[11px] leading-relaxed text-amber-200">
                        Lo que rellenes aquí abajo crea un <strong>molde nuevo en tu biblioteca</strong>,
                        aparte de la versión. Podrás verlo y editarlo después en
                        <a href="{{ route('versions.index') }}" class="font-black underline">Definiciones</a>.
                    </p>

                    <div class="grid gap-3 lg:grid-cols-[minmax(0,300px)_minmax(0,1fr)]">

                        {{-- Su cara --}}
                        <div class="space-y-2">
                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-600">
                                La cara del molde
                            </p>

                            <div class="grid gap-1.5">
                                <label class="flex cursor-pointer items-center gap-2 rounded-xl border p-2.5 transition"
                                    :class="definitionImageMode === 'SAME'
                                        ? 'border-cyan-500 bg-cyan-500/10'
                                        : 'border-slate-800 bg-slate-950 hover:border-slate-700'">
                                    <input type="radio" name="definition_image_mode" value="SAME"
                                        x-model="definitionImageMode" class="border-slate-700 bg-slate-900 text-cyan-500">
                                    <span class="min-w-0">
                                        <span class="block text-[11px] font-black text-slate-200">La misma que la versión</span>
                                        <span class="block text-[9px] leading-4 text-slate-500">
                                            Lo habitual al empezar; se cambia luego desde la ficha del molde.
                                        </span>
                                    </span>
                                </label>

                                <label class="flex cursor-pointer items-center gap-2 rounded-xl border p-2.5 transition"
                                    :class="definitionImageMode === 'UPLOAD'
                                        ? 'border-cyan-500 bg-cyan-500/10'
                                        : 'border-slate-800 bg-slate-950 hover:border-slate-700'">
                                    <input type="radio" name="definition_image_mode" value="UPLOAD"
                                        x-model="definitionImageMode" class="border-slate-700 bg-slate-900 text-cyan-500">
                                    <span class="min-w-0">
                                        <span class="block text-[11px] font-black text-slate-200">Subir una distinta</span>
                                        <span class="block text-[9px] leading-4 text-slate-500">
                                            Una imagen que represente al molde, no a esta entidad.
                                        </span>
                                    </span>
                                </label>
                            </div>

                            <div x-show="definitionImageMode === 'UPLOAD'" x-cloak class="pt-1">
                                <x-omni-image-upload name="new_version_image" label="Imagen del molde" :max-mb="2"
                                    surface="dark" />
                            </div>
                        </div>

                        {{-- Sus datos --}}
                        <div class="space-y-3">

                            <label class="block">
                                <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                                    Cómo se llama el molde
                                </span>
                                <input type="text" name="new_version_name" x-model="newVersionName"
                                    maxlength="150" placeholder="«Shippuden», «Modo Sabio», «Niño»…"
                                    class="w-full rounded-xl border-slate-800 bg-slate-900 text-xs text-slate-200 placeholder:text-slate-600 focus:border-cyan-500 focus:ring-cyan-500">
                                <span class="mt-1 block text-[10px] text-slate-600">
                                    El nombre del <strong class="text-slate-400">cambio</strong>, no el de la
                                    entidad: «Shippuden», no «Naruto Shippuden».
                                </span>
                            </label>

                            <div>
                                <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                                    De qué clase es
                                </span>

                                <div class="grid grid-cols-2 gap-1.5 sm:grid-cols-4">
                                    @foreach ($clases as $valor => [$etiqueta, $ayuda])
                                        <label title="{{ $ayuda }}"
                                            class="cursor-pointer rounded-lg border border-slate-800 bg-slate-950 px-2 py-1.5 text-center transition has-[:checked]:border-cyan-500 has-[:checked]:bg-cyan-500/10">
                                            <input type="radio" name="new_version_kind" value="{{ $valor }}"
                                                @checked(old('new_version_kind', 'OTHER') === $valor) class="sr-only">
                                            <span class="block text-[11px] font-black text-slate-300">{{ $etiqueta }}</span>
                                        </label>
                                    @endforeach
                                </div>

                                <p class="mt-1 text-[10px] leading-4 text-slate-600">
                                    Solo sirve para agrupar y filtrar; no cambia cómo funciona.
                                </p>
                            </div>

                            <div>
                                <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                                    Cuándo puede salir elegido
                                </span>

                                <div class="grid gap-1.5 sm:grid-cols-3">
                                    @foreach ($activaciones as $valor => [$etiqueta, $ayuda])
                                        <label class="cursor-pointer rounded-lg border border-slate-800 bg-slate-950 p-2 transition has-[:checked]:border-cyan-500 has-[:checked]:bg-cyan-500/10">
                                            <input type="radio" name="new_version_activation_mode" value="{{ $valor }}"
                                                @checked(old('new_version_activation_mode', 'BOTH') === $valor) class="sr-only">
                                            <span class="block text-[11px] font-black text-slate-200">{{ $etiqueta }}</span>
                                            <span class="mt-0.5 block text-[9px] leading-4 text-slate-500">{{ $ayuda }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <label class="block">
                                    <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                                        ¿Cuelga de otro molde?
                                    </span>
                                    <select name="new_version_parent_id" x-model="newVersionParentId"
                                        class="w-full rounded-xl border-slate-800 bg-slate-900 text-xs text-slate-200 focus:border-cyan-500 focus:ring-cyan-500">
                                        <option value="">De ninguno</option>
                                        @foreach ($versions as $definicion)
                                            <option value="{{ $definicion->id }}">{{ $definicion->name }}</option>
                                        @endforeach
                                    </select>
                                    <span class="mt-1 block text-[10px] leading-4 text-slate-600">
                                        Solo organiza: «Modo Sabio» puede colgar de «Shippuden».
                                    </span>
                                </label>

                                <label class="block">
                                    <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                                        Descripción
                                    </span>
                                    <textarea name="new_version_description" rows="3" maxlength="5000"
                                        placeholder="Para acordarte dentro de un año de qué distingue este molde."
                                        class="w-full rounded-xl border-slate-800 bg-slate-900 text-xs text-slate-200 placeholder:text-slate-600 focus:border-cyan-500 focus:ring-cyan-500">{{ old('new_version_description') }}</textarea>
                                </label>
                            </div>

                        </div>

                    </div>


                    {{-- Su primera regla de catálogo --}}

                    <div x-data="{
                        cat: '',
                        catNombre: '',
                        val: '',
                        valNombre: '',
                        valImagen: null,
                    }" class="rounded-xl border border-slate-800 bg-slate-900/60 p-3">

                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-600">
                            Su primera regla de catálogo <span class="text-slate-700">(opcional)</span>
                        </p>

                        <p class="mt-0.5 text-[10px] leading-relaxed text-slate-500">
                            Si eliges un valor, el molde <strong class="text-cyan-300">saldrá solo</strong>
                            cuando una entidad tenga ese valor. Puedes dejarlo vacío y añadir reglas después
                            desde la ficha del molde.
                        </p>

                        <input type="hidden" name="new_catalog_attribute_id" :value="cat">
                        <input type="hidden" name="new_catalog_attribute_option_id" :value="val">

                        <div class="mt-2.5 flex gap-1.5 overflow-x-auto pb-1">
                            <template x-for="c in catalogs" :key="c.id">
                                <button type="button" :disabled="c.options.length === 0"
                                    @click="cat = c.id; catNombre = c.name; val = ''; valNombre = ''; valImagen = null"
                                    :title="c.options.length === 0 ? 'Este catálogo no tiene valores activos' : c.name"
                                    :class="String(cat) === String(c.id)
                                        ? 'border-cyan-500 bg-cyan-500/10'
                                        : 'border-slate-800 bg-slate-950 hover:border-slate-700'"
                                    class="flex w-20 shrink-0 flex-col overflow-hidden rounded-lg border transition disabled:cursor-not-allowed disabled:opacity-35">

                                    <span class="relative block aspect-square overflow-hidden bg-slate-900">
                                        <template x-if="c.image_url">
                                            <img :src="c.image_url" alt="" loading="lazy" class="h-full w-full object-cover">
                                        </template>
                                        <template x-if="! c.image_url">
                                            <span class="flex h-full w-full items-center justify-center text-slate-700">◱</span>
                                        </template>
                                        <span class="absolute bottom-0.5 right-0.5 rounded bg-slate-950/85 px-1 font-mono text-[9px] font-black text-cyan-300"
                                            x-text="c.options.length"></span>
                                    </span>

                                    <span class="block truncate px-1 py-0.5 text-center text-[9px] font-black text-slate-400"
                                        x-text="c.name"></span>
                                </button>
                            </template>
                        </div>

                        <div x-show="cat" x-cloak x-collapse class="mt-2">
                            <div class="grid max-h-40 grid-cols-4 gap-1.5 overflow-y-auto sm:grid-cols-8">
                                <template x-for="o in optionsFor(cat)" :key="o.id">
                                    <button type="button"
                                        @click="val = o.id; valNombre = o.name; valImagen = o.image_url"
                                        :title="o.name"
                                        :class="String(val) === String(o.id)
                                            ? 'border-cyan-500 ring-1 ring-cyan-500'
                                            : 'border-slate-800 hover:border-slate-600'"
                                        class="overflow-hidden rounded-lg border bg-slate-950 transition">

                                        <span class="relative block aspect-square overflow-hidden bg-slate-900">
                                            <template x-if="o.image_url">
                                                <img :src="o.image_url" alt="" loading="lazy" class="h-full w-full object-cover">
                                            </template>
                                            <template x-if="! o.image_url">
                                                <span class="flex h-full w-full items-center justify-center text-[11px] text-slate-700">◇</span>
                                            </template>

                                            <span x-show="String(val) === String(o.id)" x-cloak
                                                class="absolute inset-0 flex items-center justify-center bg-cyan-500/30 text-sm font-black text-white">✓</span>
                                        </span>

                                        <span class="block truncate px-1 py-0.5 text-center text-[9px] font-black text-slate-400"
                                            x-text="o.name"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div x-show="val" x-cloak class="mt-2.5 flex flex-wrap items-center gap-2">
                            <span class="h-9 w-9 shrink-0 overflow-hidden rounded-lg border border-cyan-500/40 bg-slate-950">
                                <template x-if="valImagen">
                                    <img :src="valImagen" alt="" class="h-full w-full object-cover">
                                </template>
                            </span>

                            <p class="min-w-0 flex-1 text-[11px] text-slate-300">
                                Se activará cuando la entidad tenga
                                <strong class="text-white" x-text="catNombre + ' = «' + valNombre + '»'"></strong>.
                            </p>

                            <select name="new_relation_type"
                                class="rounded-lg border-slate-800 bg-slate-950 py-1.5 text-[11px] text-slate-200 focus:border-cyan-500 focus:ring-cyan-500">
                                <option value="ACTIVATES">Lo activa</option>
                                <option value="CONTEXT">Es su contexto</option>
                                <option value="RELATED">Solo relacionada</option>
                            </select>

                            <button type="button" @click="val = ''; valNombre = ''; valImagen = null"
                                class="rounded-lg border border-slate-800 px-2 py-1.5 text-[10px] font-black text-slate-400 transition hover:border-rose-500 hover:text-rose-300">
                                Quitar
                            </button>
                        </div>

                    </div>

                </div>


                {{-- Al editar no se cambia el molde: se dice, en vez de esconderlo --}}
            @else
                <div class="border-t border-slate-800 bg-slate-950/40 px-4 py-3">
                    <p class="text-[11px] leading-relaxed text-slate-500">
                        El molde de una versión no se cambia al editarla: sería otra versión distinta. Si te
                        equivocaste de molde, crea la correcta y elimina esta.
                    </p>
                </div>
            @endunless

        </section>


        {{-- ===================================================== --}}
        {{-- PASO 2 — LA VERSIÓN --}}
        {{-- ===================================================== --}}

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-500 font-mono text-[11px] font-black text-white">2</span>

                <div class="min-w-0 flex-1">
                    <h2 class="text-[13px] font-black text-white">¿Cómo se ve {{ $entity->name }} así?</h2>
                    <p class="text-[10px] text-slate-500">
                        La cara, el nombre y qué cambia respecto a la entidad original.
                    </p>
                </div>
            </div>

            <div class="grid gap-4 p-4 lg:grid-cols-[minmax(0,300px)_minmax(0,1fr)]">

                {{-- ---------- LA CARA ---------- --}}

                <div class="space-y-2">

                    <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-950">
                        <div class="relative aspect-square overflow-hidden">
                            <template x-if="imagePreview">
                                <img :src="imagePreview" alt="" class="h-full w-full object-cover">
                            </template>
                            <template x-if="! imagePreview">
                                <span class="flex h-full w-full items-center justify-center text-4xl text-slate-800">◈</span>
                            </template>
                        </div>

                        <p class="border-t border-slate-800 px-2 py-1.5 text-center text-[9px] font-black uppercase tracking-wider text-slate-600">
                            Así se verá
                        </p>
                    </div>

                    @unless ($editing)
                        <input type="hidden" name="image_source" :value="imageSource">

                        <div class="grid gap-1.5">

                            <button type="button" @click="imageSource = 'UPLOAD'; refreshImagePreview()"
                                :class="imageSource === 'UPLOAD'
                                    ? 'border-violet-500 bg-violet-500/10 text-violet-200'
                                    : 'border-slate-800 bg-slate-950 text-slate-400 hover:border-slate-700'"
                                class="rounded-xl border px-3 py-2 text-left text-[11px] font-black transition">
                                ↑ Subir una imagen nueva
                            </button>

                            @if ($entity->image)
                                <button type="button" @click="imageSource = 'ENTITY'; refreshImagePreview()"
                                    :class="imageSource === 'ENTITY'
                                        ? 'border-indigo-500 bg-indigo-500/10 text-indigo-200'
                                        : 'border-slate-800 bg-slate-950 text-slate-400 hover:border-slate-700'"
                                    class="rounded-xl border px-3 py-2 text-left text-[11px] font-black transition">
                                    ✦ Copiar la de {{ $entity->name }}
                                </button>
                            @endif

                            <template x-if="entityVersions.length > 0">
                                <button type="button" @click="imageSource = 'VERSION'; refreshImagePreview()"
                                    :class="imageSource === 'VERSION'
                                        ? 'border-cyan-500 bg-cyan-500/10 text-cyan-200'
                                        : 'border-slate-800 bg-slate-950 text-slate-400 hover:border-slate-700'"
                                    class="rounded-xl border px-3 py-2 text-left text-[11px] font-black transition">
                                    ⇄ Copiar la de otra versión suya
                                </button>
                            </template>
                        </div>

                        <div x-show="imageSource === 'UPLOAD'" x-cloak>
                            <label class="block cursor-pointer rounded-xl border border-dashed border-slate-700 p-3 text-center transition hover:border-violet-500">
                                <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
                                    @change="previewUploadedImage($event)" class="sr-only">
                                <span class="block text-[11px] font-black text-slate-300">Elegir un archivo</span>
                                <span class="mt-0.5 block text-[9px] text-slate-600">JPG, PNG o WEBP · máximo 2 MB</span>
                            </label>
                        </div>

                        <div x-show="imageSource === 'VERSION'" x-cloak>
                            <div class="grid max-h-44 grid-cols-3 gap-1.5 overflow-y-auto">
                                <template x-for="item in entityVersions" :key="item.id">
                                    <button type="button"
                                        @click="sourceEntityVersionId = item.id; refreshImagePreview()"
                                        :title="item.name"
                                        :class="String(sourceEntityVersionId) === String(item.id)
                                            ? 'border-cyan-500 ring-1 ring-cyan-500'
                                            : 'border-slate-800 hover:border-slate-600'"
                                        class="overflow-hidden rounded-lg border bg-slate-950 transition">
                                        <span class="block aspect-square overflow-hidden bg-slate-900">
                                            <template x-if="item.image_url">
                                                <img :src="item.image_url" alt="" loading="lazy" class="h-full w-full object-cover">
                                            </template>
                                        </span>
                                        <span class="block truncate px-1 py-0.5 text-center text-[9px] font-black text-slate-400"
                                            x-text="item.version_name"></span>
                                    </button>
                                </template>
                            </div>

                            <input type="hidden" name="source_entity_version_id" :value="sourceEntityVersionId">
                        </div>
                    @else
                        <div @omni-image-selected="imagePreview = $event.detail.url">
                            <x-omni-image-upload name="image" label="Cambiar la cara de esta versión"
                                :current-url="$entityVersion?->image_url" :max-mb="2" surface="dark" />
                        </div>
                    @endunless

                </div>


                {{-- ---------- SUS DATOS ---------- --}}

                <div class="space-y-3">

                    <label class="block">
                        <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                            Nombre {{ $editing ? '' : '— si lo dejas vacío se pone solo' }}
                        </span>
                        <input type="text" name="name" value="{{ old('name', $entityVersion?->name) }}"
                            {{ $editing ? 'required' : '' }} maxlength="150"
                            :placeholder="suggestedEntityVersionName()"
                            class="w-full rounded-xl border-slate-800 bg-slate-900 text-xs text-slate-200 placeholder:text-slate-600 focus:border-indigo-500 focus:ring-indigo-500">

                        @unless ($editing)
                            <span class="mt-1 block text-[10px] text-slate-600">
                                Quedaría: <strong class="text-slate-400" x-text="suggestedEntityVersionName()"></strong>
                            </span>
                        @endunless
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                            Qué cambia en esta versión
                        </span>
                        <textarea name="description" rows="3" maxlength="5000"
                            placeholder="«Más alto, con la banda rasgada y el manto de Hokage.»"
                            class="w-full rounded-xl border-slate-800 bg-slate-900 text-xs text-slate-200 placeholder:text-slate-600 focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $entityVersion?->description) }}</textarea>
                    </label>


                    {{-- Las dos casillas que sí cambian el comportamiento --}}

                    <div class="grid gap-2 sm:grid-cols-2">

                        <label class="cursor-pointer rounded-xl border border-slate-800 bg-slate-950 p-3 transition has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-500/10">
                            <input type="hidden" name="inherit_base_attributes" value="0">
                            <input type="checkbox" name="inherit_base_attributes" value="1"
                                @checked(old('inherit_base_attributes', $entityVersion?->inherit_base_attributes ?? true))
                                class="sr-only">

                            <svg viewBox="0 0 110 34" class="h-8 w-full text-indigo-400" fill="none"
                                stroke="currentColor" stroke-width="1.3" stroke-linecap="round" aria-hidden="true">
                                <rect x="2" y="4" width="34" height="26" rx="3" />
                                <path d="M8 12h22M8 18h16M8 24h20" opacity=".5" />
                                <path d="M40 17h14M54 17l-5-3M54 17l-5 3" opacity=".7" />
                                <rect x="60" y="4" width="34" height="26" rx="3" stroke-dasharray="3 2" />
                                <path d="M66 12h22M66 18h16" opacity=".3" />
                                <path d="M66 24h20" stroke="currentColor" opacity=".9" />
                                <circle cx="100" cy="24" r="4" opacity=".8" />
                            </svg>

                            <span class="mt-1.5 block text-[11px] font-black text-slate-200">Heredar características</span>
                            <span class="mt-0.5 block text-[10px] leading-4 text-slate-500">
                                Parte de las de {{ $entity->name }} y solo guarda lo que cambies. Sin esto,
                                empieza en blanco y hay que rellenarlo todo.
                            </span>
                        </label>

                        <label class="cursor-pointer rounded-xl border border-slate-800 bg-slate-950 p-3 transition has-[:checked]:border-amber-500 has-[:checked]:bg-amber-500/10">
                            <input type="hidden" name="is_default" value="0">
                            <input type="checkbox" name="is_default" value="1"
                                @checked(old('is_default', $entityVersion?->is_default ?? false)) class="sr-only">

                            <svg viewBox="0 0 110 34" class="h-8 w-full text-amber-400" fill="none"
                                stroke="currentColor" stroke-width="1.3" stroke-linecap="round" aria-hidden="true">
                                <rect x="2" y="6" width="26" height="22" rx="3" opacity=".4" />
                                <rect x="34" y="4" width="30" height="26" rx="3" />
                                <path d="M49 9l2.6 5.3 5.8.8-4.2 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8-4.2-4.1 5.8-.8z" opacity=".9" />
                                <rect x="70" y="6" width="26" height="22" rx="3" opacity=".4" />
                                <path d="M100 17h6" opacity=".5" />
                            </svg>

                            <span class="mt-1.5 block text-[11px] font-black text-slate-200">Hacerla la base activa</span>
                            <span class="mt-0.5 block text-[10px] leading-4 text-slate-500">
                                La cara que el resto de la aplicación enseñará de {{ $entity->name }}.
                                Solo puede haber una: si marcas esta, la de ahora deja de serlo.
                            </span>
                        </label>

                    </div>

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- CONFIGURACIÓN AVANZADA --}}
        {{-- ===================================================== --}}

        {{--
            Cuatro campos que antes eran cuatro etiquetas sueltas —«Padre
            concreto», «Prioridad», «Orden», «Estado»— sin decir qué hacía
            ninguno. Cada uno lleva ahora su esquema: se entienden mirándolos.
        --}}

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <button type="button" @click="advancedOpen = !advancedOpen"
                class="flex w-full items-center gap-3 px-4 py-3 text-left transition hover:bg-slate-950/50">

                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-800 text-slate-400">
                    <x-omni-icon name="engranaje" size="h-3.5 w-3.5" />
                </span>

                <span class="min-w-0 flex-1">
                    <span class="block text-[13px] font-black text-white">Ajustes finos</span>
                    <span class="block text-[10px] text-slate-500">
                        Dónde cuelga, quién gana si dos encajan, en qué orden aparece y si cuenta.
                        Se puede dejar como está.
                    </span>
                </span>

                <span class="shrink-0 text-slate-500 transition" :class="advancedOpen ? 'rotate-90' : ''">
                    <x-omni-icon name="chevron-derecha" size="h-4 w-4" />
                </span>
            </button>

            <div x-show="advancedOpen" x-cloak x-collapse class="border-t border-slate-800 p-4">

                <div class="grid gap-3 lg:grid-cols-2 xl:grid-cols-4">

                    {{-- ---------- PADRE ---------- --}}
                    <div class="rounded-xl border border-slate-800 bg-slate-950 p-3">

                        <svg viewBox="0 0 110 44" class="h-11 w-full text-violet-400" fill="none"
                            stroke="currentColor" stroke-width="1.3" stroke-linecap="round" aria-hidden="true">
                            <rect x="38" y="2" width="34" height="14" rx="3" />
                            <path d="M55 16v8M31 24h48M31 24v6M79 24v6" opacity=".6" />
                            <rect x="14" y="30" width="34" height="12" rx="3" opacity=".85" />
                            <rect x="62" y="30" width="34" height="12" rx="3" stroke-dasharray="3 2" />
                        </svg>

                        <p class="mt-2 text-[11px] font-black text-white">De qué versión cuelga</p>
                        <p class="mt-0.5 text-[10px] leading-4 text-slate-500">
                            «Modo Sabio» cuelga de «Shippuden»: se hereda de la de arriba en vez de la
                            entidad original.
                        </p>

                        @unless ($editing)
                            <label class="mt-2 flex cursor-pointer items-start gap-2 rounded-lg border border-slate-800 bg-slate-900/60 p-2">
                                <input type="hidden" name="auto_parent" value="0">
                                <input type="checkbox" name="auto_parent" value="1" x-model="autoParent"
                                    class="mt-0.5 rounded border-slate-700 bg-slate-900 text-violet-500">
                                <span class="min-w-0">
                                    <span class="block text-[10px] font-black text-slate-200">Deducirlo solo</span>
                                    <span class="block text-[9px] leading-4 text-slate-500">
                                        Del molde padre, si lo tiene.
                                    </span>
                                </span>
                            </label>

                            <p x-show="autoParent && suggestedParentName()" x-cloak
                                class="mt-1.5 rounded-lg border border-violet-500/25 bg-violet-500/5 px-2 py-1 text-[10px] text-violet-200">
                                Colgará de <strong x-text="suggestedParentName()"></strong>.
                            </p>

                            <p x-show="autoParent && ! suggestedParentName()" x-cloak
                                class="mt-1.5 text-[10px] text-slate-600">
                                No hay padre que deducir: colgará de la entidad original.
                            </p>
                        @endunless

                        <select x-model="manualParentId"
                            x-show="{{ $editing ? 'true' : '! autoParent' }}" x-cloak
                            class="mt-2 w-full rounded-lg border-slate-800 bg-slate-900 text-[11px] text-slate-200 focus:border-violet-500 focus:ring-violet-500">
                            <option value="">Ninguna: cuelga de la entidad original</option>
                            @foreach ($parentEntityVersions as $parent)
                                <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                            @endforeach
                        </select>

                        <input type="hidden" name="parent_entity_version_id"
                            :value="autoParent && !editing ? suggestedParentId() : manualParentId">
                    </div>


                    {{-- ---------- PRIORIDAD ---------- --}}
                    <div class="rounded-xl border border-slate-800 bg-slate-950 p-3">

                        <svg viewBox="0 0 110 44" class="h-11 w-full text-cyan-400" fill="none"
                            stroke="currentColor" stroke-width="1.3" stroke-linecap="round" aria-hidden="true">
                            <rect x="6" y="24" width="20" height="16" rx="2" opacity=".5" />
                            <rect x="32" y="8" width="20" height="32" rx="2" />
                            <path d="M42 14l3 5h-6z" fill="currentColor" stroke="none" opacity=".9" />
                            <rect x="58" y="28" width="20" height="12" rx="2" opacity=".5" />
                            <path d="M86 34h18M86 34l4-4M86 34l4 4" opacity=".6" />
                            <text x="95" y="20" text-anchor="middle" fill="currentColor" stroke="none"
                                font-size="9" font-weight="700">gana</text>
                        </svg>

                        <p class="mt-2 text-[11px] font-black text-white">Prioridad</p>
                        <p class="mt-0.5 text-[10px] leading-4 text-slate-500">
                            Cuando dos versiones encajan a la vez en el mismo contexto, sale la del número
                            <strong class="text-slate-300">más alto</strong>. Si nunca chocan, da igual.
                        </p>

                        <input type="number" name="priority" value="{{ old('priority', $entityVersion?->priority ?? 0) }}"
                            min="-100000" max="100000"
                            class="mt-2 w-full rounded-lg border-slate-800 bg-slate-900 text-[11px] text-slate-200 focus:border-cyan-500 focus:ring-cyan-500">
                    </div>


                    {{-- ---------- ORDEN ---------- --}}
                    <div class="rounded-xl border border-slate-800 bg-slate-950 p-3">

                        <svg viewBox="0 0 110 44" class="h-11 w-full text-slate-400" fill="none"
                            stroke="currentColor" stroke-width="1.3" stroke-linecap="round" aria-hidden="true">
                            <rect x="8" y="6" width="94" height="9" rx="2" opacity=".8" />
                            <rect x="8" y="18" width="94" height="9" rx="2" opacity=".55" />
                            <rect x="8" y="30" width="94" height="9" rx="2" opacity=".35" />
                            <text x="16" y="13" fill="currentColor" stroke="none" font-size="7" font-weight="700">1</text>
                            <text x="16" y="25" fill="currentColor" stroke="none" font-size="7" font-weight="700">2</text>
                            <text x="16" y="37" fill="currentColor" stroke="none" font-size="7" font-weight="700">3</text>
                        </svg>

                        <p class="mt-2 text-[11px] font-black text-white">Orden en los listados</p>
                        <p class="mt-0.5 text-[10px] leading-4 text-slate-500">
                            Solo coloca la ficha en las listas, de menor a mayor.
                            <strong class="text-slate-300">No cambia nada del motor.</strong>
                        </p>

                        <input type="number" name="sort_order" value="{{ old('sort_order', $entityVersion?->sort_order ?? 0) }}"
                            min="0" max="1000000"
                            class="mt-2 w-full rounded-lg border-slate-800 bg-slate-900 text-[11px] text-slate-200 focus:border-slate-500 focus:ring-slate-500">
                    </div>


                    {{-- ---------- ESTADO ---------- --}}
                    <div class="rounded-xl border border-slate-800 bg-slate-950 p-3">

                        <svg viewBox="0 0 110 44" class="h-11 w-full text-emerald-400" fill="none"
                            stroke="currentColor" stroke-width="1.3" stroke-linecap="round" aria-hidden="true">
                            <rect x="6" y="12" width="40" height="20" rx="10" />
                            <circle cx="36" cy="22" r="6" fill="currentColor" stroke="none" opacity=".9" />
                            <path d="M56 22h12M68 22l-4-3M68 22l-4 3" opacity=".5" />
                            <rect x="74" y="12" width="30" height="20" rx="3" stroke-dasharray="3 2" opacity=".6" />
                            <path d="M82 22h14" opacity=".4" />
                        </svg>

                        <p class="mt-2 text-[11px] font-black text-white">Estado</p>
                        <p class="mt-0.5 text-[10px] leading-4 text-slate-500">
                            Una versión <strong class="text-slate-300">inactiva o archivada</strong> sigue
                            guardada pero deja de ofrecerse y el motor no la elige.
                        </p>

                        <select name="status"
                            class="mt-2 w-full rounded-lg border-slate-800 bg-slate-900 text-[11px] text-slate-200 focus:border-emerald-500 focus:ring-emerald-500">
                            @foreach (['ACTIVE' => 'Activa — cuenta en todas partes', 'INACTIVE' => 'Inactiva — guardada, fuera de juego', 'ARCHIVED' => 'Archivada — retirada'] as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected(old('status', $entityVersion?->status ?? 'ACTIVE') === $valor)>
                                    {{ $etiqueta }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- GUARDAR --}}
        {{-- ===================================================== --}}

        <div class="sticky bottom-4 z-20 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-800 bg-slate-950/95 px-4 py-3 backdrop-blur">

            <p class="min-w-0 flex-1 text-[11px] text-slate-400">
                @if ($editing)
                    Se guardan los cambios de <strong class="text-white">{{ $entityVersion->name }}</strong>.
                @else
                    <span x-show="mode === 'EXISTING'">
                        Se creará <strong class="text-white" x-text="suggestedEntityVersionName()"></strong>.
                    </span>
                    <span x-show="mode !== 'EXISTING'" x-cloak>
                        Se creará el molde
                        <strong class="text-cyan-300" x-text="newVersionName || '(sin nombre todavía)'"></strong>
                        <span class="text-slate-600">y</span>
                        <strong class="text-white" x-text="suggestedEntityVersionName()"></strong>.
                    </span>
                @endif
            </p>

            <a href="{{ route('entity-versions.index', $entity) }}"
                class="rounded-xl border border-slate-800 px-4 py-2 text-[11px] font-black text-slate-400 transition hover:text-white">
                Cancelar
            </a>

            <button type="submit"
                class="rounded-xl bg-violet-500 px-5 py-2.5 text-[11px] font-black text-white transition hover:bg-violet-400">
                {{ $editing ? 'Guardar los cambios' : 'Crear la versión' }}
            </button>
        </div>

    </form>

    <script>
        function entityVersionBuilder(
            config
        ) {

            return {

                editing:
                    !!config.editing,

                entityName: config.entityName,

                entityImageUrl: config.entityImageUrl,

                versions: config.versions ?? [],

                entityVersions: config.entityVersions ?? [],

                catalogs: config.catalogs ?? [],


                /*
                |--------------------------------------------------------------------------
                | Definición
                |--------------------------------------------------------------------------
                */

                mode: config.editing ?
                    'EXISTING' : (
                        config.initialMode ||
                        'EXISTING'
                    ),

                selectedVersionId: String(
                    config.initialVersionId ||
                    ''
                ),

                versionSearch: '',

                newVersionName: @js(old('new_version_name', '')),

                newVersionParentId: String(
                    config.initialNewParent ||
                    ''
                ),

                definitionImageMode: @js(old('definition_image_mode', 'SAME')),


                /*
                |--------------------------------------------------------------------------
                | Imagen
                |--------------------------------------------------------------------------
                */

                imageSource: config.initialImageSource ||
                    'UPLOAD',

                imagePreview: config.initialImageUrl ||
                    null,

                uploadedPreview: null,

                sourceEntityVersionId: @js((string) old('source_entity_version_id', '')),


                /*
                |--------------------------------------------------------------------------
                | Jerarquía
                |--------------------------------------------------------------------------
                */

                autoParent: @js(old('auto_parent', true)),

                manualParentId: String(
                    config.initialParent ||
                    ''
                ),


                advancedOpen: @js($errors->has('parent_entity_version_id') || $errors->has('priority') || $errors->has('sort_order') || $errors->has('status')),


                /*
                |--------------------------------------------------------------------------
                | Buscar definiciones
                |--------------------------------------------------------------------------
                */

                filteredVersions() {

                    const query =
                        this.versionSearch
                        .trim()
                        .toLowerCase();


                    if (!query) {

                        return this.versions;
                    }


                    return this.versions.filter(
                        item => {

                            const text = [
                                    item.name,
                                    item.code,
                                    item.kind,
                                    item.scope,
                                    item.parent_name,
                                ]
                                .filter(Boolean)
                                .join(' ')
                                .toLowerCase();


                            return text.includes(
                                query
                            );
                        }
                    );
                },


                selectedDefinition() {

                    return this.versions.find(
                            item =>
                            String(item.id) ===
                            String(
                                this.selectedVersionId
                            )
                        ) ||
                        null;
                },


                definitionName() {

                    if (
                        this.mode === 'EXISTING'
                    ) {

                        return this
                            .selectedDefinition()
                            ?.name ||
                            'Versión';
                    }


                    return this.newVersionName
                        ?.trim() ||
                        'Nueva Versión';
                },


                /*
                |--------------------------------------------------------------------------
                | Nombre automático
                |--------------------------------------------------------------------------
                */

                suggestedEntityVersionName() {

                    return `${this.entityName} — ${this.definitionName()}`;
                },


                /*
                |--------------------------------------------------------------------------
                | Padre definición
                |--------------------------------------------------------------------------
                */

                definitionParentVersionId() {

                    if (
                        this.mode === 'EXISTING'
                    ) {

                        return String(
                            this
                            .selectedDefinition()
                            ?.parent_version_id ||
                            ''
                        );
                    }


                    return String(
                        this.newVersionParentId ||
                        ''
                    );
                },


                suggestedParentId() {

                    const parentVersionId =
                        this.definitionParentVersionId();


                    if (!parentVersionId) {

                        return '';
                    }


                    const parent =
                        this.entityVersions.find(
                            item =>
                            String(
                                item.version_id
                            ) ===
                            String(
                                parentVersionId
                            )
                        );


                    return parent ?
                        String(parent.id) :
                        '';
                },


                suggestedParentName() {

                    const id =
                        this.suggestedParentId();


                    if (!id) {
                        return '';
                    }


                    return this.entityVersions.find(
                            item =>
                            String(item.id) ===
                            String(id)
                        )
                        ?.name ||
                        '';
                },


                /*
                |--------------------------------------------------------------------------
                | Imagen
                |--------------------------------------------------------------------------
                */

                previewUploadedImage(
                    event
                ) {

                    const file =
                        event.target.files
                        ?.[
                            0
                        ];


                    if (!file) {
                        return;
                    }


                    if (
                        this.uploadedPreview &&
                        this.uploadedPreview
                        .startsWith(
                            'blob:'
                        )
                    ) {

                        URL.revokeObjectURL(
                            this.uploadedPreview
                        );
                    }


                    this.uploadedPreview =
                        URL.createObjectURL(
                            file
                        );


                    this.imageSource =
                        'UPLOAD';


                    this.imagePreview =
                        this.uploadedPreview;
                },


                refreshImagePreview() {

                    if (
                        this.imageSource ===
                        'UPLOAD'
                    ) {

                        this.imagePreview =
                            this.uploadedPreview;

                        return;
                    }


                    if (
                        this.imageSource ===
                        'ENTITY'
                    ) {

                        this.imagePreview =
                            this.entityImageUrl;

                        return;
                    }


                    const selected =
                        this.entityVersions.find(
                            item =>
                            String(item.id) ===
                            String(
                                this.sourceEntityVersionId
                            )
                        );


                    this.imagePreview =
                        selected
                        ?.image_url ||
                        null;
                },


                /*
                |--------------------------------------------------------------------------
                | Catálogos
                |--------------------------------------------------------------------------
                */

                optionsFor(
                    attributeId
                ) {

                    return this.catalogs.find(
                            item =>
                            String(item.id) ===
                            String(attributeId)
                        )
                        ?.options || [];
                },
            };
        }
    </script>

</x-app-layout>
