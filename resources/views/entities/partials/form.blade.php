@php
    /*
     * Crear y editar una entidad.
     *
     * La misma pantalla sirve para las dos cosas, y responde en orden a las
     * cuatro preguntas que definen una entidad: QUIÉN ES, QUÉ ES (su tipo),
     * CÓMO ES (sus características) y DÓNDE VIVE (sus colecciones). La
     * publicación va al final porque es lo último que se decide.
     *
     * Dos decisiones que conviene no deshacer sin querer:
     *
     * - El bloque de características se incluye TAL CUAL desde su propio
     *   archivo. Es un componente de Alpine grande y quisquilloso -un error
     *   de sintaxis dentro se lleva por delante todos sus campos sin decir
     *   nada- y aquí solo se le da su sitio, no se le toca.
     *
     * - Los nombres de los campos son exactamente los que espera el
     *   controlador: `name`, `entity_type_id`, `image`, `remove_image`,
     *   `description`, `collection_ids[]`, `visibility`, `allow_cloning` y
     *   `status`. El rediseño cambia cómo se ven, no cómo se llaman.
     *
     * A la derecha, la ficha tal y como aparecerá en la biblioteca, en vivo:
     * elegir un tipo a ciegas y descubrir el resultado en otra pantalla es lo
     * que hacía que nadie los cambiara.
     */

    $editing = isset($entity) && $entity->exists;

    $currentName = old('name', $entity->name ?? '');

    $currentType = (string) old('entity_type_id', $entity->entity_type_id ?? request('type', ''));

    $selectedCollections = old(
        'collection_ids',
        $editing ? $entity->collections->pluck('id')->map(fn($id) => (string) $id)->all() : [],
    );

    $selectedCollections = array_map('strval', $selectedCollections);

    $currentDescription = old('description', $entity->description ?? '');

    $currentVisibility = old('visibility', $entity->visibility ?? 'PRIVATE');

    $currentStatus = old('status', $entity->status ?? 'ACTIVE');

    /*
     * Los tipos viajan al cliente con su cara para que la vista previa pueda
     * pintarlos sin volver al servidor. Solo lo que se enseña: nombre, icono,
     * color e imagen.
     */
    $tiposParaVista = $entityTypes->mapWithKeys(fn($t) => [
        (string) $t->id => [
            'name' => $t->name,
            'icon' => $t->icon ?: '◇',
            'color' => $t->color ?: '#6366f1',
            'image' => $t->image_url,
        ],
    ]);

    $visibilidades = [
        ['PRIVATE', 'Privada', 'Solo tú la ves. Es lo normal mientras la construyes.'],
        ['PUBLIC', 'Pública', 'Aparece en la comunidad y otros pueden encontrarla.'],
        ['UNLISTED', 'No listada', 'No sale en listas, pero quien tenga el enlace entra.'],
    ];

    /*
     * Los tres estados que acepta la validación. No hay «borrador» aquí: eso
     * son las plantillas de torneo y de fase. Ofrecer un valor que el
     * servidor rechaza haría fallar el guardado sin explicar por qué.
     */
    $estados = [
        ['ACTIVE', 'Activa', 'Lista para usarse en torneos y universos.'],
        ['INACTIVE', 'Inactiva', 'Existe, pero no quieres que se use por ahora.'],
        ['ARCHIVED', 'Archivada', 'Fuera de circulación, pero sin borrar.'],
    ];
@endphp


