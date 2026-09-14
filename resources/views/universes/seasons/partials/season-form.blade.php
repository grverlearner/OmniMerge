@php
    /*
     * El formulario de una temporada, compartido entre crear y editar.
     *
     * Son cuatro campos y ninguno es obvio, así que cada uno dice qué cambia de
     * verdad en el mundo:
     *
     *   nombre   lo que se lee en el calendario y en cada competición
     *   fechas   opcionales, y no bloquean nada: son la referencia del mundo,
     *            no un cronómetro
     *   estado   la única decisión con consecuencia inmediata, porque un
     *            universo solo puede tener una temporada en curso
     *
     * El número no se pregunta: es correlativo al universo y es lo que hace que
     * la recurrencia de los torneos cuadre.
     */

    /* Al crear no llega ninguna, y `?->` sobre una variable inexistente no es
       lo mismo que sobre null: hay que declararla. */
    $season = $season ?? null;

    $editando = $season && $season->exists;

    $nombreActual = old('name', $season->name ?? '');
    $descripcionActual = old('description', $season->description ?? '');
    $estadoActual = old('status', $season->status ?? 'PLANNED');

    $desdeActual = old('starts_at', $season?->starts_at?->format('Y-m-d') ?? '');
    $hastaActual = old('ends_at', $season?->ends_at?->format('Y-m-d') ?? '');

    $numero = $editando ? $season->number : $siguienteNumero;

    $estados = [
        ['PLANNED', 'Planeada', 'Existe, pero el mundo todavía no está ahí.', '#94a3b8'],
        ['ACTIVE', 'En curso', 'Es el ahora del mundo. La que estuviera en curso deja de estarlo.', '#34d399'],
        ['COMPLETED', 'Terminada', 'Ya pasó. Sus competiciones quedan como historia.', '#22d3ee'],
        ['ARCHIVED', 'Archivada', 'Fuera de en medio, sin borrar nada.', '#475569'],
    ];
@endphp

