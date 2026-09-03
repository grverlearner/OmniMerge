@php
    /*
     * Escenario central — cómo funciona la fase, de un vistazo.
     *
     * Se lee de abajo arriba, que es como fluye la gente:
     *
     *   ENTRANTES   la fila de abajo, en su orden de llegada, con el color
     *               del grupo al que van y la puerta que los manda
     *          ↓
     *   GRUPOS      las tablas, una por grupo, con su color
     *          ↓
     *   SALIDAS     la franja de arriba: quién avanza y por qué puerta
     *
     * El color hace todo el trabajo: el mismo tono acompaña a una persona
     * desde su casilla de entrada, a su grupo, a su jornada y a su salida.
     * Se puede seguir con la vista sin leer un número.
     *
     * Los participantes son caras prestadas de tus universos y tu
     * biblioteca. No son inscritos y no se guardan.
     */
@endphp

<div class="p-3">

    {{-- ============================================================= --}}
    {{-- EL ORDEN GENERAL DE LA FASE --}}
    {{-- ============================================================= --}}

    {{--
        Va arriba del todo porque es la pregunta que se hace cualquiera al
        mirar una fase de grupos: «entonces, ¿quién va primero?».

        Las tablas de abajo dicen quién manda EN SU GRUPO. Esto dice quién
        manda en la fase, que no es lo mismo y depende del modo elegido en el
        panel de la izquierda. Cambiarlo ahí reordena esta lista al momento.
    --}}

    <div class="mb-3 overflow-hidden rounded-xl border border-cyan-500/30 bg-slate-900/30">

        <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-3 py-1.5">

            <p class="text-[9px] font-black uppercase tracking-[0.16em] text-cyan-400">
                ≡ Orden general
            </p>

            <span class="rounded bg-slate-800 px-1.5 text-[9px] font-black text-slate-300"
                x-text="overallModes[overallMode]?.short ?? overallMode"></span>

            <p class="ml-auto text-[9px] text-slate-600">
                <span x-show="!hasResults">previsión sobre la parrilla</span>
                <span x-show="hasResults" x-cloak>según lo simulado</span>
            </p>

        </div>

        <template x-if="!overallRanking.length">
            <p class="px-3 py-3 text-[10px] text-slate-600">
                Todavía no hay grupos formados: en cuanto los haya, aquí sale la
                lista completa de la fase.
            </p>
        </template>

        <div class="flex flex-wrap gap-1 p-2">

            <template x-for="fila in overallRanking" :key="'ov' + fila.seed">
                <span class="flex items-center gap-1 rounded-lg border bg-slate-950/70 py-0.5 pl-1 pr-1.5"
                    :class="fila.group.color.border"
                    :title="atSeed(fila.seed)?.name
                        + ' · ' + fila.group.name
                        + ' · ' + fila.group_position + 'º de su grupo'
                        + ' · ' + (fila.POINTS ?? 0) + ' pts'">

                    {{-- El puesto general, que es lo que se viene a leer --}}
                    <span class="w-4 shrink-0 text-center font-mono text-[9px] font-black"
                        :class="fila.overall_position <= 3 ? 'text-cyan-300' : 'text-slate-500'"
                        x-text="fila.overall_position"></span>

                    <span class="h-4 w-4 shrink-0 overflow-hidden rounded-sm bg-slate-800 ring-1"
                        :class="fila.group.color.ring">
                        <template x-if="atSeed(fila.seed)?.image_url">
                            <img :src="atSeed(fila.seed).image_url" alt=""
                                class="h-full w-full object-cover">
                        </template>
                    </span>

                    <span class="max-w-[92px] truncate text-[9px] font-bold text-slate-200"
                        x-text="atSeed(fila.seed)?.name ?? ('#' + fila.seed)"></span>

                    {{-- De qué grupo viene y en qué puesto quedó ahí --}}
                    <span class="font-mono text-[8px]" :class="fila.group.color.text"
                        x-text="fila.group.name.replace('Grupo ', '').slice(0, 1) + fila.group_position"></span>
                </span>
            </template>

        </div>

    </div>


    {{-- ============ SALIDAS: QUIÉN AVANZA ============ --}}

    <div class="mb-3 overflow-hidden rounded-xl border border-slate-800 bg-slate-900/30">

        <div class="flex items-center justify-between gap-2 border-b border-slate-800 px-3 py-1.5">

            <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-500">
                ▲ Salen de la fase
            </p>

            <p class="text-[9px] text-slate-600">
                <span x-show="!hasResults">previsión sobre la parrilla</span>
                <span x-show="hasResults" x-cloak>según lo simulado</span>
            </p>

        </div>

        <div class="space-y-1.5 p-2">

            <template x-for="exit in activeExits" :key="'ex' + exit.id">
                <div class="flex flex-wrap items-center gap-1.5 rounded-lg border px-2 py-1.5"
                    :class="exit.color.border">

                    <span class="h-4 w-1 shrink-0 rounded-full" :class="exit.color.dot"></span>

                    <span class="shrink-0 text-[10px] font-black" :class="exit.color.text"
                        x-text="exit.name"></span>

                    <span class="shrink-0 rounded bg-slate-800 px-1.5 text-[9px] font-black"
                        :class="emitsOf(exit) ? 'text-slate-300' : 'text-slate-600'"
                        x-text="groups.length ? emitsOf(exit) : '—'"></span>

                    {{-- Quiénes son, con cara --}}
                    <div class="flex flex-wrap items-center gap-1">
                        <template x-for="member in membersOfExit(exit)" :key="'exm' + exit.id + '-' + member.seed">
                            <span class="flex items-center gap-0.5 rounded bg-slate-900 px-1 py-0.5"
                                :title="atSeed(member.seed)?.name + ' · ' + member.group.name + ' · ' + member.position + 'º'">
                                <span class="h-3.5 w-3.5 overflow-hidden rounded-sm bg-slate-800 ring-1"
                                    :class="member.group.color.ring">
                                    <template x-if="atSeed(member.seed)?.image_url">
                                        <img :src="atSeed(member.seed).image_url" alt=""
                                            class="h-full w-full object-cover">
                                    </template>
                                </span>
                                <span class="font-mono text-[8px]" :class="member.group.color.text"
                                    x-text="member.group.name.replace('Grupo ', '').slice(0,1) + member.position"></span>
                            </span>
                        </template>
                    </div>

                </div>
            </template>

            <template x-if="activeExits.length === 0">
                <p class="px-2 py-2 text-center text-[9px] text-rose-300/70">
                    Sin puertas de salida nadie avanza a la siguiente fase.
                </p>
            </template>

        </div>

    </div>


    {{-- ============ RESUMEN ============ --}}

    <div class="mb-3 grid grid-cols-2 gap-1.5 sm:grid-cols-4 lg:grid-cols-5">

        <div class="rounded-lg border border-slate-800 bg-slate-900/50 px-2 py-1.5">
            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Compiten</p>
            <p class="font-mono text-lg font-black text-slate-100" x-text="structure.participants ?? '—'"></p>
        </div>

        <div class="rounded-lg border border-slate-800 bg-slate-900/50 px-2 py-1.5">
            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Grupos</p>
            <p class="font-mono text-lg font-black text-amber-300" x-text="structure.groups_count ?? '—'"></p>
        </div>

        <div class="rounded-lg border border-slate-800 bg-slate-900/50 px-2 py-1.5">
            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Por grupo</p>
            <p class="font-mono text-lg font-black"
                :class="structure.uneven ? 'text-amber-300' : 'text-slate-100'"
                x-text="structure.uneven
                    ? structure.min_size + '–' + structure.max_size
                    : (structure.min_size ?? '—')"></p>
        </div>

        <div class="rounded-lg border border-slate-800 bg-slate-900/50 px-2 py-1.5">
            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Jornadas</p>
            <p class="font-mono text-lg font-black text-slate-100" x-text="roundLimit ?? '—'"></p>
        </div>

        <div class="rounded-lg border border-slate-800 bg-slate-900/50 px-2 py-1.5">
            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Enfrentam.</p>
            <p class="font-mono text-lg font-black text-slate-100" x-text="totalPlayable"></p>
        </div>

    </div>


    {{-- ============ LOS GRUPOS ============ --}}

    <div class="grid gap-2 sm:grid-cols-2 2xl:grid-cols-3">

        <template x-for="group in groups" :key="'g' + group.index">
            <div class="overflow-hidden rounded-xl border bg-slate-900/30"
                :class="group.color.border">

                {{-- Cabecera del grupo --}}

                <div class="flex items-center gap-1.5 border-b border-slate-800 px-2 py-1.5"
                    :class="group.color.soft">

                    <span class="flex h-5 w-5 items-center justify-center rounded text-[10px] font-black text-slate-950"
                        :class="group.color.solid"
                        x-text="group.name.replace('Grupo ', '').slice(0, 2)"></span>

                    <span class="min-w-0 flex-1 truncate text-[10px] font-black text-slate-200"
                        x-text="group.name"></span>

                    <span class="font-mono text-[9px] text-slate-500"
                        x-text="group.size + 'p'"></span>

                    <template x-if="group.has_custom_cycles">
                        <span class="rounded bg-slate-800 px-1 font-mono text-[8px] font-black text-slate-300"
                            :title="'Vueltas propias: ' + group.cycles"
                            x-text="'×' + group.cycles"></span>
                    </template>

                    <button type="button" @click="simulateGroup(group)"
                        class="rounded px-1 text-[10px] transition hover:bg-amber-500/20"
                        :class="group.color.text"
                        title="Simular este grupo entero">⚡</button>

                </div>


                {{-- Tabla --}}

                <table class="w-full border-collapse">

                    <thead>
                        <tr class="text-[8px] font-black uppercase tracking-wider text-slate-600">
                            <th class="py-1 pl-2 pr-1 text-left">#</th>
                            <th class="px-1 py-1 text-left">Competidor</th>
                            @foreach ($payload['catalog']['standings_columns'] as $key => $label)
                                <th class="px-1 py-1 text-center
                                    @if ($key === 'POINTS') text-violet-500
                                    @elseif ($key === 'SCORE_DIFFERENCE') text-slate-500 @endif">{{ $label }}</th>
                            @endforeach
                            <th class="py-1 pl-1 pr-2 text-right">→</th>
                        </tr>
                    </thead>

                    <tbody>
                        <template x-for="(row, position) in standingsOf(group)" :key="'r' + row.seed">
                            {{--
                                El fondo dice por qué puerta sale ese puesto.

                                Casi invisible mientras no se ha jugado nada: lo
                                que se ve entonces es una previsión sobre la
                                parrilla, no un resultado. En cuanto hay
                                marcadores sube a su tono, y así simular se nota.
                            --}}
                            <tr class="border-t border-slate-800/60 transition-colors"
                                :class="exitOfGroupPosition(group, position + 1)
                                    ? (hasResults
                                        ? exitOfGroupPosition(group, position + 1).color.soft
                                        : exitOfGroupPosition(group, position + 1).color.wash)
                                    : ''">

                                {{-- Puesto --}}
                                <td class="py-0.5 pl-2 pr-1">
                                    <span class="flex h-4 w-4 items-center justify-center rounded font-mono text-[9px] font-black"
                                        :class="position === 0 ? 'bg-amber-400 text-slate-950'
                                            : position === 1 ? 'bg-slate-400 text-slate-950'
                                            : 'text-slate-500'"
                                        x-text="position + 1"></span>
                                </td>

                                {{-- Competidor --}}
                                <td class="px-1 py-0.5">
                                    <div class="flex items-center gap-1">
                                        <span class="h-4 w-4 shrink-0 overflow-hidden rounded-sm bg-slate-800 ring-1"
                                            :class="group.color.ring">
                                            <template x-if="atSeed(row.seed)?.image_url">
                                                <img :src="atSeed(row.seed).image_url" alt=""
                                                    class="h-full w-full object-cover">
                                            </template>
                                        </span>

                                        <span class="truncate text-[10px] font-bold text-slate-200"
                                            x-text="atSeed(row.seed)?.short"
                                            :title="atSeed(row.seed)?.name + ' · entrante ' + row.seed"></span>

                                        <span class="font-mono text-[8px] text-slate-700"
                                            x-text="row.seed"></span>
                                    </div>
                                </td>

                                {{-- Números --}}
                                @foreach ($payload['catalog']['standings_columns'] as $key => $label)
                                    <td class="px-1 py-0.5 text-center font-mono text-[9px]
                                        @if ($key === 'POINTS') font-black text-violet-300 @endif"
                                        @if ($key !== 'POINTS')
                                            :class="hasResults ? 'text-slate-400' : 'text-slate-700'"
                                        @endif>
                                        <span x-text="'{{ $key }}' === 'SCORE_DIFFERENCE' && row.SCORE_DIFFERENCE > 0
                                            ? '+' + row.SCORE_DIFFERENCE
                                            : row['{{ $key }}']"></span>
                                    </td>
                                @endforeach

                                {{-- Salida --}}
                                <td class="py-0.5 pl-1 pr-2 text-right">
                                    <template x-if="exitOfGroupPosition(group, position + 1)">
                                        <span class="inline-block h-3 w-1.5 rounded-full"
                                            :class="exitOfGroupPosition(group, position + 1).color.dot"
                                            :title="exitOfGroupPosition(group, position + 1).name"></span>
                                    </template>
                                </td>

                            </tr>
                        </template>
                    </tbody>

                </table>


                {{-- Pie del grupo --}}

                <div class="flex items-center justify-between gap-2 border-t border-slate-800 px-2 py-1">
                    <span class="font-mono text-[8px] text-slate-600"
                        x-text="groupPlayedCount(group) + '/' + group.rounds
                            .filter(r => r.number <= (roundLimit ?? maxRounds))
                            .reduce((s, r) => s + r.pairings.length, 0) + ' jugados'"></span>

                    <span class="font-mono text-[8px] text-slate-600"
                        x-text="group.total_rounds + ' jornadas'"></span>
                </div>

            </div>
        </template>

        <template x-if="groups.length === 0">
            <div class="col-span-full rounded-xl border border-dashed p-6 text-center"
                :class="customNeedsSetup ? 'border-amber-500/40 bg-amber-500/5' : 'border-slate-700'">

                <div class="text-3xl opacity-30" x-text="customNeedsSetup ? '◫' : '⚠'"></div>

                <p class="mt-2 text-[11px] font-black"
                    :class="customNeedsSetup ? 'text-amber-200' : 'text-slate-400'"
                    x-text="customNeedsSetup
                        ? 'Los grupos personalizados aún no tienen cupo'
                        : 'No se pueden formar grupos con esta configuración'"></p>

                <p class="mx-auto mt-1.5 max-w-md text-[10px] leading-relaxed text-slate-500">
                    <span x-show="customNeedsSetup">
                        Cada grupo necesita saber cuánta gente admite. Ponle su
                        cupo en el panel derecho, o adopta ahí mismo el reparto
                        que había antes.
                    </span>
                    <span x-show="!customNeedsSetup">
                        Revisa el panel izquierdo: el diagnóstico de arriba dice
                        exactamente qué falla.
                    </span>
                </p>

            </div>
        </template>

    </div>


    {{-- ============ ENTRANTES ============ --}}

    <div class="mt-3 overflow-hidden rounded-xl border border-slate-800 bg-slate-900/30">

        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-800 px-3 py-1.5">

            <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-500">
                ▼ Entran a la fase
            </p>

            <p class="text-[9px] text-slate-600">
                en su orden de llegada · el color es el grupo que les toca
            </p>

        </div>

        <div class="arena-scroll flex gap-1 overflow-x-auto p-2">

            <template x-for="entrant in entrants()" :key="'in' + entrant">
                <div class="flex w-11 shrink-0 flex-col items-center gap-0.5 rounded-md px-1 py-1"
                    :class="groupOfSeed(entrant) ? groupOfSeed(entrant).color.soft : 'bg-slate-900'">

                    {{-- Número de llegada --}}
                    <span class="font-mono text-[9px] font-black"
                        :class="groupOfSeed(entrant) ? groupOfSeed(entrant).color.text : 'text-slate-500'"
                        x-text="entrant"></span>

                    <div class="h-7 w-7 overflow-hidden rounded bg-slate-800 ring-1"
                        :class="groupOfSeed(entrant) ? groupOfSeed(entrant).color.ring : 'ring-slate-700'">
                        <template x-if="atSeed(entrant)?.image_url">
                            <img :src="atSeed(entrant).image_url" alt="" class="h-full w-full object-cover">
                        </template>
                    </div>

                    <span class="w-full truncate text-center text-[8px] text-slate-400"
                        x-text="atSeed(entrant)?.short"
                        :title="atSeed(entrant)?.name"></span>

                    {{-- A qué grupo va --}}
                    <span class="w-full truncate text-center font-mono text-[8px] font-black"
                        :class="groupOfSeed(entrant) ? groupOfSeed(entrant).color.text : 'text-slate-700'"
                        x-text="groupOfSeed(entrant)?.name.replace('Grupo ', '') ?? '—'"></span>

                    {{-- Por qué puerta entra --}}
                    <template x-if="gateOfEntrant(entrant)">
                        <span class="w-full rounded-sm text-center font-mono text-[7px] font-black text-slate-950"
                            :class="gateOfEntrant(entrant).color.solid ?? 'bg-slate-600'"
                            :title="gateOfEntrant(entrant).name"
                            x-text="'P' + gateOfEntrant(entrant).number"></span>
                    </template>

                </div>
            </template>

        </div>

        {{-- Cuando una puerta pide un grupo y el reparto la lleva a otro --}}

        <template x-if="gateConflicts.length">
            <div class="border-t border-rose-500/30 bg-rose-500/5 px-3 py-1.5">
                <p class="text-[9px] font-bold leading-relaxed text-rose-300">
                    <span x-text="gateConflicts.length"></span>
                    entrantes van a un grupo distinto del que pide su puerta.
                    Manda el reparto de arriba: pon el reparto en
                    <strong>Manual</strong> para que decidan las puertas.
                </p>
            </div>
        </template>

    </div>

</div>
