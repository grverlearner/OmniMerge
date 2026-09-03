@php
    /*
     * Las siete acciones que se pueden hacer sobre lo marcado.
     *
     * Cada una es un formulario independiente al mismo endpoint, con su
     * `operation` en un campo oculto y las entidades marcadas en
     * `entity_ids[]`. Eso ya era así y no se toca: lo que cambia es que ahora
     * cada una enseña, antes de pulsar, a quién va a afectar y con qué
     * resultado.
     *
     * Las pestañas viven en el motor (`tabs`), con su id y su etiqueta. Aquí
     * solo se les pone un icono del juego de la aplicación en vez del
     * carácter suelto que traían.
     */

    $iconoPestana = [
        'selection' => 'cuadricula',
        'quick' => 'controles',
        'matrix' => 'grafo',
        'attributes' => 'capas',
        'collections' => 'libro',
        'structure' => 'barras',
        'publication' => 'globo',
        'danger' => 'cerrar',
    ];

    /* El botón de enviar, igual en las siete: se desactiva sin selección */
    $boton = 'w-full rounded-xl px-4 py-2.5 text-[11px] font-black transition disabled:cursor-not-allowed disabled:opacity-30';
@endphp


{{-- ===================================================== --}}
{{-- LAS PESTAÑAS --}}
{{-- ===================================================== --}}

<div class="flex flex-wrap gap-1 rounded-2xl border border-slate-800 bg-slate-900/50 p-1.5">
    <template x-for="tab in tabs.filter(t => t.id !== 'selection')" :key="tab.id">
        <button type="button" @click="activeTab = tab.id"
            :class="activeTab === tab.id ?
                (tab.id === 'danger' ? 'bg-rose-500 text-white' : 'bg-indigo-500 text-white') :
                'text-slate-500 hover:bg-slate-950 hover:text-slate-200'"
            class="flex items-center gap-2 rounded-xl px-3 py-2 text-[11px] font-black transition">
            <span x-text="tab.label"></span>
        </button>
    </template>
</div>


{{-- ===================================================== --}}
{{-- RÁPIDA · tipo y descripción --}}
{{-- ===================================================== --}}

<section x-show="activeTab === 'quick'" x-cloak class="grid gap-4 lg:grid-cols-2">

    {{-- Cambiar el tipo --}}
    <form method="POST" action="{{ route('entities.bulk-edit.update') }}"
        class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">
        @csrf

        <input type="hidden" name="operation" value="set_property">
        <input type="hidden" name="property" value="entity_type_id">

        <template x-for="id in selectedIds" :key="`qt-${id}`">
            <input type="hidden" name="entity_ids[]" :value="id">
        </template>

        <header class="border-b border-slate-800 px-5 py-3">
            <h2 class="text-sm font-black text-white">Cambiarles el tipo</h2>
            <p class="text-[10px] text-slate-500">Todas las marcadas pasan a ser del mismo tipo.</p>
        </header>

        <div class="space-y-3 p-5" x-data="{ nuevoTipo: '' }">

            <select name="property_value" x-model="nuevoTipo"
                class="w-full rounded-xl border-slate-800 bg-slate-950 text-xs font-bold text-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Dejarlas sin tipo</option>

                <template x-for="type in entityTypes" :key="type.id">
                    <option :value="type.id" x-text="type.name"></option>
                </template>
            </select>

            @include('entities.bulk-edit.partials.preview', [
                'verbo' => 'Cambiará el tipo de',
                'tono' => 'indigo',
                'antes' => 'entity.entity_type_name',
                'despues' => "(entityTypes.find(t => t.id === nuevoTipo)?.name ?? 'Sin tipo')",
            ])

            <button type="submit" :disabled="!selectedCount"
                class="{{ $boton }} bg-indigo-500 text-white hover:bg-indigo-400">
                Cambiar el tipo
            </button>

        </div>
    </form>


    {{-- Cambiar la descripción --}}
    <form method="POST" action="{{ route('entities.bulk-edit.update') }}"
        class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">
        @csrf

        <input type="hidden" name="operation" value="set_property">
        <input type="hidden" name="property" value="description">

        <template x-for="id in selectedIds" :key="`qd-${id}`">
            <input type="hidden" name="entity_ids[]" :value="id">
        </template>

        <header class="border-b border-slate-800 px-5 py-3">
            <h2 class="text-sm font-black text-white">Escribirles la misma descripción</h2>
            <p class="text-[10px] text-slate-500">Sustituye la que tuvieran. Vacío la borra.</p>
        </header>

        <div class="space-y-3 p-5" x-data="{ nuevaDesc: '' }">

            <textarea name="property_value" x-model="nuevaDesc" rows="3"
                placeholder="Ej. Personaje de la aldea de Konoha."
                class="w-full rounded-xl border-slate-800 bg-slate-950 text-xs text-slate-300 placeholder:text-slate-700 focus:border-indigo-500 focus:ring-indigo-500"></textarea>

            @include('entities.bulk-edit.partials.preview', [
                'verbo' => 'Reescribirá la descripción de',
                'tono' => 'indigo',
                'antes' => 'entity.description',
                'despues' => 'nuevaDesc',
            ])

            <button type="submit" :disabled="!selectedCount"
                class="{{ $boton }} bg-indigo-500 text-white hover:bg-indigo-400">
                Escribir la descripción
            </button>

        </div>
    </form>

