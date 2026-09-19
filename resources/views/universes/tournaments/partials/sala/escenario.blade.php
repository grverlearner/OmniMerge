{{--
    EL ESCENARIO

    Las caras, repartidas de cuatro formas:

      dentro y fuera   quién juega y quién se queda fuera
      por puerta       una caja por entrada, con sus plazas
      por valor        una caja por cada valor de un atributo, con quién
                       de cada valor entra y quién no
      lista            todo en una tabla: por qué entra, puerta y cara
--}}

<section class="min-w-0 space-y-2">

    @include('universes.tournaments.partials.sala.jugable')

    {{-- ============ LA BARRA ============ --}}

    <div class="flex flex-wrap items-center gap-2 rounded-2xl border border-slate-800 bg-slate-900/60 p-2">

        <span class="flex rounded-xl border border-slate-800 bg-slate-950 p-1">
            @foreach ([['stage', 'Dentro y fuera', 'usuario'], ['doors', 'Por puerta', 'puerta'], ['values', 'Por valor', 'capas'], ['list', 'Lista', 'panel']] as [$clave, $texto, $icono])
                <button type="button" @click="view = '{{ $clave }}'"
                    :class="view === '{{ $clave }}' ? 'bg-rose-500 text-slate-950' : 'text-slate-400 hover:text-white'"
                    class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[11px] font-black transition">
                    <x-omni-icon :name="$icono" size="h-3.5 w-3.5" />
                    <span class="hidden sm:inline">{{ $texto }}</span>
                </button>
            @endforeach
        </span>

        <label class="relative min-w-[160px] flex-1">
            <span class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-600">
                <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
            </span>
            <input type="search" x-model="search" @keydown.enter.prevent placeholder="Buscar por nombre, tipo o valor…"
                class="w-full rounded-xl border-slate-700 bg-slate-950 py-1.5 pl-8 text-[11px] text-slate-200 placeholder:text-slate-600 focus:border-rose-500 focus:ring-rose-500">
        </label>

        <button type="button" @click="issuesOnly = ! issuesOnly"
            :class="issuesOnly ? 'border-amber-400 bg-amber-500/15 text-amber-200' : 'border-slate-700 text-slate-400 hover:text-white'"
            class="flex items-center gap-1.5 rounded-xl border px-2.5 py-1.5 text-[11px] font-black transition"
            title="Solo los que entran sin imagen o sin puerta">
            <x-omni-icon name="aviso" size="h-3.5 w-3.5" />
            Problemas
        </button>

        <button type="button" x-show="faceFilter" x-cloak @click="faceFilter = null"
            class="flex items-center gap-1 rounded-xl border px-2.5 py-1.5 text-[11px] font-black"
            :style="`border-color: ${fromTone(faceFilter)}; color: ${fromTone(faceFilter)}`">
            <span x-text="fromLabel(faceFilter)"></span>
            <x-omni-icon name="cerrar" size="h-3 w-3" />
        </button>

        <label x-show="view === 'stage'" class="flex items-center gap-1.5 text-[10px] text-slate-500" title="Tamaño de las caras">
            <x-omni-icon name="cuadricula" size="h-3.5 w-3.5" />
            <input type="range" min="4" max="12" step="1" x-model.number="size" class="w-20 accent-rose-500">
        </label>
    </div>

    <template x-if="panel === 'doors' && doors.mode === 'MANUAL' && brush">
        <p class="flex items-center gap-2 rounded-xl border px-3 py-2 text-[11px] font-black"
            :style="`border-color: ${doorColor(brush)}; background-color: ${doorColor(brush)}1a; color: ${doorColor(brush)}`">
            <x-omni-icon name="pincel" size="h-4 w-4" />
            <span>Pincel activo: pulsa una cara para meterla por <span x-text="doorName(brush)"></span></span>
            <button type="button" @click="brush = null" class="ml-auto text-slate-400 hover:text-white"><x-omni-icon name="cerrar" size="h-4 w-4" /></button>
        </p>
    </template>


    {{-- ============ DENTRO Y FUERA ============ --}}

    <div x-show="view === 'stage'" class="grid items-start gap-2 2xl:grid-cols-[minmax(0,1fr)_340px]">

        <div class="rounded-2xl border border-emerald-500/30 bg-emerald-500/5 p-2.5">
            <div class="mb-2 flex items-center gap-2">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-500/20 text-emerald-300">
                    <x-omni-icon name="check" size="h-4 w-4" />
                </span>
                <p class="text-[13px] font-black text-emerald-200">Juegan</p>
                <span class="font-mono text-[12px] text-emerald-400/70" x-text="inList.length + (inList.length !== totalIn ? ' de ' + totalIn : '')"></span>
            </div>

            <div class="grid gap-1.5" :class="gridClass">
                <template x-for="c in inList" :key="'in' + c.id">
                    @include('universes.tournaments.partials.sala.cara')
                </template>
            </div>

            <template x-if="! inList.length">
                <p class="rounded-xl border border-dashed border-emerald-500/20 px-3 py-8 text-center text-[11px] text-slate-500"
                    x-text="totalIn ? 'Nadie de los que juegan encaja con la búsqueda.' : 'Nadie juega con estas condiciones.'"></p>
            </template>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/40 p-2.5">
            <div class="mb-2 flex items-center gap-2">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-800 text-slate-400">
                    <x-omni-icon name="cerrar" size="h-4 w-4" />
                </span>
                <p class="text-[13px] font-black text-slate-300">Fuera</p>
                <span class="font-mono text-[12px] text-slate-500" x-text="outList.length"></span>
            </div>

            <div class="grid grid-cols-4 gap-1.5 sm:grid-cols-6 2xl:grid-cols-3">
                <template x-for="c in outList" :key="'out' + c.id">
                    @include('universes.tournaments.partials.sala.cara')
                </template>
            </div>

            <template x-if="! outList.length">
                <p class="rounded-xl border border-dashed border-slate-800 px-3 py-8 text-center text-[11px] text-slate-500">
                    No se queda nadie fuera.
                </p>
            </template>
        </div>
    </div>


    {{-- ============ POR PUERTA ============ --}}

    <div x-show="view === 'doors'" x-cloak class="space-y-2">

        <template x-if="! starts.length">
            <p class="rounded-2xl border border-dashed border-slate-700 px-4 py-10 text-center text-[11px] text-slate-500">
                La plantilla de este torneo no tiene puertas de entrada.
            </p>
        </template>

        <div class="grid items-start gap-2 lg:grid-cols-2">
            <template x-for="s in starts" :key="'vdoor' + s.id">
                <div class="overflow-hidden rounded-2xl border bg-slate-900/50" :style="`border-color: ${doorColor(s.id)}66`">
                    <div class="flex items-center gap-2 px-3 py-2" :style="`background: linear-gradient(120deg, ${doorColor(s.id)}26, transparent 70%)`">
                        <span class="rounded-md px-1.5 py-0.5 font-mono text-[11px] font-black text-slate-950" :style="`background-color: ${doorColor(s.id)}`" x-text="doorShort(s.id)"></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[13px] font-black text-white" x-text="s.name"></span>
                            <span class="block truncate text-[10px] text-slate-400" x-text="effDoors.mode === 'RULES' && starts.length > 1 ? doorRuleText(s.id) : (s.description || '')"></span>
                        </span>
                        <span class="text-right">
                            <span class="block font-mono text-lg font-black leading-none" :style="`color: ${doorState(s.id).tone}`" x-text="doorCount(s.id) + (s.capacity ? '/' + s.capacity : '')"></span>
                            <span class="block text-[9px] font-black" :style="`color: ${doorState(s.id).tone}`" x-text="doorState(s.id).text"></span>
                        </span>
                    </div>

                    <div class="h-1 bg-slate-800">
                        <div class="h-full transition-all" :style="`width: ${doorFill(s.id)}%; background-color: ${doorColor(s.id)}`"></div>
                    </div>

                    <div class="grid grid-cols-4 gap-1.5 p-2.5 sm:grid-cols-6 lg:grid-cols-5 2xl:grid-cols-6">
                        <template x-for="c in doorMembers(s.id)" :key="'vd' + s.id + '-' + c.id">
                            @include('universes.tournaments.partials.sala.cara')
                        </template>
                    </div>

                    <template x-if="! doorCount(s.id)">
                        <p class="px-3 pb-4 text-center text-[10px] text-slate-600">Nadie entra por esta puerta.</p>
                    </template>
                </div>
            </template>
        </div>

        <template x-if="starts.length && unplaced.length">
            <div class="rounded-2xl border border-amber-500/40 bg-amber-500/5 p-2.5">
                <p class="mb-2 flex items-center gap-1.5 text-[12px] font-black text-amber-200">
                    <x-omni-icon name="aviso" size="h-4 w-4" />
                    Cumplen pero no tienen puerta
                    <span class="font-mono text-amber-400/70" x-text="unplaced.length"></span>
                </p>
                <div class="grid grid-cols-4 gap-1.5 sm:grid-cols-8 lg:grid-cols-10">
                    <template x-for="c in unplaced.filter((x) => matchesFilters(x))" :key="'vu' + c.id">
                        @include('universes.tournaments.partials.sala.cara')
                    </template>
                </div>
            </div>
        </template>
    </div>


    {{-- ============ POR VALOR ============ --}}

    <div x-show="view === 'values'" x-cloak class="space-y-2">

        <div class="flex flex-wrap gap-1 rounded-2xl border border-slate-800 bg-slate-900/50 p-2">
            <template x-for="a in catalog" :key="'ga' + a.name">
                <button type="button" @click="groupAttr = a.name"
                    :class="groupAttr === a.name ? 'border-rose-400 bg-rose-500/15 text-rose-100' : 'border-slate-800 text-slate-400 hover:border-slate-600'"
                    class="rounded-lg border px-2 py-1 text-[11px] font-black transition">
                    <span x-text="a.label"></span>
                    <span class="font-mono text-[9px] opacity-60" x-text="a.entities"></span>
                </button>
            </template>
            <p x-show="! catalog.length" class="px-2 py-1 text-[11px] text-slate-500">No hay atributos por los que agrupar.</p>
        </div>

        <div class="grid items-start gap-2 lg:grid-cols-2">
            <template x-for="(v, vi) in groupValues" :key="'gv' + groupAttr + v.value">
                <div class="overflow-hidden rounded-2xl border bg-slate-900/50" :style="`border-color: ${valueTone(v, vi)}55; margin-left: ${v.depth * 16}px`">
                    <div class="flex items-center gap-2 px-3 py-2" :style="`background: linear-gradient(120deg, ${valueTone(v, vi)}22, transparent 70%)`">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-950">
                            <template x-if="v.image"><img :src="v.image" alt="" class="h-full w-full object-cover"></template>
                            <template x-if="! v.image"><span class="h-3 w-3 rounded-full" :style="`background-color: ${valueTone(v, vi)}`"></span></template>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[13px] font-black text-white" x-text="v.label"></span>
                            <span class="block text-[10px] text-slate-400">
                                <span class="text-emerald-300" x-text="valueMembers(v.value).filter((c) => isIn(c.id)).length + ' juegan'"></span>
                                · <span x-text="valueMembers(v.value).filter((c) => ! isIn(c.id)).length + ' fuera'"></span>
                            </span>
                        </span>
                        <button type="button"
                            @click="ruleFor(groupAttr)?.values.includes(v.value) ? toggleValue(ruleFor(groupAttr), v.value) : addRule(groupAttr, v.value)"
                            :class="ruleFor(groupAttr)?.values.includes(v.value) ? 'bg-rose-500 text-slate-950' : 'border border-slate-700 text-slate-300 hover:border-rose-400 hover:text-rose-200'"
                            class="shrink-0 rounded-lg px-2 py-1 text-[10px] font-black transition"
                            x-text="ruleFor(groupAttr)?.values.includes(v.value) ? 'En la regla' : '+ A la regla'"></button>
                    </div>

                    <div class="grid grid-cols-4 gap-1.5 p-2.5 sm:grid-cols-6 lg:grid-cols-5 2xl:grid-cols-6">
                        <template x-for="c in valueMembers(v.value)" :key="'gvm' + v.value + '-' + c.id">
                            @include('universes.tournaments.partials.sala.cara')
                        </template>
                    </div>

                    <template x-if="! valueMembers(v.value).length">
                        <p class="px-3 pb-3 text-center text-[10px] text-slate-600">Nadie lo lleva directamente: entra por sus hijos.</p>
                    </template>
                </div>
            </template>

            <template x-if="groupAttr && withoutGroupAttr.length">
                <div class="overflow-hidden rounded-2xl border border-dashed border-slate-700 bg-slate-900/30">
                    <div class="px-3 py-2">
                        <span class="block text-[13px] font-black text-slate-300">Sin <span x-text="attrLabel(groupAttr)"></span></span>
                        <span class="block text-[10px] text-slate-500" x-text="withoutGroupAttr.filter((c) => isIn(c.id)).length + ' juegan · ' + withoutGroupAttr.filter((c) => ! isIn(c.id)).length + ' fuera'"></span>
                    </div>
                    <div class="grid grid-cols-4 gap-1.5 p-2.5 pt-0 sm:grid-cols-6 lg:grid-cols-5 2xl:grid-cols-6">
                        <template x-for="c in withoutGroupAttr" :key="'sin' + c.id">
                            @include('universes.tournaments.partials.sala.cara')
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>


    {{-- ============ LISTA ============ --}}

    <div x-show="view === 'list'" x-cloak class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px]">
                <thead class="border-b border-slate-800 text-left">
                    <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                        <th class="px-3 py-2">Competidor</th>
                        <th class="px-3 py-2">Por qué</th>
                        <th class="px-3 py-2">Puerta</th>
                        <th class="px-3 py-2">Cara</th>
                        <th class="px-3 py-2">Atributos</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/70">
                    <template x-for="c in [...inList, ...outList]" :key="'row' + c.id">
                        <tr @click="ficha = c.id" class="cursor-pointer transition hover:bg-slate-950/60" :class="isIn(c.id) ? '' : 'opacity-60'">
                            <td class="px-3 py-1.5">
                                <span class="flex items-center gap-2">
                                    <span class="h-9 w-9 shrink-0 overflow-hidden rounded-lg border bg-slate-950" :style="`border-color: ${isIn(c.id) ? '#10b98166' : '#1e293b'}`">
                                        <template x-if="face(c).image_url"><img :src="face(c).image_url" alt="" class="h-full w-full object-cover" :class="isIn(c.id) ? '' : 'grayscale'"></template>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-[12px] font-black text-white" x-text="c.name"></span>
                                        <span class="block truncate text-[10px] text-slate-500" x-text="c.type || ''"></span>
                                    </span>
                                </span>
                            </td>
                            <td class="px-3 py-1.5">
                                <span class="rounded px-1.5 py-0.5 text-[10px] font-black"
                                    :class="isIn(c.id) ? 'bg-emerald-500/15 text-emerald-300' : 'bg-slate-800 text-slate-400'"
                                    x-text="reasonText(c.id)"></span>
                            </td>
                            <td class="px-3 py-1.5">
                                <template x-if="isIn(c.id) && calc.doorOf[c.id]">
                                    <span class="rounded px-1.5 py-0.5 text-[10px] font-black text-slate-950" :style="`background-color: ${doorColor(calc.doorOf[c.id])}`" x-text="doorShort(calc.doorOf[c.id]) + ' · ' + doorName(calc.doorOf[c.id])"></span>
                                </template>
                                <template x-if="isIn(c.id) && starts.length && ! calc.doorOf[c.id]">
                                    <span class="rounded bg-amber-400 px-1.5 py-0.5 text-[10px] font-black text-slate-950">sin puerta</span>
                                </template>
                            </td>
                            <td class="px-3 py-1.5">
                                <span class="block truncate text-[11px] font-bold text-slate-300" x-text="face(c).version_id ? face(c).name : 'Imagen de siempre'"></span>
                                <span class="block text-[9px] font-bold" :style="`color: ${fromTone(face(c).from)}`" x-text="fromLabel(face(c).from)"></span>
                            </td>
                            <td class="px-3 py-1.5">
                                <span class="flex flex-wrap gap-0.5">
                                    <template x-for="a in c.attributes" :key="'la' + c.id + a.name">
                                        <template x-for="(etiqueta, i) in a.labels" :key="'lv' + c.id + a.name + i">
                                            <span class="rounded px-1 py-0.5 text-[9px] font-bold"
                                                :class="valueWanted(a.name, a.values[i]) ? 'bg-rose-500/20 text-rose-200' : 'bg-slate-950 text-slate-500'"
                                                :title="a.label" x-text="etiqueta"></span>
                                        </template>
                                    </template>
                                </span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</section>
