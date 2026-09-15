@php
    /*
     * Que premia este mundo.
     *
     * Cinco numeros que no dicen nada por separado: lo que importa es que clase
     * de mundo describen juntos. Un universo donde el titulo vale 100 y la
     * victoria 3 premia ganar la final; uno donde la victoria vale 10 y el
     * titulo 20 premia la regularidad.
     *
     * Por eso hay tres arquetipos que rellenan los cinco a la vez, y el mismo
     * simulador de tres trayectorias que usa el panel de Clasificacion: es la
     * forma de ver que mundo se esta describiendo antes de crearlo.
     */

    $conceptos = [
        ['points_champion', 'campeon', 'Ganar la competición', '#fbbf24', 'Por cada título.'],
        ['points_win', 'victoria', 'Ganar un enfrentamiento', '#34d399', 'Por cada victoria suelta.'],
        ['points_draw', 'empate', 'Empatar', '#94a3b8', 'Solo en juegos que admiten empate.'],
        ['points_loss', 'derrota', 'Perder', '#fb7185', 'Normalmente cero. Ponlo si quieres premiar el intento.'],
        ['points_participation', 'participar', 'Solo por presentarse', '#60a5fa', 'Por cada competición en la que entra.'],
    ];

    $arquetipos = [
        ['titulos', 'Manda el que gana finales', '#fbbf24', 'Un título vale más que una racha entera.', [100, 3, 1, 0, 1]],
        ['equilibrado', 'Equilibrado', '#a78bfa', 'El de fábrica: el título pesa, la regularidad también.', [10, 3, 1, 0, 1]],
        ['constancia', 'Manda el constante', '#34d399', 'Ganar mucho durante mucho tiempo vale más que una final.', [15, 10, 4, 1, 2]],
    ];
@endphp

<section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <header class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-2.5">

        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
            <x-omni-icon name="controles" size="h-4 w-4" />
        </span>

        <div class="min-w-0 flex-1">
            <h2 class="text-[13px] font-black text-white">Qué premia este mundo</h2>
            <p class="text-[10px] text-slate-500">
                Decide quién manda en la clasificación. Se puede cambiar cuando quieras: la
                tabla se recalcula sola, sin volver a jugar nada.
            </p>
        </div>
    </header>

    <div class="grid gap-4 p-4 lg:grid-cols-[minmax(0,1fr)_280px]">

        <div class="space-y-3">

            {{-- ---------- LOS TRES ARQUETIPOS ---------- --}}

            <div>
                <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                    Elige una forma de ser y ajústala si quieres
                </p>

                <div class="mt-1.5 grid gap-1.5 sm:grid-cols-3">
                    @foreach ($arquetipos as [$clave, $texto, $tono, $ayuda, $valores])
                        <button type="button" @click="ponerArquetipo('{{ $clave }}', {{ json_encode($valores) }})"
                            class="block rounded-xl border p-2 text-left transition"
                            :style="arquetipo === '{{ $clave }}'
                                ? 'border-color: {{ $tono }}; background-color: {{ $tono }}14'
                                : 'border-color: #1e293b'">

                            <span class="flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full" style="background-color: {{ $tono }}"></span>
                                <span class="text-[11px] font-black leading-tight"
                                    :class="arquetipo === '{{ $clave }}' ? 'text-white' : 'text-slate-400'">
                                    {{ $texto }}
                                </span>
                            </span>

                            <span class="mt-0.5 block text-[9px] leading-3 text-slate-600">{{ $ayuda }}</span>
                        </button>
                    @endforeach
                </div>
            </div>


            {{-- ---------- LOS CINCO NÚMEROS ---------- --}}

            <div class="grid gap-2 sm:grid-cols-3 lg:grid-cols-5">
                @foreach ($conceptos as [$campo, $modelo, $etiqueta, $tono, $ayuda])
                    <label class="block rounded-xl border bg-slate-950 p-2"
                        style="border-color: {{ $tono }}25">

                        <span class="mb-1 flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-sm" style="background-color: {{ $tono }}"></span>
                            <span class="text-[10px] font-black leading-3 text-slate-200">{{ $etiqueta }}</span>
                        </span>

                        <input type="number" name="{{ $campo }}" x-model.number="puntos.{{ $modelo }}"
                            @input="arquetipo = 'manual'" min="0" max="1000"
                            class="w-full rounded-lg border-slate-800 bg-slate-900 px-1 py-1.5 text-center font-mono text-[14px] font-black focus:border-violet-500 focus:ring-violet-500"
                            style="color: {{ $tono }}">

                        <span class="mt-1 block text-[8px] leading-3 text-slate-600">{{ $ayuda }}</span>

                        <x-input-error :messages="$errors->get($campo)" class="mt-1" />
                    </label>
                @endforeach
            </div>
        </div>


        {{-- ---------- A QUIÉN PREMIA ---------- --}}

        <aside class="rounded-xl border border-violet-500/25 bg-slate-950 p-3">

            <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">A quién premia</p>

            <p class="mt-0.5 text-[10px] leading-3 text-slate-500">
                Tres trayectorias de ejemplo, con los números que estás poniendo.
            </p>

            <div class="mt-2 space-y-1.5">
                <template x-for="ejemplo in ejemplosDePuntos" :key="ejemplo.nombre">
                    <div class="rounded-lg border p-2"
                        :class="ejemplo.lider ? 'border-violet-500/60 bg-violet-500/10' : 'border-slate-800 bg-slate-900'">

                        <div class="flex items-center gap-2">
                            <span class="min-w-0 flex-1 truncate text-[11px] font-black text-slate-200"
                                x-text="ejemplo.nombre"></span>
                            <span class="shrink-0 font-mono text-[13px] font-black"
                                :class="ejemplo.lider ? 'text-violet-300' : 'text-slate-500'"
                                x-text="ejemplo.total"></span>
                        </div>

                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-950">
                            <div class="h-full rounded-full"
                                :class="ejemplo.lider ? 'bg-violet-500' : 'bg-slate-700'"
                                :style="`width: ${ejemplo.ancho}%`"></div>
                        </div>
                    </div>
                </template>
            </div>

            <p class="mt-2 text-[9px] leading-3 text-slate-600">
                Si el que gana la final siempre queda arriba, el mundo premia los títulos. Si
                gana el regular, premia la constancia.
            </p>
        </aside>
    </div>
</section>