</section>


{{-- ===================================================== --}}
{{-- MATRIZ · editar celda a celda --}}
{{-- ===================================================== --}}

<section x-show="activeTab === 'matrix'" x-cloak
    class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <form method="POST" action="{{ route('entities.bulk-edit.update') }}" @submit="matrixSubmitting = true">
        @csrf

        <input type="hidden" name="operation" value="matrix_update">
        <input type="hidden" name="matrix_payload" :value="JSON.stringify(matrixPayload())">

        <template x-for="id in selectedIds" :key="`mx-${id}`">
            <input type="hidden" name="entity_ids[]" :value="id">
        </template>

        <header class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-5 py-3">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-cyan-500/15 text-cyan-300">
                <x-omni-icon name="grafo" size="h-4 w-4" />
            </span>

            <div class="min-w-0 flex-1">
                <h2 class="text-sm font-black text-white">Matriz</h2>
                <p class="text-[10px] text-slate-500">
                    Una hoja de cálculo: las marcadas en filas, las características que elijas en
                    columnas. Se escribe celda a celda y se guarda todo de una vez.
                </p>
            </div>
        </header>

        {{-- Qué columnas quiero ver --}}
        <div class="border-b border-slate-800 bg-slate-950/60 px-4 py-2.5">
            <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">Columnas</p>

            <div class="mt-1.5 flex flex-wrap gap-1.5">
                <template x-for="attribute in attributes" :key="`mc-${attribute.id}`">
                    <label
                        :class="matrixAttributeIds.includes(String(attribute.id)) ?
                            'border-cyan-500/50 bg-cyan-500/10 text-cyan-300' :
                            'border-slate-800 bg-slate-950 text-slate-400 hover:border-slate-700'"
                        class="flex cursor-pointer items-center gap-1.5 rounded-lg border px-2 py-1 text-[10px] font-bold transition">

                        <input type="checkbox" :value="String(attribute.id)" x-model="matrixAttributeIds"
                            class="sr-only">

                        <span :style="'color: ' + attribute.color" x-text="attribute.icon"></span>
                        <span x-text="attribute.name"></span>
                    </label>
                </template>
            </div>
        </div>

        {{-- La hoja --}}
        <div class="max-h-[28rem] overflow-auto">
            <table class="w-full min-w-[620px]">

                <thead class="sticky top-0 border-b border-slate-800 bg-slate-950 text-left">
                    <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                        <th class="px-3 py-2.5">Entidad</th>

                        <template x-for="attribute in matrixAttributes" :key="`mh-${attribute.id}`">
                            <th class="px-3 py-2.5">
                                <span class="flex items-center gap-1">
                                    <span :style="'color: ' + attribute.color" x-text="attribute.icon"></span>
                                    <span x-text="attribute.name"></span>
                                </span>
                            </th>
                        </template>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-800/70">
                    <template x-for="entity in selectedEntities" :key="`mr-${entity.id}`">
                        <tr class="transition hover:bg-slate-900/60">

                            <td class="px-3 py-2">
                                <span class="flex items-center gap-2">
                                    <span class="h-8 w-8 shrink-0 overflow-hidden rounded-md border border-slate-800 bg-slate-900">
                                        <template x-if="entity.image_url">
                                            <img :src="entity.image_url" alt="" loading="lazy"
                                                class="h-full w-full object-cover">
                                        </template>

                                        <template x-if="!entity.image_url">
                                            <span class="flex h-full w-full items-center justify-center text-[10px] font-black text-slate-600"
                                                x-text="entity.name.charAt(0).toUpperCase()"></span>
                                        </template>
                                    </span>

                                    <span class="min-w-0">
                                        <span class="block truncate text-[11px] font-black text-white"
                                            x-text="entity.name"></span>
                                        <span class="block truncate font-mono text-[9px] text-slate-600"
                                            x-text="entity.code"></span>
                                    </span>
                                </span>
                            </td>

                            <template x-for="attribute in matrixAttributes" :key="`mcell-${entity.id}-${attribute.id}`">
                                <td class="px-3 py-2">
                                    <span class="block min-w-[130px]">
                                        @include('entities.bulk.partials.value-editor', [
                                            'model' => 'entity.edit.attribute_values[attribute.id]',
                                            'name' => null,
                                        ])
                                    </span>
                                </td>
                            </template>

                        </tr>
                    </template>
                </tbody>

            </table>

            <p x-show="!selectedCount" class="py-10 text-center text-[11px] text-slate-600">
                Marca entidades arriba para editarlas aquí.
            </p>

            <p x-show="selectedCount && !matrixAttributes.length" x-cloak
                class="py-10 text-center text-[11px] text-slate-600">
                Elige alguna columna para empezar a escribir.
            </p>
        </div>

        <div class="border-t border-slate-800 p-4">
            <button type="submit" :disabled="!selectedCount || !matrixAttributes.length || matrixSubmitting"
                class="{{ $boton }} bg-cyan-500 text-slate-950 hover:bg-cyan-400">
                <span x-show="!matrixSubmitting">
                    Guardar la matriz · <span x-text="selectedCount"></span>
                    <span x-text="selectedCount === 1 ? 'entidad' : 'entidades'"></span>
                </span>
                <span x-show="matrixSubmitting" x-cloak>Guardando…</span>
            </button>
        </div>

    </form>

