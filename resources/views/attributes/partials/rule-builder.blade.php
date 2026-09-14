@php
    /*
     * El constructor de una regla contextual.
     *
     * Antes eran cuatro desplegables de texto. Ahora es lo mismo pero eligiendo
     * por la cara del atributo y por la cara del valor, que es como el usuario
     * los reconoce: nadie recuerda que «Naruto» es la opción 20.
     *
     * Cuidado con Alpine aquí: las condiciones de `x-show` se escriben en línea
     * —`['EQUALS','NOT_EQUALS'].includes(condicion.operator)`— y no metidas en
     * un método. Llamando a un método, la propiedad que decide se lee dentro y
     * la expresión no vuelve a evaluarse nunca: el bloque se queda como estaba.
     */
@endphp

<form method="POST" action="{{ route('attributes.structure.rules.store') }}"
    class="overflow-hidden rounded-2xl border border-violet-500/30 bg-slate-900/50">
    @csrf

    <div class="flex flex-wrap items-center gap-3 border-b border-slate-800 bg-violet-500/5 px-4 py-3">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
            <x-omni-icon name="chispa" size="h-4 w-4" />
        </span>

        <div class="min-w-0 flex-1">
            <h2 class="text-[13px] font-black text-white">Escribir una regla</h2>
            <p class="text-[10px] leading-relaxed text-slate-500">
                Mira lo que ya tiene la entidad y decide si otro atributo aparece, desaparece o pasa a
                ser obligatorio.
            </p>
        </div>
    </div>

    <div class="space-y-4 p-4">

        {{-- ============================================= --}}
        {{-- 1 · A QUIÉN LE PASA --}}
        {{-- ============================================= --}}

        <div>
            <div class="mb-2 flex flex-wrap items-center gap-2">
                <span class="flex h-5 w-5 items-center justify-center rounded-md bg-violet-500/20 font-mono text-[10px] font-black text-violet-300">1</span>
                <span class="text-[11px] font-black text-white">¿A qué atributo le pasa algo?</span>

                <label class="relative ml-auto min-w-[140px]">
                    <span class="sr-only">Buscar atributo</span>
                    <input type="search" x-model="buscarObjetivo" placeholder="Buscar…"
                        class="w-full rounded-xl border-slate-800 bg-slate-950 py-1.5 text-[11px] text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                </label>
            </div>

            <input type="hidden" name="target_attribute_id" :value="objetivo" required>

            <div class="grid max-h-64 grid-cols-2 gap-1.5 overflow-y-auto rounded-xl border border-slate-800 bg-slate-950 p-2 sm:grid-cols-3 lg:grid-cols-4">

                <template x-for="attr in atributos" :key="'obj' + attr.id">
                    <button type="button"
                        x-show="! buscarObjetivo || attr.name.toLowerCase().includes(buscarObjetivo.toLowerCase())"
                        @click="objetivo = String(attr.id)"
                        :class="String(objetivo) === String(attr.id)
                            ? 'border-violet-500 bg-violet-500/10'
                            : 'border-slate-800 bg-slate-900/60 hover:border-slate-600'"
                        class="flex items-center gap-2 rounded-xl border p-1.5 text-left transition">

                        <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                            <template x-if="attr.image">
                                <img :src="attr.image" alt="" loading="lazy" class="h-full w-full object-cover">
                            </template>
                            <template x-if="! attr.image">
                                <span class="flex h-full w-full items-center justify-center text-[11px]"
                                    :style="`color: ${attr.color}`" x-text="attr.icon"></span>
                            </template>
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[11px] font-black text-white" x-text="attr.name"></span>
                            <span class="block truncate text-[9px] text-slate-600">
                                <span x-text="attr.type_label"></span>
                                ·
                                <span x-text="attr.uses === 0 ? 'sin entidades' : attr.uses + (attr.uses === 1 ? ' entidad' : ' entidades')"
                                    :class="attr.uses === 0 ? 'text-amber-400' : ''"></span>
                            </span>
                        </span>

                        <span x-show="String(objetivo) === String(attr.id)" class="shrink-0 text-violet-400">✓</span>
                    </button>
                </template>
            </div>

            {{-- Un atributo al que nadie tiene puesto: la regla se guarda y no hace nada --}}
            <p x-show="objetivo && atributo(objetivo)?.uses === 0" x-cloak
                class="mt-1.5 rounded-xl border border-amber-500/25 bg-amber-500/5 px-3 py-1.5 text-[10px] leading-relaxed text-amber-200/80">
                Ninguna entidad tiene <strong class="text-amber-200" x-text="atributo(objetivo)?.name"></strong>
                asignado todavía, así que esta regla se guardará correctamente pero no cambiará nada hasta
                que alguna lo use.
            </p>
        </div>


        {{-- ============================================= --}}
        {{-- 2 · QUÉ LE PASA --}}
        {{-- ============================================= --}}

        <div>
            <div class="mb-2 flex items-center gap-2">
                <span class="flex h-5 w-5 items-center justify-center rounded-md bg-violet-500/20 font-mono text-[10px] font-black text-violet-300">2</span>
                <span class="text-[11px] font-black text-white">¿Qué le pasa cuando se cumple?</span>
            </div>

            <input type="hidden" name="action" :value="accion">

            <div class="grid gap-2 sm:grid-cols-3">

                {{-- Mostrar --}}
                <button type="button" @click="accion = 'SHOW'"
                    :class="accion === 'SHOW' ? 'border-emerald-500 bg-emerald-500/10' : 'border-slate-800 bg-slate-950 hover:border-slate-600'"
                    class="rounded-2xl border p-3 text-left transition">

                    <svg viewBox="0 0 120 44" class="h-auto w-full text-emerald-400" fill="none"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                        <rect x="4" y="8" width="44" height="28" rx="4" stroke-dasharray="4 4" opacity=".3" />
                        <path d="M56 22h14M70 22l-5-4M70 22l-5 4" opacity=".8" />
                        <rect x="76" y="8" width="40" height="28" rx="4" />
                        <circle cx="88" cy="18" r="4" opacity=".7" />
                        <path d="M96 18h14M82 28h28" opacity=".45" />
                    </svg>

                    <p class="mt-1.5 text-[12px] font-black text-emerald-300">Mostrar</p>
                    <p class="text-[10px] leading-relaxed text-slate-500">
                        No estaba y aparece. Sirve para los atributos que solo tienen sentido en un caso.
                    </p>
                </button>


                {{-- Ocultar --}}
                <button type="button" @click="accion = 'HIDE'"
                    :class="accion === 'HIDE' ? 'border-rose-500 bg-rose-500/10' : 'border-slate-800 bg-slate-950 hover:border-slate-600'"
                    class="rounded-2xl border p-3 text-left transition">

                    <svg viewBox="0 0 120 44" class="h-auto w-full text-rose-400" fill="none"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                        <rect x="4" y="8" width="44" height="28" rx="4" />
                        <circle cx="16" cy="18" r="4" opacity=".7" />
                        <path d="M24 18h18M10 28h32" opacity=".45" />
                        <path d="M56 22h14M70 22l-5-4M70 22l-5 4" opacity=".8" />
                        <rect x="76" y="8" width="40" height="28" rx="4" stroke-dasharray="4 4" opacity=".3" />
                        <path d="M86 16l20 12M106 16l-20 12" opacity=".55" />
                    </svg>

                    <p class="mt-1.5 text-[12px] font-black text-rose-300">Ocultar</p>
                    <p class="text-[10px] leading-relaxed text-slate-500">
                        Estaba y desaparece. El valor que ya tuviera no se borra, solo deja de pedirse.
                    </p>
                </button>


                {{-- Exigir --}}
                <button type="button" @click="accion = 'REQUIRE'"
                    :class="accion === 'REQUIRE' ? 'border-amber-500 bg-amber-500/10' : 'border-slate-800 bg-slate-950 hover:border-slate-600'"
                    class="rounded-2xl border p-3 text-left transition">

                    <svg viewBox="0 0 120 44" class="h-auto w-full text-amber-400" fill="none"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                        <rect x="4" y="8" width="44" height="28" rx="4" opacity=".55" />
                        <path d="M12 18h28M12 28h18" opacity=".4" />
                        <path d="M56 22h14M70 22l-5-4M70 22l-5 4" opacity=".8" />
                        <rect x="76" y="8" width="40" height="28" rx="4" />
                        <path d="M84 18h20M84 28h14" opacity=".5" />
                        <circle cx="110" cy="12" r="5" />
                        <path d="M110 9.5v3M110 15h.01" />
                    </svg>

                    <p class="mt-1.5 text-[12px] font-black text-amber-300">Exigir</p>
                    <p class="text-[10px] leading-relaxed text-slate-500">
                        Sigue estando, pero ya no se puede guardar la entidad sin rellenarlo.
                    </p>
                </button>
            </div>
        </div>


        {{-- ============================================= --}}
        {{-- 3 · CUÁNDO --}}
        {{-- ============================================= --}}

        <div>
            <div class="mb-2 flex flex-wrap items-center gap-2">
                <span class="flex h-5 w-5 items-center justify-center rounded-md bg-violet-500/20 font-mono text-[10px] font-black text-violet-300">3</span>
                <span class="text-[11px] font-black text-white">¿Cuándo?</span>

                <span class="text-[10px] text-slate-600">
                    Lo que tiene que mirar en la entidad para decidir.
                </span>

                <button type="button" @click="anadirCondicion()"
                    class="ml-auto rounded-lg bg-violet-500/15 px-2.5 py-1.5 text-[10px] font-black text-violet-300 transition hover:bg-violet-500 hover:text-white">
                    + Otra condición
                </button>
            </div>

            {{-- Si hay más de una: ¿todas o basta con una? --}}
            <div x-show="condiciones.length > 1" x-cloak class="mb-2 grid gap-2 sm:grid-cols-2">

                <input type="hidden" name="match_mode" :value="modo">

                <button type="button" @click="modo = 'ALL'"
                    :class="modo === 'ALL' ? 'border-cyan-500 bg-cyan-500/10' : 'border-slate-800 bg-slate-950 hover:border-slate-600'"
                    class="flex items-center gap-2.5 rounded-xl border p-2.5 text-left transition">

                    <svg viewBox="0 0 44 30" class="h-8 w-11 shrink-0 text-cyan-400" fill="none"
                        stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <circle cx="15" cy="15" r="10" opacity=".5" />
                        <circle cx="29" cy="15" r="10" opacity=".5" />
                        <path d="M22 6.5a10 10 0 000 17 10 10 0 000-17z" fill="currentColor" opacity=".35" stroke="none" />
                    </svg>

                    <span class="min-w-0">
                        <span class="block text-[11px] font-black text-white">Se cumplen todas</span>
                        <span class="block text-[9px] leading-3 text-slate-500">
                            Más estricta: falla una y la regla no se aplica.
                        </span>
                    </span>
                </button>

                <button type="button" @click="modo = 'ANY'"
                    :class="modo === 'ANY' ? 'border-amber-500 bg-amber-500/10' : 'border-slate-800 bg-slate-950 hover:border-slate-600'"
                    class="flex items-center gap-2.5 rounded-xl border p-2.5 text-left transition">

                    <svg viewBox="0 0 44 30" class="h-8 w-11 shrink-0 text-amber-400" fill="none"
                        stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <circle cx="15" cy="15" r="10" fill="currentColor" fill-opacity=".22" />
                        <circle cx="29" cy="15" r="10" fill="currentColor" fill-opacity=".22" />
                    </svg>

                    <span class="min-w-0">
                        <span class="block text-[11px] font-black text-white">Basta con una</span>
                        <span class="block text-[9px] leading-3 text-slate-500">
                            Más generosa: con que se cumpla cualquiera, se aplica.
                        </span>
                    </span>
                </button>
            </div>

            <div class="space-y-2">
                <template x-for="(condicion, index) in condiciones" :key="condicion.key">
                    <article class="rounded-2xl border border-slate-800 bg-slate-950 p-3">

                        <input type="hidden" :name="`conditions[${index}][source_attribute_id]`"
                            :value="condicion.source_attribute_id">
                        <input type="hidden" :name="`conditions[${index}][operator]`"
                            :value="condicion.operator">
                        <input type="hidden" :name="`conditions[${index}][source_option_id]`"
                            :value="condicion.source_option_id">

                        <div class="mb-1.5 flex items-center gap-2">
                            <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                <span x-show="index === 0">Cuando el atributo…</span>
                                <span x-show="index > 0"
                                    x-text="modo === 'ALL' ? 'Y además el atributo…' : 'O bien el atributo…'"></span>
                            </span>

                            <button type="button" x-show="condiciones.length > 1"
                                @click="quitarCondicion(index)"
                                class="ml-auto rounded-lg px-1.5 py-0.5 text-[10px] font-black text-slate-600 transition hover:text-rose-300">
                                × Quitar
                            </button>
                        </div>

                        {{-- Qué atributo se mira --}}
                        <div class="flex gap-1.5 overflow-x-auto pb-1">
                            <template x-for="attr in atributos" :key="condicion.key + '-src' + attr.id">
                                <button type="button" @click="elegirFuente(condicion, attr.id)"
                                    :title="attr.name"
                                    :class="String(condicion.source_attribute_id) === String(attr.id)
                                        ? 'border-cyan-500 bg-cyan-500/10'
                                        : 'border-slate-800 bg-slate-900/60 hover:border-slate-600'"
                                    class="flex shrink-0 items-center gap-1.5 rounded-xl border py-1 pl-1 pr-2 transition">

                                    <span class="h-7 w-7 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                                        <template x-if="attr.image">
                                            <img :src="attr.image" alt="" loading="lazy" class="h-full w-full object-cover">
                                        </template>
                                        <template x-if="! attr.image">
                                            <span class="flex h-full w-full items-center justify-center text-[10px]"
                                                :style="`color: ${attr.color}`" x-text="attr.icon"></span>
                                        </template>
                                    </span>

                                    <span class="text-[10px] font-black text-white" x-text="attr.name"></span>
                                </button>
                            </template>
                        </div>

                        {{-- Qué se comprueba --}}
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <template x-for="op in [
                                    { valor: 'EQUALS', texto: 'es' },
                                    { valor: 'NOT_EQUALS', texto: 'no es' },
                                    { valor: 'EXISTS', texto: 'tiene algún valor' },
                                    { valor: 'NOT_EXISTS', texto: 'está vacío' },
                                ]" :key="condicion.key + op.valor">
                                <button type="button" @click="cambiarOperador(condicion, op.valor)"
                                    :class="condicion.operator === op.valor
                                        ? 'border-violet-500 bg-violet-500/15 text-violet-200'
                                        : 'border-slate-800 text-slate-500 hover:border-slate-600 hover:text-slate-300'"
                                    class="rounded-lg border px-2.5 py-1 text-[10px] font-black transition"
                                    x-text="op.texto"></button>
                            </template>
                        </div>

                        {{--
                            El valor solo hace falta para «es» y «no es». La condición
                            se escribe aquí en línea a propósito: metida en un método
                            no se volvería a evaluar al cambiar el operador.
                        --}}
                        <div x-show="['EQUALS', 'NOT_EQUALS'].includes(condicion.operator)" x-cloak class="mt-2">

                            <p class="mb-1 text-[9px] font-black uppercase tracking-wider text-slate-600">
                                ¿Qué valor?
                            </p>

                            <div x-show="! condicion.source_attribute_id"
                                class="rounded-xl border border-dashed border-slate-800 py-3 text-center text-[10px] text-slate-600">
                                Elige primero un atributo aquí arriba.
                            </div>

                            <div x-show="condicion.source_attribute_id && opcionesDe(condicion.source_attribute_id).length === 0"
                                class="rounded-xl border border-dashed border-amber-500/30 bg-amber-500/5 py-3 text-center text-[10px] leading-relaxed text-amber-200/70">
                                Este atributo no es un catálogo, así que no tiene valores concretos que
                                comparar. Usa «tiene algún valor» o «está vacío».
                            </div>

                            <div x-show="condicion.source_attribute_id && opcionesDe(condicion.source_attribute_id).length > 0"
                                class="grid max-h-44 grid-cols-3 gap-1.5 overflow-y-auto sm:grid-cols-5 lg:grid-cols-7">

                                <template x-for="opcion in opcionesDe(condicion.source_attribute_id)"
                                    :key="condicion.key + '-val' + opcion.id">
                                    <button type="button" @click="condicion.source_option_id = String(opcion.id)"
                                        :title="opcion.name"
                                        :class="String(condicion.source_option_id) === String(opcion.id)
                                            ? 'border-violet-500'
                                            : 'border-slate-800 hover:border-slate-600'"
                                        class="overflow-hidden rounded-xl border bg-slate-900/60 transition">

                                        <span class="relative block aspect-square overflow-hidden bg-slate-950">
                                            <template x-if="opcion.image">
                                                <img :src="opcion.image" alt="" loading="lazy" class="h-full w-full object-cover">
                                            </template>
                                            <template x-if="! opcion.image">
                                                <span class="flex h-full w-full items-center justify-center text-sm"
                                                    :style="`color: ${opcion.color}`"
                                                    x-text="opcion.icon || '◇'"></span>
                                            </template>

                                            <span x-show="String(condicion.source_option_id) === String(opcion.id)"
                                                class="absolute inset-0 flex items-center justify-center bg-violet-500/40 text-sm font-black text-white">✓</span>
                                        </span>

                                        <span class="block truncate px-1 py-0.5 text-center text-[9px] font-black text-slate-300"
                                            x-text="opcion.name"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </article>
                </template>
            </div>
        </div>


        {{-- ============================================= --}}
        {{-- CÓMO QUEDARÍA --}}
        {{-- ============================================= --}}

        <div class="rounded-2xl border border-slate-800 bg-slate-950 p-3">

            <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">Cómo quedaría</p>

            <p class="mt-1.5 flex flex-wrap items-center gap-1.5 text-[12px] leading-relaxed text-slate-300">

                <span class="text-slate-500">Si</span>

                <template x-for="(condicion, index) in condiciones" :key="'vp' + condicion.key">
                    <span class="flex flex-wrap items-center gap-1.5">

                        <span x-show="index > 0" class="text-[9px] font-black uppercase tracking-wider"
                            :class="modo === 'ALL' ? 'text-cyan-400' : 'text-amber-400'"
                            x-text="modo === 'ALL' ? 'y' : 'o'"></span>

                        <span class="inline-flex items-center gap-1 rounded-lg border border-slate-800 bg-slate-900 px-1.5 py-0.5">

                            {{-- La cara solo si la hay: un recuadro vacío se lee como imagen rota --}}
                            <template x-if="atributo(condicion.source_attribute_id)?.image">
                                <span class="-ml-1 h-5 w-5 shrink-0 overflow-hidden rounded border border-slate-800 bg-slate-950">
                                    <img :src="atributo(condicion.source_attribute_id).image" alt="" class="h-full w-full object-cover">
                                </span>
                            </template>

                            <span class="text-[10px] font-black text-white"
                                x-text="atributo(condicion.source_attribute_id)?.name || '…'"></span>

                            <span class="text-[10px] text-slate-500"
                                x-text="({ EQUALS: 'es', NOT_EQUALS: 'no es', EXISTS: 'tiene algún valor', NOT_EXISTS: 'está vacío' })[condicion.operator]"></span>

                            <template x-if="condicion.source_option_id">
                                <span class="inline-flex items-center gap-1">
                                    <template x-if="opcion(condicion.source_attribute_id, condicion.source_option_id)?.image">
                                        <span class="h-5 w-5 shrink-0 overflow-hidden rounded border border-slate-800 bg-slate-950">
                                            <img :src="opcion(condicion.source_attribute_id, condicion.source_option_id).image"
                                                alt="" class="h-full w-full object-cover">
                                        </span>
                                    </template>
                                    <span class="text-[10px] font-black text-violet-300"
                                        x-text="opcion(condicion.source_attribute_id, condicion.source_option_id)?.name"></span>
                                </span>
                            </template>
                        </span>
                    </span>
                </template>

                <span class="text-slate-500">entonces</span>

                <span class="rounded-lg px-1.5 py-0.5 text-[10px] font-black uppercase tracking-wider"
                    :class="{
                        'SHOW': 'bg-emerald-500/20 text-emerald-300',
                        'HIDE': 'bg-rose-500/20 text-rose-300',
                        'REQUIRE': 'bg-amber-500/20 text-amber-300',
                    }[accion]"
                    x-text="({ SHOW: 'mostrar', HIDE: 'ocultar', REQUIRE: 'exigir' })[accion]"></span>

                <span class="inline-flex items-center gap-1 rounded-lg border border-slate-800 bg-slate-900 px-1.5 py-0.5">
                    <template x-if="atributo(objetivo)?.image">
                        <span class="-ml-1 h-5 w-5 shrink-0 overflow-hidden rounded border border-slate-800 bg-slate-950">
                            <img :src="atributo(objetivo).image" alt="" class="h-full w-full object-cover">
                        </span>
                    </template>
                    <span class="text-[10px] font-black text-white" x-text="atributo(objetivo)?.name || '…'"></span>
                </span>
            </p>
        </div>


        {{-- ============================================= --}}
        {{-- LO OPCIONAL Y GUARDAR --}}
        {{-- ============================================= --}}

        <div class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_auto_auto]">

            <label class="block">
                <span class="mb-1 block text-[9px] font-black uppercase tracking-wider text-slate-600">
                    Ponle nombre (opcional)
                </span>
                <input type="text" name="name" x-model="nombreRegla" maxlength="150"
                    placeholder="Ej. «Solo los personajes de Naruto tienen clan»"
                    class="w-full rounded-xl border-slate-800 bg-slate-950 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
            </label>

            <label class="block">
                <span class="mb-1 block text-[9px] font-black uppercase tracking-wider text-slate-600"
                    title="Cuando dos reglas se contradicen sobre el mismo atributo, gana la de número más alto">
                    Prioridad
                </span>
                <input type="number" name="priority" x-model.number="prioridad" step="1"
                    class="w-24 rounded-xl border-slate-800 bg-slate-950 text-center font-mono text-xs text-slate-200 focus:border-violet-500 focus:ring-violet-500">
            </label>

            <div class="flex items-end">
                <button type="submit" :disabled="! reglaCompleta"
                    class="w-full rounded-xl bg-violet-500 px-5 py-2.5 text-[12px] font-black text-white transition hover:bg-violet-400 disabled:cursor-not-allowed disabled:bg-slate-800 disabled:text-slate-600">
                    Guardar la regla
                </button>
            </div>
        </div>

        <p x-show="! reglaCompleta" x-cloak class="text-[10px] text-slate-600">
            Falta elegir el atributo al que le pasa algo, y en cada condición el atributo que se mira
            —y su valor, si la comparación es «es» o «no es»—.
        </p>

    </div>
</form>