<div x-data="editorDeTemporada({
    nombre: @js($nombreActual),
    estado: @js($estadoActual),
    desde: @js($desdeActual),
    hasta: @js($hastaActual),
    numero: {{ $numero }},
    hayOtraActiva: {{ ($otraActiva ?? null) ? 'true' : 'false' }},
})" class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_320px]">

    <div class="space-y-4">

        {{-- ---------- 1 · CÓMO SE LLAMA ---------- --}}

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <div class="flex items-center gap-3 border-b border-slate-800 px-4 py-2.5">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 font-mono text-[11px] font-black text-violet-300">1</span>
                <h3 class="min-w-0 flex-1 text-[12px] font-black text-white">Cómo se llama</h3>
                <span class="shrink-0 rounded-lg border border-slate-800 px-2 py-0.5 font-mono text-[11px] font-black text-slate-400"
                    title="El número es correlativo al universo y no se elige">
                    nº {{ $numero }}
                </span>
            </div>

            <div class="space-y-3 p-4">

                <label class="block">
                    <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                        Nombre
                    </span>
                    <input type="text" name="name" x-model="nombre" required maxlength="150"
                        placeholder="«Temporada {{ $numero }}», «Era de shinobis»…"
                        class="w-full rounded-xl border-slate-800 bg-slate-950 text-sm font-bold text-white placeholder:font-normal placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
                    <span class="mt-1 block text-[10px] leading-4 text-slate-600">
                        Es lo que se lee en el calendario y en cada competición que se juegue dentro.
                    </span>
                    @error('name')
                        <span class="mt-1 block text-[11px] font-bold text-rose-300">{{ $message }}</span>
                    @enderror
                </label>

                <label class="block">
                    <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                        Qué pasa en ella
                    </span>
                    <textarea name="description" rows="3" maxlength="5000"
                        placeholder="Opcional. Lo que define este tramo del mundo."
                        class="w-full rounded-xl border-slate-800 bg-slate-950 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">{{ $descripcionActual }}</textarea>
                    @error('description')
                        <span class="mt-1 block text-[11px] font-bold text-rose-300">{{ $message }}</span>
                    @enderror
                </label>
            </div>
        </section>


        {{-- ---------- 2 · CUÁNDO ---------- --}}

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <div class="flex items-center gap-3 border-b border-slate-800 px-4 py-2.5">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-cyan-500/15 font-mono text-[11px] font-black text-cyan-300">2</span>
                <h3 class="min-w-0 flex-1 text-[12px] font-black text-white">Cuándo transcurre</h3>
                <span class="shrink-0 text-[10px] font-bold text-slate-600">Opcional</span>
            </div>

            <div class="p-4">

                <p class="text-[11px] leading-relaxed text-slate-500">
                    Las fechas son la referencia del mundo, no un cronómetro: nada se activa ni se cierra
                    solo al llegar el día. Sirven para situarla en el calendario y para saber qué venía
                    antes de qué.
                </p>

                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                            Empieza
                        </span>
                        <input type="date" name="starts_at" x-model="desde"
                            class="w-full rounded-xl border-slate-800 bg-slate-950 text-xs text-slate-200 focus:border-violet-500 focus:ring-violet-500">
                        @error('starts_at')
                            <span class="mt-1 block text-[11px] font-bold text-rose-300">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                            Termina
                        </span>
                        <input type="date" name="ends_at" x-model="hasta"
                            class="w-full rounded-xl border-slate-800 bg-slate-950 text-xs text-slate-200 focus:border-violet-500 focus:ring-violet-500">
                        @error('ends_at')
                            <span class="mt-1 block text-[11px] font-bold text-rose-300">{{ $message }}</span>
                        @enderror
                    </label>
                </div>

                {{--
                    El aviso se escribe con las dos fechas leídas en la propia
                    expresión: si dependiera solo del captador, Alpine no
                    registraría de qué depende y no se actualizaría al teclear.
                --}}
                <p x-show="desde && hasta && hasta < desde" x-cloak
                    class="mt-2 rounded-xl border border-amber-500/30 bg-amber-500/10 px-2.5 py-1.5 text-[10px] leading-4 text-amber-200/90">
                    ⚠ La fecha de fin es anterior a la de inicio. Se puede guardar igual, pero en el
                    calendario quedará al revés.
                </p>

                <p x-show="desde && hasta && hasta >= desde" x-cloak
                    class="mt-2 text-[10px] text-slate-500">
                    Dura <strong class="text-cyan-300" x-text="duracion"></strong>.
                </p>
            </div>
        </section>


        {{-- ---------- 3 · EN QUÉ ESTADO ---------- ---}}

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <div class="flex items-center gap-3 border-b border-slate-800 px-4 py-2.5">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-emerald-500/15 font-mono text-[11px] font-black text-emerald-300">3</span>
                <h3 class="min-w-0 flex-1 text-[12px] font-black text-white">En qué estado nace</h3>
            </div>

            <div class="grid gap-1.5 p-4 sm:grid-cols-2">
                @foreach ($estados as [$valor, $etiqueta, $ayuda, $tono])
                    <label class="flex cursor-pointer items-start gap-2 rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 transition has-[:checked]:bg-slate-900"
                        style="--tw-ring-color: {{ $tono }}">
                        <input type="radio" name="status" value="{{ $valor }}" x-model="estado"
                            class="mt-0.5 border-slate-700 bg-slate-900 focus:ring-offset-0"
                            style="color: {{ $tono }}">
                        <span class="min-w-0">
                            <span class="block text-[11px] font-black" style="color: {{ $tono }}">{{ $etiqueta }}</span>
                            <span class="block text-[9px] leading-3 text-slate-600">{{ $ayuda }}</span>
                        </span>
                    </label>
                @endforeach
            </div>

            @if ($otraActiva ?? null)
                <p x-show="estado === 'ACTIVE'" x-cloak
                    class="mx-4 mb-4 rounded-xl border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-[10px] leading-relaxed text-amber-200/90">
                    <strong class="text-amber-200">Ojo:</strong> ahora mismo está en curso
                    «{{ $otraActiva->name }}». Un universo solo puede tener una, así que al guardar esta
                    aquella dejará de estarlo.
                </p>
            @endif
        </section>
    </div>


    {{-- ---------- LO QUE SE VA A GUARDAR ---------- --}}

    <aside class="space-y-3 xl:sticky xl:top-2 xl:self-start">

        <section class="overflow-hidden rounded-2xl border border-violet-500/30 bg-slate-900/50">

            <div class="border-b border-slate-800 px-3 py-2">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-600">
                    Así quedará
                </p>
            </div>

            <div class="p-3">
                <article class="overflow-hidden rounded-xl border bg-slate-950"
                    :style="`border-color: ${tonoEstado}55`">

                    <div class="flex aspect-[16/10] items-center justify-center font-mono text-5xl font-black"
                        :style="`color: ${tonoEstado}66; background: radial-gradient(120% 120% at 50% 0%, ${tonoEstado}22, #020617 70%)`">
                        {{ $numero }}
                    </div>

                    <div class="p-2.5">
                        <p class="truncate text-[12px] font-black text-white" x-text="nombre || 'Sin nombre'"></p>
                        <p class="truncate text-[10px]" :style="`color: ${tonoEstado}`" x-text="etiquetaEstado"></p>
                        <p class="mt-0.5 truncate font-mono text-[9px] text-slate-600" x-text="periodo"></p>
                    </div>
                </article>
            </div>
        </section>

        <section class="space-y-2 rounded-2xl border border-slate-800 bg-slate-900/50 p-3">
            <button type="submit" :disabled="! nombre.trim()"
                class="w-full rounded-xl bg-violet-500 px-4 py-2.5 text-[12px] font-black text-white transition hover:bg-violet-400 disabled:cursor-not-allowed disabled:opacity-40">
                {{ $editando ? 'Guardar los cambios' : 'Crear la temporada' }}
            </button>

            <a href="{{ $editando ? route('universes.seasons.show', [$universe, $season]) : route('universes.seasons.index', $universe) }}"
                class="block rounded-xl border border-slate-800 px-4 py-2 text-center text-[11px] font-black text-slate-400 transition hover:border-slate-700 hover:text-white">
                Cancelar
            </a>
        </section>

    </aside>