</section>


{{-- ===================================================== --}}
{{-- CARACTERÍSTICAS · poner, añadir, quitar --}}
{{-- ===================================================== --}}

<section x-show="activeTab === 'attributes'" x-cloak
    class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <form method="POST" action="{{ route('entities.bulk-edit.update') }}">
        @csrf

        <input type="hidden" name="operation" :value="attributeOperation">
        <input type="hidden" name="attribute_id" :value="selectedAttributeId">
        <input type="hidden" name="attribute_value_json" :value="JSON.stringify(attributeValue)">

        <template x-for="id in selectedIds" :key="`at-${id}`">
            <input type="hidden" name="entity_ids[]" :value="id">
        </template>

        <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                <x-omni-icon name="capas" size="h-4 w-4" />
            </span>

            <div>
                <h2 class="text-sm font-black text-white">Sus características</h2>
                <p class="text-[10px] text-slate-500">Poner un valor, añadirlo, quitarlo o retirar la característica entera.</p>
            </div>
        </header>

        <div class="space-y-4 p-5">

            {{-- Qué característica --}}
            <div>
                <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Cuál</p>

                <select x-model="selectedAttributeId" @change="resetAttributeValue()"
                    class="mt-1.5 w-full rounded-xl border-slate-800 bg-slate-950 text-xs font-bold text-slate-200 focus:border-violet-500 focus:ring-violet-500">
                    <option value="">Elegir característica…</option>

                    <template x-for="attribute in attributes" :key="`as-${attribute.id}`">
                        <option :value="String(attribute.id)"
                            x-text="attribute.name + ' · ' + attribute.data_type_label"></option>
                    </template>
                </select>
            </div>

            {{-- Qué hacer con ella --}}
            <div>
                <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Qué hacer</p>

                <div class="mt-1.5 grid gap-1.5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ([['set_attribute', 'Poner este valor', 'Sustituye lo que tuvieran.'], ['append_attribute', 'Añadir este valor', 'Solo en las de varios valores.'], ['remove_attribute_value', 'Quitar este valor', 'Deja los demás valores.'], ['clear_attribute_value', 'Vaciarla', 'La característica sigue, sin valor.'], ['remove_attribute', 'Quitarla del todo', 'Se les retira la característica.']] as [$valor, $titulo, $texto])
                        <button type="button" @click="attributeOperation = '{{ $valor }}'"
                            :class="attributeOperation === '{{ $valor }}' ?
                                'border-violet-500/50 bg-violet-500/10' :
                                'border-slate-800 bg-slate-950 hover:border-slate-700'"
                            class="rounded-xl border p-2.5 text-left transition">
                            <span class="block text-[11px] font-black text-white">{{ $titulo }}</span>
                            <span class="mt-0.5 block text-[9px] leading-3 text-slate-500">{{ $texto }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- El valor, si la acción lo necesita --}}
            <div x-show="currentAttribute && ['set_attribute', 'append_attribute', 'remove_attribute_value'].includes(attributeOperation)"
                x-cloak>
                <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Con qué valor</p>

                <div class="mt-1.5 max-w-sm" x-data="{ get attribute() { return currentAttribute } }">
                    @include('entities.bulk.partials.value-editor', [
                        'model' => 'attributeValue',
                        'name' => null,
                    ])
                </div>
            </div>

            @include('entities.bulk-edit.partials.preview', [
                'verbo' => 'Tocará las características de',
                'tono' => 'violet',
                'antes' => null,
                'despues' => "(currentAttribute?.name ?? '—')",
            ])

            <button type="submit" :disabled="!selectedCount || !selectedAttributeId"
                class="{{ $boton }} bg-violet-500 text-white hover:bg-violet-400">
                Aplicar a <span x-text="selectedCount"></span>
                <span x-text="selectedCount === 1 ? 'entidad' : 'entidades'"></span>
            </button>

        </div>

    </form>

