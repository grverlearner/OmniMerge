@php
    /*
     * Panel derecho — grupos y puertas.
     *
     * Tres bloques que se leen en el mismo orden que el escenario:
     *
     *   GRUPOS    qué grupos hay. Solo se editan uno a uno cuando la
     *             construcción es «personalizada»; con las otras dos el
     *             reparto lo calcula la fase y aquí se ve, pero no se toca.
     *
     *   ENTRADAS  qué tramo de los que llegan va a qué grupo. Es lo que una
     *             puerta decide en una fase de grupos: no cuántos entran,
     *             sino a dónde.
     *
     *   SALIDAS   quién avanza. Cada salida lleva dentro los criterios que
     *             la cruzan, porque una puerta sin criterio no la cruza
     *             nadie. Se editan aquí uno a uno: una puerta puede llevar
     *             varios, así que «el» criterio no existe.
     */

    $families = collect($payload['catalog']['rule_types'])
        ->groupBy(fn($definition) => $definition['family'] ?? 'Otros', true);
@endphp

<div class="divide-y divide-slate-800"
    x-data="{ newGroup: false, editGroup: null, newGate: false, editGate: null,
              newExit: false, editExit: null, editRule: null, addRuleTo: null }">

    {{-- ================= GRUPOS ================= --}}

    <section class="p-3">

        <div class="flex items-center justify-between gap-2">

            <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-500">
                Grupos
            </p>

            <div class="flex items-center gap-1.5">
                <span class="rounded bg-slate-800 px-1.5 py-0.5 font-mono text-[9px] text-slate-400"
                    x-text="groups.length"></span>

                <button type="button" x-show="isCustom" x-cloak
                    @click="newGroup = !newGroup; editGroup = null"
                    class="rounded-md border border-slate-700 px-2 py-0.5 text-[10px] font-black text-slate-400 transition hover:border-amber-500 hover:text-amber-400">
                    <span x-show="!newGroup">+ Grupo</span>
                    <span x-show="newGroup" x-cloak>Cerrar</span>
                </button>
            </div>

        </div>


        {{-- Con construcción automática, aquí solo se mira --}}

        <p x-show="!isCustom" x-cloak class="mt-1.5 text-[9px] leading-relaxed text-slate-600">
            Los calcula la fase a partir de la construcción del panel
            izquierdo. Para editarlos uno a uno, elige
            <strong class="text-slate-500">grupos personalizados</strong>.
        </p>


        {{--
            Personalizado recien elegido y sin nada que dibujar.

            Los grupos venian del modo automatico y no tienen cupo, que es lo
            que este modo exige. En vez de dejar la pantalla en blanco con un
            error generico, se ofrece partir de lo que ya habia: es lo que se
            quiere casi siempre.
        --}}
        <div x-show="canAdoptSplit" x-cloak
            class="mt-2 rounded-lg border border-amber-500/40 bg-amber-500/10 p-2">

            <p class="text-[9px] font-bold leading-relaxed text-amber-200">
                Sin cupo no hay reparto que dibujar. Puedes partir de este:
                <span class="font-mono" x-text="adoptableSizes.join(' · ')"></span>
            </p>

            <p x-show="suggestionFollowsGates" x-cloak
                class="mt-1 text-[9px] leading-relaxed text-amber-200/70">
                Ya cuenta lo que prometen tus puertas de entrada: cada grupo
                lleva al menos los sitios que le mandan.
            </p>

            <form method="POST" class="mt-2"
                action="{{ route('tournaments.phase-templates.super.groups.adopt', $phaseTemplate) }}">
                @csrf
                @include('tournaments.phase-templates.super.partials.preview-state')

                <template x-for="(size, i) in adoptableSizes" :key="'sz' + i">
                    <input type="hidden" name="sizes[]" :value="size">
                </template>

                <button class="w-full rounded-md bg-amber-500 px-3 py-1 text-[10px] font-black text-slate-950 transition hover:bg-amber-400">
                    Adoptar este reparto
                </button>
            </form>

        </div>


        {{-- ALTA --}}

        <div x-show="newGroup" x-cloak class="mt-2 rounded-lg border border-amber-500/40 bg-amber-500/5 p-2">
            @include('tournaments.phase-templates.super.group-stage.group-form', ['group' => null])
        </div>


        <div class="mt-2 space-y-1.5">

            <template x-for="group in groups" :key="'gp' + group.index">
                <div class="rounded-lg border bg-slate-950/40 px-2 py-1.5" :class="group.color.border">

                    <div class="flex items-center gap-1.5">

                        <span class="flex h-4 w-4 items-center justify-center rounded text-[9px] font-black text-slate-950"
                            :class="group.color.solid"
                            x-text="group.name.replace('Grupo ', '').slice(0, 2)"></span>

                        <span class="min-w-0 flex-1 truncate text-[10px] font-bold text-slate-200"
                            x-text="group.name"></span>

                        <span class="font-mono text-[9px]" :class="group.color.text"
                            x-text="group.size + 'p · ×' + group.cycles"></span>

                        <template x-if="isCustom && group.definition_id">
                            <span class="flex shrink-0 items-center gap-1">
                                <button type="button"
                                    @click="editGroup = editGroup === group.definition_id ? null : group.definition_id; newGroup = false"
                                    class="text-[10px] text-slate-500 transition hover:text-amber-400">✎</button>

                                <form method="POST"
                                    :action="@js(route('tournaments.phase-templates.super.groups.destroy', [$phaseTemplate, '__ID__'])).replace('__ID__', group.definition_id)"
                                    @submit="confirm('¿Eliminar este grupo?') || $event.preventDefault()">
                                    @csrf
                                    @include('tournaments.phase-templates.super.partials.preview-state')
                                    @method('DELETE')
                                    <button class="text-[10px] text-slate-600 transition hover:text-rose-400">×</button>
                                </form>
                            </span>
                        </template>

                    </div>

                    {{-- EDICIÓN --}}
                    <div x-show="editGroup === group.definition_id" x-cloak
                        class="mt-2 border-t border-slate-800 pt-2">
                        <template x-if="editGroup === group.definition_id">
                            <div>
                                @include('tournaments.phase-templates.super.group-stage.group-form', ['group' => 'alpine'])
                            </div>
                        </template>
                    </div>

                </div>
            </template>

        </div>

    </section>


    {{-- ================= ENTRADAS ================= --}}

    <section class="p-3">

        <div class="flex items-center justify-between gap-2">

            <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-500">
                Entrada · a qué grupo
            </p>

            <button type="button" @click="newGate = !newGate; editGate = null"
                class="rounded-md border border-slate-700 px-2 py-0.5 text-[10px] font-black text-slate-400 transition hover:border-emerald-500 hover:text-emerald-400">
                <span x-show="!newGate">+ Puerta</span>
                <span x-show="newGate" x-cloak>Cerrar</span>
            </button>

        </div>


        <div x-show="newGate" x-cloak class="mt-2 rounded-lg border border-emerald-500/40 bg-emerald-500/5 p-2">
            @include('tournaments.phase-templates.super.group-stage.gate-form', ['gate' => null])
        </div>


        <div class="mt-2 space-y-1.5">

            <template x-for="gate in payload.gates" :key="'gt' + gate.id">
                <div class="rounded-lg border bg-slate-950/40 px-2 py-1.5"
                    :class="gate.status === 'ACTIVE' ? gate.color.border : 'border-slate-800 opacity-50'">

                    <div class="flex items-center gap-1.5">
                        <span class="h-4 w-1 shrink-0 rounded-full" :class="gate.color.dot"></span>

                        <span class="font-mono text-[10px] font-black" :class="gate.color.text"
                            x-text="'#' + gate.number"></span>

                        <span class="min-w-0 flex-1 truncate text-[10px] font-bold text-slate-200"
                            x-text="gate.name"></span>

                        <button type="button"
                            @click="editGate = editGate === gate.id ? null : gate.id; newGate = false"
                            class="shrink-0 text-[10px] text-slate-500 transition hover:text-emerald-400">✎</button>

                        <form method="POST" class="shrink-0"
                            :action="@js(route('tournaments.phase-templates.super.gates.destroy', [$phaseTemplate, '__ID__'])).replace('__ID__', gate.id)"
                            @submit="confirm('¿Eliminar esta puerta?') || $event.preventDefault()">
                            @csrf
                            @include('tournaments.phase-templates.super.partials.preview-state')
                            @method('DELETE')
                            <button class="text-[10px] text-slate-600 transition hover:text-rose-400">×</button>
                        </form>
                    </div>

                    <div class="mt-1 flex flex-wrap items-center gap-1.5 pl-2.5">
                        <span class="font-mono text-[9px] text-slate-500" x-text="gate.range_label"></span>

                        <template x-if="gate.target_group_name">
                            <span class="rounded px-1 text-[9px] font-black" :class="[gate.color.soft, gate.color.text]"
                                x-text="'→ ' + gate.target_group_name"></span>
                        </template>

                        <template x-if="!gate.target_group_name">
                            <span class="text-[9px] text-slate-700">sin grupo destino</span>
                        </template>
                    </div>

                    <div x-show="editGate === gate.id" x-cloak class="mt-2 border-t border-slate-800 pt-2">
                        <template x-if="editGate === gate.id">
                            <div>
                                @include('tournaments.phase-templates.super.group-stage.gate-form', ['gate' => 'alpine'])
                            </div>
                        </template>
                    </div>

                </div>
            </template>

            <template x-if="payload.gates.length === 0">
                <div x-show="!newGate" class="rounded-lg border border-dashed border-slate-700 px-2 py-3 text-center">
                    <p class="text-[9px] leading-relaxed text-slate-500">
                        Sin puertas, el reparto del panel izquierdo decide solo.
                    </p>
                </div>
            </template>

        </div>

        <p x-show="!isManualDraw && payload.gates.length > 0" x-cloak
            class="mt-1.5 rounded-md bg-amber-500/10 px-2 py-1 text-[9px] font-bold leading-relaxed text-amber-300">
            Las puertas solo mandan con reparto <strong>Manual</strong>. Con
            otro reparto se guardan, pero decide el algoritmo.
        </p>

    </section>


    {{-- ================= SALIDAS ================= --}}

    <section class="p-3">

        <div class="flex items-center justify-between gap-2">

            <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-500">
                Salida · quién avanza
            </p>

            <button type="button" @click="newExit = !newExit; editExit = null"
                class="rounded-md border border-slate-700 px-2 py-0.5 text-[10px] font-black text-slate-400 transition hover:border-violet-500 hover:text-violet-400">
                <span x-show="!newExit">+ Puerta</span>
                <span x-show="newExit" x-cloak>Cerrar</span>
            </button>

        </div>


        <div x-show="newExit" x-cloak class="mt-2 rounded-lg border border-violet-500/40 bg-violet-500/5 p-2">
            @include('tournaments.phase-templates.super.group-stage.exit-form', ['exit' => null])
        </div>


        <div class="mt-2 space-y-1.5">

            <template x-for="exit in payload.exits" :key="'xt' + exit.id">
                <div class="rounded-lg border bg-slate-950/40 px-2 py-1.5"
                    :class="exit.status === 'ACTIVE' ? exit.color.border : 'border-slate-800 opacity-50'">

                    <div class="flex items-center gap-1.5">
                        <span class="h-4 w-1 shrink-0 rounded-full" :class="exit.color.dot"></span>

                        <span class="font-mono text-[10px] font-black" :class="exit.color.text"
                            x-text="'#' + exit.number"></span>

                        <span class="min-w-0 flex-1 truncate text-[10px] font-bold text-slate-200"
                            x-text="exit.name"></span>

                        {{--
                            Sin reparto valido no hay pronostico. Se dice con
                            una raya, no con un cero: un cero significa que no
                            sale nadie, que es otra cosa.
                        --}}
                        {{--
                            Cuanta gente sale, contada sobre lo que hay en
                            pantalla. Una raya cuando no se puede calcular:
                            un cero significaria que no sale nadie.
                        --}}
                        <span class="shrink-0 rounded bg-slate-800 px-1 font-mono text-[9px] font-black"
                            :class="emitsOf(exit) ? 'text-slate-300' : 'text-slate-600'"
                            x-text="groups.length ? emitsOf(exit) : '—'"></span>

                        <button type="button"
                            @click="editExit = editExit === exit.id ? null : exit.id; newExit = false; editRule = null; addRuleTo = null"
                            class="shrink-0 text-[10px] text-slate-500 transition hover:text-violet-400"
                            title="Editar la salida">✎</button>

                        <form method="POST" class="shrink-0"
                            :action="@js(route('tournaments.phase-templates.super.exits.destroy', [$phaseTemplate, '__ID__'])).replace('__ID__', exit.id)"
                            @submit="confirm('¿Eliminar esta salida y sus criterios?') || $event.preventDefault()">
                            @csrf
                            @include('tournaments.phase-templates.super.partials.preview-state')
                            @method('DELETE')
                            <button class="text-[10px] text-slate-600 transition hover:text-rose-400">×</button>
                        </form>
                    </div>


                    {{-- EDITAR LA PUERTA --}}

                    <div x-show="editExit === exit.id" x-cloak class="mt-2 border-t border-slate-800 pt-2">
                        <template x-if="editExit === exit.id">
                            <form method="POST" class="space-y-2"
                                :action="@js(route('tournaments.phase-templates.super.exits.update', [$phaseTemplate, '__ID__'])).replace('__ID__', exit.id)">
                                @csrf
                                @method('PUT')

                                {{--
                                    Al editar no se pregunta el criterio: una
                                    puerta puede llevar varios, asi que
                                    cambiar "el" criterio no significaria
                                    nada. Cada uno se edita en su linea.
                                --}}
                                <input type="hidden" name="rule_type" value="REMAINING">

                                <input type="text" name="name" required maxlength="120" :value="exit.name"
                                    class="w-full rounded-md border-slate-700 bg-slate-950 px-2 py-1 text-[11px] font-bold text-slate-100 focus:border-violet-500 focus:ring-violet-500">

                                <div class="grid grid-cols-2 gap-1.5">
                                    <label class="block">
                                        <span class="text-[9px] font-black text-slate-500">Prioridad</span>
                                        <input type="number" name="priority" min="1" max="999" :value="exit.priority ?? 10"
                                            class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1.5 py-0.5 text-center text-[11px] text-slate-100">
                                    </label>

                                    <label class="block">
                                        <span class="text-[9px] font-black text-slate-500">Estado</span>
                                        <select name="status"
                                            class="mt-0.5 w-full rounded-md border-slate-700 bg-slate-950 px-1 py-0.5 text-[11px] text-slate-100">
                                            <option value="ACTIVE" :selected="exit.status === 'ACTIVE'">Activa</option>
                                            <option value="INACTIVE" :selected="exit.status !== 'ACTIVE'">Inactiva</option>
                                        </select>
                                    </label>
                                </div>

                                <button class="w-full rounded-md bg-violet-600 px-3 py-1 text-[10px] font-black text-white transition hover:bg-violet-500">
                                    Guardar salida
                                </button>
                            </form>
                        </template>
                    </div>


                    {{-- LOS CRITERIOS QUE LA CRUZAN --}}

                    <div class="mt-1 space-y-1 pl-2.5">

                        <template x-for="rule in rulesOfExit(exit.id)" :key="'rl' + rule.id">
                            <div>
                                <p class="flex items-center gap-1 text-[9px]">
                                    <span class="min-w-0 flex-1 truncate text-slate-400" x-text="rule.summary"></span>

                                    <template x-if="rule.group_name">
                                        <span class="shrink-0 text-slate-600" x-text="rule.group_name"></span>
                                    </template>

                                    <button type="button"
                                        @click="editRule = editRule === rule.id ? null : rule.id; addRuleTo = null; editExit = null"
                                        class="shrink-0 text-slate-500 transition hover:text-violet-400"
                                        title="Editar el criterio">✎</button>

                                    <form method="POST" class="shrink-0"
                                        :action="@js(route('tournaments.group-stage.advancement-rules.destroy', [$phaseTemplate, '__ID__'])).replace('__ID__', rule.id)"
                                        @submit="confirm('¿Quitar este criterio?') || $event.preventDefault()">
                                        @csrf
                                        @include('tournaments.phase-templates.super.partials.preview-state')
                                        @method('DELETE')
                                        <button class="text-slate-600 transition hover:text-rose-400">×</button>
                                    </form>
                                </p>

                                <div x-show="editRule === rule.id" x-cloak
                                    class="mt-1 rounded-md border border-slate-800 bg-slate-950/60 p-2">
                                    <template x-if="editRule === rule.id">
                                        <form method="POST" class="space-y-2"
                                            :action="@js(route('tournaments.group-stage.advancement-rules.update', [$phaseTemplate, '__ID__'])).replace('__ID__', rule.id)"
                                            x-data="exitCriterionFields(rule.rule_type, {
                                                take: rule.take, from: rule.position_from,
                                                to: rule.position_to, groupId: rule.group_id,
                                            })">
                                            @csrf
                                            @method('PUT')

                                            <input type="hidden" name="phase_exit_id" :value="exit.id">
                                            <input type="hidden" name="status" value="ACTIVE">

                                            @include('tournaments.phase-templates.super.group-stage.criterion-fields', [
                                                'families' => $families,
                                                'rule' => 'alpine',
                                            ])

                                            <button class="w-full rounded-md bg-violet-600 px-3 py-1 text-[10px] font-black text-white transition hover:bg-violet-500">
                                                Guardar criterio
                                            </button>
                                        </form>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <template x-if="rulesOfExit(exit.id).length === 0">
                            <p class="text-[9px] text-amber-400/70">Sin criterio: no la cruza nadie.</p>
                        </template>

                        <button type="button"
                            @click="addRuleTo = addRuleTo === exit.id ? null : exit.id; editRule = null; editExit = null"
                            class="text-[9px] font-black text-violet-400 transition hover:underline">
                            <span x-show="addRuleTo !== exit.id">+ criterio</span>
                            <span x-show="addRuleTo === exit.id" x-cloak>cerrar</span>
                        </button>

                        <div x-show="addRuleTo === exit.id" x-cloak
                            class="rounded-md border border-violet-500/40 bg-violet-500/5 p-2">
                            <template x-if="addRuleTo === exit.id">
                                <form method="POST" class="space-y-2"
                                    action="{{ route('tournaments.group-stage.advancement-rules.store', $phaseTemplate) }}"
                                    x-data="exitCriterionFields('EACH_GROUP_TOP_N')">
                                    @csrf

                                    <input type="hidden" name="phase_exit_id" :value="exit.id">
                                    <input type="hidden" name="status" value="ACTIVE">

                                    @include('tournaments.phase-templates.super.group-stage.criterion-fields', [
                                        'families' => $families,
                                        'rule' => null,
                                    ])

                                    <button class="w-full rounded-md bg-violet-600 px-3 py-1 text-[10px] font-black text-white transition hover:bg-violet-500">
                                        Añadir criterio
                                    </button>
                                </form>
                            </template>
                        </div>

                    </div>

                </div>
            </template>

            <template x-if="payload.exits.length === 0">
                <div x-show="!newExit" class="rounded-lg border border-dashed border-rose-500/40 px-2 py-3 text-center">
                    <p class="text-[9px] leading-relaxed text-rose-300/70">
                        Sin salidas nadie avanza a la siguiente fase.
                    </p>
                </div>
            </template>

        </div>

    </section>

</div>
