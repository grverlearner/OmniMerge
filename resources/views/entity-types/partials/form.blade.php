@php
    /*
     * Crear y editar un tipo de entidad.
     *
     * Un tipo es poca cosa —un nombre, un icono, un color— pero es lo que se
     * ve en cada ficha, en cada lista y en cada filtro de la biblioteca. Por
     * eso esta pantalla es sobre todo una VISTA PREVIA: a la derecha se ve, en
     * vivo, cómo va a quedar en los cuatro sitios donde aparece.
     *
     * Los nombres de los campos son los que espera el controlador y no se
     * tocan: `name`, `description`, `image`, `remove_image`, `icon`, `color`,
     * `status` y `sort_order`.
     */

    $editing = isset($entityType) && $entityType->exists;

    $initialName = old('name', $entityType->name ?? '');

    $initialIcon = old('icon', $entityType->icon ?? '◇');

    $initialColor = old('color', $entityType->color ?? '#6366F1');

    $initialDescription = old('description', $entityType->description ?? '');

    $initialStatus = old('status', $entityType->status ?? 'ACTIVE');

    $initialOrder = old('sort_order', $entityType->sort_order ?? 0);

    /*
     * Iconos sugeridos: atajo, no jaula. El campo admite cualquier cosa, y
     * quien quiera pegar un emoji puede.
     */
    $iconosSugeridos = ['◇', '◆', '★', '✦', '▲', '●', '■', '☷', '⚔', '🧍', '🌍', '🏛', '🐾', '🎭', '⚙', '🎬'];

    /*
     * Una paleta de arranque. El selector de color sigue estando: esto solo
     * evita tener que elegir un hexadecimal a pulso para el caso normal.
     */
    $coloresSugeridos = [
        '#6366F1', '#8B5CF6', '#EC4899', '#EF4444',
        '#F59E0B', '#10B981', '#06B6D4', '#3B82F6',
        '#84CC16', '#F97316', '#14B8A6', '#64748B',
    ];

    $estados = [
        ['ACTIVE', 'Activo', 'Se puede usar y aparece en los desplegables.'],
        ['INACTIVE', 'Inactivo', 'Existe, pero no quieres que se use por ahora.'],
        ['ARCHIVED', 'Archivado', 'Fuera de circulación, sin borrarlo.'],
    ];
@endphp