</section>


{{-- ===================================================== --}}
{{-- COLECCIONES --}}
{{-- ===================================================== --}}

<section x-show="activeTab === 'collections'" x-cloak class="grid gap-4 lg:grid-cols-3">

    @foreach ([['add_collection', 'Meterlas en una colección', 'Se suman a las que ya tengan.', 'emerald', 'collection_id'], ['remove_collection', 'Sacarlas de una colección', 'Las demás se quedan como están.', 'amber', 'collection_id'], ['set_collections', 'Dejarlas solo en estas', 'Sustituye todas sus colecciones.', 'rose', 'collection_ids']] as [$operacion, $titulo, $texto, $color, $campo])
        <form method="POST" action="{{ route('entities.bulk-edit.update') }}"
            class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">
            @csrf

            <input type="hidden" name="operation" value="{{ $operacion }}">

            <template x-for="id in selectedIds" :key="`{{ $operacion }}-${id}`">
                <input type="hidden" name="entity_ids[]" :value="id">
            </template>

            <header class="border-b border-slate-800 px-4 py-3">
                <h2 class="text-[13px] font-black text-white">{{ $titulo }}</h2>
                <p class="text-[10px] text-slate-500">{{ $texto }}</p>
            </header>

            <div class="space-y-3 p-4">

                @if ($campo === 'collection_ids')
                    {{-- Varias, con su imagen --}}
                    <div class="max-h-40 space-y-1 overflow-y-auto pr-1">
                        <template x-for="collection in collections" :key="`sc-${collection.id}`">
                            <label
                                class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-800 bg-slate-950 p-1.5 transition hover:border-slate-700">
                                <input type="checkbox" name="collection_ids[]" :value="collection.id"
                                    class="rounded border-slate-700 bg-slate-900 text-rose-500 focus:ring-rose-500">

                                <span class="h-7 w-7 shrink-0 overflow-hidden rounded-md bg-slate-900">
                                    <template x-if="collection.image_url">
                                        <img :src="collection.image_url" alt="" class="h-full w-full object-cover">
                                    </template>

                                    <template x-if="!collection.image_url">
                                        <span class="flex h-full w-full items-center justify-center text-[11px] text-slate-600"
                                            x-text="collection.icon ?? '◈'"></span>
                                    </template>
                                </span>

                                <span class="min-w-0 flex-1 truncate text-[11px] font-bold text-slate-200"
                                    x-text="collection.name"></span>
                            </label>
                        </template>
                    </div>
                @else
                    <select name="collection_id"
                        class="w-full rounded-xl border-slate-800 bg-slate-950 text-xs font-bold text-slate-200 focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Elegir colección…</option>

                        <template x-for="collection in collections" :key="`{{ $operacion }}o-${collection.id}`">
                            <option :value="collection.id" x-text="collection.name"></option>
                        </template>
                    </select>
                @endif

                @include('entities.bulk-edit.partials.preview', [
                    'verbo' => 'Afectará a',
                    'tono' => $color,
                    'antes' => 'entity.collections.length + " col."',
                    'despues' => null,
                ])

                <button type="submit" :disabled="!selectedCount"
                    class="{{ $boton }} {{ $color === 'emerald' ? 'bg-emerald-500 text-slate-950 hover:bg-emerald-400' : ($color === 'amber' ? 'bg-amber-500 text-slate-950 hover:bg-amber-400' : 'bg-rose-500 text-white hover:bg-rose-400') }}">
                    {{ $titulo }}
                </button>

            </div>
        </form>
    @endforeach

