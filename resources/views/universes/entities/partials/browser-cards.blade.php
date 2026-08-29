@php
    /*
     * LOS OTROS CUATRO MODOS.
     *
     * Comparten rejilla y enlace, y cambian solo por dentro. Están juntos
     * porque son la misma ficha con más o menos detalle: separarlos en
     * cuatro archivos habría multiplicado por cuatro cada arreglo del
     * enlace, del récord o del trofeo.
     */
@endphp

<div x-show="view !== 'TABLE'" x-cloak class="grid gap-2" :class="grid">

    <template x-for="e in shown" :key="'c' + e.id">
        <a :href="'{{ route('universes.entities.index', $universe) }}/' + e.id"
            class="group block overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50 transition hover:border-emerald-500/40 hover:bg-slate-900">


            {{-- ====== CUADRÍCULA: solo la cara ====== --}}

            <template x-if="view === 'GRID'">
                <span class="block">
                    <span class="relative block aspect-square overflow-hidden bg-slate-950">
                        <template x-if="e.image_url">
                            <img :src="e.image_url" alt="" loading="lazy"
                                class="h-full w-full object-cover transition duration-200 group-hover:scale-105">
                        </template>
                        <template x-if="!e.image_url">
                            <span class="flex h-full w-full items-center justify-center font-mono text-[13px] font-black text-slate-700"
                                x-text="e.name.slice(0, 2).toUpperCase()"></span>
                        </template>

                        {{-- Los títulos son lo único que cabe aquí sin estorbar --}}
                        <span x-show="e.record.titles"
                            class="absolute left-1 top-1 rounded bg-amber-500 px-1 font-mono text-[9px] font-black text-slate-950"
                            x-text="'★' + e.record.titles"></span>

                        <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950 to-transparent px-1.5 pb-1 pt-5">
                            <span class="block truncate text-[10px] font-black text-slate-100" x-text="e.name"></span>
                        </span>
                    </span>
                </span>
            </template>


            {{-- ====== GALERÍA: la cara y sus atributos ====== --}}

            <template x-if="view === 'GALLERY'">
                <span class="block">
                    <span class="relative block aspect-[4/5] overflow-hidden bg-slate-950">
                        <template x-if="e.image_url">
                            <img :src="e.image_url" alt="" loading="lazy"
                                class="h-full w-full object-cover transition duration-200 group-hover:scale-105">
                        </template>
                        <template x-if="!e.image_url">
                            <span class="flex h-full w-full items-center justify-center font-mono text-[18px] font-black text-slate-700"
                                x-text="e.name.slice(0, 2).toUpperCase()"></span>
                        </template>

                        <span class="absolute right-1 top-1 flex gap-0.5">
                            <template x-for="tr in e.trophies.slice(0, 3)" :key="'gt' + e.id + tr.id">
                                <span class="flex h-5 w-5 items-center justify-center overflow-hidden rounded bg-slate-950/90 text-[10px]"
                                    :title="tr.name">
                                    <template x-if="tr.image_url">
                                        <img :src="tr.image_url" alt="" class="h-full w-full object-cover">
                                    </template>
                                    <template x-if="!tr.image_url">
                                        <span x-text="tr.icon || '🏆'"></span>
                                    </template>
                                </span>
                            </template>
                        </span>

                        <span x-show="e.versions.length > 1"
                            class="absolute left-1 top-1 rounded bg-violet-500/90 px-1 font-mono text-[8px] font-black text-slate-950"
                            x-text="e.versions.length + ' ver.'"></span>

                        <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950 via-slate-950/80 to-transparent px-2 pb-1.5 pt-6">
                            <span class="block truncate text-[12px] font-black text-slate-100" x-text="e.name"></span>
                            <span class="block truncate text-[9px] text-slate-400" x-text="recordText(e)"></span>
                        </span>
                    </span>

                    <span class="flex flex-wrap gap-0.5 p-1.5">
                        <template x-for="chip in chipsOf(e).slice(0, 5)" :key="'gc' + e.id + chip.key">
                            <span class="truncate rounded px-1 py-0.5 text-[8px]"
                                :class="chip.featured ? 'bg-emerald-500/15 text-emerald-300' : 'bg-slate-950 text-slate-400'"
                                :title="chip.attribute + ': ' + chip.value"
                                x-text="chip.value"></span>
                        </template>

                        <span class="text-[8px] text-slate-700" x-show="chipsOf(e).length === 0">sin atributos</span>
                    </span>
                </span>
            </template>


            {{-- ====== LISTA: una línea con todo ====== --}}

            <template x-if="view === 'LIST'">
                <span class="flex items-center gap-2.5 p-2">

                    <span class="relative flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-slate-950">
                        <template x-if="e.image_url">
                            <img :src="e.image_url" alt="" loading="lazy" class="h-full w-full object-cover">
                        </template>
                        <template x-if="!e.image_url">
                            <span class="font-mono text-[11px] font-black text-slate-700"
                                x-text="e.name.slice(0, 2).toUpperCase()"></span>
                        </template>
                    </span>

                    <span class="w-44 shrink-0">
                        <span class="block truncate text-[12px] font-black text-slate-100" x-text="e.name"></span>
                        <span class="block truncate font-mono text-[8px] text-slate-600"
                            x-text="e.code + (e.type ? ' · ' + e.type : '')"></span>
                    </span>

                    <span class="flex min-w-0 flex-1 flex-wrap gap-0.5">
                        <template x-for="chip in chipsOf(e)" :key="'lc' + e.id + chip.key">
                            <span class="rounded px-1.5 py-0.5 text-[9px]"
                                :class="chip.featured ? 'bg-emerald-500/15' : 'bg-slate-950'">
                                <span class="text-slate-600" x-text="chip.attribute"></span>
                                <span class="ml-1 text-slate-300" x-text="chip.value"></span>
                            </span>
                        </template>

                        <span class="text-[9px] text-slate-700" x-show="chipsOf(e).length === 0">sin atributos</span>
                    </span>

                    <span class="hidden shrink-0 items-center gap-0.5 sm:flex">
                        <template x-for="tr in e.trophies.slice(0, 4)" :key="'lt' + e.id + tr.id">
                            <span class="flex h-6 w-6 items-center justify-center overflow-hidden rounded bg-slate-950 text-[11px]"
                                :title="tr.name">
                                <template x-if="tr.image_url">
                                    <img :src="tr.image_url" alt="" class="h-full w-full object-cover">
                                </template>
                                <template x-if="!tr.image_url">
                                    <span x-text="tr.icon || '🏆'"></span>
                                </template>
                            </span>
                        </template>
                    </span>

                    <span class="w-24 shrink-0 text-right">
                        <span class="block font-mono text-[11px]"
                            :class="e.record.winrate === null ? 'text-slate-700' : 'text-slate-300'"
                            x-text="e.record.winrate === null ? 'sin jugar' : e.record.wins + '–' + e.record.losses"></span>
                        <span class="block font-mono text-[8px] text-slate-600"
                            x-text="e.record.titles ? '★ ' + e.record.titles : recordText(e).split(' · ')[0]"></span>
                    </span>
                </span>
            </template>


            {{-- ====== FICHA: todo, versiones incluidas ====== --}}

            <template x-if="view === 'CARD'">
                <span class="block">

                    <span class="flex gap-2.5 p-2.5">

                        <span class="relative flex h-24 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-slate-950">
                            <template x-if="e.image_url">
                                <img :src="e.image_url" alt="" loading="lazy" class="h-full w-full object-cover">
                            </template>
                            <template x-if="!e.image_url">
                                <span class="font-mono text-[15px] font-black text-slate-700"
                                    x-text="e.name.slice(0, 2).toUpperCase()"></span>
                            </template>
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[13px] font-black text-slate-100" x-text="e.name"></span>
                            <span class="block truncate font-mono text-[8px] text-slate-600"
                                x-text="e.code + (e.type ? ' · ' + e.type : '')"></span>

                            {{-- Su récord, en cifras --}}
                            <span class="mt-1.5 grid grid-cols-4 gap-1">
                                @foreach ([
                                    ['tournaments', 'torneos', 'text-slate-300'],
                                    ['wins', 'ganadas', 'text-emerald-300'],
                                    ['titles', 'títulos', 'text-amber-300'],
                                    ['trophies', 'trofeos', 'text-violet-300'],
                                ] as [$campo, $label, $tono])
                                    <span class="rounded-lg bg-slate-950 px-1 py-1 text-center">
                                        <span class="block font-mono text-[13px] font-black leading-none {{ $tono }}"
                                            x-text="e.record.{{ $campo }}"></span>
                                        <span class="block text-[7px] uppercase tracking-wider text-slate-600">{{ $label }}</span>
                                    </span>
                                @endforeach
                            </span>
                        </span>
                    </span>

                    {{-- Su catálogo, entero --}}

                    <span class="block border-t border-slate-800 px-2.5 py-1.5">
                        <span class="text-[8px] font-black uppercase tracking-wider text-slate-600">Atributos</span>

                        <span class="mt-0.5 flex flex-wrap gap-0.5">
                            <template x-for="a in e.attributes" :key="'ca' + e.id + a.key">
                                <span class="rounded px-1.5 py-0.5 text-[9px]"
                                    :class="a.featured ? 'bg-emerald-500/15' : 'bg-slate-950'">
                                    <span class="text-slate-600" x-text="a.name"></span>
                                    <span class="ml-1 text-slate-300" x-text="a.display || a.values.join(', ')"></span>
                                </span>
                            </template>

                            <span class="text-[9px] text-slate-700" x-show="e.attributes.length === 0">
                                sin atributos
                            </span>
                        </span>
                    </span>

                    {{--
                        Sus versiones, con su propia cara.

                        Es lo que decide con qué imagen sale en un torneo:
                        la de Shippuden en un torneo de Shippuden, la del
                        niño en uno de la primera serie.
                    --}}

                    <span class="block border-t border-slate-800 px-2.5 py-1.5" x-show="e.versions.length">
                        <span class="text-[8px] font-black uppercase tracking-wider text-violet-400">
                            Versiones <span class="font-mono text-slate-600" x-text="e.versions.length"></span>
                        </span>

                        <span class="mt-1 flex flex-wrap gap-1">
                            <template x-for="v in e.versions" :key="'cv' + e.id + v.id">
                                <span class="flex items-center gap-1 rounded-lg border px-1 py-0.5"
                                    :class="v.is_base
                                        ? 'border-violet-500/50 bg-violet-500/10'
                                        : 'border-slate-800 bg-slate-950'">

                                    <span class="flex h-6 w-6 items-center justify-center overflow-hidden rounded bg-slate-900">
                                        <template x-if="v.image_url">
                                            <img :src="v.image_url" alt="" loading="lazy" class="h-full w-full object-cover">
                                        </template>
                                        <template x-if="!v.image_url">
                                            <span class="text-[8px] text-slate-700">—</span>
                                        </template>
                                    </span>

                                    <span class="max-w-[6rem] truncate text-[9px]"
                                        :class="v.is_base ? 'text-violet-200' : 'text-slate-400'"
                                        x-text="v.name"></span>

                                    <span x-show="v.is_base" class="text-[8px] text-violet-400" title="La versión base">★</span>
                                </span>
                            </template>
                        </span>
                    </span>

                    <span class="block border-t border-slate-800 px-2.5 py-1 text-[8px] text-slate-600"
                        x-show="e.versions.length === 0">
                        Sin versiones: sale siempre con la misma imagen.
                    </span>
                </span>
            </template>

        </a>
    </template>
</div>
