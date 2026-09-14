@php
    /*
     * Crear un trofeo, sin salir de la vitrina.
     *
     * Con vista previa: un trofeo es una imagen antes que un registro, así que
     * lo que se ve mientras se rellena es la copa tal como quedará en la
     * estantería.
     *
     * El alcance es la decisión que no es obvia y por eso se explica: un trofeo
     * del universo lo pueden repartir todos sus torneos; uno atado a una
     * edición nace y muere con ella, y sirve para inventarse un premio de
     * aniversario sin ensuciar la vitrina permanente.
     */
@endphp

<section x-show="abrirNuevo" x-cloak x-collapse
    class="overflow-hidden rounded-2xl border border-amber-500/30 bg-amber-500/5">

    <form method="POST" action="{{ route('universes.trophies.store', $universe) }}"
        enctype="multipart/form-data"
        x-data="{
            nombre: @js(old('name', '')),
            nivel: @js(old('tier', 'GOLD')),
            simbolo: @js(old('icon', '')),
            imagen: null,

            get tono() {
                return {
                    GOLD: '#fbbf24',
                    SILVER: '#cbd5e1',
                    BRONZE: '#f59e0b',
                    SPECIAL: '#a78bfa',
                }[this.nivel] ?? '#fbbf24';
            },

            verImagen(evento) {
                const archivo = evento.target.files?.[0];

                if (! archivo) return;

                const lector = new FileReader();
                lector.onload = (e) => { this.imagen = e.target.result; };
                lector.readAsDataURL(archivo);
            },
        }">
        @csrf

        <div class="flex flex-wrap items-center gap-3 border-b border-amber-500/20 px-4 py-2.5">

            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 text-amber-300">
                <x-omni-icon name="trofeo" size="h-4 w-4" />
            </span>

            <div class="min-w-0 flex-1">
                <h2 class="text-[13px] font-black text-white">Un trofeo nuevo</h2>
                <p class="text-[10px] leading-relaxed text-amber-200/60">
                    Crearlo no lo reparte: después hay que engancharlo a la recompensa de un torneo.
                </p>
            </div>

            <button type="button" @click="abrirNuevo = false"
                class="shrink-0 rounded-lg px-2 py-1 text-[11px] font-black text-slate-500 transition hover:text-white">
                Cerrar
            </button>
        </div>


        <div class="grid gap-4 p-4 lg:grid-cols-[minmax(0,1fr)_260px]">

            <div class="space-y-3">

                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                            Cómo se llama
                        </span>
                        <input type="text" name="name" x-model="nombre" required maxlength="150"
                            placeholder="«Copa del Mundo», «Trofeo de plata»…"
                            class="w-full rounded-xl border-slate-800 bg-slate-950 text-sm font-bold text-white placeholder:font-normal placeholder:text-slate-600 focus:border-amber-500 focus:ring-amber-500">
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                            Símbolo
                        </span>
                        <input type="text" name="icon" x-model="simbolo" maxlength="16" placeholder="🏆"
                            class="w-full rounded-xl border-slate-800 bg-slate-950 text-center text-sm text-slate-200 placeholder:text-slate-600 focus:border-amber-500 focus:ring-amber-500">
                        <span class="mt-1 block text-[9px] leading-3 text-slate-600">
                            El respaldo si no le pones imagen.
                        </span>
                    </label>
                </div>


                <div>
                    <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                        De qué nivel
                    </span>

                    <div class="grid grid-cols-2 gap-1.5 sm:grid-cols-4">
                        @foreach ($tonoNivel as $clave => [$tono, $etiqueta])
                            <label class="flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-800 bg-slate-950 px-2.5 py-2 transition has-[:checked]:bg-slate-900"
                                :class="nivel === '{{ $clave }}' ? '' : ''"
                                :style="nivel === '{{ $clave }}' ? 'border-color: {{ $tono }}' : ''">
                                <input type="radio" name="tier" value="{{ $clave }}" x-model="nivel"
                                    class="border-slate-700 bg-slate-900"
                                    style="color: {{ $tono }}">
                                <span class="text-[11px] font-black" style="color: {{ $tono }}">{{ $etiqueta }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>


                <label class="block">
                    <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                        Qué premia
                    </span>
                    <textarea name="description" rows="2" maxlength="500"
                        placeholder="Opcional. Para acordarte de por qué existe."
                        class="w-full rounded-xl border-slate-800 bg-slate-950 text-xs text-slate-200 placeholder:text-slate-600 focus:border-amber-500 focus:ring-amber-500">{{ old('description') }}</textarea>
                </label>


                <div class="rounded-xl border border-slate-800 bg-slate-950 p-3">
                    <span class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-600">
                        Para quién existe
                    </span>

                    <select name="tournament_instance_id"
                        class="w-full rounded-xl border-slate-800 bg-slate-900 text-xs text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                        <option value="">Para todo el universo</option>
                        @foreach ($ediciones as $edicion)
                            <option value="{{ $edicion->id }}" @selected(old('tournament_instance_id') == $edicion->id)>
                                Solo en «{{ $edicion->name }}»
                            </option>
                        @endforeach
                    </select>

                    <p class="mt-1.5 text-[10px] leading-4 text-slate-600">
                        <strong class="text-slate-400">Del universo</strong> lo puede repartir cualquiera de
                        sus torneos y se queda en la vitrina para siempre.
                        <strong class="text-slate-400">De una edición</strong> nace y muere con ella: es la
                        forma de inventarse un premio de aniversario sin ensuciar la vitrina permanente.
                    </p>
                </div>
            </div>


            {{-- ---------- CÓMO QUEDARÁ EN LA ESTANTERÍA ---------- --}}

            <aside>
                <p class="mb-1.5 text-[10px] font-black uppercase tracking-wider text-slate-600">
                    Cómo quedará
                </p>

                <article class="overflow-hidden rounded-2xl border bg-slate-900/50"
                    :style="`border-color: ${tono}55`">

                    <div class="relative aspect-square overflow-hidden"
                        :style="`background: radial-gradient(120% 110% at 50% 10%, ${tono}28, #020617 70%)`">

                        <template x-if="imagen">
                            <img :src="imagen" alt="" class="h-full w-full object-contain p-4">
                        </template>

                        <template x-if="! imagen">
                            <span class="flex h-full w-full items-center justify-center text-6xl"
                                :style="`color: ${tono}`" x-text="simbolo || '🏆'"></span>
                        </template>
                    </div>

                    <div class="p-2.5">
                        <p class="truncate text-[13px] font-black text-white" x-text="nombre || 'Sin nombre'"></p>
                        <p class="text-[10px] font-black" :style="`color: ${tono}`"
                            x-text="{ GOLD: 'Oro', SILVER: 'Plata', BRONZE: 'Bronce', SPECIAL: 'Especial' }[nivel]"></p>
                    </div>
                </article>

                <label class="mt-2 block cursor-pointer rounded-xl border border-dashed border-slate-700 px-3 py-2 text-center transition hover:border-amber-500">
                    <input type="file" name="image" accept="image/*" @change="verImagen($event)" class="sr-only">
                    <span class="text-[10px] font-black text-slate-300">Elegir imagen</span>
                    <span class="mt-0.5 block text-[9px] text-slate-600">Hasta 2 MB. Con fondo transparente luce más.</span>
                </label>

                <button type="submit" :disabled="! nombre.trim()"
                    class="mt-2 w-full rounded-xl bg-amber-500 px-4 py-2.5 text-[12px] font-black text-slate-950 transition hover:bg-amber-400 disabled:cursor-not-allowed disabled:opacity-40">
                    Crear el trofeo
                </button>
            </aside>
        </div>
    </form>

</section>
