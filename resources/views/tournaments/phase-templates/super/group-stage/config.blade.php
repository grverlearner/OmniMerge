@php
    /*
     * Panel izquierdo — cómo se construye la fase de grupos.
     *
     * Tres decisiones, en el orden en que se toman de verdad:
     *
     *   1. cuántos grupos hay y de qué tamaño
     *   2. cómo se reparte la gente entre ellos
     *   3. cómo se juega dentro de cada grupo
     *
     * Aquí no se configura cómo se pelea un enfrentamiento —ni best of, ni
     * series, ni juegos fijos—: eso pertenece al torneo real que use esta
     * fase. Tampoco se editan los criterios de desempate; la cadena es fija
     * y se enseña abajo para saber cómo ordena, no para tocarla.
     */
@endphp

<div class="divide-y divide-slate-800">

    {{-- ================= CONSTRUCCIÓN ================= --}}

    <section class="p-3">

        <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-500">
            Cómo se forman los grupos
        </p>

        <div class="mt-2 space-y-1">
            @foreach ($payload['catalog']['group_count_modes'] as $key => $mode)
                <button type="button" @click="groupCountMode = @js($key)"
                    class="w-full rounded-lg border px-2 py-1.5 text-left transition"
                    :class="groupCountMode === @js($key)
                        ? 'border-amber-500 bg-amber-500/10'
                        : 'border-slate-800 hover:border-slate-700'">

                    <span class="flex items-center gap-1.5">
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full"
                            :class="groupCountMode === @js($key) ? 'bg-amber-400' : 'bg-slate-700'"></span>

                        <span class="text-[10px] font-black"
                            :class="groupCountMode === @js($key) ? 'text-amber-300' : 'text-slate-300'">
                            {{ $mode['label'] }}
                        </span>
                    </span>

                    <span class="mt-0.5 block pl-3 text-[9px] leading-tight text-slate-500">
                        {{ $mode['description'] }}
                    </span>
                </button>
            @endforeach
        </div>


        {{-- Cuántos grupos --}}

        <div x-show="groupCountMode === 'FIXED_GROUP_COUNT'" x-cloak class="mt-2">
            <label class="text-[9px] font-black text-slate-500">Cuántos grupos</label>

            <div class="mt-1 flex items-center gap-1.5">
                <button type="button" @click="groupCount = Math.max(2, groupCount - 1)"
                    class="flex h-6 w-6 items-center justify-center rounded-md border border-slate-700 text-slate-400 transition hover:border-slate-500 hover:text-slate-100">−</button>

                <input type="number" min="2" max="64" x-model.number="groupCount"
                    class="w-full rounded-md border-slate-700 bg-slate-950 px-1 py-0.5 text-center text-sm font-black text-slate-100 focus:border-amber-500 focus:ring-amber-500">

                <button type="button" @click="groupCount = Math.min(64, groupCount + 1)"
                    class="flex h-6 w-6 items-center justify-center rounded-md border border-slate-700 text-slate-400 transition hover:border-slate-500 hover:text-slate-100">+</button>
            </div>
        </div>


        {{-- Tamaño objetivo --}}

        <div x-show="groupCountMode === 'TARGET_GROUP_SIZE'" x-cloak class="mt-2">
            <label class="text-[9px] font-black text-slate-500">Cuántos por grupo</label>

            <div class="mt-1 flex items-center gap-1.5">
                <button type="button" @click="targetGroupSize = Math.max(2, targetGroupSize - 1)"
                    class="flex h-6 w-6 items-center justify-center rounded-md border border-slate-700 text-slate-400 transition hover:border-slate-500 hover:text-slate-100">−</button>

                <input type="number" min="2" max="64" x-model.number="targetGroupSize"
                    class="w-full rounded-md border-slate-700 bg-slate-950 px-1 py-0.5 text-center text-sm font-black text-slate-100 focus:border-amber-500 focus:ring-amber-500">

                <button type="button" @click="targetGroupSize = Math.min(64, targetGroupSize + 1)"
                    class="flex h-6 w-6 items-center justify-center rounded-md border border-slate-700 text-slate-400 transition hover:border-slate-500 hover:text-slate-100">+</button>
            </div>
        </div>


        {{-- Personalizado: se editan a la derecha --}}

        <div x-show="isCustom" x-cloak
            class="mt-2 rounded-md bg-sky-500/10 px-2 py-1.5">
            <p class="text-[9px] font-bold leading-relaxed text-sky-200">
                Los grupos se crean y se editan uno a uno en el panel derecho,
                con su nombre, su cupo y sus vueltas propias.
            </p>
        </div>


        {{-- Límites de tamaño --}}

        <div class="mt-2 grid grid-cols-2 gap-1.5" x-show="!isCustom" x-cloak>
            <label class="block">
                <span class="text-[9px] font-black text-slate-500">Mín. por grupo</span>
                <input type="number" min="2" max="64" x-model.number="minGroupSize"
                    class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1 py-0.5 text-center text-[11px] text-slate-100">
            </label>

            <label class="block">
                <span class="text-[9px] font-black text-slate-500">Máx. por grupo</span>
                <input type="number" min="2" max="64" x-model.number="maxGroupSize"
                    class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1 py-0.5 text-center text-[11px] text-slate-100">
            </label>
        </div>


        {{-- Qué hacer con los que sobran --}}

        <div class="mt-2" x-show="!isCustom" x-cloak>
            <label class="text-[9px] font-black text-slate-500">Si sobran participantes</label>

            <select x-model="remainderPolicy"
                class="mt-1 w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-[11px] text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                @foreach ($payload['catalog']['remainder_policies'] as $key => $policy)
                    <option value="{{ $key }}">{{ $policy['label'] }}</option>
                @endforeach
            </select>

            @foreach ($payload['catalog']['remainder_policies'] as $key => $policy)
                <p x-show="remainderPolicy === @js($key)" x-cloak
                    class="mt-1 text-[9px] leading-tight text-slate-500">
                    {{ $policy['description'] }}
                </p>
            @endforeach
        </div>


        {{-- Resultado real de la construcción --}}

        <div class="mt-2 flex items-center gap-1.5 rounded-md bg-slate-950/60 px-2 py-1.5">
            <span class="font-mono text-sm font-black text-amber-300"
                x-text="structure.groups_count ?? '—'"></span>
            <span class="text-[9px] text-slate-500">grupos de</span>
            <span class="font-mono text-sm font-black text-slate-100"
                x-text="structure.uneven
                    ? structure.min_size + '–' + structure.max_size
                    : (structure.min_size ?? '—')"></span>
        </div>

    </section>


    {{-- ================= PARTICIPANTES ================= --}}

    <section class="p-3">

        <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-500">Participantes</p>

        {{--
            Con grupos personalizados la cantidad NO se escribe: es la suma
            de los cupos.

            Tratarlas como dos cosas independientes obligaba a que cuadraran,
            y cambiar el cupo de un solo grupo dejaba la fase inválida hasta
            retocar otro. No había forma de editar un grupo sin pasar por un
            estado roto.
        --}}

        <div class="mt-2 flex items-center gap-2" x-show="!participantsAreDerived">

            <button type="button" @click="participants = Math.max(2, participants - 1)"
                class="flex h-7 w-7 items-center justify-center rounded-md border border-slate-700 text-slate-400 transition hover:border-slate-500 hover:text-slate-100">−</button>

            <input type="number" min="2" max="256" x-model.number="participants"
                class="w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-center text-sm font-black text-slate-100 focus:border-amber-500 focus:ring-amber-500">

            <button type="button" @click="participants = Math.min(256, participants + 1)"
                class="flex h-7 w-7 items-center justify-center rounded-md border border-slate-700 text-slate-400 transition hover:border-slate-500 hover:text-slate-100">+</button>

        </div>

        <div x-show="participantsAreDerived" x-cloak
            class="mt-2 rounded-md border border-slate-700 bg-slate-950/60 px-2 py-1.5">

            <p class="text-center font-mono text-lg font-black text-amber-300"
                x-text="contract.resolved"></p>

            <p class="mt-0.5 text-center text-[9px] leading-relaxed text-slate-500">
                La suma de los cupos de tus grupos.
                <span class="block text-slate-600">Cámbiala editándolos, a la derecha.</span>
            </p>

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

        <label class="mt-2 flex cursor-pointer items-start gap-1.5" x-show="!participantsAreDerived">
            <input type="checkbox" x-model="pinParticipants" @change="dirty = true"
                class="mt-0.5 h-3 w-3 rounded border-slate-600 bg-slate-950 text-amber-500 focus:ring-amber-500">
            <span class="text-[9px] leading-relaxed text-slate-500">
                Fijar como cantidad exacta de la fase.
                <span class="text-slate-600">
                    Sin marcar, el número se recuerda para abrir aquí pero la
                    fase sigue admitiendo su rango.
                </span>
            </span>
        </label>

    </section>


    {{-- ================= REPARTO ================= --}}

    <section class="p-3">

        <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-500">
            Cómo se reparten
        </p>

        <div class="mt-2 space-y-1">
            @foreach ($payload['catalog']['distribution_modes'] as $key => $mode)
                <button type="button" @click="distributionMode = @js($key)"
                    class="w-full rounded-lg border px-2 py-1.5 text-left transition"
                    :class="distributionMode === @js($key)
                        ? 'border-amber-500 bg-amber-500/10'
                        : 'border-slate-800 hover:border-slate-700'">

                    <span class="flex items-center gap-1.5">
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full"
                            :class="distributionMode === @js($key) ? 'bg-amber-400' : 'bg-slate-700'"></span>

                        <span class="text-[10px] font-black"
                            :class="distributionMode === @js($key) ? 'text-amber-300' : 'text-slate-300'">
                            {{ $mode['label'] }}
                        </span>
                    </span>

                    <span class="mt-0.5 block pl-3 text-[9px] leading-tight text-slate-500">
                        {{ $mode['description'] }}
                    </span>
                </button>
            @endforeach
        </div>

        <button type="button" x-show="distributionMode === 'RANDOM'" x-cloak @click="reshuffle()"
            class="mt-2 w-full rounded-lg border border-slate-700 py-1.5 text-[10px] font-black text-slate-300 transition hover:border-amber-500 hover:text-amber-300">
            ⟳ Otro sorteo
        </button>

        <p x-show="isManualDraw" x-cloak
            class="mt-2 rounded-md bg-sky-500/10 px-2 py-1.5 text-[9px] font-bold leading-relaxed text-sky-200">
            Con reparto manual son las puertas de entrada las que mandan a
            cada tramo de entrantes a su grupo. Se configuran a la derecha.
        </p>

    </section>


    {{-- ================= DENTRO DE CADA GRUPO ================= --}}

    <section class="p-3">

        <div class="flex items-center gap-1.5">
            <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-500">
                Dentro de cada grupo
            </p>
        </div>

        <p class="mt-1 text-[9px] leading-relaxed text-slate-600">
            Cada grupo es una liga pequeña: todos contra todos, dentro.
        </p>

        <div class="mt-2 grid grid-cols-2 gap-1.5">
            <template x-for="option in [
                { value: 1, label: 'Una vuelta' },
                { value: 2, label: 'Ida y vuelta' },
            ]" :key="option.value">
                <button type="button" @click="cycles = option.value"
                    class="rounded-lg border px-2 py-1.5 text-center transition"
                    :class="cycles === option.value
                        ? 'border-amber-500 bg-amber-500/10'
                        : 'border-slate-700 hover:border-slate-600'">
                    <span class="text-[10px] font-black"
                        :class="cycles === option.value ? 'text-amber-300' : 'text-slate-300'"
                        x-text="option.label"></span>
                </button>
            </template>
        </div>

        {{-- Mas de dos vueltas existe, pero es raro: no ocupa sitio por defecto --}}
        <div class="mt-1.5 flex items-center gap-2" x-show="cycles > 2" x-cloak>
            <span class="text-[9px] font-black text-slate-500">Vueltas</span>
            <input type="number" min="1" max="10" x-model.number="cycles"
                class="w-14 rounded-md border-slate-700 bg-slate-950 px-1.5 py-0.5 text-[11px] text-slate-200">
            <button type="button" @click="cycles = 1"
                class="text-[9px] font-black text-slate-600 transition hover:text-slate-400">volver a 1</button>
        </div>

        <button type="button" x-show="cycles <= 2" @click="cycles = 3"
            class="mt-1.5 text-[9px] font-black text-slate-600 transition hover:text-slate-400">
            + más vueltas
        </button>

        <p x-show="isCustom" x-cloak class="mt-1.5 text-[9px] leading-relaxed text-slate-500">
            Un grupo puede llevar sus propias vueltas: se le ponen en su
            tarjeta, a la derecha.
        </p>


        {{-- Jornadas a jugar --}}

        <div class="mt-3">

            <div class="flex items-center justify-between gap-2">
                <label class="text-[9px] font-black text-slate-500">Jornadas a jugar</label>
                <span class="font-mono text-[9px] text-slate-600" x-text="'de ' + maxRounds"></span>
            </div>

            <div class="mt-1 flex items-center gap-2">
                <input type="range" min="1" :max="maxRounds" x-model.number="roundLimit"
                    class="h-1 w-full cursor-pointer appearance-none rounded-full bg-slate-700 accent-amber-500">

                <input type="number" min="1" :max="maxRounds" x-model.number="roundLimit"
                    class="w-12 shrink-0 rounded-md border-slate-700 bg-slate-950 px-1 py-0.5 text-center text-[11px] font-black text-slate-100">
            </div>

            <p class="mt-1 text-[9px] leading-relaxed"
                :class="isTrimmed ? 'text-amber-300' : 'text-slate-500'">
                <span x-show="!isTrimmed">Los grupos se juegan enteros.</span>
                <span x-show="isTrimmed" x-cloak>
                    Se recortan <span x-text="maxRounds - roundLimit"></span>:
                    dentro de cada grupo no jugarán todos contra todos.
                </span>
            </p>

        </div>

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
                    class="mt-1 w-full rounded-md border-slate-700 bg-slate-950 px-1 py-1 text-center text-xs font-black text-slate-100">
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
            El orden es fijo. Como termina en el orden de entrada, un grupo
            nunca acaba en un empate sin resolver.
        </p>

    </section>


    {{-- ============================================================= --}}
    {{-- LA LISTA ÚNICA DE LA FASE --}}
    {{-- ============================================================= --}}

    {{--
        Una fase de grupos produce varias tablas, y casi siempre hace falta
        UNA sola: para repartir plazas, para sembrar el cuadro que viene,
        para entregar premios por puesto.

        Cuál es «la primera» de esa lista no es un hecho, es una decisión —el
        1.º de un grupo flojo puede haber hecho menos puntos que el 2.º de
        uno fuerte—. Por eso se elige aquí, y se ve arriba, en el centro, qué
        lista produce la elección.
    --}}

    <section class="border-t border-slate-800 p-3">

        <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-500">
            Orden general de la fase
        </p>

        <div class="mt-2 space-y-1.5">

            <template x-for="(modo, clave) in overallModes" :key="clave">
                <button type="button" @click="setOverallMode(clave)"
                    class="w-full rounded-lg border p-2 text-left transition"
                    :class="overallMode === clave
                        ? 'border-cyan-400/60 bg-cyan-500/10'
                        : 'border-slate-800 bg-slate-950/40 hover:border-slate-600'">

                    <span class="flex items-center gap-1.5">
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full border transition"
                            :class="overallMode === clave
                                ? 'border-cyan-400 bg-cyan-400'
                                : 'border-slate-600'"></span>

                        <span class="text-[10px] font-black"
                            :class="overallMode === clave ? 'text-cyan-300' : 'text-slate-300'"
                            x-text="modo.label"></span>
                    </span>

                    <span class="mt-1 block text-[9px] leading-relaxed text-slate-500"
                        x-text="modo.help"></span>
                </button>
            </template>

        </div>

        <p class="mt-2 text-[9px] leading-relaxed text-slate-600">
            El desempate dentro de cada caso es siempre el de arriba, así que
            esta lista nunca puede contradecir a las tablas de cada grupo.
        </p>

    </section>

</div>
