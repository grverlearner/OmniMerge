@php
    /*
     * Crear varias temporadas de golpe.
     *
     * Un mundo con historia necesita diez temporadas, no una, y crearlas de una
     * en una es diez veces el mismo formulario.
     *
     * La estructura es deliberadamente pequeña —cuatro decisiones— porque cada
     * opción de más es una pregunta que hay que contestar diez veces mentalmente
     * antes de pulsar:
     *
     *   cuántas   de 1 a 50
     *   nombre    un patrón con {n}, que se sustituye por el número de cada una
     *   fechas    opcional: cuándo empieza la primera y cuánto dura cada una;
     *             la siguiente arranca donde terminó la anterior
     *   estado    si la primera de la tanda queda en curso
     *
     * El número no se pregunta: sigue siendo correlativo al universo. Es lo
     * único que garantiza que la recurrencia de los torneos cuadre.
     *
     * La vista previa de abajo se calcula en el navegador con las mismas reglas
     * que aplica el servidor, para que lo que se ve sea lo que se va a crear.
     */
@endphp

<section x-show="abrirLote" x-cloak x-collapse
    class="overflow-hidden rounded-2xl border border-cyan-500/30 bg-cyan-500/5">

    <form method="POST" action="{{ route('universes.seasons.bulk', $universe) }}"
        x-data="loteDeTemporadas({ siguiente: {{ ($seasons->max('number') ?? 0) + 1 }} })">
        @csrf

        <div class="flex flex-wrap items-center gap-3 border-b border-cyan-500/20 px-4 py-2.5">

            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-cyan-500/15 text-cyan-300">
                <x-omni-icon name="capas" size="h-4 w-4" />
            </span>

            <div class="min-w-0 flex-1">
                <h2 class="text-[13px] font-black text-white">Crear varias temporadas de golpe</h2>
                <p class="text-[10px] leading-relaxed text-cyan-200/60">
                    Se numeran solas, seguidas a las que ya hay. La siguiente sería la
                    <strong class="text-cyan-200" x-text="siguiente"></strong>.
                </p>
            </div>

            <button type="button" @click="abrirLote = false"
                class="shrink-0 rounded-lg px-2 py-1 text-[11px] font-black text-slate-500 transition hover:text-white">
                Cerrar
            </button>
        </div>


        <div class="grid gap-4 p-4 lg:grid-cols-[minmax(0,1fr)_320px]">

            <div class="space-y-3">

                {{-- 1 · CUÁNTAS --}}
                <div class="rounded-xl border border-slate-800 bg-slate-950 p-3">
                    <div class="flex items-center gap-2">
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded bg-cyan-500/20 font-mono text-[10px] font-black text-cyan-300">1</span>
                        <span class="text-[11px] font-black text-slate-200">¿Cuántas?</span>
                    </div>

                    <div class="mt-2 flex flex-wrap items-center gap-1.5">
                        <template x-for="atajo in [3, 5, 10, 20]" :key="atajo">
                            <button type="button" @click="cuantas = atajo"
                                :class="cuantas === atajo ? 'border-cyan-500 bg-cyan-500/15 text-cyan-200' : 'border-slate-800 text-slate-400'"
                                class="rounded-lg border px-3 py-1.5 font-mono text-[12px] font-black transition"
                                x-text="atajo"></button>
                        </template>

                        <input type="number" name="count" x-model.number="cuantas" min="1" max="50"
                            class="w-20 rounded-lg border-slate-800 bg-slate-900 px-2 py-1.5 text-center font-mono text-[12px] font-black text-white focus:border-cyan-500 focus:ring-cyan-500">

                        <span class="text-[10px] text-slate-600">de 1 a 50</span>
                    </div>
                </div>


                {{-- 2 · CÓMO SE LLAMAN --}}
                <div class="rounded-xl border border-slate-800 bg-slate-950 p-3">
                    <div class="flex items-center gap-2">
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded bg-cyan-500/20 font-mono text-[10px] font-black text-cyan-300">2</span>
                        <span class="text-[11px] font-black text-slate-200">¿Cómo se llaman?</span>
                    </div>

                    <p class="mt-1 text-[10px] leading-4 text-slate-600">
                        <code class="rounded bg-slate-900 px-1 text-cyan-300">{n}</code>
                        se sustituye por el número de cada una.
                    </p>

                    <div class="mt-2 flex flex-wrap items-center gap-1.5">
                        <template x-for="modelo in ['Temporada {n}', 'Año {n}', 'Era {n}', 'Ciclo {n}']" :key="modelo">
                            <button type="button" @click="patron = modelo"
                                :class="patron === modelo ? 'border-cyan-500 bg-cyan-500/15 text-cyan-200' : 'border-slate-800 text-slate-400'"
                                class="rounded-lg border px-2 py-1 text-[10px] font-black transition"
                                x-text="modelo"></button>
                        </template>
                    </div>

                    <input type="text" name="name_pattern" x-model="patron" maxlength="150" required
                        class="mt-2 w-full rounded-lg border-slate-800 bg-slate-900 text-xs font-bold text-white focus:border-cyan-500 focus:ring-cyan-500">
                </div>


                {{-- 3 · CUÁNDO --}}
                <div class="rounded-xl border border-slate-800 bg-slate-950 p-3">
                    <div class="flex items-center gap-2">
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded bg-cyan-500/20 font-mono text-[10px] font-black text-cyan-300">3</span>
                        <span class="min-w-0 flex-1 text-[11px] font-black text-slate-200">¿Cuándo empiezan?</span>
                        <span class="shrink-0 text-[10px] font-bold text-slate-600">Opcional</span>
                    </div>

                    <p class="mt-1 text-[10px] leading-4 text-slate-600">
                        Si pones fecha y duración, cada temporada arranca donde terminó la anterior. Si lo
                        dejas vacío, se crean sin fechas.
                    </p>

                    <div class="mt-2 grid gap-2 sm:grid-cols-3">
                        <label class="block">
                            <span class="mb-0.5 block text-[9px] font-black uppercase tracking-wider text-slate-600">
                                La primera empieza
                            </span>
                            <input type="date" name="starts_at" x-model="desde"
                                class="w-full rounded-lg border-slate-800 bg-slate-900 px-2 py-1.5 text-[11px] text-slate-200 focus:border-cyan-500 focus:ring-cyan-500">
                        </label>

                        <label class="block">
                            <span class="mb-0.5 block text-[9px] font-black uppercase tracking-wider text-slate-600">
                                Cada una dura
                            </span>
                            <input type="number" name="duration" x-model.number="duracion" min="0" max="120"
                                class="w-full rounded-lg border-slate-800 bg-slate-900 px-2 py-1.5 text-center font-mono text-[11px] text-slate-200 focus:border-cyan-500 focus:ring-cyan-500">
                        </label>

                        <label class="block">
                            <span class="mb-0.5 block text-[9px] font-black uppercase tracking-wider text-slate-600">
                                Unidad
                            </span>
                            <select name="duration_unit" x-model="unidad"
                                class="w-full rounded-lg border-slate-800 bg-slate-900 px-2 py-1.5 text-[11px] text-slate-200 focus:border-cyan-500 focus:ring-cyan-500">
                                <option value="days">días</option>
                                <option value="weeks">semanas</option>
                                <option value="months">meses</option>
                                <option value="years">años</option>
                            </select>
                        </label>
                    </div>
                </div>


                {{-- 4 · ESTADO --}}
                <div class="rounded-xl border border-slate-800 bg-slate-950 p-3">
                    <div class="flex items-center gap-2">
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded bg-cyan-500/20 font-mono text-[10px] font-black text-cyan-300">4</span>
                        <span class="text-[11px] font-black text-slate-200">¿Alguna empieza en curso?</span>
                    </div>

                    <div class="mt-2 grid gap-1.5 sm:grid-cols-2">
                        @foreach ([['PLANNED', 'Todas planeadas', 'Nadie está en curso hasta que lo digas.'], ['ACTIVE', 'La primera, en curso', 'La que estuviera activa deja de estarlo.']] as [$valor, $etiqueta, $ayuda])
                            <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-slate-800 bg-slate-900 px-2.5 py-2 transition has-[:checked]:border-cyan-500 has-[:checked]:bg-cyan-500/10">
                                <input type="radio" name="first_status" value="{{ $valor }}"
                                    x-model="estado"
                                    class="mt-0.5 border-slate-700 bg-slate-950 text-cyan-500 focus:ring-cyan-500">
                                <span class="min-w-0">
                                    <span class="block text-[11px] font-black text-slate-200">{{ $etiqueta }}</span>
                                    <span class="block text-[9px] leading-3 text-slate-600">{{ $ayuda }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>


            {{-- ---------- LO QUE SE VA A CREAR ---------- --}}

            <aside class="rounded-xl border border-cyan-500/30 bg-slate-950 p-3">

                <p class="text-[10px] font-black uppercase tracking-wider text-slate-600">
                    Lo que se va a crear
                </p>

                <p class="mt-0.5 text-[10px] leading-4 text-slate-500">
                    <span class="font-black text-cyan-300" x-text="cuantas"></span>
                    temporadas, de la
                    <span class="font-black text-cyan-300" x-text="siguiente"></span>
                    a la
                    <span class="font-black text-cyan-300" x-text="siguiente + cuantas - 1"></span>.
                </p>

                <div class="mt-2 max-h-64 space-y-1 overflow-y-auto">
                    <template x-for="fila in vistaPrevia" :key="fila.numero">
                        <div class="flex items-center gap-2 rounded-lg border border-slate-800 bg-slate-900 p-1.5">

                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded font-mono text-[10px] font-black"
                                :class="fila.activa ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-800 text-slate-500'"
                                x-text="fila.numero"></span>

                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[11px] font-black text-slate-200" x-text="fila.nombre"></span>
                                <span class="block truncate font-mono text-[9px] text-slate-600" x-text="fila.fechas"></span>
                            </span>

                            <span x-show="fila.activa"
                                class="shrink-0 rounded bg-emerald-500/15 px-1 text-[8px] font-black uppercase text-emerald-300">
                                en curso
                            </span>
                        </div>
                    </template>
                </div>

                <p x-show="cuantas > vistaPrevia.length" x-cloak
                    class="mt-1 text-[9px] text-slate-600">
                    Y <span x-text="cuantas - vistaPrevia.length"></span> más iguales.
                </p>

                <button type="submit" :disabled="! patron.trim() || cuantas < 1"
                    class="mt-3 w-full rounded-xl bg-cyan-500 px-4 py-2.5 text-[12px] font-black text-slate-950 transition hover:bg-cyan-400 disabled:cursor-not-allowed disabled:opacity-40">
                    Crear las <span x-text="cuantas"></span>
                </button>

                <p class="mt-1.5 text-[9px] leading-3 text-slate-600">
                    Se crean todas o ninguna: si algo falla a mitad, no queda un mundo con siete
                    temporadas de diez.
                </p>
            </aside>
        </div>
    </form>


    <script>
        function loteDeTemporadas(config) {

            return {

                siguiente: config.siguiente ?? 1,

                cuantas: 10,
                patron: 'Temporada {n}',
                desde: '',
                duracion: 1,
                unidad: 'months',
                estado: 'PLANNED',

                /*
                 * Las mismas reglas que aplica el servidor, para que lo que se
                 * ve aquí sea lo que se va a crear. Solo se dibujan las ocho
                 * primeras: con veinte filas la vista previa deja de ser una
                 * vista previa y pasa a ser otra lista.
                 */
                get vistaPrevia() {

                    const filas = [];

                    const cuantas = Math.max(1, Math.min(50, this.cuantas || 1));

                    let cursor = this.desde ? new Date(this.desde + 'T00:00:00') : null;

                    for (let i = 0; i < Math.min(cuantas, 8); i++) {

                        const numero = this.siguiente + i;

                        let fechas = 'sin fechas';

                        if (cursor && this.duracion > 0) {

                            const fin = this.sumar(cursor, this.duracion);
                            const anterior = new Date(fin);
                            anterior.setDate(anterior.getDate() - 1);

                            fechas = this.escribir(cursor) + ' → ' + this.escribir(anterior);

                            cursor = fin;
                        } else if (cursor) {
                            fechas = 'desde ' + this.escribir(cursor);
                        }

                        filas.push({
                            numero,
                            nombre: (this.patron || 'Temporada {n}').replace(/\{n\}/gi, numero),
                            fechas,
                            activa: i === 0 && this.estado === 'ACTIVE',
                        });
                    }

                    return filas;
                },

                sumar(fecha, cuanto) {
                    const nueva = new Date(fecha);

                    if (this.unidad === 'days') nueva.setDate(nueva.getDate() + cuanto);
                    if (this.unidad === 'weeks') nueva.setDate(nueva.getDate() + cuanto * 7);
                    if (this.unidad === 'months') nueva.setMonth(nueva.getMonth() + cuanto);
                    if (this.unidad === 'years') nueva.setFullYear(nueva.getFullYear() + cuanto);

                    return nueva;
                },

                escribir(fecha) {
                    return String(fecha.getDate()).padStart(2, '0')
                        + '/' + String(fecha.getMonth() + 1).padStart(2, '0')
                        + '/' + fecha.getFullYear();
                },
            };
        }
    </script>

</section>