</section>


{{-- ===================================================== --}}
{{-- ESTRUCTURA · cómo se presentan sus características --}}
{{-- ===================================================== --}}

<section x-show="activeTab === 'structure'" x-cloak class="grid gap-4 lg:grid-cols-2">

    {{-- Presentación de una característica --}}
    <form method="POST" action="{{ route('entities.bulk-edit.update') }}"
        class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">
        @csrf

        <input type="hidden" name="operation" value="attribute_presentation">

        <template x-for="id in selectedIds" :key="`ap-${id}`">
            <input type="hidden" name="entity_ids[]" :value="id">
        </template>

        <header class="border-b border-slate-800 px-5 py-3">
            <h2 class="text-sm font-black text-white">Cómo se presenta una característica</h2>
            <p class="text-[10px] text-slate-500">Su nombre a medida, si se ve, si destaca y en qué orden.</p>
        </header>

        <div class="space-y-3 p-5">

            <select name="attribute_id"
                class="w-full rounded-xl border-slate-800 bg-slate-950 text-xs font-bold text-slate-200 focus:border-sky-500 focus:ring-sky-500">
                <option value="">Elegir característica…</option>

                <template x-for="attribute in attributes" :key="`pr-${attribute.id}`">
                    <option :value="String(attribute.id)" x-text="attribute.name"></option>
                </template>
            </select>

            <div class="grid gap-2 sm:grid-cols-2">
                <label class="block">
                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Nombre a medida</span>
                    <input type="text" name="custom_label" placeholder="Ej. Poder"
                        class="mt-1 w-full rounded-xl border-slate-800 bg-slate-950 text-[11px] text-slate-200 placeholder:text-slate-700 focus:border-sky-500 focus:ring-sky-500">
                </label>

                <label class="block">
                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Orden</span>
                    <input type="number" name="presentation_sort_order" min="0" step="10" placeholder="0"
                        class="mt-1 w-full rounded-xl border-slate-800 bg-slate-950 text-[11px] text-slate-200 placeholder:text-slate-700 focus:border-sky-500 focus:ring-sky-500">
                </label>

                <label class="block">
                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">¿Se ve?</span>
                    <select name="presentation_visibility"
                        class="mt-1 w-full rounded-xl border-slate-800 bg-slate-950 text-[11px] text-slate-200 focus:border-sky-500 focus:ring-sky-500">
                        <option value="">Dejarlo como está</option>
                        <option value="1">Sí, se ve</option>
                        <option value="0">No, oculta</option>
                    </select>
                </label>

                <label class="block">
                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">¿Destaca?</span>
                    <select name="presentation_featured"
                        class="mt-1 w-full rounded-xl border-slate-800 bg-slate-950 text-[11px] text-slate-200 focus:border-sky-500 focus:ring-sky-500">
                        <option value="">Dejarlo como está</option>
                        <option value="1">Sí, destacada</option>
                        <option value="0">No</option>
                    </select>
                </label>
            </div>

            <label class="block">
                <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Nota</span>
                <input type="text" name="notes" placeholder="Nota opcional…"
                    class="mt-1 w-full rounded-xl border-slate-800 bg-slate-950 text-[11px] text-slate-200 placeholder:text-slate-700 focus:border-sky-500 focus:ring-sky-500">
            </label>

            @include('entities.bulk-edit.partials.preview', [
                'verbo' => 'Cambiará la presentación en',
                'tono' => 'sky',
                'antes' => null,
                'despues' => null,
            ])

            <button type="submit" :disabled="!selectedCount"
                class="{{ $boton }} bg-sky-500 text-slate-950 hover:bg-sky-400">
                Aplicar la presentación
            </button>

        </div>
    </form>


    {{-- Reordenar --}}
    <form method="POST" action="{{ route('entities.bulk-edit.update') }}"
        class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">
        @csrf

        <input type="hidden" name="operation" value="reorder_attributes">

        <template x-for="id in selectedIds" :key="`ro-${id}`">
            <input type="hidden" name="entity_ids[]" :value="id">
        </template>

        <template x-for="id in orderAttributeIds" :key="`oo-${id}`">
            <input type="hidden" name="attribute_order[]" :value="id">
        </template>

        <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
            <div class="min-w-0 flex-1">
                <h2 class="text-sm font-black text-white">En qué orden se ven</h2>
                <p class="text-[10px] text-slate-500">El orden en que aparecen sus características en la ficha.</p>
            </div>

            <button type="button" @click="loadOrderAttributes()"
                class="shrink-0 rounded-lg border border-slate-800 px-2.5 py-1.5 text-[10px] font-black text-slate-400 transition hover:border-sky-500 hover:text-sky-300">
                Cargar las suyas
            </button>
        </header>

        <div class="space-y-3 p-5">

            <div class="max-h-56 space-y-1 overflow-y-auto pr-1">
                <template x-for="(id, index) in orderAttributeIds" :key="`or-${id}`">
                    <div class="flex items-center gap-2 rounded-lg border border-slate-800 bg-slate-950 px-2 py-1.5">
                        <span class="font-mono text-[10px] font-black text-slate-600" x-text="index + 1"></span>
                        <span class="min-w-0 flex-1 truncate text-[11px] font-bold text-slate-200"
                            x-text="attributeName(id)"></span>

                        <button type="button" @click="moveOrderUp(index)" :disabled="index === 0"
                            class="rounded px-1.5 text-[11px] font-black text-slate-600 transition hover:text-sky-300 disabled:opacity-20">↑</button>

                        <button type="button" @click="moveOrderDown(index)"
                            :disabled="index === orderAttributeIds.length - 1"
                            class="rounded px-1.5 text-[11px] font-black text-slate-600 transition hover:text-sky-300 disabled:opacity-20">↓</button>
                    </div>
                </template>

                <p x-show="!orderAttributeIds.length" class="py-6 text-center text-[10px] text-slate-600">
                    Pulsa «Cargar las suyas» para traer las características de lo que hayas marcado.
                </p>
            </div>

            <button type="submit" :disabled="!selectedCount || !orderAttributeIds.length"
                class="{{ $boton }} bg-sky-500 text-slate-950 hover:bg-sky-400">
                Guardar este orden
            </button>

        </div>
    </form>

