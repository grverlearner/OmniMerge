@php
    /*
     * El formulario de una colección.
     *
     * Lo que era: una columna de campos sueltos y, para elegir entidades, una
     * rejilla de casillas con un buscador y un desplegable de tipo. Nada decía
     * en qué colecciones estaba ya cada entidad —el dato que decide si vale la
     * pena meterla también aquí— ni cuántas llevabas elegidas.
     *
     * Lo que hace ahora:
     *
     *   · la identidad se ve mientras se escribe, en una vista previa que es la
     *     ficha real tal como saldrá en el índice
     *   · el color y el icono se eligen por lo que son, no escribiendo un
     *     hexadecimal a ciegas
     *   · el selector de entidades tiene tres maneras de mirar, tamaño,
     *     filtros de verdad —incluido «las que no están en ninguna»— y una
     *     bandeja con lo ya elegido
     *   · la visibilidad se elige entre tres tarjetas que dicen qué implica
     *     cada una, no en un desplegable de tres palabras
     */

    $editing = isset($collection) && $collection->exists;

    $selectedEntities = old(
        'entity_ids',
        $editing ? $collection->entities->pluck('id')->map(fn($id) => (string) $id)->all() : [],
    );

    $selectedEntities = array_map('strval', $selectedEntities);

    /* Los datos que el selector necesita, ya masticados. */
    $entidadesParaAlpine = $entities
        ->map(
            fn($entidad) => [
                'id' => (string) $entidad->id,
                'name' => $entidad->name,
                'type_id' => (string) ($entidad->entity_type_id ?? ''),
                'type' => $entidad->entityType?->name,
                'image_url' => $entidad->image_url,
                'collections' => $entidad->collections
                    ->when($editing, fn($c) => $c->where('id', '!=', $collection->id))
                    ->map(fn($c) => ['name' => $c->name, 'color' => $c->color ?: '#8b5cf6'])
                    ->values(),
            ],
        )
        ->values();

    $paleta = [
        '#8b5cf6' => 'Violeta',
        '#6366f1' => 'Índigo',
        '#06b6d4' => 'Cian',
        '#10b981' => 'Verde',
        '#f59e0b' => 'Ámbar',
        '#ee8420' => 'Naranja',
        '#f43f5e' => 'Rosa',
        '#a855f7' => 'Púrpura',
        '#64748b' => 'Pizarra',
    ];

    $iconos = ['◫', '◈', '★', '⚔', '☯', '✦', '❖', '⬢', '♜', '☾', '⚑', '♞'];

    /*
     * Las clases van enteras: Tailwind escanea el código fuente y solo genera
     * lo que encuentra escrito literalmente, así que `border-{$tono}-500` no
     * existiría en el CSS.
     */
    $visibilidades = [
        'PUBLIC' => [
            'Público',
            'globo',
            'Cualquiera puede encontrarla en Comunidad y verla.',
            'border-emerald-500 bg-emerald-500/10',
            'text-emerald-400',
        ],
        'UNLISTED' => [
            'No listado',
            'brujula',
            'No sale en las búsquedas: solo quien tenga el enlace.',
            'border-amber-500 bg-amber-500/10',
            'text-amber-400',
        ],
        'PRIVATE' => [
            'Privado',
            'usuario',
            'Solo tú. Ni aparece ni se puede abrir desde fuera.',
            'border-slate-500 bg-slate-500/10',
            'text-slate-400',
        ],
    ];
@endphp