</div>


<script>
    function editorDeTemporada(config) {

        return {

            nombre: config.nombre ?? '',
            estado: config.estado ?? 'PLANNED',
            desde: config.desde ?? '',
            hasta: config.hasta ?? '',
            numero: config.numero ?? 1,

            get tonoEstado() {
                return {
                    PLANNED: '#94a3b8',
                    ACTIVE: '#34d399',
                    COMPLETED: '#22d3ee',
                    ARCHIVED: '#475569',
                }[this.estado] ?? '#94a3b8';
            },

            get etiquetaEstado() {
                return {
                    PLANNED: 'Planeada',
                    ACTIVE: 'En curso',
                    COMPLETED: 'Terminada',
                    ARCHIVED: 'Archivada',
                }[this.estado] ?? 'Planeada';
            },

            get periodo() {
                if (! this.desde && ! this.hasta) return 'sin fechas';
                if (this.desde && ! this.hasta) return 'desde ' + this.escribir(this.desde);
                if (! this.desde && this.hasta) return 'hasta ' + this.escribir(this.hasta);

                return this.escribir(this.desde) + ' → ' + this.escribir(this.hasta);
            },

            get duracion() {
                if (! this.desde || ! this.hasta) return '';

                const dias = Math.round(
                    (new Date(this.hasta) - new Date(this.desde)) / 86400000
                ) + 1;

                if (dias < 0) return '';
                if (dias < 14) return dias + (dias === 1 ? ' día' : ' días');
                if (dias < 60) return Math.round(dias / 7) + ' semanas';
                if (dias < 700) return Math.round(dias / 30) + ' meses';

                return (dias / 365).toFixed(1).replace('.', ',') + ' años';
            },

            escribir(iso) {
                const [a, m, d] = iso.split('-');
                return `${d}/${m}/${a}`;
            },
        };
    }
</script>
