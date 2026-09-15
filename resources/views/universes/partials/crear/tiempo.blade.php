@php
    /*
     * El calendario del mundo, decidido al crearlo.
     *
     * Un universo sin temporadas no sabe cuando toca cada torneo: la
     * recurrencia -«cada dos temporadas», «solo en la primera»- no tiene contra
     * que medirse. El Resumen lo avisa despues con «este mundo no tiene tiempo
     * todavia», y arreglarlo obligaba a ir a otra pantalla.
     *
     * Esto usa el mismo servicio que el panel de Temporadas
     * (UniverseSeasonService::createMany), asi que las reglas son identicas: el
     * patron con {n}, las fechas encadenadas y el estado de la primera.
     */
@endphp

<section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <header class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-2.5">

        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-sky-500/15 text-sky-300">
            <x-omni-icon name="calendario" size="h-4 w-4" />
        </span>

        <div class="min-w-0 flex-1">
            <h2 class="text-[13px] font-black text-white">Cómo se mide el tiempo aquí</h2>
            <p class="text-[10px] text-slate-500">
                Las temporadas son el reloj del mundo. Sin ellas, un torneo que se repite
                «cada dos temporadas» no sabe si le toca.
            </p>
        </div>

        <span class="shrink-0 font-mono text-[11px] font-black"
            :class="temporadas.cuantas > 0 ? 'text-sky-300' : 'text-slate-600'"
            x-text="temporadas.cuantas > 0 ? temporadas.cuantas + (temporadas.cuantas === 1 ? ' temporada' : ' temporadas') : 'ninguna'"></span>
    </header>

    <div class="grid gap-4 p-4 lg:grid-cols-[minmax(0,1fr)_260px]">

        <div class="space-y-3">

            {{-- Cuántas --}}
            <div>
                <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                    Cuántas se crean ya
                </p>

                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                    <template x-for="n in [0, 1, 2, 4, 6, 12]" :key="n">
                        <button type="button" @click="temporadas.cuantas = n"
                            class="rounded-lg border px-2.5 py-1.5 text-[11px] font-black transition"
                            :class="temporadas.cuantas === n
                                ? 'border-sky-500 bg-sky-500/15 text-sky-200'
                                : 'border-slate-800 text-slate-500 hover:border-slate-700'"
                            x-text="n === 0 ? 'Ninguna' : n"></button>
                    </template>

                    <input type="number" min="0" max="24" x-model.number="temporadas.cuantas"
                        class="w-16 rounded-lg border-slate-800 bg-slate-950 px-2 py-1.5 text-center font-mono text-[12px] font-black text-sky-300 focus:border-sky-500 focus:ring-sky-500">
                </div>

                <input type="hidden" name="seasons_count" :value="temporadas.cuantas">
            </div>


            <div x-show="temporadas.cuantas > 0" x-cloak x-collapse>
                <div class="space-y-3">

                    {{-- Cómo se llaman --}}
                    <label class="block">
                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                            Cómo se llaman
                        </span>

                        <input type="text" name="seasons_pattern" x-model="temporadas.patron" maxlength="120"
                            class="mt-1 w-full rounded-xl border-slate-800 bg-slate-950 text-[12px] font-bold text-white focus:border-sky-500 focus:ring-sky-500">

                        <span class="mt-0.5 block text-[9px] leading-3 text-slate-600">
                            <code class="text-sky-400">{n}</code> se cambia por el número de cada una.
                            «Era {n}» dará Era 1, Era 2, Era 3…
                        </span>
                    </label>


                    {{-- Cuándo empieza y cuánto dura --}}
                    <div class="grid gap-2 sm:grid-cols-3">

                        <label class="block">
                            <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                Empieza
                            </span>
                            <input type="date" name="seasons_starts_at" x-model="temporadas.desde"
                                class="mt-1 w-full rounded-xl border-slate-800 bg-slate-950 text-[11px] text-slate-200 focus:border-sky-500 focus:ring-sky-500">
                        </label>

                        <label class="block">
                            <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                Cada una dura
                            </span>
                            <input type="number" name="seasons_duration" min="0" max="120"
                                x-model.number="temporadas.duracion"
                                class="mt-1 w-full rounded-xl border-slate-800 bg-slate-950 text-center font-mono text-[12px] font-black text-white focus:border-sky-500 focus:ring-sky-500">
                        </label>

                        <label class="block">
                            <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                &nbsp;
                            </span>
                            <select name="seasons_duration_unit" x-model="temporadas.unidad"
                                class="mt-1 w-full rounded-xl border-slate-800 bg-slate-950 text-[11px] font-bold text-slate-300 focus:border-sky-500 focus:ring-sky-500">
                                <option value="days">días</option>
                                <option value="weeks">semanas</option>
                                <option value="months">meses</option>
                                <option value="years">años</option>
                            </select>
                        </label>
                    </div>

                    <p class="text-[9px] leading-3 text-slate-600">
                        Sin fecha, las temporadas se crean sin calendario: siguen sirviendo para
                        ordenar, solo que no dicen cuándo.
                    </p>


                    {{-- La primera, ¿arranca? --}}
                    <div>
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                            La primera
                        </p>

                        <div class="mt-1 grid gap-1.5 sm:grid-cols-2">
                            @foreach ([['PLANNED', '#60a5fa', 'Queda planificada', 'El mundo nace con calendario pero sin reloj en marcha.'], ['ACTIVE', '#34d399', 'Arranca ya', 'La temporada 1 queda en curso desde el primer momento.']] as [$valor, $tono, $texto, $ayuda])
                                <label class="block cursor-pointer rounded-xl border p-2 transition"
                                    :style="temporadas.primera === '{{ $valor }}'
                                        ? 'border-color: {{ $tono }}; background-color: {{ $tono }}14'
                                        : 'border-color: #1e293b'">

                                    <input type="radio" name="seasons_first_status" value="{{ $valor }}"
                                        x-model="temporadas.primera" class="sr-only">

                                    <span class="flex items-center gap-1.5">
                                        <span class="h-2 w-2 rounded-full" style="background-color: {{ $tono }}"></span>
                                        <span class="text-[11px] font-black"
                                            :class="temporadas.primera === '{{ $valor }}' ? 'text-white' : 'text-slate-400'">
                                            {{ $texto }}
                                        </span>
                                    </span>

                                    <span class="mt-0.5 block text-[9px] leading-3 text-slate-600">{{ $ayuda }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>


            <p x-show="temporadas.cuantas === 0" x-cloak
                class="rounded-xl border border-dashed border-slate-800 px-3 py-2 text-[10px] leading-4 text-slate-600">
                Sin temporadas el mundo se crea igual, y podrás añadirlas cuando quieras. El
                Resumen te lo recordará mientras no las tenga.
            </p>
        </div>


        {{-- ---------- CÓMO QUEDARÁ ---------- --}}

        <aside x-show="temporadas.cuantas > 0" x-cloak
            class="rounded-xl border border-sky-500/25 bg-slate-950 p-3">

            <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">Quedarán así</p>

            <div class="mt-2 space-y-1">
                <template x-for="t in vistaPreviaTemporadas" :key="t.numero">
                    <div class="flex items-center gap-2 rounded-lg border px-2 py-1.5"
                        :class="t.activa ? 'border-emerald-500/40 bg-emerald-500/10' : 'border-slate-800 bg-slate-900'">

                        <span class="w-6 shrink-0 text-center font-mono text-[11px] font-black"
                            :class="t.activa ? 'text-emerald-300' : 'text-slate-600'"
                            x-text="'T' + t.numero"></span>

                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[11px] font-black text-slate-200" x-text="t.nombre"></span>
                            <span class="block font-mono text-[9px] text-slate-600" x-text="t.fechas"></span>
                        </span>

                        <template x-if="t.activa">
                            <span class="shrink-0 rounded bg-emerald-500/20 px-1 text-[8px] font-black uppercase tracking-wider text-emerald-300">
                                en curso
                            </span>
                        </template>
                    </div>
                </template>

                <p x-show="temporadas.cuantas > 6" class="text-[9px] text-slate-600">
                    …y <span x-text="temporadas.cuantas - 6"></span> más.
                </p>
            </div>
        </aside>
    </div>
</section>