<div x-data="constructorDeColeccion({
        nombre: @js(old('name', $collection->name ?? '')),
        color: @js(old('color', $collection->color ?? '#8b5cf6')),
        icono: @js(old('icon', $collection->icon ?? '◫')),
        visibilidad: @js(old('visibility', $collection->visibility ?? 'PUBLIC')),
        imagen: @js($editing ? $collection->image_url : null),
        elegidas: @js($selectedEntities),
        entidades: @js($entidadesParaAlpine),
    })" class="space-y-4">


    {{-- ===================================================== --}}
    {{-- PASO 1 · QUÉ ES --}}
    {{-- ===================================================== --}}

    <section class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_300px]">

        <div class="space-y-3">

            <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <div class="flex items-center gap-3 border-b border-slate-800 px-4 py-3">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-violet-500 font-mono text-[11px] font-black text-white">1</span>

                    <div class="min-w-0 flex-1">
                        <h2 class="text-[13px] font-black text-white">¿Qué agrupa?</h2>
                        <p class="text-[10px] text-slate-500">
                            El nombre y el motivo. Una colección puede ser una franquicia, un equipo, o
                            simplemente «las que me gustan».
                        </p>
                    </div>

                    <span class="shrink-0 rounded-lg border border-slate-800 px-2 py-1 font-mono text-[10px] text-slate-500">
                        {{ $previewCode }}
                    </span>
                </div>

                <div class="space-y-3 p-4">

                    <label class="block">
                        <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                            Cómo se llama
                        </span>
                        <input type="text" name="name" x-model="nombre" required maxlength="150"
                            placeholder="«Franquicia Naruto», «Equipo 7», «Mis favoritas»…"
                            class="w-full rounded-xl border-slate-800 bg-slate-900 text-sm font-bold text-white placeholder:font-normal placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                        @error('name')
                            <span class="mt-1 block text-[10px] font-bold text-rose-300">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                            De qué va
                        </span>
                        <textarea name="description" rows="3" maxlength="5000"
                            placeholder="Qué tienen en común las que metas aquí. Dentro de un año lo agradecerás."
                            class="w-full rounded-xl border-slate-800 bg-slate-900 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">{{ old('description', $collection->description ?? '') }}</textarea>
                        @error('description')
                            <span class="mt-1 block text-[10px] font-bold text-rose-300">{{ $message }}</span>
                        @enderror
                    </label>


                    {{-- El color, elegido por lo que es --}}
                    <div>
                        <span class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                            Su color
                        </span>

                        <input type="hidden" name="color" :value="color">

                        <div class="flex flex-wrap items-center gap-1.5">
                            @foreach ($paleta as $hex => $nombreColor)
                                <button type="button" @click="color = @js($hex)" title="{{ $nombreColor }}"
                                    :class="color === @js($hex) ? 'ring-2 ring-offset-2 ring-offset-slate-900' : 'opacity-60 hover:opacity-100'"
                                    class="h-8 w-8 rounded-lg border border-slate-700 transition"
                                    style="background-color: {{ $hex }}; --tw-ring-color: {{ $hex }}"></button>
                            @endforeach

                            <label class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-slate-800 bg-slate-950 px-2 py-1.5 text-[10px] font-black text-slate-400 transition hover:border-slate-600">
                                <input type="color" x-model="color" class="h-5 w-5 cursor-pointer rounded border-0 bg-transparent p-0">
                                Otro
                            </label>
                        </div>

                        <p class="mt-1 text-[10px] text-slate-600">
                            Tiñe su borde y sus cifras en los listados. Sirve para reconocerla de un vistazo
                            entre veinte.
                        </p>
                    </div>


                    {{-- El icono, para cuando no hay portada --}}
                    <div>
                        <span class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                            Su símbolo
                            <span class="font-bold normal-case tracking-normal text-slate-700">— se usa si no le pones portada</span>
                        </span>

                        <input type="hidden" name="icon" :value="icono">

                        <div class="flex flex-wrap items-center gap-1">
                            @foreach ($iconos as $simbolo)
                                <button type="button" @click="icono = @js($simbolo)"
                                    :class="icono === @js($simbolo) ? 'border-violet-500 bg-violet-500/15' : 'border-slate-800 bg-slate-950 hover:border-slate-600'"
                                    class="flex h-9 w-9 items-center justify-center rounded-lg border text-base transition"
                                    :style="icono === @js($simbolo) ? 'color: ' + color : ''">
                                    {{ $simbolo }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                </div>
            </div>


            {{-- La portada --}}
            <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                <div class="border-b border-slate-800 px-4 py-3">
                    <h2 class="text-[13px] font-black text-white">Su portada</h2>
                    <p class="text-[10px] text-slate-500">
                        Opcional. Sin ella se usa el símbolo sobre su color, que también queda bien.
                    </p>
                </div>

                <div class="p-4">
                    <label class="block cursor-pointer rounded-xl border border-dashed border-slate-700 p-4 text-center transition hover:border-violet-500">
                        <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
                            @change="verImagen($event)" class="sr-only">
                        <span class="block text-[11px] font-black text-slate-300">
                            <span x-text="imagen ? 'Elegir otra imagen' : 'Elegir un archivo'"></span>
                        </span>
                        <span class="mt-0.5 block text-[9px] text-slate-600">JPG, PNG o WEBP · máximo 4 MB</span>
                    </label>

                    @error('image')
                        <span class="mt-1 block text-[10px] font-bold text-rose-300">{{ $message }}</span>
                    @enderror

                    @if ($editing)
                        <label class="mt-2 flex cursor-pointer items-center gap-2 text-[10px] font-black text-slate-500 transition hover:text-rose-300">
                            <input type="checkbox" name="remove_image" value="1" @change="if ($event.target.checked) imagen = null"
                                class="rounded border-slate-700 bg-slate-900 text-rose-500">
                            Quitar la portada que tiene
                        </label>
                    @endif
                </div>
            </div>

        </div>


        {{-- ---------- LA VISTA PREVIA ---------- --}}

        {{--
            No es una maqueta: es la misma ficha que sale en el índice, con su
            color, su símbolo, su portada y las caras de lo que lleva elegido.
            Se ve mientras se escribe.
        --}}

        <div class="lg:sticky lg:top-24 lg:self-start">

            <p class="mb-2 text-[10px] font-black uppercase tracking-wider text-slate-600">
                Así se verá
            </p>

            <article class="overflow-hidden rounded-2xl border bg-slate-900/50 transition"
                :style="'border-color: ' + color + '55'">

                <div class="relative aspect-[16/9] overflow-hidden bg-slate-950">
                    <template x-if="imagen">
                        <img :src="imagen" alt="" class="h-full w-full object-cover">
                    </template>

                    <template x-if="! imagen">
                        <span class="flex h-full w-full items-center justify-center text-4xl"
                            :style="'color: ' + color + '88; background: radial-gradient(120% 90% at 50% 0%, ' + color + '22, transparent 70%)'"
                            x-text="icono"></span>
                    </template>

                    <span class="absolute left-2 top-2 rounded-lg bg-slate-950/85 px-2 py-1 text-[9px] font-black uppercase tracking-wider"
                        :style="'color: ' + color"
                        x-text="etiquetaVisibilidad"></span>
                </div>

                <div class="p-3">
                    <p class="truncate text-[13px] font-black text-white"
                        x-text="nombre || 'Sin nombre todavía'"
                        :class="nombre ? '' : 'text-slate-600'"></p>

                    <p class="font-mono text-[9px] text-slate-600">{{ $previewCode }}</p>

                    <div class="mt-2.5 flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-950 p-1.5">
                        <span class="flex -space-x-2">
                            <template x-for="e in elegidasComoObjetos.slice(0, 5)" :key="'p' + e.id">
                                <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border-2 border-slate-950 bg-slate-900"
                                    :title="e.name">
                                    <template x-if="e.image_url">
                                        <img :src="e.image_url" alt="" class="h-full w-full object-cover">
                                    </template>
                                    <template x-if="! e.image_url">
                                        <span class="flex h-full w-full items-center justify-center text-[10px] text-slate-700">◍</span>
                                    </template>
                                </span>
                            </template>

                            <span x-show="elegidas.length === 0"
                                class="flex h-8 w-8 items-center justify-center rounded-lg border-2 border-dashed border-slate-800 text-[10px] text-slate-700">?</span>
                        </span>

                        <span class="min-w-0 flex-1 text-[10px] leading-3">
                            <span class="block font-mono text-sm font-black" :style="'color: ' + color"
                                x-text="elegidas.length"></span>
                            <span class="block text-slate-500"
                                x-text="elegidas.length === 1 ? 'entidad dentro' : 'entidades dentro'"></span>
                        </span>
                    </div>
                </div>
            </article>

            <p class="mt-2 text-[10px] leading-relaxed text-slate-600">
                Se actualiza mientras escribes. Es la misma ficha que verás en el índice.
            </p>
        </div>

    </section>


    {{-- ===================================================== --}}
    {{-- PASO 2 · QUIÉN VA DENTRO --}}
    {{-- ===================================================== --}}

    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

        {{-- Los ids, una sola vez: los tres modos de vista conviven en el DOM --}}
        <template x-for="id in elegidas" :key="'sel' + id">
            <input type="hidden" name="entity_ids[]" :value="id">
        </template>

        <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-4 py-3">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-violet-500 font-mono text-[11px] font-black text-white">2</span>

            <div class="min-w-0 flex-1">
                <h2 class="text-[13px] font-black text-white">¿Quién va dentro?</h2>
                <p class="text-[10px] text-slate-500">
                    Una entidad puede estar en varias colecciones a la vez: meterla aquí no la saca de
                    ninguna otra ni la mueve de sitio.
                </p>
            </div>

            <span class="shrink-0 rounded-xl border border-violet-500/30 bg-violet-500/10 px-3 py-1.5 font-mono text-[11px] font-black text-violet-300"
                x-text="elegidas.length + ' elegidas'"></span>
        </div>


        {{-- ---------- FILTROS ---------- --}}

        <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 bg-slate-950/40 px-4 py-3">

            <label class="relative min-w-[170px] flex-1">
                <span class="sr-only">Buscar entidad</span>
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                    <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                </span>
                <input type="search" x-model="buscar" placeholder="Buscar por nombre…"
                    class="w-full rounded-xl border-slate-800 bg-slate-900 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
            </label>

            <select x-model="tipo"
                class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                <option value="ALL">Cualquier tipo</option>
                @foreach ($entityTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>

            <select x-model="pertenencia"
                class="rounded-xl border-slate-800 bg-slate-900 py-2 text-[11px] font-bold text-slate-300 focus:border-violet-500 focus:ring-violet-500">
                <option value="ALL">Estén donde estén</option>
                <option value="NONE">Las que no están en ninguna</option>
                <option value="SOME">Las que ya están en alguna</option>
                <option value="SELECTED">Solo las elegidas</option>
            </select>

            <span class="flex flex-wrap items-center gap-1">
                <button type="button" @click="todas()"
                    class="rounded-lg border border-slate-800 px-2.5 py-2 text-[10px] font-black text-slate-400 transition hover:border-violet-500 hover:text-violet-300">
                    Todas las visibles
                </button>

                <button type="button" @click="ninguna()"
                    class="rounded-lg border border-slate-800 px-2.5 py-2 text-[10px] font-black text-slate-400 transition hover:border-slate-600 hover:text-white">
                    Ninguna
                </button>

                <button type="button" @click="invertir()"
                    class="rounded-lg border border-slate-800 px-2.5 py-2 text-[10px] font-black text-slate-400 transition hover:border-slate-600 hover:text-white">
                    Invertir
                </button>
            </span>

            <span class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-900 p-1">
                @foreach ([['gallery', 'galeria', 'Galería: elegir por la cara'], ['grid', 'cuadricula', 'Cuadrícula: con su tipo y dónde está ya'], ['list', 'menu', 'Lista: una línea por entidad']] as [$modo, $icono, $ayuda])
                    <button type="button" @click="vista = '{{ $modo }}'" title="{{ $ayuda }}"
                        :class="vista === '{{ $modo }}' ? 'bg-violet-500 text-white' : 'text-slate-500 hover:text-slate-200'"
                        class="rounded-lg px-2 py-1.5 transition">
                        <x-omni-icon :name="$icono" size="h-4 w-4" />
                    </button>
                @endforeach
            </span>

            <span x-show="vista !== 'list'" x-cloak
                class="flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-900 p-1">
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


        {{-- ---------- LA BANDEJA DE LO ELEGIDO ---------- --}}

        <div x-show="elegidas.length > 0" x-cloak x-collapse
            class="border-b border-slate-800 bg-violet-500/5 px-4 py-2.5">

            <div class="flex flex-wrap items-center gap-2">
                <span class="text-[10px] font-black uppercase tracking-wider text-violet-300">
                    Ya dentro
                </span>

                <div class="flex min-w-0 flex-1 flex-wrap gap-1">
                    <template x-for="e in elegidasComoObjetos" :key="'b' + e.id">
                        <button type="button" @click="alternar(e.id)" :title="'Quitar ' + e.name"
                            class="group flex items-center gap-1.5 rounded-lg border border-violet-500/30 bg-slate-950 py-1 pl-1 pr-2 transition hover:border-rose-500">
                            <span class="h-5 w-5 shrink-0 overflow-hidden rounded border border-slate-800">
                                <template x-if="e.image_url">
                                    <img :src="e.image_url" alt="" class="h-full w-full object-cover">
                                </template>
                            </span>
                            <span class="max-w-[140px] truncate text-[10px] font-black text-slate-300" x-text="e.name"></span>
                            <span class="text-[10px] text-slate-600 transition group-hover:text-rose-300">✕</span>
                        </button>
                    </template>
                </div>
            </div>
        </div>


        {{-- ---------- LAS ENTIDADES ---------- --}}

        @if ($entities->isEmpty())

            <div class="p-10 text-center">
                <span class="inline-flex text-slate-700"><x-omni-icon name="usuario" size="h-9 w-9" /></span>

                <p class="mt-2 text-[13px] font-black text-white">Todavía no tienes entidades</p>

                <p class="mx-auto mt-1 max-w-md text-[11px] leading-relaxed text-slate-500">
                    Una colección agrupa entidades, así que primero hace falta tener alguna. Puedes crear la
                    colección vacía ahora y llenarla después.
                </p>

                <a href="{{ route('entities.create') }}"
                    class="mt-4 inline-block rounded-xl border border-slate-700 px-4 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                    Crear una entidad →
                </a>
            </div>

        @else

            {{-- GALERÍA --}}
            <div x-show="vista === 'gallery'" x-cloak class="grid gap-2 p-4" :class="columnas">
                <template x-for="e in visibles" :key="'g' + e.id">
                    <label class="group relative cursor-pointer overflow-hidden rounded-xl border bg-slate-950 transition"
                        :class="elegidas.includes(e.id)
                            ? 'border-violet-500 ring-1 ring-violet-500'
                            : 'border-slate-800 hover:border-slate-600'">

                        <input type="checkbox" class="sr-only"
                            :checked="elegidas.includes(e.id)" @change="alternar(e.id)">

                        <span class="relative block aspect-square overflow-hidden bg-slate-900">
                            <template x-if="e.image_url">
                                <img :src="e.image_url" alt="" loading="lazy"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            </template>
                            <template x-if="! e.image_url">
                                <span class="flex h-full w-full items-center justify-center text-2xl text-slate-800">◍</span>
                            </template>

                            <template x-if="e.collections.length > 0">
                                <span class="absolute left-1 top-1 rounded bg-slate-950/85 px-1 font-mono text-[9px] font-black text-slate-400"
                                    :title="'Ya está en: ' + e.collections.map(c => c.name).join(', ')"
                                    x-text="'◫' + e.collections.length"></span>
                            </template>

                            <span x-show="elegidas.includes(e.id)" x-cloak
                                class="absolute inset-0 flex items-center justify-center bg-violet-500/25 text-xl font-black text-white">✓</span>
                        </span>

                        <span class="block truncate px-1.5 py-1 text-center text-[10px] font-black text-slate-300"
                            x-text="e.name"></span>
                    </label>
                </template>
            </div>


            {{-- CUADRÍCULA --}}
            <div x-show="vista === 'grid'" class="grid gap-2 p-4" :class="columnas">
                <template x-for="e in visibles" :key="'c' + e.id">
                    <label class="group cursor-pointer overflow-hidden rounded-xl border bg-slate-950 transition"
                        :class="elegidas.includes(e.id)
                            ? 'border-violet-500 ring-1 ring-violet-500'
                            : 'border-slate-800 hover:border-slate-600'">

                        <input type="checkbox" class="sr-only"
                            :checked="elegidas.includes(e.id)" @change="alternar(e.id)">

                        <span class="relative block aspect-square overflow-hidden bg-slate-900">
                            <template x-if="e.image_url">
                                <img :src="e.image_url" alt="" loading="lazy"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            </template>
                            <template x-if="! e.image_url">
                                <span class="flex h-full w-full items-center justify-center text-2xl text-slate-800">◍</span>
                            </template>

                            <span x-show="elegidas.includes(e.id)" x-cloak
                                class="absolute inset-0 flex items-center justify-center bg-violet-500/25 text-xl font-black text-white">✓</span>
                        </span>

                        <span class="block p-1.5">
                            <span class="block truncate text-[10px] font-black text-white" x-text="e.name"></span>
                            <span class="block truncate text-[9px] text-slate-600" x-text="e.type ?? 'Sin tipo'"></span>

                            {{-- Dónde está ya: el dato que decide --}}
                            <span class="mt-1 flex flex-wrap gap-0.5">
                                <template x-for="c in e.collections.slice(0, 3)" :key="c.name">
                                    <span class="truncate rounded px-1 text-[8px] font-black"
                                        :style="'color: ' + c.color + '; background-color: ' + c.color + '22'"
                                        x-text="c.name"></span>
                                </template>

                                <template x-if="e.collections.length === 0">
                                    <span class="rounded bg-slate-800 px-1 text-[8px] font-black text-slate-500">
                                        En ninguna
                                    </span>
                                </template>
                            </span>
                        </span>
                    </label>
                </template>
            </div>


            {{-- LISTA --}}
            <div x-show="vista === 'list'" x-cloak class="max-h-[32rem] divide-y divide-slate-800/70 overflow-y-auto">
                <template x-for="e in visibles" :key="'l' + e.id">
                    <label class="flex cursor-pointer items-center gap-3 px-4 py-2 transition hover:bg-slate-950/50"
                        :class="elegidas.includes(e.id) ? 'bg-violet-500/5' : ''">

                        <input type="checkbox" :checked="elegidas.includes(e.id)" @change="alternar(e.id)"
                            class="rounded border-slate-700 bg-slate-900 text-violet-500">

                        <span class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                            <template x-if="e.image_url">
                                <img :src="e.image_url" alt="" loading="lazy" class="h-full w-full object-cover">
                            </template>
                            <template x-if="! e.image_url">
                                <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                            </template>
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[12px] font-black text-white" x-text="e.name"></span>
                            <span class="block truncate text-[10px] text-slate-500" x-text="e.type ?? 'Sin tipo'"></span>
                        </span>

                        <span class="hidden shrink-0 flex-wrap justify-end gap-1 sm:flex">
                            <template x-for="c in e.collections.slice(0, 3)" :key="'x' + c.name">
                                <span class="rounded px-1.5 py-0.5 text-[9px] font-black"
                                    :style="'color: ' + c.color + '; background-color: ' + c.color + '22'"
                                    x-text="c.name"></span>
                            </template>

                            <template x-if="e.collections.length === 0">
                                <span class="rounded bg-slate-800 px-1.5 py-0.5 text-[9px] font-black text-slate-500">
                                    En ninguna
                                </span>
                            </template>
                        </span>
                    </label>
                </template>
            </div>

            <p x-show="visibles.length === 0" x-cloak class="p-8 text-center text-[11px] text-slate-600">
                Ninguna entidad encaja con lo que has filtrado.
            </p>

            <p class="border-t border-slate-800 px-4 py-2 text-[10px] text-slate-600">
                <span x-text="visibles.length"></span> de {{ $entities->count() }} entidades.
                La etiqueta de color de cada una dice en qué colecciones está ya.
            </p>

        @endif

    </section>


    {{-- ===================================================== --}}
    {{-- PASO 3 · QUIÉN LA VE --}}
    {{-- ===================================================== --}}

    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

        <div class="flex items-center gap-3 border-b border-slate-800 px-4 py-3">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-violet-500 font-mono text-[11px] font-black text-white">3</span>

            <div class="min-w-0 flex-1">
                <h2 class="text-[13px] font-black text-white">¿Quién puede verla?</h2>
                <p class="text-[10px] text-slate-500">
                    Se puede cambiar en cualquier momento, también desde el propio índice.
                </p>
            </div>
        </div>

        <div class="grid gap-2 p-4 lg:grid-cols-3">
            @foreach ($visibilidades as $valor => [$etiqueta, $icono, $ayuda, $tonoActivo, $tonoIcono])
                <label class="cursor-pointer rounded-xl border p-3 transition"
                    :class="visibilidad === @js($valor)
                        ? @js($tonoActivo)
                        : 'border-slate-800 bg-slate-950 hover:border-slate-600'">

                    <input type="radio" name="visibility" value="{{ $valor }}" x-model="visibilidad" class="sr-only">

                    <span class="flex items-center gap-2">
                        <span class="{{ $tonoIcono }}"><x-omni-icon :name="$icono" size="h-4 w-4" /></span>
                        <span class="text-[12px] font-black text-white">{{ $etiqueta }}</span>
                    </span>

                    <span class="mt-1 block text-[10px] leading-4 text-slate-500">{{ $ayuda }}</span>
                </label>
            @endforeach
        </div>

        <div class="grid gap-2 border-t border-slate-800 p-4 sm:grid-cols-2">

            <label class="block">
                <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                    Estado
                </span>
                <select name="status"
                    class="w-full rounded-xl border-slate-800 bg-slate-900 text-xs text-slate-200 focus:border-violet-500 focus:ring-violet-500">
                    @foreach (['ACTIVE' => 'Activa — cuenta en todas partes', 'INACTIVE' => 'Inactiva — guardada, fuera de juego', 'ARCHIVED' => 'Archivada — retirada'] as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected(old('status', $collection->status ?? 'ACTIVE') === $valor)>
                            {{ $etiqueta }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="cursor-pointer rounded-xl border border-slate-800 bg-slate-950 p-3 transition has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-500/10">
                <input type="hidden" name="allow_cloning" value="0">
                <input type="checkbox" name="allow_cloning" value="1"
                    @checked(old('allow_cloning', $collection->allow_cloning ?? true)) class="sr-only">

                <span class="text-[12px] font-black text-white">Dejar que la copien</span>
                <span class="mt-0.5 block text-[10px] leading-4 text-slate-500">
                    Otros usuarios pueden llevársela a su biblioteca. Se copia la colección y sus entidades:
                    las tuyas no se tocan.
                </span>
            </label>
        </div>

    </section>


    {{-- ===================================================== --}}
    {{-- GUARDAR --}}
    {{-- ===================================================== --}}

    <div class="sticky bottom-4 z-20 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-800 bg-slate-950/95 px-4 py-3 backdrop-blur">

        <p class="min-w-0 flex-1 text-[11px] leading-relaxed text-slate-400">
            <span x-show="! nombre">Ponle nombre para poder guardarla.</span>

            <span x-show="nombre" x-cloak>
                {{ $editing ? 'Se guardan los cambios de' : 'Se creará' }}
                <strong class="text-white" x-text="'«' + nombre + '»'"></strong>
                <span x-show="elegidas.length > 0">
                    con <strong :style="'color: ' + color" x-text="elegidas.length"></strong>
                    <span x-text="elegidas.length === 1 ? 'entidad dentro' : 'entidades dentro'"></span>.
                </span>
                <span x-show="elegidas.length === 0" x-cloak class="text-slate-500">
                    vacía. Podrás añadirle entidades cuando quieras.
                </span>
            </span>
        </p>

        <a href="{{ $editing ? route('collections.show', $collection) : route('collections.index') }}"
            class="rounded-xl border border-slate-800 px-4 py-2 text-[11px] font-black text-slate-400 transition hover:text-white">
            Cancelar
        </a>

        <button type="submit" :disabled="! nombre"
            class="rounded-xl bg-violet-500 px-5 py-2.5 text-[11px] font-black text-white transition hover:bg-violet-400 disabled:cursor-not-allowed disabled:opacity-40">
            {{ $editing ? 'Guardar los cambios' : 'Crear la colección' }}
        </button>
    </div>

</div>


<script>
    /*
     * El constructor de una colección.
     *
     * Lo único con enjundia es el selector: filtra por nombre, por tipo y por
     * DÓNDE ESTÁ YA cada entidad, y mantiene la lista de elegidas como única
     * fuente de verdad —los ids se envían una sola vez, aparte, porque los tres
     * modos de vista conviven en el DOM y `x-show` oculta pero no desmonta—.
     */
    function constructorDeColeccion(config) {

        return {

            nombre: config.nombre ?? '',
            color: config.color ?? '#8b5cf6',
            icono: config.icono ?? '◫',
            visibilidad: config.visibilidad ?? 'PUBLIC',
            imagen: config.imagen ?? null,

            entidades: config.entidades ?? [],
            elegidas: config.elegidas ?? [],

            buscar: '',
            tipo: 'ALL',
            pertenencia: 'ALL',

            vista: 'grid',
            tamano: 6,


            init() {
                try {
                    const g = JSON.parse(localStorage.getItem('omnimerge.collectionForm.view') ?? '{}');
                    if (['gallery', 'grid', 'list'].includes(g.vista)) this.vista = g.vista;
                    if (g.tamano >= 4 && g.tamano <= 9) this.tamano = g.tamano;
                } catch (e) {}

                this.$watch('vista', () => this.recordar());
                this.$watch('tamano', () => this.recordar());
            },

            recordar() {
                try {
                    localStorage.setItem('omnimerge.collectionForm.view',
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


            get etiquetaVisibilidad() {
                return {
                    PUBLIC: 'Público',
                    UNLISTED: 'No listado',
                    PRIVATE: 'Privado',
                }[this.visibilidad] ?? 'Público';
            },


            get visibles() {
                const q = this.buscar.trim().toLowerCase();

                return this.entidades.filter((e) => {

                    if (q && ! e.name.toLowerCase().includes(q)) return false;

                    if (this.tipo !== 'ALL' && String(e.type_id) !== String(this.tipo)) return false;

                    if (this.pertenencia === 'NONE' && e.collections.length > 0) return false;
                    if (this.pertenencia === 'SOME' && e.collections.length === 0) return false;
                    if (this.pertenencia === 'SELECTED' && ! this.elegidas.includes(e.id)) return false;

                    return true;
                });
            },


            get elegidasComoObjetos() {
                return this.entidades.filter((e) => this.elegidas.includes(e.id));
            },


            alternar(id) {
                const i = this.elegidas.indexOf(id);

                if (i === -1) this.elegidas.push(id); else this.elegidas.splice(i, 1);
            },


            todas() {
                const ids = this.visibles.map((e) => e.id);

                this.elegidas = [...new Set([...this.elegidas, ...ids])];
            },


            ninguna() {
                this.elegidas = [];
            },


            invertir() {
                const ids = this.visibles.map((e) => e.id);

                this.elegidas = ids.filter((id) => ! this.elegidas.includes(id));
            },


            verImagen(evento) {
                const archivo = evento.target.files?.[0];

                if (! archivo) return;

                const lector = new FileReader();

                lector.onload = (e) => { this.imagen = e.target.result; };

                lector.readAsDataURL(archivo);
            },
        };
    }
</script>