<div x-data="{

    name: @js($currentName),

    selectedType: @js($currentType),

    typeSearch: '',

    collectionSearch: '',

    imagePreview: @js($editing ? $entity->image_url : null),

    removeImage: false,

    description: @js($currentDescription),

    visibility: @js($currentVisibility),

    status: @js($currentStatus),

    collections: @js($selectedCollections),

    types: @js($tiposParaVista),

    dirty: false,


    /*
     * La subida de imagen es un componente aparte y avisa por eventos. Se
     * escuchan para que la vista previa de la derecha enseñe lo mismo que
     * acaba de elegirse, sin duplicar la lógica del componente.
     */
    onImageSelected(event) {

        this.imagePreview =
            event.detail?.url
            ?? null;

        this.removeImage =
            false;

        this.dirty = true;
    },


    onImageCleared() {

        this.imagePreview =
            null;

        this.removeImage =
            true;

        this.dirty = true;
    },


    onImageRestored(event) {

        this.imagePreview =
            event.detail?.url
            ?? null;

        this.removeImage =
            false;

        this.dirty = true;
    },


    toggleCollection(id) {

        const clave =
            String(id);


        if (
            this.collections.includes(clave)
        ) {

            this.collections =
                this.collections.filter(
                    actual => actual !== clave
                );
        } else {

            this.collections =
                [...this.collections, clave];
        }

        this.dirty = true;
    },


    hasCollection(id) {

        return this.collections
            .includes(String(id));
    },


    /* Lo que la ficha de la biblioteca enseñaría ahora mismo */
    get currentType() {

        return this.types[this.selectedType]
            ?? null;
    },


    typeColor() {

        return this.currentType?.color
            ?? '#6366f1';
    },


    typeName() {

        return this.currentType?.name
            ?? 'Sin tipo';
    },


    typeIcon() {

        return this.currentType?.icon
            ?? '◇';
    },


    statusLabel() {

        return {
            ACTIVE: 'Activa',
            INACTIVE: 'Inactiva',
            ARCHIVED: 'Archivada',
        }[this.status] ?? this.status;
    },


    visibilityLabel() {

        return {
            PRIVATE: 'Privada',
            PUBLIC: 'Pública',
            UNLISTED: 'No listada',
        }[this.visibility] ?? this.visibility;
    },


    slugify(value) {

        return value
            .toString()
            .normalize('NFD')
            .replace(
                /[̀-ͯ]/g,
                ''
            )
            .toLowerCase()
            .trim()
            .replace(
                /[^a-z0-9]+/g,
                '-'
            )
            .replace(
                /^-+|-+$/g,
                ''
            );
    }
}" @omni-image-selected="onImageSelected($event)" @omni-image-cleared="onImageCleared()"
    @omni-image-restored="onImageRestored($event)" @input="dirty = true" @change="dirty = true"
    class="grid gap-4 pb-4 xl:grid-cols-[minmax(0,1fr)_340px] xl:items-start">


    {{-- ============================================================= --}}
    {{-- LO QUE SE DEFINE --}}
    {{-- ============================================================= --}}

    <div class="space-y-4">

        @if ($errors->any())
            <section class="rounded-2xl border border-rose-500/40 bg-rose-500/10 p-4" role="alert">
                <p class="text-xs font-black uppercase tracking-wider text-rose-300">
                    No se pudo guardar
                </p>

                <ul class="mt-2 list-disc space-y-1 pl-5 text-[11px] font-bold text-rose-200/80">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </section>
        @endif


        {{-- ===================================================== --}}
        {{-- 01 · QUIÉN ES --}}
        {{-- ===================================================== --}}

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-500/15 text-[11px] font-black text-indigo-300">
                    01
                </span>
                <div>
                    <h2 class="text-sm font-black text-white">Quién es</h2>
                    <p class="text-[10px] text-slate-500">Su cara, su nombre y qué contar de ella.</p>
                </div>
            </header>

            <div class="grid gap-5 p-5 lg:grid-cols-[260px_1fr]">

                <div>
                    <x-omni-image-upload name="image" label="Su cara" surface="dark" :current-url="$editing ? $entity->image_url : null"
                        :max-mb="4" :remove-name="$editing ? 'remove_image' : null" />
                </div>

                <div class="space-y-4">

                    <div>
                        <label for="entity-name" class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                            Nombre *
                        </label>

                        <input id="entity-name" name="name" type="text" x-model="name" required maxlength="255"
                            placeholder="Ej. Naruto Uzumaki, Perú, Selección de 1998..."
                            class="mt-1.5 w-full rounded-xl border-slate-800 bg-slate-950 text-sm font-bold text-white placeholder:text-slate-700 focus:border-indigo-500 focus:ring-indigo-500">

                        <p class="mt-1.5 flex items-center gap-2 text-[10px] text-slate-600">
                            <span>Su enlace será</span>
                            <span class="rounded bg-slate-950 px-1.5 py-0.5 font-mono text-slate-500"
                                x-text="slugify(name) || '—'"></span>
                        </p>

                        @error('name')
                            <p class="mt-1.5 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="entity-description"
                            class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                            Descripción
                        </label>

                        <textarea id="entity-description" name="description" x-model="description" rows="5"
                            placeholder="Quién o qué es, de dónde viene, qué la distingue..."
                            class="mt-1.5 w-full rounded-xl border-slate-800 bg-slate-950 text-xs leading-relaxed text-slate-300 placeholder:text-slate-700 focus:border-indigo-500 focus:ring-indigo-500"></textarea>

                        @error('description')
                            <p class="mt-1.5 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div
                        class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-800 bg-slate-950 px-3 py-2">
                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">Código</span>
                        <span class="font-mono text-xs font-black text-indigo-300">
                            {{ $editing ? $entity->code : $previewCode }}
                        </span>
                        <span class="text-[9px] text-slate-600">se genera solo, no depende del nombre</span>
                    </div>

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- 02 · QUÉ ES --}}
        {{-- ===================================================== --}}

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-500/15 text-[11px] font-black text-violet-300">
                    02
                </span>
                <div class="min-w-0 flex-1">
                    <h2 class="text-sm font-black text-white">Qué es</h2>
                    <p class="text-[10px] text-slate-500">
                        Su tipo. Es una etiqueta para organizarte: no limita qué características puede
                        tener.
                    </p>
                </div>

                <a href="{{ route('entity-types.index') }}"
                    class="shrink-0 text-[10px] font-black text-slate-500 transition hover:text-violet-300">
                    Gestionar tipos →
                </a>
            </header>

            <div class="p-5">

                <input type="hidden" name="entity_type_id" :value="selectedType">

                @if ($entityTypes->isEmpty())

                    <div class="rounded-xl border border-dashed border-slate-800 px-4 py-8 text-center">
                        <p class="text-sm font-black text-white">Todavía no tienes tipos</p>
                        <p class="mx-auto mt-1 max-w-sm text-[11px] leading-relaxed text-slate-500">
                            Un tipo es «Personaje», «País», «Equipo»... Puedes crear la entidad sin
                            tipo y ponérselo después.
                        </p>

                        <a href="{{ route('entity-types.create') }}"
                            class="mt-3 inline-block rounded-xl border border-slate-700 px-4 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                            + Crear un tipo
                        </a>
                    </div>

                @else

                    @if ($entityTypes->count() > 8)
                        <label class="relative mb-3 block">
                            <span class="sr-only">Buscar tipo</span>

                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                                <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                            </span>

                            <input type="search" x-model="typeSearch" placeholder="Buscar entre tus tipos..."
                                class="w-full rounded-xl border-slate-800 bg-slate-950 pl-9 text-xs text-slate-200 placeholder:text-slate-700 focus:border-violet-500 focus:ring-violet-500">
                        </label>
                    @endif

                    <div class="grid gap-2 sm:grid-cols-3 lg:grid-cols-4">

                        {{-- Sin tipo también es una opción, y hay que poder elegirla --}}
                        <button type="button" @click="selectedType = ''; dirty = true"
                            x-show="!typeSearch"
                            :class="selectedType === '' ?
                                'border-slate-500 bg-slate-800/60' :
                                'border-slate-800 bg-slate-950 hover:border-slate-700'"
                            class="flex items-center gap-2.5 rounded-xl border p-2.5 text-left transition">

                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-dashed border-slate-700 text-lg text-slate-600">
                                ◇
                            </span>

                            <span class="min-w-0">
                                <span class="block truncate text-[11px] font-black text-slate-300">Sin tipo</span>
                                <span class="block text-[9px] text-slate-600">decidirlo después</span>
                            </span>
                        </button>

                        @foreach ($entityTypes as $tipoEntidad)
                            @php $colorTipo = $tipoEntidad->color ?: '#6366f1'; @endphp

                            <button type="button" @click="selectedType = '{{ $tipoEntidad->id }}'; dirty = true"
                                x-show="!typeSearch || '{{ mb_strtolower($tipoEntidad->name) }}'.includes(typeSearch.toLowerCase())"
                                :aria-pressed="selectedType === '{{ $tipoEntidad->id }}'"
                                :style="selectedType === '{{ $tipoEntidad->id }}'
                                    ? 'border-color: {{ $colorTipo }}; background-color: {{ $colorTipo }}1a'
                                    : ''"
                                :class="selectedType === '{{ $tipoEntidad->id }}' ?
                                    '' :
                                    'border-slate-800 bg-slate-950 hover:border-slate-700'"
                                class="flex items-center gap-2.5 rounded-xl border p-2.5 text-left transition">

                                <span class="h-10 w-10 shrink-0 overflow-hidden rounded-lg bg-slate-900">
                                    @if ($tipoEntidad->image_url)
                                        <img src="{{ $tipoEntidad->image_url }}" alt="" loading="lazy"
                                            class="h-full w-full object-cover">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-lg"
                                            style="color: {{ $colorTipo }}">{{ $tipoEntidad->icon ?: '◇' }}</span>
                                    @endif
                                </span>

                                <span class="min-w-0">
                                    <span class="block truncate text-[11px] font-black text-white">
                                        {{ $tipoEntidad->name }}
                                    </span>
                                    <span class="block truncate text-[9px]" style="color: {{ $colorTipo }}">
                                        {{ $tipoEntidad->entities_count ?? 0 }} entidades
                                    </span>
                                </span>
                            </button>
                        @endforeach

                    </div>

                @endif

                @error('entity_type_id')
                    <p class="mt-2 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                @enderror

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- 03 · CÓMO ES --}}
        {{-- ===================================================== --}}

        {{--
            El constructor de características, intacto.

            Es un componente de Alpine grande y quisquilloso: un error de
            sintaxis dentro se lleva por delante todos sus campos sin decir
            nada. Aquí solo se le da su sitio.
        --}}

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-cyan-500/15 text-[11px] font-black text-cyan-300">
                    03
                </span>
                <div class="min-w-0 flex-1">
                    <h2 class="text-sm font-black text-white">Cómo es</h2>
                    <p class="text-[10px] text-slate-500">
                        Sus características y el valor de cada una. Se eligen de tus atributos.
                    </p>
                </div>

                <a href="{{ route('attributes.index') }}"
                    class="shrink-0 text-[10px] font-black text-slate-500 transition hover:text-cyan-300">
                    Gestionar atributos →
                </a>
            </header>

            <div class="p-5">
                @include('entities.partials.characteristics-builder')
            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- 04 · DÓNDE VIVE --}}
        {{-- ===================================================== --}}

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/15 text-[11px] font-black text-emerald-300">
                    04
                </span>
                <div class="min-w-0 flex-1">
                    <h2 class="text-sm font-black text-white">Dónde vive</h2>
                    <p class="text-[10px] text-slate-500">
                        Las colecciones donde estará. Puede estar en varias, o en ninguna.
                    </p>
                </div>

                <span class="shrink-0 rounded-lg bg-emerald-500/15 px-2 py-1 font-mono text-[11px] font-black text-emerald-300"
                    x-text="collections.length"></span>
            </header>

            <div class="p-5">

                @if ($collections->isEmpty())

                    <div class="rounded-xl border border-dashed border-slate-800 px-4 py-8 text-center">
                        <p class="text-sm font-black text-white">Todavía no tienes colecciones</p>
                        <p class="mx-auto mt-1 max-w-sm text-[11px] leading-relaxed text-slate-500">
                            Una colección agrupa entidades que compiten juntas o que quieres tener a
                            mano. La entidad puede existir sin ninguna.
                        </p>

                        <a href="{{ route('collections.create') }}"
                            class="mt-3 inline-block rounded-xl border border-slate-700 px-4 py-2 text-[11px] font-black text-slate-300 transition hover:border-emerald-500 hover:text-emerald-300">
                            + Crear una colección
                        </a>
                    </div>

                @else

                    @if ($collections->count() > 8)
                        <label class="relative mb-3 block">
                            <span class="sr-only">Buscar colección</span>

                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                                <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                            </span>

                            <input type="search" x-model="collectionSearch"
                                placeholder="Buscar entre tus colecciones..."
                                class="w-full rounded-xl border-slate-800 bg-slate-950 pl-9 text-xs text-slate-200 placeholder:text-slate-700 focus:border-emerald-500 focus:ring-emerald-500">
                        </label>
                    @endif

                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($collections as $coleccion)
                            @php $colorColeccion = $coleccion->color ?: '#10b981'; @endphp

                            {{--
                                La casilla real viaja escondida dentro de la etiqueta: el
                                cuadro entero es el area de click, pero lo que se envia
                                sigue siendo un checkbox con su name de siempre.
                            --}}
                            <label
                                x-show="!collectionSearch || '{{ mb_strtolower($coleccion->name) }}'.includes(collectionSearch.toLowerCase())"
                                :style="hasCollection('{{ $coleccion->id }}')
                                    ? 'border-color: {{ $colorColeccion }}; background-color: {{ $colorColeccion }}1a'
                                    : ''"
                                :class="hasCollection('{{ $coleccion->id }}') ?
                                    '' :
                                    'border-slate-800 bg-slate-950 hover:border-slate-700'"
                                class="flex cursor-pointer items-center gap-2.5 rounded-xl border p-2.5 transition">

                                <input type="checkbox" name="collection_ids[]" value="{{ $coleccion->id }}"
                                    @checked(in_array((string) $coleccion->id, $selectedCollections, true))
                                    @change="toggleCollection('{{ $coleccion->id }}')" class="sr-only">

                                <span class="h-10 w-10 shrink-0 overflow-hidden rounded-lg bg-slate-900">
                                    @if ($coleccion->image_url)
                                        <img src="{{ $coleccion->image_url }}" alt="" loading="lazy"
                                            class="h-full w-full object-cover">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-lg"
                                            style="color: {{ $colorColeccion }}">{{ $coleccion->icon ?: '◈' }}</span>
                                    @endif
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-[11px] font-black text-white">
                                        {{ $coleccion->name }}
                                    </span>
                                    <span class="block truncate text-[9px] text-slate-500">
                                        {{ $coleccion->entities_count ?? 0 }} entidades
                                    </span>
                                </span>

                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md border transition"
                                    :style="hasCollection('{{ $coleccion->id }}')
                                        ? 'background-color: {{ $colorColeccion }}; border-color: {{ $colorColeccion }}'
                                        : ''"
                                    :class="hasCollection('{{ $coleccion->id }}') ? 'text-slate-950' :
                                        'border-slate-700 text-transparent'">
                                    <span class="text-[11px] font-black">✓</span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                @endif

                @error('collection_ids')
                    <p class="mt-2 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                @enderror

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- 05 · PUBLICACIÓN --}}
        {{-- ===================================================== --}}

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-500/15 text-[11px] font-black text-sky-300">
                    05
                </span>
                <div>
                    <h2 class="text-sm font-black text-white">Publicación</h2>
                    <p class="text-[10px] text-slate-500">Quién la ve y en qué estado está.</p>
                </div>
            </header>

            <div class="space-y-4 p-5">

                <div>
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Visibilidad</p>

                    <div class="mt-2 grid gap-2 sm:grid-cols-3">
                        @foreach ($visibilidades as [$valor, $titulo, $texto])
                            <label
                                :class="visibility === '{{ $valor }}' ?
                                    'border-sky-500/50 bg-sky-500/10' :
                                    'border-slate-800 bg-slate-950 hover:border-slate-700'"
                                class="cursor-pointer rounded-xl border p-3 transition">

                                <input type="radio" name="visibility" value="{{ $valor }}" x-model="visibility"
                                    class="sr-only">

                                <span class="block text-xs font-black text-white">{{ $titulo }}</span>
                                <span class="mt-0.5 block text-[10px] leading-4 text-slate-500">{{ $texto }}</span>
                            </label>
                        @endforeach
                    </div>

                    @error('visibility')
                        <p class="mt-1.5 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Estado</p>

                    <div class="mt-2 grid gap-2 sm:grid-cols-3">
                        @foreach ($estados as [$valor, $titulo, $texto])
                            <label
                                :class="status === '{{ $valor }}' ?
                                    'border-emerald-500/50 bg-emerald-500/10' :
                                    'border-slate-800 bg-slate-950 hover:border-slate-700'"
                                class="cursor-pointer rounded-xl border p-3 transition">

                                <input type="radio" name="status" value="{{ $valor }}" x-model="status"
                                    class="sr-only">

                                <span class="block text-xs font-black text-white">{{ $titulo }}</span>
                                <span class="mt-0.5 block text-[10px] leading-4 text-slate-500">{{ $texto }}</span>
                            </label>
                        @endforeach
                    </div>

                    @error('status')
                        <p class="mt-1.5 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                    @enderror
                </div>

                {{-- El 0 va delante para que desmarcar signifique «no» y no «no lo dijo» --}}
                <input type="hidden" name="allow_cloning" value="0">

                <label
                    class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-800 bg-slate-950 p-3 transition hover:border-sky-500/40">
                    <input type="checkbox" name="allow_cloning" value="1"
                        @checked(old('allow_cloning', $entity->allow_cloning ?? true))
                        class="mt-0.5 rounded border-slate-700 bg-slate-900 text-sky-500 focus:ring-sky-500">
                    <span>
                        <span class="block text-xs font-black text-white">Permitir que la copien</span>
                        <span class="mt-0.5 block text-[10px] leading-4 text-slate-500">
                            Solo tiene efecto cuando la entidad es pública.
                        </span>
                    </span>
                </label>

            </div>

        </section>

    </div>


    {{-- ============================================================= --}}
    {{-- LO QUE SE VERÁ --}}
    {{-- ============================================================= --}}

    <aside class="space-y-4 xl:sticky xl:top-4">

        <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">

            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                Así se verá en la biblioteca
            </p>

            <article class="mt-3 overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <div class="relative aspect-[4/5] overflow-hidden bg-slate-950">
                    <template x-if="imagePreview">
                        <img :src="imagePreview" alt="" class="h-full w-full object-cover">
                    </template>

                    <template x-if="!imagePreview">
                        <span class="flex h-full w-full items-center justify-center text-5xl font-black"
                            :style="'color: ' + typeColor() + '44'" x-text="typeIcon()"></span>
                    </template>

                    <span class="absolute inset-x-0 bottom-0 h-2/5 bg-gradient-to-t from-slate-950 to-transparent"
                        x-show="imagePreview"></span>

                    <span class="absolute left-2 top-2 flex items-center gap-1.5 rounded-lg border bg-slate-950/85 px-2 py-1"
                        :style="'border-color: ' + typeColor() + '66'">
                        <span class="text-[11px]" x-text="typeIcon()"></span>
                        <span class="text-[9px] font-black uppercase tracking-wider" :style="'color: ' + typeColor()"
                            x-text="typeName()"></span>
                    </span>

                    <span class="absolute right-2 top-2 rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider"
                        :class="status === 'ACTIVE' ?
                            'bg-emerald-500/15 text-emerald-300' :
                            (status === 'INACTIVE' ? 'bg-amber-500/15 text-amber-300' : 'bg-slate-800 text-slate-500')"
                        x-text="statusLabel()"></span>
                </div>

                <div class="p-2.5">
                    <p class="truncate text-[12px] font-black text-white" x-text="name || 'Entidad sin nombre'"></p>

                    <p class="font-mono text-[9px] text-slate-600">
                        {{ $editing ? $entity->code : $previewCode }}
                    </p>

                    <p class="mt-1 line-clamp-2 text-[10px] leading-relaxed text-slate-500"
                        x-text="description || 'Sin descripción.'"></p>

                    <div class="mt-2 flex flex-wrap gap-1">
                        <span class="rounded-lg border border-slate-800 bg-slate-950 px-2 py-1 text-[9px] font-bold text-slate-400"
                            x-text="visibilityLabel()"></span>

                        <span x-show="collections.length"
                            class="rounded-lg border border-slate-800 bg-slate-950 px-2 py-1 text-[9px] font-bold text-emerald-300">
                            <span x-text="collections.length"></span> colecciones
                        </span>
                    </div>
                </div>

            </article>

            <p class="mt-2 text-[10px] leading-4 text-slate-600">
                Las características se cuentan solas a partir de lo que pongas en el bloque 03.
            </p>

        </section>


        {{-- Lo que aquí no se decide --}}

        @if ($editing)
            <section class="rounded-2xl border border-violet-500/30 bg-violet-500/5 p-4">
                <p class="text-[9px] font-black uppercase tracking-wider text-violet-300">
                    Lo que no se decide aquí
                </p>

                <p class="mt-2 text-[11px] leading-4 text-slate-400">
                    Esto edita la entidad <strong class="text-white">original</strong>. Si tiene una
                    Base activa, la cara que ve el resto de la aplicación es la de esa versión, y se
                    edita desde ella.
                </p>

                <a href="{{ route('entity-versions.index', $entity) }}"
                    class="mt-3 block rounded-xl border border-violet-500/40 bg-slate-950 px-3 py-2 text-center text-[11px] font-black text-violet-300 transition hover:bg-slate-900">
                    Ver sus versiones →
                </a>
            </section>
        @else
            <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">
                <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Y después</p>

                <p class="mt-2 text-[11px] leading-4 text-slate-500">
                    En cuanto exista podrás darle versiones —la misma entidad en otro momento u otra
                    realidad— y elegir cuál de ellas es la cara que ve el resto de la aplicación.
                </p>
            </section>
        @endif

    </aside>


    {{-- ============================================================= --}}
    {{-- LA BARRA DE GUARDAR --}}
    {{-- ============================================================= --}}

    {{--
        Pegada abajo, pero DENTRO del contenido y no a la ventana: con el
        sidebar delante, una barra fija a la ventana empezaría debajo de él.
    --}}

    <div
        class="sticky bottom-4 z-30 rounded-2xl border border-slate-800 bg-slate-950/95 shadow-2xl shadow-slate-950/60 backdrop-blur xl:col-span-2">

        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">

            <div class="min-h-4 text-[11px] font-bold">
                <span x-show="dirty" x-cloak class="text-amber-300">● Hay cambios sin guardar</span>
                <span x-show="!dirty" class="text-slate-600">
                    {{ $editing ? 'Sin cambios pendientes' : 'Rellena lo necesario y créala' }}
                </span>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ $editing ? route('entities.show', $entity) : route('entities.index') }}"
                    class="rounded-xl border border-slate-800 px-4 py-2.5 text-[11px] font-black text-slate-400 transition hover:border-slate-600 hover:text-slate-200">
                    Cancelar
                </a>

                <button type="submit"
                    class="rounded-xl bg-indigo-500 px-5 py-2.5 text-[11px] font-black text-white transition hover:bg-indigo-400">
                    {{ $editing ? 'Guardar cambios' : 'Crear la entidad' }}
                </button>
            </div>

        </div>

    </div>

</div>
