@php
    /*
     * Atar dos catálogos.
     *
     * «Cuando en País eliges Perú, en Región deja solo Tacna, Lima, Arequipa.»
     * Eso es lo único que hace esta pantalla, y antes se pedía con cuatro
     * desplegables de texto encadenados en los que era imposible saber si te
     * habías equivocado hasta después de guardar.
     *
     * Ahora se eligen las dos caras —la del valor que manda y la del valor que
     * obedece— y la frase se lee entera antes de guardarla.
     */
@endphp

<form method="POST" action="{{ route('attributes.structure.options.store') }}"
    class="overflow-hidden rounded-2xl border border-fuchsia-500/30 bg-slate-900/50">
    @csrf

    <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 bg-fuchsia-500/5 px-4 py-3">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-fuchsia-500/15 text-fuchsia-300">
            <x-omni-icon name="capas" size="h-4 w-4" />
        </span>

        <div class="min-w-0 flex-1">
            <h2 class="text-[13px] font-black text-white">Atar dos catálogos</h2>
            <p class="text-[10px] leading-relaxed text-slate-500">
                Que elegir un valor deje disponibles —o descarte— valores de otro catálogo.
            </p>
        </div>
    </div>


    {{-- Sin al menos dos catálogos con valores esto no puede hacer nada --}}
    <div x-show="catalogos.length < 2" x-cloak class="p-10 text-center">
        <span class="inline-flex text-slate-700"><x-omni-icon name="capas" size="h-9 w-9" /></span>

        <p class="mt-2 text-[13px] font-black text-white">Hacen falta al menos dos catálogos</p>

        <p class="mx-auto mt-1 max-w-md text-[11px] leading-relaxed text-slate-500">
            Esto ata los valores de un catálogo a los de otro, así que con uno solo no hay nada que
            atar. Ahora mismo tienes <strong class="text-slate-300" x-text="catalogos.length"></strong>.
        </p>

        <a href="{{ route('attributes.create') }}"
            class="mt-3 inline-block rounded-xl bg-fuchsia-500 px-4 py-2 text-[11px] font-black text-white transition hover:bg-fuchsia-400">
            Crear un atributo de catálogo
        </a>
    </div>


    <div x-show="catalogos.length >= 2" class="space-y-4 p-4">

        <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)]">

            {{-- ============================================= --}}
            {{-- EL QUE MANDA --}}
            {{-- ============================================= --}}

            <div class="rounded-2xl border border-slate-800 bg-slate-950 p-3">

                <div class="mb-2 flex items-center gap-2">
                    <span class="flex h-5 w-5 items-center justify-center rounded-md bg-fuchsia-500/20 font-mono text-[10px] font-black text-fuchsia-300">1</span>
                    <span class="text-[11px] font-black text-white">El valor que manda</span>
                </div>

                {{-- Su catálogo --}}
                <div class="flex gap-1.5 overflow-x-auto pb-1">
                    <template x-for="cat in catalogos" :key="'cf' + cat.id">
                        <button type="button" @click="elegirCatalogoFuente(cat.id)" :title="cat.name"
                            :class="String(catFuente) === String(cat.id)
                                ? 'border-fuchsia-500 bg-fuchsia-500/10'
                                : 'border-slate-800 bg-slate-900/60 hover:border-slate-600'"
                            class="flex shrink-0 items-center gap-1.5 rounded-xl border py-1 pl-1 pr-2 transition">

                            <span class="h-7 w-7 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                <template x-if="cat.image">
                                    <img :src="cat.image" alt="" loading="lazy" class="h-full w-full object-cover">
                                </template>
                                <template x-if="! cat.image">
                                    <span class="flex h-full w-full items-center justify-center text-[10px]"
                                        :style="`color: ${cat.color}`" x-text="cat.icon"></span>
                                </template>
                            </span>

                            <span class="text-[10px] font-black text-white" x-text="cat.name"></span>
                            <span class="font-mono text-[9px] text-slate-600" x-text="cat.options.length"></span>
                        </button>
                    </template>
                </div>

                {{-- Su valor --}}
                <input type="hidden" name="source_option_id" :value="catValorFuente" required>

                <div x-show="! catFuente" class="mt-2 rounded-xl border border-dashed border-slate-800 py-4 text-center text-[10px] text-slate-600">
                    Elige un catálogo aquí arriba.
                </div>

                <div x-show="catFuente && opcionesDe(catFuente).length === 0" x-cloak
                    class="mt-2 rounded-xl border border-dashed border-amber-500/30 bg-amber-500/5 py-4 text-center text-[10px] leading-relaxed text-amber-200/70">
                    Este catálogo no tiene ningún valor activo todavía.
                </div>

                <div x-show="catFuente && opcionesDe(catFuente).length > 0" x-cloak
                    class="mt-2 grid max-h-52 grid-cols-3 gap-1.5 overflow-y-auto sm:grid-cols-4">

                    <template x-for="opcion in opcionesDe(catFuente)" :key="'vf' + opcion.id">
                        <button type="button" @click="catValorFuente = String(opcion.id)" :title="opcion.name"
                            :class="String(catValorFuente) === String(opcion.id)
                                ? 'border-fuchsia-500'
                                : 'border-slate-800 hover:border-slate-600'"
                            class="overflow-hidden rounded-xl border bg-slate-900/60 transition">

                            <span class="relative block aspect-square overflow-hidden bg-slate-950">
                                <template x-if="opcion.image">
                                    <img :src="opcion.image" alt="" loading="lazy" class="h-full w-full object-cover">
                                </template>
                                <template x-if="! opcion.image">
                                    <span class="flex h-full w-full items-center justify-center text-sm"
                                        :style="`color: ${opcion.color}`" x-text="opcion.icon || '◇'"></span>
                                </template>

                                <span x-show="String(catValorFuente) === String(opcion.id)"
                                    class="absolute inset-0 flex items-center justify-center bg-fuchsia-500/40 text-sm font-black text-white">✓</span>
                            </span>

                            <span class="block truncate px-1 py-0.5 text-center text-[9px] font-black text-slate-300"
                                x-text="opcion.name"></span>
                        </button>
                    </template>
                </div>
            </div>


            {{-- ============================================= --}}
            {{-- QUÉ HACE --}}
            {{-- ============================================= --}}

            <div class="flex flex-col justify-center gap-2 lg:w-40">

                <input type="hidden" name="relationship_type" :value="catTipo">

                <span class="flex h-5 w-5 items-center justify-center self-center rounded-md bg-fuchsia-500/20 font-mono text-[10px] font-black text-fuchsia-300">2</span>

                <button type="button" @click="catTipo = 'ALLOWS'"
                    :class="catTipo === 'ALLOWS' ? 'border-emerald-500 bg-emerald-500/10' : 'border-slate-800 bg-slate-950 hover:border-slate-600'"
                    class="rounded-2xl border p-2.5 text-left transition">

                    <svg viewBox="0 0 90 34" class="h-auto w-full text-emerald-400" fill="none"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                        <rect x="3" y="6" width="26" height="22" rx="4" opacity=".55" />
                        <path d="M35 17h14M49 17l-5-4M49 17l-5 4" opacity=".8" />
                        <rect x="55" y="3" width="32" height="12" rx="3" />
                        <rect x="55" y="19" width="32" height="12" rx="3" stroke-dasharray="3 3" opacity=".25" />
                        <path d="M60 9l3 3 6-6" opacity=".9" />
                    </svg>

                    <p class="mt-1 text-[11px] font-black text-emerald-300">Permite</p>
                    <p class="text-[9px] leading-3 text-slate-500">
                        Deja disponible solo lo permitido.
                    </p>
                </button>

                <button type="button" @click="catTipo = 'BLOCKS'"
                    :class="catTipo === 'BLOCKS' ? 'border-rose-500 bg-rose-500/10' : 'border-slate-800 bg-slate-950 hover:border-slate-600'"
                    class="rounded-2xl border p-2.5 text-left transition">

                    <svg viewBox="0 0 90 34" class="h-auto w-full text-rose-400" fill="none"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                        <rect x="3" y="6" width="26" height="22" rx="4" opacity=".55" />
                        <path d="M35 17h14M49 17l-5-4M49 17l-5 4" opacity=".8" />
                        <rect x="55" y="3" width="32" height="12" rx="3" opacity=".9" />
                        <rect x="55" y="19" width="32" height="12" rx="3" opacity=".3" />
                        <path d="M60 22l8 6M68 22l-8 6" opacity=".8" />
                    </svg>

                    <p class="mt-1 text-[11px] font-black text-rose-300">Bloquea</p>
                    <p class="text-[9px] leading-3 text-slate-500">
                        Descarta ese valor y deja el resto.
                    </p>
                </button>
            </div>


            {{-- ============================================= --}}
            {{-- EL QUE OBEDECE --}}
            {{-- ============================================= --}}

            <div class="rounded-2xl border border-slate-800 bg-slate-950 p-3">

                <div class="mb-2 flex items-center gap-2">
                    <span class="flex h-5 w-5 items-center justify-center rounded-md bg-fuchsia-500/20 font-mono text-[10px] font-black text-fuchsia-300">3</span>
                    <span class="text-[11px] font-black text-white">El valor al que afecta</span>
                </div>

                <div class="flex gap-1.5 overflow-x-auto pb-1">
                    <template x-for="cat in catalogos" :key="'cd' + cat.id">
                        <button type="button" @click="elegirCatalogoDestino(cat.id)" :title="cat.name"
                            :class="String(catDestino) === String(cat.id)
                                ? 'border-fuchsia-500 bg-fuchsia-500/10'
                                : 'border-slate-800 bg-slate-900/60 hover:border-slate-600'"
                            class="flex shrink-0 items-center gap-1.5 rounded-xl border py-1 pl-1 pr-2 transition">

                            <span class="h-7 w-7 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                <template x-if="cat.image">
                                    <img :src="cat.image" alt="" loading="lazy" class="h-full w-full object-cover">
                                </template>
                                <template x-if="! cat.image">
                                    <span class="flex h-full w-full items-center justify-center text-[10px]"
                                        :style="`color: ${cat.color}`" x-text="cat.icon"></span>
                                </template>
                            </span>

                            <span class="text-[10px] font-black text-white" x-text="cat.name"></span>
                            <span class="font-mono text-[9px] text-slate-600" x-text="cat.options.length"></span>
                        </button>
                    </template>
                </div>

                <input type="hidden" name="target_option_id" :value="catValorDestino" required>

                <div x-show="! catDestino" class="mt-2 rounded-xl border border-dashed border-slate-800 py-4 text-center text-[10px] text-slate-600">
                    Elige un catálogo aquí arriba.
                </div>

                {{-- Atar un catálogo consigo mismo no describe nada útil --}}
                <div x-show="catDestino && String(catDestino) === String(catFuente)" x-cloak
                    class="mt-2 rounded-xl border border-dashed border-amber-500/30 bg-amber-500/5 px-3 py-3 text-center text-[10px] leading-relaxed text-amber-200/70">
                    Has elegido el mismo catálogo en los dos lados. Se puede guardar, pero lo normal es
                    atar un catálogo a <strong class="text-amber-200">otro</strong>.
                </div>

                <div x-show="catDestino && opcionesDe(catDestino).length === 0" x-cloak
                    class="mt-2 rounded-xl border border-dashed border-amber-500/30 bg-amber-500/5 py-4 text-center text-[10px] leading-relaxed text-amber-200/70">
                    Este catálogo no tiene ningún valor activo todavía.
                </div>

                <div x-show="catDestino && opcionesDe(catDestino).length > 0" x-cloak
                    class="mt-2 grid max-h-52 grid-cols-3 gap-1.5 overflow-y-auto sm:grid-cols-4">

                    <template x-for="opcion in opcionesDe(catDestino)" :key="'vd' + opcion.id">
                        <button type="button" @click="catValorDestino = String(opcion.id)" :title="opcion.name"
                            :class="String(catValorDestino) === String(opcion.id)
                                ? 'border-fuchsia-500'
                                : 'border-slate-800 hover:border-slate-600'"
                            class="overflow-hidden rounded-xl border bg-slate-900/60 transition">

                            <span class="relative block aspect-square overflow-hidden bg-slate-950">
                                <template x-if="opcion.image">
                                    <img :src="opcion.image" alt="" loading="lazy" class="h-full w-full object-cover">
                                </template>
                                <template x-if="! opcion.image">
                                    <span class="flex h-full w-full items-center justify-center text-sm"
                                        :style="`color: ${opcion.color}`" x-text="opcion.icon || '◇'"></span>
                                </template>

                                <span x-show="String(catValorDestino) === String(opcion.id)"
                                    class="absolute inset-0 flex items-center justify-center bg-fuchsia-500/40 text-sm font-black text-white">✓</span>
                            </span>

                            <span class="block truncate px-1 py-0.5 text-center text-[9px] font-black text-slate-300"
                                x-text="opcion.name"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>


        {{-- ============================================= --}}
        {{-- CÓMO QUEDARÍA --}}
        {{-- ============================================= --}}

        <div class="rounded-2xl border border-slate-800 bg-slate-950 p-3">

            <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">Cómo quedaría</p>

            <p class="mt-1.5 flex flex-wrap items-center gap-1.5 text-[12px] leading-relaxed text-slate-300">

                <span class="text-slate-500">Cuando en</span>

                <span class="font-black text-white" x-text="atributo(catFuente)?.name || '…'"></span>

                <span class="text-slate-500">se elija</span>

                <span class="inline-flex items-center gap-1 rounded-lg border border-slate-800 bg-slate-900 px-1.5 py-0.5">
                    {{-- La cara solo si la hay: un recuadro vacío se lee como imagen rota --}}
                    <template x-if="opcion(catFuente, catValorFuente)?.image">
                        <span class="-ml-1 h-5 w-5 shrink-0 overflow-hidden rounded border border-slate-800 bg-slate-950">
                            <img :src="opcion(catFuente, catValorFuente).image" alt="" class="h-full w-full object-cover">
                        </span>
                    </template>
                    <span class="text-[10px] font-black text-fuchsia-300"
                        x-text="opcion(catFuente, catValorFuente)?.name || '…'"></span>
                </span>

                <span class="rounded-lg px-1.5 py-0.5 text-[10px] font-black uppercase tracking-wider"
                    :class="catTipo === 'ALLOWS' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-rose-500/20 text-rose-300'"
                    x-text="catTipo === 'ALLOWS' ? 'permite' : 'bloquea'"></span>

                <span class="inline-flex items-center gap-1 rounded-lg border border-slate-800 bg-slate-900 px-1.5 py-0.5">
                    <template x-if="opcion(catDestino, catValorDestino)?.image">
                        <span class="-ml-1 h-5 w-5 shrink-0 overflow-hidden rounded border border-slate-800 bg-slate-950">
                            <img :src="opcion(catDestino, catValorDestino).image" alt="" class="h-full w-full object-cover">
                        </span>
                    </template>
                    <span class="text-[10px] font-black text-white"
                        x-text="opcion(catDestino, catValorDestino)?.name || '…'"></span>
                </span>

                <span class="text-slate-500">de</span>

                <span class="font-black text-white" x-text="atributo(catDestino)?.name || '…'"></span>
            </p>
        </div>


        <div class="flex flex-wrap items-end gap-2">

            <label class="block">
                <span class="mb-1 block text-[9px] font-black uppercase tracking-wider text-slate-600"
                    title="Cuando varias dependencias afectan al mismo valor, gana la de número más alto">
                    Prioridad
                </span>
                <input type="number" name="priority" x-model.number="catPrioridad" step="1"
                    class="w-24 rounded-xl border-slate-800 bg-slate-950 text-center font-mono text-xs text-slate-200 focus:border-fuchsia-500 focus:ring-fuchsia-500">
            </label>

            <button type="submit" :disabled="! mapeoCompleto"
                class="rounded-xl bg-fuchsia-500 px-5 py-2.5 text-[12px] font-black text-white transition hover:bg-fuchsia-400 disabled:cursor-not-allowed disabled:bg-slate-800 disabled:text-slate-600">
                Guardar la dependencia
            </button>

            <p x-show="! mapeoCompleto" x-cloak class="text-[10px] text-slate-600">
                Faltan los dos valores: el que manda y el que obedece.
            </p>
        </div>

    </div>
</form>
