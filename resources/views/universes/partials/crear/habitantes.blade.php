@php
    /*
     * Quienes lo habitan, desde el primer momento.
     *
     * Un universo vacio no puede jugar nada, y traer gente obligaba a crear el
     * mundo, entrar, ir a Entidades y buscarlos alli. Aqui se eligen con la
     * cara delante, que es como se reconoce a un competidor.
     *
     * Importante y se dice: importar es COPIAR. La copia del universo evoluciona
     * por su cuenta y editar el original en la Biblioteca ya no la toca.
     */
@endphp

<section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <header class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-2.5">

        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 text-amber-300">
            <x-omni-icon name="usuario" size="h-4 w-4" />
        </span>

        <div class="min-w-0 flex-1">
            <h2 class="text-[13px] font-black text-white">Quiénes lo habitan</h2>
            <p class="text-[10px] text-slate-500">
                Traer es <strong class="text-slate-400">copiar</strong>: la copia vive en este
                mundo y editar el original en tu Biblioteca ya no la afecta.
            </p>
        </div>

        <span class="shrink-0 font-mono text-[11px] font-black"
            :class="elegidas.length > 0 ? 'text-amber-300' : 'text-slate-600'"
            x-text="elegidas.length > 0 ? elegidas.length + (elegidas.length === 1 ? ' elegido' : ' elegidos') : 'ninguno'"></span>
    </header>


    @if ($biblioteca->isEmpty())

        <div class="px-4 py-8 text-center">
            <span class="inline-flex text-slate-700"><x-omni-icon name="libro" size="h-8 w-8" /></span>
            <p class="mt-2 text-[12px] font-black text-slate-300">Tu Biblioteca está vacía</p>
            <p class="mx-auto mt-1 max-w-sm text-[11px] leading-relaxed text-slate-500">
                Los habitantes de un universo salen de tus entidades. Crea alguna en la
                Biblioteca y podrás traerla aquí.
            </p>
            <a href="{{ route('entities.index') }}"
                class="mt-3 inline-block rounded-xl border border-slate-800 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                Ir a la Biblioteca
            </a>
        </div>

    @else

        {{-- ---------- MANDOS ---------- --}}

        <div class="flex flex-wrap items-center gap-2 border-b border-slate-800/70 px-4 py-2">

            <label class="relative min-w-[160px] flex-1">
                <span class="sr-only">Buscar en la Biblioteca</span>
                <span class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-600">
                    <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                </span>
                <input type="search" x-model="buscarHabitante" placeholder="Buscar por nombre o tipo…"
                    class="w-full rounded-xl border-slate-800 bg-slate-950 py-1.5 pl-8 text-[11px] text-slate-200 placeholder:text-slate-600 focus:border-amber-500 focus:ring-amber-500">
            </label>

            @if ($tipos->isNotEmpty())
                <span class="flex flex-wrap items-center gap-1">
                    <button type="button" @click="tipoHabitante = ''"
                        class="rounded-lg border px-2 py-1 text-[10px] font-black transition"
                        :class="tipoHabitante === '' ? 'border-slate-600 bg-slate-800 text-slate-200' : 'border-slate-800 text-slate-500'">
                        Todos
                    </button>

                    @foreach ($tipos as $tipo)
                        <button type="button" @click="tipoHabitante = (tipoHabitante === @js($tipo) ? '' : @js($tipo))"
                            class="rounded-lg border px-2 py-1 text-[10px] font-black transition"
                            :class="tipoHabitante === @js($tipo) ? 'border-amber-500 bg-amber-500/15 text-amber-200' : 'border-slate-800 text-slate-500 hover:text-slate-300'">
                            {{ $tipo }}
                        </button>
                    @endforeach
                </span>
            @endif

            <span class="flex-1"></span>

            <button type="button" @click="elegirVisibles()"
                class="rounded-lg border border-amber-500/40 bg-amber-500/10 px-2.5 py-1.5 text-[10px] font-black text-amber-300 transition hover:bg-amber-500 hover:text-slate-950">
                Elegir los <span x-text="habitantesVisibles.length"></span> que se ven
            </button>

            <button type="button" @click="elegidas = []" x-show="elegidas.length > 0" x-cloak
                class="rounded-lg px-2 py-1.5 text-[10px] font-black text-slate-500 underline transition hover:text-slate-200">
                Quitar todos
            </button>
        </div>


        {{-- ---------- LAS CARAS ---------- --}}

        <div class="max-h-[340px] overflow-y-auto p-3">

            <div class="grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(88px, 1fr))">

                <template x-for="e in habitantesVisibles" :key="e.id">

                    <button type="button" @click="alternarHabitante(e.id)"
                        class="group relative block overflow-hidden rounded-xl border bg-slate-950 transition"
                        :class="elegidas.includes(e.id)
                            ? 'border-amber-500 -translate-y-0.5'
                            : 'border-slate-800 hover:border-slate-700'"
                        :title="e.nombre + (e.tipo ? ' · ' + e.tipo : '')">

                        <span class="relative block h-20 overflow-hidden bg-slate-900">
                            <template x-if="e.img">
                                <img :src="e.img" alt="" loading="lazy"
                                    class="h-full w-full object-cover transition duration-300"
                                    :class="elegidas.includes(e.id) ? '' : 'opacity-70 group-hover:opacity-100'">
                            </template>

                            <template x-if="! e.img">
                                <span class="flex h-full w-full items-center justify-center text-2xl text-slate-800">◍</span>
                            </template>

                            <template x-if="elegidas.includes(e.id)">
                                <span class="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded-full bg-amber-400 text-[11px] font-black text-slate-950">
                                    ✓
                                </span>
                            </template>
                        </span>

                        <span class="block px-1.5 py-1">
                            <span class="block truncate text-center text-[10px] font-black"
                                :class="elegidas.includes(e.id) ? 'text-amber-200' : 'text-slate-400'"
                                x-text="e.nombre"></span>

                            <template x-if="e.tipo">
                                <span class="block truncate text-center text-[8px] text-slate-600" x-text="e.tipo"></span>
                            </template>
                        </span>
                    </button>
                </template>
            </div>

            <p x-show="habitantesVisibles.length === 0" x-cloak
                class="py-6 text-center text-[11px] text-slate-600">
                Nadie de tu Biblioteca encaja con eso.
            </p>
        </div>


        {{-- Los elegidos viajan en el envío --}}
        <template x-for="id in elegidas" :key="'e' + id">
            <input type="hidden" name="entity_ids[]" :value="id">
        </template>

        <p class="border-t border-slate-800 px-4 py-2 text-[9px] leading-3 text-slate-600">
            Tienes {{ $biblioteca->count() }}
            {{ $biblioteca->count() === 1 ? 'entidad' : 'entidades' }} en la Biblioteca.
            Puedes traer más en cualquier momento desde el panel de Entidades del universo.
        </p>
    @endif
</section>
