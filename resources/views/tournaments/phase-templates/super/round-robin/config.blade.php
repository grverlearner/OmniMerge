@php
    /*
     * Panel izquierdo — configuración de una fase Round Robin.
     *
     * Solo lo que decide CÓMO FUNCIONA LA FASE. Aquí no se configura cómo
     * se pelea un enfrentamiento: ni best of, ni series, ni juegos fijos.
     * Eso pertenece al torneo real que use esta fase, y mezclarlo haría que
     * una misma liga no se pudiera reutilizar en dos torneos con reglas de
     * combate distintas.
     *
     * Tampoco se editan los criterios de desempate. La cadena es fija y se
     * enseña abajo para que se sepa cómo ordena, no para tocarla.
     */
@endphp

<div class="divide-y divide-slate-800">

    {{-- ================= VUELTAS ================= --}}

    <section class="p-3">

        <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-500">Ciclo</p>

        <div class="mt-2 grid grid-cols-2 gap-1.5">
            <template x-for="option in [
                { value: 1, label: 'Una vuelta', hint: 'Todos contra todos' },
                { value: 2, label: 'Ida y vuelta', hint: 'Dos veces' },
            ]" :key="option.value">
                <button type="button" @click="cycles = option.value"
                    class="rounded-lg border px-2 py-2 text-left transition"
                    :class="cycles === option.value
                        ? 'border-amber-500 bg-amber-500/10'
                        : 'border-slate-700 hover:border-slate-600'">
                    <span class="block text-[10px] font-black"
                        :class="cycles === option.value ? 'text-amber-300' : 'text-slate-300'"
                        x-text="option.label"></span>
                    <span class="block text-[9px] text-slate-500" x-text="option.hint"></span>
                </button>
            </template>
        </div>

        {{-- Más de dos vueltas existe, pero es raro: no ocupa sitio por defecto --}}
        <div class="mt-1.5 flex items-center gap-2" x-show="cycles > 2" x-cloak>
            <span class="text-[9px] font-black text-slate-500">Vueltas</span>
            <input type="number" min="1" max="10" x-model.number="cycles"
                class="w-14 rounded-md border-slate-700 bg-slate-950 px-1.5 py-0.5 text-[11px] text-slate-200">
        </div>

        <button type="button" x-show="cycles <= 2" @click="cycles = 3"
            class="mt-1.5 text-[9px] font-black text-slate-600 transition hover:text-slate-400">
            + más vueltas
        </button>

    </section>


    {{-- ================= PARTICIPANTES ================= --}}

    <section class="p-3">

        <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-500">Participantes</p>

        <div class="mt-2 flex items-center gap-2">

            <button type="button" @click="participants = Math.max(2, participants - 1)"
                class="flex h-7 w-7 items-center justify-center rounded-md border border-slate-700 text-slate-400 transition hover:border-slate-500 hover:text-slate-100">
                −
            </button>

            <input type="number" min="2" max="256" x-model.number="participants"
                class="w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-center text-sm font-black text-slate-100 focus:border-amber-500 focus:ring-amber-500">

            <button type="button" @click="participants = Math.min(256, participants + 1)"
                class="flex h-7 w-7 items-center justify-center rounded-md border border-slate-700 text-slate-400 transition hover:border-slate-500 hover:text-slate-100">
                +
            </button>

        </div>

        <p class="mt-1.5 text-[9px] leading-relaxed text-slate-500">
            La fase admite
            <span class="font-black text-slate-400" x-text="contract.exact !== null
                ? contract.exact + ' exactos'
                : contract.min + (contract.max !== null ? '–' + contract.max : ' o más')"></span>.
        </p>

        <template x-if="contractWarning">
            <p class="mt-1.5 rounded-md bg-rose-500/10 px-2 py-1 text-[9px] font-bold leading-relaxed text-rose-300"
                x-text="contractWarning"></p>
        </template>

        {{--
            Mover el control es previsualizar. Fijarlo estrecha el contrato
            de una pieza reutilizable, así que se pide a propósito.
        --}}
        <label class="mt-2 flex cursor-pointer items-start gap-1.5">
            <input type="checkbox" x-model="pinParticipants" @change="dirty = true"
                class="mt-0.5 h-3 w-3 rounded border-slate-600 bg-slate-950 text-amber-500 focus:ring-amber-500">
            <span class="text-[9px] leading-relaxed text-slate-500">
                Fijar como cantidad exacta de la fase.
                <span class="text-slate-600">Si no, esto solo es previsualización.</span>
            </span>
        </label>

    </section>


    {{-- ================= JORNADAS A JUGAR ================= --}}
    {{--
        Normalmente se juega la liga entera y este control no hace falta.

        Existe porque es lo que le da sentido a sembrar por puerta: con el
        calendario completo todo el mundo se enfrenta a todo el mundo, así
        que el puesto inicial solo cambia el ORDEN de los partidos. En
        cuanto la liga se corta, el puesto decide contra quién llegas a
        jugar y contra quién no.
    --}}

    <section class="p-3">

        <div class="flex items-center justify-between gap-2">
            <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-500">
                Jornadas a jugar
            </p>

            <span class="font-mono text-[9px] text-slate-600"
                x-text="'de ' + maxRounds"></span>
        </div>

        <div class="mt-2 flex items-center gap-2">

            <input type="range" min="1" :max="maxRounds" x-model.number="roundLimit"
                class="h-1 w-full cursor-pointer appearance-none rounded-full bg-slate-700 accent-amber-500">

            <input type="number" min="1" :max="maxRounds" x-model.number="roundLimit"
                class="w-14 shrink-0 rounded-md border-slate-700 bg-slate-950 px-1 py-0.5 text-center text-xs font-black text-slate-100 focus:border-amber-500 focus:ring-amber-500">

        </div>

        <div class="mt-1.5 flex items-center justify-between gap-2">

            <p class="text-[9px] leading-relaxed" :class="isTrimmed ? 'text-amber-300' : 'text-slate-500'">
                <span x-show="!isTrimmed">Liga completa.</span>
                <span x-show="isTrimmed" x-cloak>
                    Se recortan <span x-text="maxRounds - roundLimit"></span>.
                    No todos se enfrentarán entre sí.
                </span>
            </p>

            <button type="button" x-show="isTrimmed" x-cloak @click="roundLimit = maxRounds"
                class="shrink-0 text-[9px] font-black text-slate-500 transition hover:text-amber-400">
                completa
            </button>

        </div>

    </section>


    {{-- ================= ORDEN ================= --}}

    <section class="p-3">

        <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-500">Orden inicial</p>

        <div class="mt-2 space-y-1">
            @foreach ($payload['catalog']['order_modes'] as $key => $mode)
                <button type="button" @click="orderMode = @js($key)"
                    class="w-full rounded-lg border px-2 py-1.5 text-left transition"
                    :class="orderMode === @js($key)
                        ? 'border-amber-500 bg-amber-500/10'
                        : 'border-slate-800 hover:border-slate-700'">

                    <span class="flex items-center gap-1.5">
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full"
                            :class="orderMode === @js($key) ? 'bg-amber-400' : 'bg-slate-700'"></span>

                        <span class="text-[10px] font-black"
                            :class="orderMode === @js($key) ? 'text-amber-300' : 'text-slate-300'">
                            {{ $mode['label'] }}
                        </span>
                    </span>

                    <span class="mt-0.5 block pl-3 text-[9px] leading-tight text-slate-500">
                        {{ $mode['hint'] }}
                    </span>
                </button>
            @endforeach
        </div>

        {{--
            Con la liga entera, sembrar por puerta no cambia contra quién
            juegas: cambia cuándo. Merece decirse donde se elige.
        --}}
        <p x-show="gateOrderIsCosmetic" x-cloak
            class="mt-2 rounded-md bg-amber-500/10 px-2 py-1.5 text-[9px] font-bold leading-relaxed text-amber-300">
            Con la liga completa todos se enfrentan a todos: el puesto solo
            cambia el orden de los partidos. Recorta jornadas arriba para
            que decida algo más.
        </p>

        {{-- Aleatorio: rebarajar para ver otra distribución --}}
        <button type="button" x-show="orderMode === 'RANDOM'" x-cloak @click="reshuffle()"
            class="mt-2 w-full rounded-lg border border-slate-700 py-1.5 text-[10px] font-black text-slate-300 transition hover:border-amber-500 hover:text-amber-300">
            ⟳ Barajar otra vez
        </button>

    </section>


    {{-- ================= PUNTUACIÓN ================= --}}

    <section class="p-3">

        <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-500">Puntuación</p>

        <div class="mt-2 grid grid-cols-3 gap-1.5">

            <label class="block">
                <span class="block text-center text-[9px] font-black text-emerald-400">Victoria</span>
                <input type="number" step="0.5" x-model.number="points.win" @input="dirty = true"
                    class="mt-1 w-full rounded-md border-slate-700 bg-slate-950 px-1 py-1 text-center text-xs font-black text-slate-100 focus:border-emerald-500 focus:ring-emerald-500">
            </label>

            <label class="block" :class="allowDraws ? '' : 'opacity-40'">
                <span class="block text-center text-[9px] font-black text-slate-400">Empate</span>
                <input type="number" step="0.5" x-model.number="points.draw" @input="dirty = true"
                    :disabled="!allowDraws"
                    class="mt-1 w-full rounded-md border-slate-700 bg-slate-950 px-1 py-1 text-center text-xs font-black text-slate-100 focus:border-slate-500 focus:ring-slate-500">
            </label>

            <label class="block">
                <span class="block text-center text-[9px] font-black text-rose-400">Derrota</span>
                <input type="number" step="0.5" x-model.number="points.loss" @input="dirty = true"
                    class="mt-1 w-full rounded-md border-slate-700 bg-slate-950 px-1 py-1 text-center text-xs font-black text-slate-100 focus:border-rose-500 focus:ring-rose-500">
            </label>

        </div>

        <label class="mt-2 flex cursor-pointer items-center gap-1.5">
            <input type="checkbox" x-model="allowDraws" @change="dirty = true"
                class="h-3 w-3 rounded border-slate-600 bg-slate-950 text-amber-500 focus:ring-amber-500">
            <span class="text-[9px] text-slate-500">Permitir empates</span>
        </label>

    </section>


    {{-- ================= DESEMPATE ================= --}}

    <section class="p-3">

        <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-500">
            Cómo se desempata
        </p>

        <ol class="mt-2 space-y-0.5">
            @foreach ($payload['catalog']['tiebreak_chain'] as $index => $criterion)
                <li class="flex items-center gap-1.5 text-[9px] text-slate-400">
                    <span class="w-3 shrink-0 text-right font-mono text-slate-600">{{ $index + 1 }}</span>
                    <span>{{ $criterion }}</span>
                </li>
            @endforeach
        </ol>

        <p class="mt-2 text-[9px] leading-relaxed text-slate-600">
            El orden es fijo. Como termina en el orden de entrada, una liga
            nunca acaba en un empate sin resolver.
        </p>

    </section>

</div>