<div x-data="{

    name: @js($initialName),

    icon: @js($initialIcon),

    color: @js($initialColor),

    description: @js($initialDescription),

    status: @js($initialStatus),

    imagePreview: @js($editing ? $entityType->image_url : null),

    removeImage: false,

    dirty: false,


    /*
     * La subida de imagen es un componente aparte y avisa por eventos: se
     * escuchan para que la vista previa enseñe lo que acaba de elegirse, sin
     * duplicar su lógica.
     */
    onImageSelected(event) {

        this.imagePreview =
            event.detail?.url
            ?? null;

        this.removeImage = false;

        this.dirty = true;
    },


    onImageCleared() {

        this.imagePreview = null;

        this.removeImage = true;

        this.dirty = true;
    },


    onImageRestored(event) {

        this.imagePreview =
            event.detail?.url
            ?? null;

        this.removeImage = false;

        this.dirty = true;
    },


    statusLabel() {

        return {
            ACTIVE: 'Activo',
            INACTIVE: 'Inactivo',
            ARCHIVED: 'Archivado',
        }[this.status] ?? this.status;
    },


    /* Con qué se ve el tipo cuando no tiene imagen */
    get mark() {

        return this.icon || '◇';
    }
}" @omni-image-selected="onImageSelected($event)" @omni-image-cleared="onImageCleared()"
    @omni-image-restored="onImageRestored($event)" @input="dirty = true" @change="dirty = true"
    :style="'--tipo: ' + color" class="grid gap-4 pb-4 xl:grid-cols-[minmax(0,1fr)_340px] xl:items-start">


    {{-- ============================================================= --}}
    {{-- LO QUE SE DEFINE --}}
    {{-- ============================================================= --}}

    <div class="space-y-4">

        @if ($errors->any())
            <section class="rounded-2xl border border-rose-500/40 bg-rose-500/10 p-4" role="alert">
                <p class="text-xs font-black uppercase tracking-wider text-rose-300">No se pudo guardar</p>

                <ul class="mt-2 list-disc space-y-1 pl-5 text-[11px] font-bold text-rose-200/80">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </section>
        @endif


        {{-- ===================================================== --}}
        {{-- 01 · QUÉ ES --}}
        {{-- ===================================================== --}}

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-500/15 text-[11px] font-black text-indigo-300">
                    01
                </span>
                <div>
                    <h2 class="text-sm font-black text-white">Qué es</h2>
                    <p class="text-[10px] text-slate-500">
                        Cómo se llama y para qué lo vas a usar.
                    </p>
                </div>
            </header>

            <div class="space-y-4 p-5">

                <div>
                    <label for="name" class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                        Nombre *
                    </label>

                    <input id="name" name="name" type="text" x-model="name" required maxlength="100"
                        placeholder="Ej. Personaje, País, Equipo, Objeto…"
                        class="mt-1.5 w-full rounded-xl border-slate-800 bg-slate-950 text-sm font-bold text-white placeholder:text-slate-700 focus:border-indigo-500 focus:ring-indigo-500">

                    @error('name')
                        <p class="mt-1.5 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                        Descripción
                    </label>

                    <textarea id="description" name="description" x-model="description" rows="3" maxlength="1000"
                        placeholder="Qué clase de cosas van a llevar este tipo, y qué las distingue de las demás."
                        class="mt-1.5 w-full rounded-xl border-slate-800 bg-slate-950 text-xs leading-relaxed text-slate-300 placeholder:text-slate-700 focus:border-indigo-500 focus:ring-indigo-500"></textarea>

                    <p class="mt-1.5 text-[10px] leading-4 text-slate-600">
                        Un tipo es una <strong class="text-slate-500">etiqueta para organizarte</strong>: no limita
                        qué características puede tener una entidad. Cualquier entidad puede llevar
                        cualquier característica, tenga el tipo que tenga.
                    </p>

                    @error('description')
                        <p class="mt-1.5 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                    @enderror
                </div>

                @if ($editing)
                    <div class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-800 bg-slate-950 px-3 py-2">
                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">Código</span>
                        <span class="font-mono text-xs font-black text-indigo-300">{{ $entityType->code }}</span>
                        <span class="text-[9px] text-slate-600">se genera solo</span>
                    </div>
                @endif

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- 02 · CÓMO SE VE --}}
        {{-- ===================================================== --}}

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-500/15 text-[11px] font-black text-violet-300">
                    02
                </span>
                <div>
                    <h2 class="text-sm font-black text-white">Cómo se ve</h2>
                    <p class="text-[10px] text-slate-500">
                        Esto sale en cada entidad que lo lleve, en cada lista y en cada filtro.
                    </p>
                </div>
            </header>

            <div class="grid gap-5 p-5 lg:grid-cols-[240px_1fr]">

                <div>
                    <x-omni-image-upload name="image" label="Su imagen" surface="dark" :current-url="$editing ? $entityType->image_url : null"
                        :max-mb="4" :remove-name="$editing ? 'remove_image' : null" />

                    <p class="mt-2 text-[10px] leading-4 text-slate-600">
                        Opcional. Si no la pones se usa el icono sobre su color.
                    </p>
                </div>

                <div class="space-y-4">

                    {{-- El icono --}}
                    <div>
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Icono</p>

                        <div class="mt-2 flex flex-wrap items-center gap-1.5">
                            @foreach ($iconosSugeridos as $sugerido)
                                <button type="button" @click="icon = '{{ $sugerido }}'; dirty = true"
                                    :style="icon === '{{ $sugerido }}' ?
                                        ('border-color: ' + color + '; background-color: ' + color + '26') : ''"
                                    :class="icon === '{{ $sugerido }}' ? '' :
                                        'border-slate-800 bg-slate-950 hover:border-slate-600'"
                                    class="h-9 w-9 rounded-lg border text-base transition">
                                    {{ $sugerido }}
                                </button>
                            @endforeach

                            <input id="icon" name="icon" type="text" x-model="icon" maxlength="100"
                                placeholder="otro"
                                class="h-9 w-20 rounded-lg border-slate-800 bg-slate-950 text-center text-base text-white placeholder:text-[10px] placeholder:text-slate-700 focus:border-violet-500 focus:ring-violet-500">
                        </div>

                        @error('icon')
                            <p class="mt-1.5 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- El color --}}
                    <div>
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Color</p>

                        <div class="mt-2 flex flex-wrap items-center gap-1.5">
                            @foreach ($coloresSugeridos as $sugerido)
                                <button type="button" @click="color = '{{ $sugerido }}'; dirty = true"
                                    title="{{ $sugerido }}"
                                    :class="color.toUpperCase() === '{{ $sugerido }}' ?
                                        'border-white/50 ring-2 ring-white/20' :
                                        'border-slate-800 hover:border-slate-600'"
                                    class="flex h-9 w-9 items-center justify-center rounded-lg border bg-slate-950 transition">
                                    <span class="h-4 w-4 rounded-full"
                                        style="background-color: {{ $sugerido }}"></span>
                                </button>
                            @endforeach

                            <span class="flex items-center gap-1.5 rounded-lg border border-slate-800 bg-slate-950 px-1.5 py-1">
                                <input id="color" name="color" type="color" x-model="color"
                                    class="h-6 w-7 cursor-pointer rounded border-0 bg-transparent p-0">

                                <span class="font-mono text-[10px] text-slate-500" x-text="color.toUpperCase()"></span>
                            </span>
                        </div>

                        @error('color')
                            <p class="mt-1.5 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- 03 · CÓMO SE ORDENA --}}
        {{-- ===================================================== --}}

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/15 text-[11px] font-black text-emerald-300">
                    03
                </span>
                <div>
                    <h2 class="text-sm font-black text-white">Su sitio</h2>
                    <p class="text-[10px] text-slate-500">Si se puede usar, y dónde aparece en las listas.</p>
                </div>
            </header>

            <div class="space-y-4 p-5">

                <div>
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Estado</p>

                    <input type="hidden" name="status" :value="status">

                    <div class="mt-2 grid gap-2 sm:grid-cols-3">
                        @foreach ($estados as [$valor, $titulo, $texto])
                            <button type="button" @click="status = '{{ $valor }}'; dirty = true"
                                :aria-pressed="status === '{{ $valor }}'"
                                :class="status === '{{ $valor }}' ?
                                    'border-emerald-500/50 bg-emerald-500/10' :
                                    'border-slate-800 bg-slate-950 hover:border-slate-700'"
                                class="rounded-xl border p-3 text-left transition">
                                <span class="block text-xs font-black text-white">{{ $titulo }}</span>
                                <span class="mt-0.5 block text-[10px] leading-4 text-slate-500">{{ $texto }}</span>
                            </button>
                        @endforeach
                    </div>

                    @error('status')
                        <p class="mt-1.5 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                    @enderror
                </div>

                {{--
                    El orden ya existía en la base y se usaba para ordenar los
                    tipos, pero no se preguntaba en ninguna parte: se ordenaban
                    por un número que nadie podía cambiar.
                --}}
                <div>
                    <label for="sort_order" class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                        Orden en las listas
                    </label>

                    <div class="mt-1.5 flex flex-wrap items-center gap-2">
                        <input id="sort_order" name="sort_order" type="number" min="0" max="9999"
                            value="{{ $initialOrder }}"
                            class="w-28 rounded-xl border-slate-800 bg-slate-950 text-sm font-black text-white focus:border-emerald-500 focus:ring-emerald-500">

                        <span class="text-[10px] leading-4 text-slate-600">
                            Cuanto más bajo, más arriba sale. Los que empatan se ordenan por nombre.
                        </span>
                    </div>

                    @error('sort_order')
                        <p class="mt-1.5 text-[11px] font-bold text-rose-300">{{ $message }}</p>
                    @enderror
                </div>

            </div>

        </section>

    </div>


    {{-- ============================================================= --}}
    {{-- DÓNDE VA A SALIR --}}
    {{-- ============================================================= --}}

    {{--
        Un tipo no se mira en su propia ficha: se mira en los sitios donde
        aparece. Por eso la vista previa enseña los cuatro, y no una tarjeta
        genérica.
    --}}

    <aside class="space-y-4 xl:sticky xl:top-4">

        <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">

            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                Dónde va a salir
            </p>

            <div class="mt-3 space-y-3">

                {{-- 1 · Su propia ficha --}}
                <div>
                    <p class="mb-1 text-[9px] font-bold text-slate-600">Su ficha</p>

                    <div class="overflow-hidden rounded-xl border bg-slate-950"
                        :style="'border-color: ' + color + '44'">
                        <div class="relative flex items-center gap-3 p-3">
                            <span class="pointer-events-none absolute inset-0"
                                :style="'background: radial-gradient(70% 120% at 15% 0%, ' + color + '26, transparent 65%)'"></span>

                            <span class="relative h-12 w-12 shrink-0 overflow-hidden rounded-xl border bg-slate-950"
                                :style="'border-color: ' + color + '55'">
                                <template x-if="imagePreview">
                                    <img :src="imagePreview" alt="" class="h-full w-full object-cover">
                                </template>

                                <template x-if="!imagePreview">
                                    <span class="flex h-full w-full items-center justify-center text-2xl"
                                        :style="'color: ' + color" x-text="mark"></span>
                                </template>
                            </span>

                            <span class="relative min-w-0 flex-1">
                                <span class="block truncate text-[13px] font-black text-white"
                                    x-text="name || 'Tipo sin nombre'"></span>
                                <span class="block truncate text-[10px] text-slate-500"
                                    x-text="description || 'Sin descripción.'"></span>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- 2 · En una entidad --}}
                <div>
                    <p class="mb-1 text-[9px] font-bold text-slate-600">En la ficha de una entidad</p>

                    <div class="rounded-xl border border-slate-800 bg-slate-950 p-2.5">
                        <span class="inline-flex items-center gap-2 rounded-lg border px-2 py-1"
                            :style="'border-color: ' + color + '55'">
                            <template x-if="imagePreview">
                                <img :src="imagePreview" alt="" class="h-4 w-4 rounded object-cover">
                            </template>

                            <template x-if="!imagePreview">
                                <span class="text-[11px]" x-text="mark"></span>
                            </template>

                            <span class="text-[9px] font-black uppercase tracking-wider" :style="'color: ' + color"
                                x-text="name || 'Sin nombre'"></span>
                        </span>
                    </div>
                </div>

                {{-- 3 · En la galería --}}
                <div>
                    <p class="mb-1 text-[9px] font-bold text-slate-600">En la galería de entidades</p>

                    <div class="grid grid-cols-3 gap-1.5">
                        @foreach ([1, 2, 3] as $i)
                            <span class="relative block aspect-[3/4] overflow-hidden rounded-lg bg-slate-900 ring-1 ring-slate-800">
                                <span class="flex h-full w-full items-center justify-center text-2xl font-black"
                                    :style="'color: ' + color + '55; background: radial-gradient(120% 90% at 50% 0%, ' + color + '22, transparent 70%)'"
                                    x-text="mark"></span>

                                <span
                                    class="pointer-events-none absolute inset-x-0 bottom-0 h-2/3 bg-gradient-to-t from-slate-950 to-transparent"></span>

                                <span class="absolute inset-x-0 bottom-0 p-1">
                                    <span class="block truncate text-[8px] font-black text-white">Entidad</span>
                                    <span class="block truncate text-[7px] font-black uppercase tracking-wider"
                                        :style="'color: ' + color" x-text="name || '—'"></span>
                                </span>
                            </span>
                        @endforeach
                    </div>
                </div>

                {{-- 4 · En un filtro --}}
                <div>
                    <p class="mb-1 text-[9px] font-bold text-slate-600">En los filtros</p>

                    <div class="rounded-xl border border-slate-800 bg-slate-950 p-2">
                        <span class="flex items-center gap-2 text-[11px] text-slate-300">
                            <span class="h-2 w-2 rounded-full" :style="'background-color: ' + color"></span>
                            <span x-text="mark"></span>
                            <span x-text="name || 'Tipo sin nombre'"></span>
                            <span class="ml-auto font-mono text-[10px] text-slate-600">
                                {{ $editing ? $entityType->entities()->count() : 0 }}
                            </span>
                        </span>
                    </div>
                </div>

            </div>

            <p class="mt-3 flex items-center gap-2 border-t border-slate-800 pt-2.5 text-[10px] text-slate-600">
                <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider"
                    :class="status === 'ACTIVE' ?
                        'bg-emerald-500/15 text-emerald-300' :
                        (status === 'INACTIVE' ? 'bg-amber-500/15 text-amber-300' : 'bg-slate-800 text-slate-500')"
                    x-text="statusLabel()"></span>

                <span x-show="status !== 'ACTIVE'" x-cloak>No aparecerá en los desplegables.</span>
                <span x-show="status === 'ACTIVE'">Se podrá elegir al crear entidades.</span>
            </p>

        </section>


        @if ($editing && $entityType->entities()->count() > 0)
            <section class="rounded-2xl border border-amber-500/30 bg-amber-500/5 p-4">
                <p class="text-[9px] font-black uppercase tracking-wider text-amber-300">Ya está en uso</p>

                <p class="mt-2 text-[11px] leading-4 text-slate-400">
                    Lo llevan <strong class="text-white">{{ $entityType->entities()->count() }}</strong>
                    entidades. Cambiar su color o su icono cambia cómo se ven todas ellas, en todas
                    las pantallas.
                </p>

                <a href="{{ route('entity-types.show', $entityType) }}"
                    class="mt-3 block rounded-xl border border-amber-500/40 bg-slate-950 px-3 py-2 text-center text-[11px] font-black text-amber-300 transition hover:bg-slate-900">
                    Ver cuáles →
                </a>
            </section>
        @endif

    </aside>


    {{-- ============================================================= --}}
    {{-- LA BARRA DE GUARDAR --}}
    {{-- ============================================================= --}}

    <div
        class="sticky bottom-4 z-30 rounded-2xl border border-slate-800 bg-slate-950/95 shadow-2xl shadow-slate-950/60 backdrop-blur xl:col-span-2">

        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">

            <div class="min-h-4 text-[11px] font-bold">
                <span x-show="dirty" x-cloak class="text-amber-300">● Hay cambios sin guardar</span>
                <span x-show="!dirty" class="text-slate-600">
                    {{ $editing ? 'Sin cambios pendientes' : 'Rellena el nombre y créalo' }}
                </span>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ $editing ? route('entity-types.show', $entityType) : route('entity-types.index') }}"
                    class="rounded-xl border border-slate-800 px-4 py-2.5 text-[11px] font-black text-slate-400 transition hover:border-slate-600 hover:text-slate-200">
                    Cancelar
                </a>

                <button type="submit"
                    class="rounded-xl px-5 py-2.5 text-[11px] font-black text-slate-950 transition hover:opacity-90"
                    :style="'background-color: ' + color">
                    {{ $editing ? 'Guardar cambios' : 'Crear el tipo' }}
                </button>
            </div>

        </div>

    </div>

</div>
