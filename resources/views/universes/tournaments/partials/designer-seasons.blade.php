@php
    /*
     * 04 · TEMPORADAS — cada cuánto aparece este torneo.
     *
     * Un universo avanza por temporadas, y no todos los torneos ocurren en
     * todas: unos son anuales, otros cada cuatro, y otros los convoca el
     * creador cuando le parece.
     *
     * Lo que se decide aquí es la PROGRAMACIÓN, no el calendario: dice
     * cuándo tocaría, no crea las ediciones. Crearlas sigue siendo un acto
     * deliberado, porque una temporada puede saltarse.
     */
    $t = $universeTournament ?? null;

    $modes = [
        'EVERY_SEASON' => [
            'label' => 'Cada temporada',
            'help' => 'Ocurre siempre. Es lo normal en la competición principal de un universo.',
            'icon' => '●',
        ],
        'EVERY_N_SEASONS' => [
            'label' => 'Cada N temporadas',
            'help' => 'Como unos Juegos Olímpicos: llega cada cierto tiempo y por eso pesa más.',
            'icon' => '◍',
        ],
        'MANUAL' => [
            'label' => 'Cuando yo diga',
            'help' => 'No se programa. Se convoca a mano cuando tiene sentido en la historia.',
            'icon' => '◌',
        ],
    ];
@endphp

<section x-show="isOpen('seasons')" x-cloak
    class="mb-3 overflow-hidden rounded-2xl border border-cyan-500/30 bg-slate-900/50">

    <div class="flex items-center gap-2 border-b border-slate-800 bg-cyan-500/10 px-4 py-2">
        <span class="font-mono text-[9px] text-slate-600">04</span>
        <span class="text-[11px]">↻</span>
        <h2 class="text-[11px] font-black uppercase tracking-wider text-cyan-300">Temporadas</h2>
        <span class="ml-auto text-[10px] text-slate-600">Cada cuánto aparece</span>
    </div>

    <div class="p-4">

        <div class="grid gap-2 sm:grid-cols-3">

            @foreach ($modes as $value => $mode)
                <button type="button" @click="recurrenceMode = '{{ $value }}'"
                    class="rounded-xl border p-3 text-left transition"
                    :class="recurrenceMode === '{{ $value }}'
                        ? 'border-cyan-400/60 bg-cyan-500/10'
                        : 'border-slate-800 bg-slate-950/50 hover:border-slate-700'">

                    <div class="flex items-center gap-1.5">
                        <span class="text-[13px]"
                            :class="recurrenceMode === '{{ $value }}' ? 'text-cyan-300' : 'text-slate-600'">{{ $mode['icon'] }}</span>
                        <span class="text-[12px] font-black"
                            :class="recurrenceMode === '{{ $value }}' ? 'text-cyan-300' : 'text-slate-300'">{{ $mode['label'] }}</span>
                    </div>

                    <p class="mt-1 text-[10px] leading-relaxed text-slate-500">{{ $mode['help'] }}</p>
                </button>
            @endforeach

        </div>

        <input type="hidden" name="recurrence_mode" :value="recurrenceMode">


        {{-- ============ EL DETALLE DE CADA MODO ============ --}}

        <div class="mt-3 grid gap-3 sm:grid-cols-2">

            <label x-show="recurrenceMode === 'EVERY_N_SEASONS'" x-cloak class="block">
                <span class="text-[9px] font-black uppercase tracking-wider text-cyan-300">
                    Cada cuántas temporadas
                </span>

                <input type="number" name="recurrence_interval" min="1" max="50"
                    value="{{ old('recurrence_interval', $t->recurrence_interval ?? 2) }}"
                    class="mt-1 w-24 rounded-xl border-slate-700 bg-slate-950 px-3 py-1.5 text-center font-mono text-[15px] font-black text-slate-100 focus:border-cyan-500 focus:ring-cyan-500">

                <span class="mt-1 block text-[9px] text-slate-600">
                    2 = un año sí y otro no. 4 = como unos Juegos.
                </span>

                <x-input-error :messages="$errors->get('recurrence_interval')" class="mt-1" />
            </label>

            <label x-show="recurrenceMode !== 'MANUAL'" x-cloak class="block">
                <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                    Primera temporada
                </span>

                <input type="number" name="first_season_number" min="1" max="9999"
                    value="{{ old('first_season_number', $t->first_season_number ?? 1) }}"
                    class="mt-1 w-24 rounded-xl border-slate-700 bg-slate-950 px-3 py-1.5 text-center font-mono text-[15px] font-black text-slate-100 focus:border-cyan-500 focus:ring-cyan-500">

                <span class="mt-1 block text-[9px] text-slate-600">
                    Desde cuándo existe. Antes de esa temporada, no se convoca.
                </span>

                <x-input-error :messages="$errors->get('first_season_number')" class="mt-1" />
            </label>

        </div>


        {{-- ============ CÓMO SE VERÁ ============ --}}

        {{--
            Una línea de temporadas dibujada. Un intervalo es fácil de
            escribir y difícil de imaginar: «cada 3 desde la 2» se entiende
            de golpe al ver qué temporadas se encienden.
        --}}

        <div class="mt-4 rounded-2xl border border-slate-800 bg-slate-950/60 p-3"
            x-data="{
                get intervalo() {
                    return Math.max(1, parseInt(document.querySelector('[name=recurrence_interval]')?.value || 2, 10));
                },
                get primera() {
                    return Math.max(1, parseInt(document.querySelector('[name=first_season_number]')?.value || 1, 10));
                },
                ocurre(n) {
                    if (recurrenceMode === 'MANUAL') return false;
                    if (n < this.primera) return false;
                    if (recurrenceMode === 'EVERY_SEASON') return true;

                    return (n - this.primera) % this.intervalo === 0;
                },
            }">

            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                En qué temporadas tocaría
            </p>

            <div class="mt-2 flex flex-wrap gap-1">
                @for ($n = 1; $n <= 12; $n++)
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg border font-mono text-[11px] font-black transition"
                        :class="ocurre({{ $n }})
                            ? 'border-cyan-400/60 bg-cyan-500/15 text-cyan-300'
                            : 'border-slate-800 bg-slate-950 text-slate-700'">{{ $n }}</span>
                @endfor
            </div>

            <p class="mt-2 text-[9px] leading-relaxed text-slate-600"
                x-text="recurrenceMode === 'MANUAL'
                    ? 'No se programa: cada edición se convoca a mano cuando quieras.'
                    : 'Esto dice cuándo TOCARÍA. Crear la edición sigue siendo un acto tuyo, porque una temporada puede saltarse.'"></p>

        </div>

    </div>

</section>
