@php
    /*
     * Elegir sobre quién se va a actuar.
     *
     * Es la mitad más importante de la pantalla: todo lo demás opera sobre lo
     * que se marque aquí. Por eso tiene tres maneras de mirar —fichas, lista
     * y tabla— y dos niveles de agrupación: en un lote de doscientas, «por
     * tipo y luego por estado» encuentra en dos clics lo que una lista plana
     * esconde.
     *
     * `entity_type_color` viaja en el payload para poder pintar cada entidad
     * con el color de su tipo sin volver al servidor.
     */
@endphp

<section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <header class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-5 py-3">
        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-cyan-500/15 text-cyan-300">
            <x-omni-icon name="cuadricula" size="h-4 w-4" />
        </span>

        <div class="min-w-0 flex-1">
            <h2 class="text-sm font-black text-white">Sobre quién actuamos</h2>
            <p class="text-[10px] text-slate-500">
                Lo que marques aquí es lo que van a cambiar todas las acciones de abajo.
            </p>
        </div>

        {{-- Tres maneras de mirarlas --}}
        <span class="flex shrink-0 items-center gap-1 rounded-xl border border-slate-800 bg-slate-950 p-1">
            @foreach ([['cards', 'galeria', 'Fichas: con la cara grande'], ['list', 'controles', 'Lista: una línea por entidad'], ['table', 'grafo', 'Tabla: para comparar']] as [$modo, $icono, $ayuda])
                <button type="button" @click="pickView = '{{ $modo }}'" title="{{ $ayuda }}"
                    :aria-pressed="pickView === '{{ $modo }}'"
                    :class="pickView === '{{ $modo }}' ? 'bg-cyan-500 text-slate-950' :
                        'text-slate-500 hover:text-slate-200'"
                    class="rounded-lg px-2 py-1.5 transition">
                    <x-omni-icon :name="$icono" size="h-4 w-4" />
                </button>
            @endforeach
        </span>
    </header>


    {{-- ============ AGRUPAR Y MARCAR ============ --}}

    <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 bg-slate-950/60 px-4 py-2.5">

        <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">Agrupar por</span>

        @foreach ([['groupLevel1', 'primer nivel'], ['groupLevel2', 'segundo nivel']] as [$nivel, $ayuda])
            <select x-model="{{ $nivel }}" title="{{ $ayuda }}"
                class="rounded-lg border-slate-800 bg-slate-900 py-1.5 text-[10px] font-bold text-slate-300 focus:border-cyan-500 focus:ring-cyan-500">
                <option value="">Sin agrupar</option>
                <option value="type">Tipo</option>
                <option value="status">Estado</option>
                <option value="visibility">Visibilidad</option>
                <option value="collection">Colección</option>
            </select>
        @endforeach

        <button type="button" @click="selectAll()"
            class="rounded-lg border border-slate-800 bg-slate-900 px-2.5 py-1.5 text-[10px] font-black text-slate-300 transition hover:border-cyan-500 hover:text-cyan-300">
            Marcar todas
        </button>

        <button type="button" @click="clearSelection()" :disabled="!selectedCount"
            class="rounded-lg border border-slate-800 bg-slate-900 px-2.5 py-1.5 text-[10px] font-black text-slate-300 transition hover:border-rose-500 hover:text-rose-300 disabled:opacity-30">
            Desmarcar
        </button>

        <span class="ml-auto rounded-lg bg-cyan-500/15 px-2.5 py-1.5 font-mono text-[11px] font-black text-cyan-300">
            <span x-text="selectedCount"></span> marcadas
        </span>

    </div>


    {{-- ============ LAS ENTIDADES, AGRUPADAS ============ --}}

    <div class="max-h-[32rem] space-y-3 overflow-y-auto p-4">

        <template x-for="(grupo, etiqueta) in groupEntities(entities, groupLevel1)" :key="`g1-${etiqueta}`">
            <div>

                {{-- El rótulo del grupo, con su botón de marcar el grupo entero --}}
                <div x-show="groupLevel1" class="mb-1.5 flex items-center gap-2">
                    <span class="text-[9px] font-black uppercase tracking-wider text-cyan-400" x-text="etiqueta"></span>
                    <span class="h-px flex-1 bg-slate-800"></span>
                    <span class="font-mono text-[10px] text-slate-600" x-text="grupo.length"></span>

                    <button type="button" @click="selectEntities(grupo)"
                        class="rounded-md border border-slate-800 px-1.5 py-0.5 text-[9px] font-black text-slate-500 transition hover:border-cyan-500 hover:text-cyan-300">
                        marcar grupo
                    </button>
                </div>

                <template x-for="(subgrupo, subetiqueta) in groupSecond(grupo)" :key="`g2-${etiqueta}-${subetiqueta}`">
                    <div class="mb-2">

                        <div x-show="groupLevel2" class="mb-1 flex items-center gap-2 pl-2">
                            <span class="text-[9px] font-bold text-slate-500" x-text="subetiqueta"></span>
                            <span class="h-px flex-1 bg-slate-800/60"></span>

                            <button type="button" @click="selectEntities(subgrupo)"
                                class="text-[9px] font-black text-slate-600 transition hover:text-cyan-300">
                                marcar
                            </button>
                        </div>


                        {{-- FICHAS --}}
                        <div x-show="pickView === 'cards'"
                            class="grid gap-1.5 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                            <template x-for="entity in subgrupo" :key="`c-${entity.id}`">
                                <button type="button" @click="toggleSelection(entity.id)"
                                    :style="isSelected(entity.id) ?
                                        ('border-color: ' + (entity.entity_type_color || '#06b6d4')) : ''"
                                    :class="isSelected(entity.id) ? 'bg-cyan-500/10' :
                                        'border-slate-800 bg-slate-950/60 hover:border-slate-700'"
                                    class="group relative overflow-hidden rounded-xl border text-left transition">

                                    <span class="relative block aspect-[4/3] overflow-hidden bg-slate-950">
                                        <template x-if="entity.image_url">
                                            <img :src="entity.image_url" alt="" loading="lazy"
                                                class="h-full w-full object-cover transition group-hover:scale-105">
                                        </template>

                                        <template x-if="!entity.image_url">
                                            <span class="flex h-full w-full items-center justify-center text-lg font-black"
                                                :style="'color: ' + (entity.entity_type_color || '#475569') + '66'"
                                                x-text="entity.name.charAt(0).toUpperCase()"></span>
                                        </template>

                                        <span
                                            class="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded-md border text-[10px] font-black transition"
                                            :class="isSelected(entity.id) ?
                                                'border-cyan-400 bg-cyan-500 text-slate-950' :
                                                'border-slate-700 bg-slate-950/80 text-transparent'">✓</span>
                                    </span>

                                    <span class="block p-1.5">
                                        <span class="block truncate text-[10px] font-black text-white"
                                            x-text="entity.name"></span>
                                        <span class="block truncate text-[9px] text-slate-500"
                                            x-text="entity.entity_type_name"></span>
                                    </span>
                                </button>
                            </template>
                        </div>


                        {{-- LISTA --}}
                        <div x-show="pickView === 'list'" x-cloak class="space-y-1">
                            <template x-for="entity in subgrupo" :key="`l-${entity.id}`">
                                <button type="button" @click="toggleSelection(entity.id)"
                                    :class="isSelected(entity.id) ?
                                        'border-cyan-500/50 bg-cyan-500/10' :
                                        'border-slate-800 bg-slate-950/60 hover:border-slate-700'"
                                    class="flex w-full items-center gap-2.5 rounded-lg border px-2 py-1.5 text-left transition">

                                    <span
                                        class="flex h-4 w-4 shrink-0 items-center justify-center rounded border text-[9px] font-black transition"
                                        :class="isSelected(entity.id) ?
                                            'border-cyan-400 bg-cyan-500 text-slate-950' :
                                            'border-slate-700 text-transparent'">✓</span>

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

                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-[11px] font-black text-white"
                                            x-text="entity.name"></span>
                                        <span class="block truncate font-mono text-[9px] text-slate-600"
                                            x-text="entity.code"></span>
                                    </span>

                                    <span class="flex shrink-0 items-center gap-1.5 text-[9px]">
                                        <span class="flex items-center gap-1 text-slate-500">
                                            <span class="h-1.5 w-1.5 rounded-full"
                                                :style="'background-color: ' + (entity.entity_type_color || '#475569')"></span>
                                            <span x-text="entity.entity_type_name"></span>
                                        </span>

                                        <span class="rounded bg-slate-900 px-1.5 py-0.5 font-bold text-slate-400"
                                            x-text="entity.status_label"></span>
                                    </span>
                                </button>
                            </template>
                        </div>


                        {{-- TABLA --}}
                        <div x-show="pickView === 'table'" x-cloak
                            class="overflow-x-auto rounded-lg border border-slate-800">
                            <table class="w-full min-w-[620px]">
                                <tbody class="divide-y divide-slate-800/70">
                                    <template x-for="entity in subgrupo" :key="`t-${entity.id}`">
                                        <tr @click="toggleSelection(entity.id)"
                                            :class="isSelected(entity.id) ? 'bg-cyan-500/10' : 'hover:bg-slate-900/60'"
                                            class="cursor-pointer transition">

                                            <td class="w-8 px-2 py-1.5">
                                                <span
                                                    class="flex h-4 w-4 items-center justify-center rounded border text-[9px] font-black"
                                                    :class="isSelected(entity.id) ?
                                                        'border-cyan-400 bg-cyan-500 text-slate-950' :
                                                        'border-slate-700 text-transparent'">✓</span>
                                            </td>

                                            <td class="w-10 px-2 py-1.5">
                                                <span class="block h-7 w-7 overflow-hidden rounded border border-slate-800 bg-slate-900">
                                                    <template x-if="entity.image_url">
                                                        <img :src="entity.image_url" alt="" loading="lazy"
                                                            class="h-full w-full object-cover">
                                                    </template>

                                                    <template x-if="!entity.image_url">
                                                        <span class="flex h-full w-full items-center justify-center text-[10px] font-black text-slate-600"
                                                            x-text="entity.name.charAt(0).toUpperCase()"></span>
                                                    </template>
                                                </span>
                                            </td>

                                            <td class="px-2 py-1.5 text-[11px] font-black text-white" x-text="entity.name"></td>
                                            <td class="px-2 py-1.5 font-mono text-[9px] text-slate-600" x-text="entity.code"></td>
                                            <td class="px-2 py-1.5 text-[10px] text-slate-400" x-text="entity.entity_type_name"></td>
                                            <td class="px-2 py-1.5 text-[10px] text-slate-400" x-text="entity.status_label"></td>
                                            <td class="px-2 py-1.5 text-[10px] text-slate-400" x-text="entity.visibility_label"></td>
                                            <td class="px-2 py-1.5 text-right font-mono text-[10px] text-slate-600"
                                                x-text="entity.collections.length + ' col.'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </template>

            </div>
        </template>

        <p x-show="entities.length === 0" class="py-10 text-center text-[11px] text-slate-600">
            Ninguna entidad encaja con los filtros de arriba.
        </p>

    </div>

</section>