</section>


{{-- ===================================================== --}}
{{-- PUBLICACIÓN --}}
{{-- ===================================================== --}}

<section x-show="activeTab === 'publication'" x-cloak
    class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <form method="POST" action="{{ route('entities.bulk-edit.update') }}"
        x-data="{ nuevoEstado: '', nuevaVis: '', nuevaCopia: '' }">
        @csrf

        <input type="hidden" name="operation" value="set_publication">

        <template x-for="id in selectedIds" :key="`pu-${id}`">
            <input type="hidden" name="entity_ids[]" :value="id">
        </template>

        <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-300">
                <x-omni-icon name="globo" size="h-4 w-4" />
            </span>

            <div>
                <h2 class="text-sm font-black text-white">Publicación</h2>
                <p class="text-[10px] text-slate-500">
                    Lo que se deje en «dejarlo como está» no se toca.
                </p>
            </div>
        </header>

        <div class="grid gap-4 p-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">

            <div class="space-y-3">

                <label class="block">
                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Estado</span>
                    <select name="publication_status" x-model="nuevoEstado"
                        class="mt-1 w-full rounded-xl border-slate-800 bg-slate-950 text-xs font-bold text-slate-200 focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Dejarlo como está</option>
                        <option value="ACTIVE">Activas</option>
                        <option value="INACTIVE">Inactivas</option>
                        <option value="ARCHIVED">Archivadas</option>
                    </select>
                </label>

                <label class="block">
                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Visibilidad</span>
                    <select name="publication_visibility" x-model="nuevaVis"
                        class="mt-1 w-full rounded-xl border-slate-800 bg-slate-950 text-xs font-bold text-slate-200 focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Dejarla como está</option>
                        <option value="PRIVATE">Privadas</option>
                        <option value="PUBLIC">Públicas</option>
                        <option value="UNLISTED">No listadas</option>
                    </select>
                </label>

                <label class="block">
                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Permitir copia</span>
                    <select name="publication_allow_cloning" x-model="nuevaCopia"
                        class="mt-1 w-full rounded-xl border-slate-800 bg-slate-950 text-xs font-bold text-slate-200 focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Dejarlo como está</option>
                        <option value="1">Sí, se pueden copiar</option>
                        <option value="0">No</option>
                    </select>
                </label>

            </div>

            <div class="space-y-3">
                @include('entities.bulk-edit.partials.preview', [
                    'verbo' => 'Publicará',
                    'tono' => 'emerald',
                    'antes' => 'entity.status_label + " · " + entity.visibility_label',
                    'despues' =>
                        "((nuevoEstado ? {ACTIVE:'Activa',INACTIVE:'Inactiva',ARCHIVED:'Archivada'}[nuevoEstado] : entity.status_label) + ' · ' + (nuevaVis ? {PRIVATE:'Privada',PUBLIC:'Pública',UNLISTED:'No listada'}[nuevaVis] : entity.visibility_label))",
                ])

                <button type="submit" :disabled="!selectedCount"
                    class="{{ $boton }} bg-emerald-500 text-slate-950 hover:bg-emerald-400">
                    Aplicar la publicación
                </button>
            </div>

        </div>

    </form>

