{{--
    RESUMEN — qué enseña la portada del universo y en qué orden.

    Los bloques de arriba van a lo ancho; los de la columna principal y la
    lateral, debajo. Se ordenan dentro de su zona y cualquiera se puede
    esconder.
--}}

@php
    $zonas = [
        'top' => ['Arriba, a lo ancho', '#34d399'],
        'left' => ['Columna principal', '#38bdf8'],
        'right' => ['Columna lateral', '#fbbf24'],
    ];
@endphp

<section id="resumen" data-seccion class="scroll-mt-24 overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <header class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-3"
        style="background: linear-gradient(120deg, #34d3991a, transparent 60%)">
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-300">
            <x-omni-icon name="cuadricula" size="h-4 w-4" />
        </span>
        <div class="min-w-0 flex-1">
            <h2 class="text-[14px] font-black text-white">Resumen</h2>
            <p class="text-[10px] text-slate-500">Qué bloques salen en la portada del universo y en qué orden.</p>
        </div>
        <a href="{{ route('universes.show', $universe) }}" target="_blank" rel="noopener"
            class="rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-500 transition hover:border-emerald-500 hover:text-emerald-300">Ver el Resumen</a>
        <button type="button" @click="restablecer(['summary_order', 'summary_hidden'])"
            class="flex items-center gap-1 rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-500 transition hover:border-slate-600 hover:text-white">
            <x-omni-icon name="deshacer" size="h-3 w-3" />
            Restablecer
        </button>
    </header>

    <div class="grid gap-4 p-4 lg:grid-cols-[minmax(0,1fr)_220px]">

        <div class="space-y-3">
            @foreach ($zonas as $zona => [$textoZona, $tonoZona])
                <div>
                    <p class="mb-1 flex items-center gap-1.5 text-[10px] font-black uppercase tracking-wider" style="color: {{ $tonoZona }}">
                        <span class="h-2 w-2 rounded-full" style="background-color: {{ $tonoZona }}"></span>
                        {{ $textoZona }}
                    </p>

                    <div class="space-y-1">
                        <template x-for="(bloque, i) in s.summary_order" :key="'blq' + bloque">
                            <div x-show="bloques[bloque][1] === '{{ $zona }}'"
                                class="flex items-center gap-2 rounded-xl border px-2.5 py-2 transition"
                                :class="s.summary_hidden.includes(bloque) ? 'border-slate-800 bg-slate-950/40' : 'bg-slate-950'"
                                :style="s.summary_hidden.includes(bloque) ? '' : 'border-color: {{ $tonoZona }}44'">

                                <span class="flex flex-col">
                                    <button type="button" @click="mover('summary_order', i, -1)" title="Subir" class="text-slate-600 hover:text-white">
                                        <x-omni-icon name="chevron-izquierda" size="h-3 w-3" class="rotate-90" />
                                    </button>
                                    <button type="button" @click="mover('summary_order', i, 1)" title="Bajar" class="text-slate-600 hover:text-white">
                                        <x-omni-icon name="chevron-derecha" size="h-3 w-3" class="rotate-90" />
                                    </button>
                                </span>

                                <span class="min-w-0 flex-1 truncate text-[12px] font-black"
                                    :class="s.summary_hidden.includes(bloque) ? 'text-slate-600 line-through' : 'text-slate-100'"
                                    x-text="bloques[bloque][0]"></span>

                                <button type="button" @click="alternar('summary_hidden', bloque)"
                                    class="flex items-center gap-1 rounded-lg px-2 py-1 text-[10px] font-black transition"
                                    :class="s.summary_hidden.includes(bloque) ? 'text-slate-500 hover:text-white' : 'text-emerald-300 hover:bg-emerald-500/10'">
                                    <template x-if="! s.summary_hidden.includes(bloque)"><x-omni-icon name="ojo" size="h-3.5 w-3.5" /></template>
                                    <template x-if="s.summary_hidden.includes(bloque)"><x-omni-icon name="ojo-tachado" size="h-3.5 w-3.5" /></template>
                                    <span x-text="s.summary_hidden.includes(bloque) ? 'Escondido' : 'Visible'"></span>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Cómo queda la portada --}}
        <div class="rounded-2xl border border-slate-800 bg-slate-950 p-2">
            <p class="mb-1.5 text-[9px] font-black uppercase tracking-wider text-slate-600">Así queda</p>
            <span class="mb-1 block h-6 rounded-md" :style="`background: linear-gradient(120deg, ${s.accent}44, #0f172a)`"></span>

            <template x-for="b in bloquesDe('top')" :key="'mt' + b">
                <span class="mb-1 block truncate rounded-md bg-emerald-500/15 px-1.5 py-1 text-[9px] font-bold text-emerald-200" x-text="bloques[b][0]"></span>
            </template>

            <div class="grid grid-cols-[minmax(0,1fr)_40%] gap-1">
                <div class="space-y-1">
                    <template x-for="b in bloquesDe('left')" :key="'ml' + b">
                        <span class="block truncate rounded-md bg-sky-500/15 px-1.5 py-2 text-[9px] font-bold text-sky-200" x-text="bloques[b][0]"></span>
                    </template>
                </div>
                <div class="space-y-1">
                    <template x-for="b in bloquesDe('right')" :key="'mr' + b">
                        <span class="block truncate rounded-md bg-amber-500/15 px-1.5 py-1.5 text-[9px] font-bold text-amber-200" x-text="bloques[b][0]"></span>
                    </template>
                </div>
            </div>

            <p x-show="! bloquesDe('top').length && ! bloquesDe('left').length && ! bloquesDe('right').length"
                class="py-4 text-center text-[10px] text-rose-300">Todo escondido: el Resumen quedará vacío.</p>
        </div>
    </div>
</section>
