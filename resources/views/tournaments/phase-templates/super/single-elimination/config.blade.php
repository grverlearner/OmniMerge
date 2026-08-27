@php
    /*
     * Panel izquierdo — cómo funciona el cuadro.
     *
     * Tres decisiones, en el orden en que se toman:
     *
     *   1. hasta dónde se juega
     *   2. quién ocupa cada puesto del cuadro
     *   3. cómo se cruzan esos puestos
     *
     * Aquí no se configura cómo se pelea un enfrentamiento —ni best of, ni
     * series, ni juegos fijos—: eso pertenece al torneo real que use esta
     * fase, y mezclarlo haría que un mismo cuadro no se pudiera reutilizar
     * en dos torneos con reglas de combate distintas.
     */
@endphp

<div class="divide-y divide-slate-800">

    {{-- ================= HASTA DÓNDE ================= --}}

    <section class="p-3">

        <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-500">
            Hasta dónde se juega
        </p>

        <div class="mt-2 space-y-1">
            @foreach ($payload['catalog']['completion_modes'] as $key => $mode)
                <button type="button" @click="completionMode = @js($key)"
                    class="w-full rounded-lg border px-2 py-1.5 text-left transition"
                    :class="completionMode === @js($key)
                        ? 'border-amber-500 bg-amber-500/10'
                        : 'border-slate-800 hover:border-slate-700'">

                    <span class="flex items-center gap-1.5">
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full"
                            :class="completionMode === @js($key) ? 'bg-amber-400' : 'bg-slate-700'"></span>
                        <span class="text-[10px] font-black"
                            :class="completionMode === @js($key) ? 'text-amber-300' : 'text-slate-300'">
                            {{ $mode['label'] }}
                        </span>
                    </span>

                    <span class="mt-0.5 block pl-3 text-[9px] leading-tight text-slate-500">
                        {{ $mode['hint'] }}
                    </span>
                </button>
            @endforeach
        </div>

        <div x-show="completionMode === 'SURVIVORS'" x-cloak class="mt-2">
            <label class="text-[9px] font-black text-slate-500">Cuántos quedan vivos</label>

            <div class="mt-1 flex items-center gap-1.5">
                <button type="button" @click="targetSurvivors = Math.max(2, targetSurvivors - 1)"
                    class="flex h-6 w-6 items-center justify-center rounded-md border border-slate-700 text-slate-400 transition hover:border-slate-500 hover:text-slate-100">−</button>

                <input type="number" min="2" max="256" x-model.number="targetSurvivors"
                    class="w-full rounded-md border-slate-700 bg-slate-950 px-1 py-0.5 text-center text-sm font-black text-slate-100 focus:border-amber-500 focus:ring-amber-500">

                <button type="button" @click="targetSurvivors = targetSurvivors + 1"
                    class="flex h-6 w-6 items-center justify-center rounded-md border border-slate-700 text-slate-400 transition hover:border-slate-500 hover:text-slate-100">+</button>
            </div>

            <p class="mt-1 text-[9px] leading-relaxed text-slate-500">
                Se paran las últimas rondas. Cada superviviente puede tener su
                propia puerta de salida.
            </p>
        </div>


        {{--
            El partido por el tercer puesto estaba aquí, como interruptor
            suelto. Ya no: es un grupo de puestos más, y vive abajo con
            todos los demás.
        --}}

    </section>


    {{-- ================= PARTICIPANTES ================= --}}

    <section class="p-3">

        <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-500">Participantes</p>

        <div class="mt-2 flex items-center gap-2">

            <button type="button" @click="participants = Math.max(2, participants - 1)"
                class="flex h-7 w-7 items-center justify-center rounded-md border border-slate-700 text-slate-400 transition hover:border-slate-500 hover:text-slate-100">−</button>

            <input type="number" min="2" max="256" x-model.number="participants"
                class="w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-center text-sm font-black text-slate-100 focus:border-amber-500 focus:ring-amber-500">

            <button type="button" @click="participants = Math.min(256, participants + 1)"
                class="flex h-7 w-7 items-center justify-center rounded-md border border-slate-700 text-slate-400 transition hover:border-slate-500 hover:text-slate-100">+</button>

        </div>

        {{--
            Un cuadro solo existe en potencias de dos. Con 12 participantes
            el cuadro es de 16 y cuatro pasan sin jugar: conviene verlo al
            escribir el número, no al mirar el árbol.
        --}}
        <div class="mt-1.5 flex items-center gap-1.5 rounded-md bg-slate-950/60 px-2 py-1">
            <span class="text-[9px] text-slate-500">Cuadro de</span>
            <span class="font-mono text-sm font-black text-amber-300"
                x-text="structure.bracket_size || '—'"></span>
            <template x-if="byeCount > 0">
                <span class="ml-auto rounded bg-amber-500/15 px-1.5 text-[9px] font-black text-amber-300"
                    x-text="byeCount + (byeCount === 1 ? ' pasa directo' : ' pasan directo')"></span>
            </template>
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

        <label class="mt-2 flex cursor-pointer items-start gap-1.5">
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


    {{-- ================= QUIÉN OCUPA CADA PUESTO ================= --}}

    <section class="p-3">

        <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-500">
            Orden en el cuadro
        </p>

        <div class="mt-2 space-y-1">
            @foreach ($payload['catalog']['seeding_modes'] as $key => $mode)
                <button type="button" @click="seedingMode = @js($key)"
                    class="w-full rounded-lg border px-2 py-1.5 text-left transition"
                    :class="seedingMode === @js($key)
                        ? 'border-amber-500 bg-amber-500/10'
                        : 'border-slate-800 hover:border-slate-700'">

                    <span class="flex items-center gap-1.5">
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full"
                            :class="seedingMode === @js($key) ? 'bg-amber-400' : 'bg-slate-700'"></span>
                        <span class="text-[10px] font-black"
                            :class="seedingMode === @js($key) ? 'text-amber-300' : 'text-slate-300'">
                            {{ $mode['label'] }}
                        </span>
                    </span>

                    <span class="mt-0.5 block pl-3 text-[9px] leading-tight text-slate-500">
                        {{ $mode['hint'] }}
                    </span>
                </button>
            @endforeach
        </div>

        <button type="button" x-show="seedingMode === 'RANDOM'" x-cloak @click="reshuffle()"
            class="mt-2 w-full rounded-lg border border-slate-700 py-1.5 text-[10px] font-black text-slate-300 transition hover:border-amber-500 hover:text-amber-300">
            ⟳ Otro sorteo
        </button>

        <p x-show="showsManual" x-cloak
            class="mt-2 rounded-md bg-sky-500/10 px-2 py-1.5 text-[9px] font-bold leading-relaxed text-sky-200">
            Con orden manual mandan las puertas de entrada, y puedes mover a
            cada uno con las flechas del cuadro.
        </p>

    </section>


    {{-- ================= CÓMO SE CRUZAN ================= --}}

    <section class="p-3">

        <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-500">
            Cómo se cruzan
        </p>

        <div class="mt-2 space-y-1">
            @foreach ($payload['catalog']['pairing_modes'] as $key => $mode)
                <button type="button" @click="pairingMode = @js($key)"
                    class="w-full rounded-lg border px-2 py-1.5 text-left transition"
                    :class="pairingMode === @js($key)
                        ? 'border-amber-500 bg-amber-500/10'
                        : 'border-slate-800 hover:border-slate-700'">

                    <span class="flex items-center gap-1.5">
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full"
                            :class="pairingMode === @js($key) ? 'bg-amber-400' : 'bg-slate-700'"></span>
                        <span class="text-[10px] font-black"
                            :class="pairingMode === @js($key) ? 'text-amber-300' : 'text-slate-300'">
                            {{ $mode['label'] }}
                        </span>
                    </span>

                    <span class="mt-0.5 block pl-3 text-[9px] leading-tight text-slate-500">
                        {{ $mode['hint'] }}
                    </span>
                </button>
            @endforeach
        </div>


        {{-- A quién le tocan los descansos --}}

        <div class="mt-2" x-show="byeCount > 0" x-cloak>
            <label class="text-[9px] font-black text-slate-500">Quién pasa directo</label>

            <select x-model="byeAssignment"
                class="mt-1 w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-[11px] text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                @foreach ($payload['catalog']['bye_assignments'] as $key => $mode)
                    <option value="{{ $key }}">{{ $mode['label'] }}</option>
                @endforeach
            </select>

            @foreach ($payload['catalog']['bye_assignments'] as $key => $mode)
                <p x-show="byeAssignment === @js($key)" x-cloak
                    class="mt-1 text-[9px] leading-tight text-slate-500">
                    {{ $mode['hint'] }}
                </p>
            @endforeach
        </div>

    </section>


    {{-- ================= PUESTOS QUE SE DECIDEN ================= --}}

    {{--
        Aquí está lo que un cuadro NO sabe hacer solo.

        Decide el primero y el segundo, y de ahí para abajo solo agrupa: los
        dos que caen en semifinales comparten el tercer puesto, los cuatro
        que caen en cuartos comparten del quinto al octavo. No hay nada en el
        árbol que los separe porque nunca se han jugado entre ellos.

        Marcar un grupo es decir «quiero saber el orden exacto de estos», y
        entonces se juega un cuadro de clasificación entre ellos. El partido
        por el tercer puesto es exactamente eso con dos.
    --}}

    <section class="p-3">

        <div class="flex items-baseline gap-2">
            <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-500">
                Puestos que se deciden
            </p>

            <span class="ml-auto font-mono text-[9px] text-slate-600"
                x-show="placementCost > 0" x-cloak>
                +<span x-text="placementCost"></span> duelos
            </span>
        </div>

        <p class="mt-1 text-[9px] leading-relaxed text-slate-600">
            Marca los que quieras ordenar. Lo que dejes sin marcar queda
            empatado, que es lo que un cuadro decide de verdad.
        </p>

        <div class="mt-2 space-y-1">

            <template x-for="group in groups" :key="group.key">
                <div>

                    {{-- Ya decidido: no hay nada que activar --}}

                    <template x-if="group.auto">
                        <div class="flex items-center gap-1.5 rounded-md border border-slate-800 bg-slate-950/50 px-2 py-1">
                            <span class="font-mono text-[9px] font-black text-emerald-400"
                                x-text="group.from"></span>

                            <span class="truncate text-[9px] font-bold text-slate-300"
                                x-text="group.label"></span>

                            <span class="ml-auto shrink-0 text-[8px] uppercase tracking-wider text-emerald-500/70">
                                ya sale
                            </span>
                        </div>
                    </template>

                    {{-- Empatado: se puede ordenar jugando --}}

                    <template x-if="!group.auto">
                        <label class="flex cursor-pointer items-center gap-1.5 rounded-md border px-2 py-1 transition"
                            :class="isOrdered(group.key)
                                ? group.color.border + ' ' + group.color.soft
                                : 'border-slate-800 bg-slate-950/50 hover:border-slate-700'">

                            <input type="checkbox" :checked="isOrdered(group.key)"
                                @change="togglePlacement(group.key)"
                                class="h-3 w-3 shrink-0 rounded border-slate-600 bg-slate-950 text-amber-500 focus:ring-amber-500">

                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[9px] font-bold"
                                    :class="isOrdered(group.key) ? group.color.text : 'text-slate-300'"
                                    x-text="group.label"></span>

                                <span class="block truncate text-[8px] text-slate-600"
                                    x-text="group.hint"></span>
                            </span>

                            <span class="shrink-0 font-mono text-[8px]"
                                :class="isOrdered(group.key) ? group.color.text : 'text-slate-600'"
                                x-text="'+' + group.cost"></span>

                        </label>
                    </template>

                </div>
            </template>

            <template x-if="groups.length === 0">
                <p class="py-2 text-center text-[9px] text-slate-600">
                    Sin cuadro válido no hay puestos que repartir.
                </p>
            </template>

        </div>

        <p class="mt-2 text-[9px] leading-relaxed text-slate-600">
            En un cuadro no hay puntos ni desempates: se gana o se cae. La
            única forma de separar a dos empatados es hacerles jugar.
        </p>

    </section>

</div>
