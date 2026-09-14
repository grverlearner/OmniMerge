@php
    /*
     * El sistema de puntos del universo.
     *
     * Cinco números, y ninguno dice nada por sí solo: lo que importa es qué
     * clase de mundo describen juntos. Un universo donde el título vale 100 y
     * la victoria 3 premia ganar la final; uno donde la victoria vale 10 y el
     * título 20 premia la regularidad.
     *
     * Por eso el panel de al lado no es una vista previa decorativa: calcula,
     * con los valores que se estén tecleando, cuántos puntos sacarían tres
     * competidores de ejemplo con trayectorias distintas. Es la forma de ver
     * qué clase de mundo se está describiendo antes de guardarlo.
     */
@endphp

<section x-show="abrirPuntos" x-cloak x-collapse
    class="overflow-hidden rounded-2xl border border-violet-500/30 bg-violet-500/5">

    <form method="POST" action="{{ route('universes.ranking.points', $universe) }}"
        x-data="{
            campeon: {{ (int) ($settings['points_champion'] ?? 0) }},
            victoria: {{ (int) ($settings['points_win'] ?? 0) }},
            empate: {{ (int) ($settings['points_draw'] ?? 0) }},
            derrota: {{ (int) ($settings['points_loss'] ?? 0) }},
            participar: {{ (int) ($settings['points_participation'] ?? 0) }},

            /*
             * Tres trayectorias que existen en cualquier mundo. Con los
             * valores puestos, esto dice a quién premia el sistema.
             */
            get ejemplos() {
                const perfiles = [
                    { nombre: 'El que gana la final', t: 1, g: 4, e: 0, p: 1, comp: 1 },
                    { nombre: 'El regular', t: 0, g: 9, e: 3, p: 4, comp: 4 },
                    { nombre: 'El que participa', t: 0, g: 1, e: 1, p: 6, comp: 4 },
                ];

                const cuentas = perfiles.map((perfil) => ({
                    ...perfil,
                    total: perfil.t * this.campeon
                        + perfil.g * this.victoria
                        + perfil.e * this.empate
                        + perfil.p * this.derrota
                        + perfil.comp * this.participar,
                }));

                const techo = Math.max(1, ...cuentas.map((c) => c.total));

                return cuentas.map((c) => ({
                    ...c,
                    ancho: Math.max(Math.round((c.total / techo) * 100), 2),
                    lider: c.total === techo,
                }));
            },
        }">
        @csrf
        @method('PUT')

        <div class="flex flex-wrap items-center gap-3 border-b border-violet-500/20 px-4 py-2.5">

            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                <x-omni-icon name="controles" size="h-4 w-4" />
            </span>

            <div class="min-w-0 flex-1">
                <h2 class="text-[13px] font-black text-white">Qué vale cada cosa</h2>
                <p class="text-[10px] leading-relaxed text-violet-200/60">
                    La clasificación se recalcula sola: no hay que volver a jugar nada.
                </p>
            </div>

            <button type="button" @click="abrirPuntos = false"
                class="shrink-0 rounded-lg px-2 py-1 text-[11px] font-black text-slate-500 transition hover:text-white">
                Cerrar
            </button>
        </div>


        <div class="grid gap-4 p-4 lg:grid-cols-[minmax(0,1fr)_320px]">

            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([['points_champion', 'campeon', 'Ganar la competición', '#fbbf24', 'Por cada título.'], ['points_win', 'victoria', 'Ganar un enfrentamiento', '#34d399', 'Por cada victoria suelta.'], ['points_draw', 'empate', 'Empatar', '#94a3b8', 'Solo en juegos que admiten empate.'], ['points_loss', 'derrota', 'Perder', '#fb7185', 'Normalmente cero. Ponlo si quieres premiar el intento.'], ['points_participation', 'participar', 'Solo por presentarse', '#60a5fa', 'Por cada competición en la que entra.']] as [$campo, $modelo, $etiqueta, $tono, $ayuda])

                    <label class="block rounded-xl border border-slate-800 bg-slate-950 p-3"
                        style="border-color: {{ $tono }}25">

                        <span class="mb-1 flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-sm" style="background-color: {{ $tono }}"></span>
                            <span class="text-[11px] font-black text-slate-200">{{ $etiqueta }}</span>
                        </span>

                        <input type="number" name="{{ $campo }}" x-model.number="{{ $modelo }}"
                            min="0" max="1000" required
                            class="w-full rounded-lg border-slate-800 bg-slate-900 px-2 py-1.5 text-center font-mono text-[15px] font-black text-white focus:border-violet-500 focus:ring-violet-500"
                            style="color: {{ $tono }}">

                        <span class="mt-1 block text-[9px] leading-3 text-slate-600">{{ $ayuda }}</span>

                        @error($campo)
                            <span class="mt-1 block text-[10px] font-bold text-rose-300">{{ $message }}</span>
                        @enderror
                    </label>
                @endforeach
            </div>


            {{-- ---------- A QUIÉN PREMIA ESTE SISTEMA ---------- --}}

            <aside class="rounded-xl border border-violet-500/30 bg-slate-950 p-3">

                <p class="text-[10px] font-black uppercase tracking-wider text-slate-600">
                    A quién premia
                </p>

                <p class="mt-0.5 text-[10px] leading-4 text-slate-500">
                    Tres trayectorias de ejemplo, con los números que estás poniendo.
                </p>

                <div class="mt-2 space-y-2">
                    <template x-for="ejemplo in ejemplos" :key="ejemplo.nombre">
                        <div class="rounded-lg border p-2"
                            :class="ejemplo.lider ? 'border-violet-500/60 bg-violet-500/10' : 'border-slate-800 bg-slate-900'">

                            <div class="flex items-center gap-2">
                                <span class="min-w-0 flex-1 truncate text-[11px] font-black text-slate-200"
                                    x-text="ejemplo.nombre"></span>
                                <span class="shrink-0 font-mono text-[14px] font-black"
                                    :class="ejemplo.lider ? 'text-violet-300' : 'text-slate-500'"
                                    x-text="ejemplo.total"></span>
                            </div>

                            <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-950">
                                <div class="h-full rounded-full"
                                    :class="ejemplo.lider ? 'bg-violet-500' : 'bg-slate-700'"
                                    :style="`width: ${ejemplo.ancho}%`"></div>
                            </div>

                            <p class="mt-0.5 font-mono text-[8px] text-slate-600">
                                <span x-text="ejemplo.t"></span>🏆 ·
                                <span x-text="ejemplo.g"></span>G ·
                                <span x-text="ejemplo.e"></span>E ·
                                <span x-text="ejemplo.p"></span>P ·
                                <span x-text="ejemplo.comp"></span> comp.
                            </p>
                        </div>
                    </template>
                </div>

                <p class="mt-2 text-[9px] leading-3 text-slate-600">
                    Si el que gana la final siempre queda arriba, el mundo premia los títulos. Si gana el
                    regular, premia la constancia.
                </p>

                <button type="submit"
                    class="mt-3 w-full rounded-xl bg-violet-500 px-4 py-2.5 text-[12px] font-black text-white transition hover:bg-violet-400">
                    Guardar el sistema
                </button>
            </aside>
        </div>
    </form>

</section>