</section>


{{-- ===================================================== --}}
{{-- PELIGRO --}}
{{-- ===================================================== --}}

<section x-show="activeTab === 'danger'" x-cloak class="grid gap-4 lg:grid-cols-2">

    @foreach ([['archive', 'Archivarlas', 'Salen de circulación pero no se pierden: se pueden reactivar.', 'amber', 'Archivar seleccionadas'], ['delete', 'Borrarlas', 'Se van con ellas sus características, sus versiones y su sitio en las colecciones.', 'rose', 'Sí, eliminar seleccionadas']] as [$operacion, $titulo, $texto, $color, $confirmar])
        <form method="POST" action="{{ route('entities.bulk-edit.update') }}"
            class="overflow-hidden rounded-2xl border {{ $color === 'amber' ? 'border-amber-500/30 bg-amber-500/5' : 'border-rose-500/30 bg-rose-500/5' }}"
            data-omni-confirm data-confirm-variant="danger" data-confirm-icon="×"
            data-confirm-title="{{ $titulo }} en masa"
            data-confirm-message="{{ $texto }}"
            data-confirm-detail="Esta acción se aplica a todas las entidades marcadas."
            data-confirm-action="{{ $confirmar }}">
            @csrf

            <input type="hidden" name="operation" value="{{ $operacion }}">

            <template x-for="id in selectedIds" :key="`{{ $operacion }}-${id}`">
                <input type="hidden" name="entity_ids[]" :value="id">
            </template>

            <header class="border-b {{ $color === 'amber' ? 'border-amber-500/20' : 'border-rose-500/20' }} px-5 py-3">
                <h2 class="text-sm font-black text-white">{{ $titulo }}</h2>
                <p class="text-[10px] leading-4 text-slate-500">{{ $texto }}</p>
            </header>

            <div class="space-y-3 p-5">

                @include('entities.bulk-edit.partials.preview', [
                    'verbo' => $titulo === 'Borrarlas' ? 'Se borrarán' : 'Se archivarán',
                    'tono' => $color,
                    'antes' => null,
                    'despues' => null,
                ])

                <button type="submit" :disabled="!selectedCount"
                    class="{{ $boton }} {{ $color === 'amber' ? 'bg-amber-500 text-slate-950 hover:bg-amber-400' : 'bg-rose-500 text-white hover:bg-rose-400' }}">
                    {{ $titulo }} · <span x-text="selectedCount"></span>
                </button>

            </div>
        </form>
    @endforeach

</section>
