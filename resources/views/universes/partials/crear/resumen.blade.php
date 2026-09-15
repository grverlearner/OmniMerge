@php
    /*
     * Lo que se va a crear, mientras se decide.
     *
     * Arriba, el mundo tal y como se vera en la estanteria -misma portada,
     * mismo estado, mismas caras- porque es la forma mas honesta de enseñar el
     * resultado: no una descripcion de lo que pasara, sino la cosa.
     *
     * Debajo, la lista de lo que quedara montado, y lo que NO: un universo sin
     * temporadas o sin gente se puede crear igual, pero conviene saberlo antes
     * y no descubrirlo en el Resumen.
     */
@endphp

<aside class="sticky top-2 space-y-2">

    {{-- ---------- EL MUNDO, TAL Y COMO SE VERÁ ---------- --}}

    <section class="overflow-hidden rounded-2xl border bg-slate-900/50 transition"
        :style="`border-color: ${tonoEstado}44`">

        <div class="relative h-24 overflow-hidden bg-slate-950">

            {{-- Las caras elegidas, de fondo --}}
            <template x-if="carasElegidas.length > 0">
                <span class="absolute inset-0 grid grid-cols-6 opacity-30">
                    <template x-for="c in carasElegidas" :key="'p' + c.id">
                        <span class="block aspect-square overflow-hidden">
                            <img :src="c.img" alt="" class="h-full w-full object-cover">
                        </span>
                    </template>
                </span>
            </template>

            <span class="absolute inset-0"
                :style="`background: linear-gradient(120deg, #020617 18%, ${tonoEstado}22 65%, #02061788 100%)`"></span>

            <span class="absolute left-3 top-3 h-16 w-16 overflow-hidden rounded-xl border-2 bg-slate-950"
                :style="`border-color: ${tonoEstado}88`">

                <template x-if="portada">
                    <img :src="portada" alt="" class="h-full w-full object-cover">
                </template>

                <template x-if="! portada">
                    <span class="flex h-full w-full items-center justify-center text-slate-700">
                        <x-omni-icon name="globo" size="h-6 w-6" />
                    </span>
                </template>
            </span>

            <span class="absolute right-2 top-2 rounded-lg px-2 py-0.5 text-[9px] font-black uppercase tracking-wider backdrop-blur"
                :style="`color: ${tonoEstado}; background-color: ${tonoEstado}26`"
                x-text="textoEstado"></span>
        </div>

        <div class="p-3">
            <p class="truncate text-[14px] font-black leading-tight text-white"
                x-text="nombre.trim() || 'Un mundo sin nombre'"></p>

            <p class="font-mono text-[9px] text-slate-600">{{ $previewCode }}</p>

            <p x-show="descripcion.trim() !== ''" x-cloak
                class="mt-1 line-clamp-2 text-[10px] leading-relaxed text-slate-500"
                x-text="descripcion"></p>

            <div class="mt-2 grid grid-cols-4 gap-1">
                <template x-for="c in cifrasPrevias" :key="c.etiqueta">
                    <span class="rounded-lg border border-slate-800 bg-slate-950 py-1 text-center">
                        <span class="block font-mono text-[13px] font-black"
                            :style="`color: ${c.valor > 0 ? c.tono : '#475569'}`" x-text="c.valor"></span>
                        <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600"
                            x-text="c.etiqueta"></span>
                    </span>
                </template>
            </div>
        </div>
    </section>


    {{-- ---------- LO QUE QUEDARÁ MONTADO ---------- --}}

    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

        <header class="border-b border-slate-800 px-3 py-2">
            <h2 class="text-[12px] font-black text-white">Al crearlo, se hará esto</h2>
        </header>

        <div class="divide-y divide-slate-800/70">
            <template x-for="paso in loQueSeHara" :key="paso.texto">
                <div class="flex items-start gap-2 px-3 py-1.5">

                    <span class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full text-[10px] font-black"
                        :style="paso.hecho
                            ? `background-color: ${paso.tono}; color: #020617`
                            : 'background-color: #1e293b; color: #475569'"
                        x-text="paso.hecho ? '✓' : '·'"></span>

                    <span class="min-w-0 flex-1">
                        <span class="block text-[11px] font-bold leading-tight"
                            :class="paso.hecho ? 'text-slate-200' : 'text-slate-500'"
                            x-text="paso.texto"></span>

                        <span x-show="paso.nota" class="block text-[9px] leading-3 text-slate-600"
                            x-text="paso.nota"></span>
                    </span>
                </div>
            </template>
        </div>
    </section>


    {{-- ---------- CREAR ---------- --}}

    <div class="space-y-1.5">
        <button type="submit" :disabled="nombre.trim() === ''"
            class="w-full rounded-xl bg-violet-500 px-4 py-3 text-[13px] font-black text-white transition hover:bg-violet-400 disabled:cursor-not-allowed disabled:bg-slate-800 disabled:text-slate-600">
            <span x-text="nombre.trim() === '' ? 'Ponle un nombre primero' : 'Crear este mundo'"></span>
        </button>

        <a href="{{ route('universes.index') }}"
            class="block w-full rounded-xl border border-slate-800 px-4 py-2 text-center text-[11px] font-black text-slate-500 transition hover:text-slate-200">
            Cancelar
        </a>
    </div>
</aside>
