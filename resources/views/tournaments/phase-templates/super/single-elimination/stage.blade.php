@php
    /*
     * Escenario central — el cuadro, como árbol.
     *
     * Se lee de izquierda a derecha, que es como avanza la gente:
     *
     *   RONDA 1  →  CUARTOS  →  SEMIS  →  FINAL  →  CAMPEÓN
     *
     * Cada columna es una ronda y lleva su color, así que el borde de un
     * enfrentamiento dice a qué altura del cuadro está. La flecha entre
     * columnas señala hacia dónde avanza el ganador.
     *
     * Un hueco vacío no es un error: es un enfrentamiento que todavía no se
     * ha jugado, y por eso no se sabe quién lo ocupa. Se dibuja con una
     * silueta a rayas.
     *
     * Solo duelos 1 contra 1 en esta versión.
     *
     * Los participantes son caras prestadas de tus universos y tu
     * biblioteca. No son inscritos y no se guardan.
     */
@endphp

<div class="p-3">

    {{-- ============ RESUMEN ============ --}}

    <div class="mb-3 grid grid-cols-2 gap-1.5 sm:grid-cols-3 lg:grid-cols-6">

        <div class="rounded-lg border border-slate-800 bg-slate-900/50 px-2 py-1.5">
            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Compiten</p>
            <p class="font-mono text-lg font-black text-slate-100" x-text="structure.participants ?? '—'"></p>
        </div>

        <div class="rounded-lg border border-slate-800 bg-slate-900/50 px-2 py-1.5">
            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Cuadro</p>
            <p class="font-mono text-lg font-black text-amber-300" x-text="structure.bracket_size || '—'"></p>
        </div>

        <div class="rounded-lg border border-slate-800 bg-slate-900/50 px-2 py-1.5">
            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Rondas</p>
            <p class="font-mono text-lg font-black text-slate-100" x-text="structure.round_count ?? '—'"></p>
        </div>

        <div class="rounded-lg border border-slate-800 bg-slate-900/50 px-2 py-1.5">
            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Duelos</p>
            <p class="font-mono text-lg font-black text-slate-100" x-text="totalPlayable"></p>
        </div>

        <div class="rounded-lg border border-slate-800 bg-slate-900/50 px-2 py-1.5">
            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Directos</p>
            <p class="font-mono text-lg font-black"
                :class="byeCount ? 'text-amber-300' : 'text-slate-600'"
                x-text="byeCount"></p>
        </div>

        <div class="rounded-lg border border-slate-800 bg-slate-900/50 px-2 py-1.5">
            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Jugados</p>
            <p class="font-mono text-lg font-black"
                :class="playedCount ? 'text-emerald-300' : 'text-slate-600'"
                x-text="playedCount"></p>
        </div>

    </div>


    {{-- ============ EL ÁRBOL ============ --}}

    <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900/30">

        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-800 px-3 py-1.5">

            <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-500">
                El cuadro
            </p>

            <div class="flex items-center gap-2">
                <template x-if="hasResults">
                    <span class="rounded bg-amber-500/15 px-1.5 py-0.5 text-[9px] font-black text-amber-300">
                        <span x-text="playedCount"></span>/<span x-text="totalPlayable"></span> jugados
                    </span>
                </template>

                <button type="button" @click="simulateAll()"
                    class="rounded-md bg-amber-500 px-2 py-0.5 text-[10px] font-black text-slate-950 transition hover:bg-amber-400">
                    ⚡ Simular todo
                </button>

                <button type="button" x-show="hasResults" x-cloak @click="clearResults()"
                    class="text-[9px] font-black text-slate-500 transition hover:text-rose-400">
                    limpiar
                </button>
            </div>

        </div>


        <div class="arena-scroll overflow-x-auto p-3">

            <div class="flex min-w-max items-stretch gap-1">

                <template x-for="(round, roundIndex) in rounds" :key="'r' + round.number">
                    <div class="flex items-stretch">

                        {{-- La columna de una ronda --}}

                        <div class="flex w-[132px] shrink-0 flex-col">

                            <div class="mb-1.5 flex items-center gap-1 rounded px-1 py-0.5"
                                :class="round.color.soft">
                                <span class="h-2.5 w-1 rounded-full" :class="round.color.dot"></span>
                                <span class="min-w-0 flex-1 truncate text-[9px] font-black"
                                    :class="round.color.text" x-text="round.label"></span>

                                <button type="button" @click="simulateRound(round)"
                                    class="rounded px-0.5 text-[9px] transition hover:bg-amber-500/20"
                                    :class="round.color.text"
                                    title="Simular esta ronda">⚡</button>
                            </div>

                            {{-- Los enfrentamientos, repartidos a lo alto --}}

                            <div class="flex flex-1 flex-col justify-around gap-1">

                                <template x-for="match in round.matches" :key="'m' + match.index">
                                    @include('tournaments.phase-templates.super.single-elimination.match')
                                </template>

                            </div>

                        </div>


                        {{-- La flecha hacia la ronda siguiente --}}

                        <div class="flex w-4 shrink-0 items-center justify-center"
                            x-show="roundIndex < rounds.length - 1">
                            <span class="text-[10px]" :class="round.color.text">→</span>
                        </div>

                    </div>
                </template>


                {{-- ============ EL CAMPEÓN ============ --}}

                <div class="flex w-[132px] shrink-0 flex-col" x-show="endsWithWinner">

                    <div class="mb-1.5 flex items-center gap-1 rounded bg-amber-500/15 px-1 py-0.5">
                        <span class="text-[9px]">🏆</span>
                        <span class="text-[9px] font-black text-amber-300">Campeón</span>
                    </div>

                    <div class="flex flex-1 items-center">
                        <div class="w-full rounded-lg border p-2 text-center transition"
                            :class="champion
                                ? 'border-amber-400/60 bg-amber-500/10'
                                : 'border-dashed border-slate-700'">

                            <template x-if="champion">
                                <div>
                                    <div class="mx-auto h-10 w-10 overflow-hidden rounded-lg bg-slate-800 ring-2 ring-amber-400">
                                        <template x-if="champion.image_url">
                                            <img :src="champion.image_url" alt="" class="h-full w-full object-cover">
                                        </template>
                                    </div>
                                    <p class="mt-1 truncate text-[10px] font-black text-amber-200"
                                        x-text="champion.short" :title="champion.name"></p>
                                </div>
                            </template>

                            <template x-if="!champion">
                                <p class="py-3 text-[9px] leading-relaxed text-slate-600">
                                    Sin jugar<br>la final
                                </p>
                            </template>

                        </div>
                    </div>

                </div>


                {{--
                    Con varios supervivientes no hay campeón: hay uno por
                    rama.

                    Antes esto era un cartel que decía «salen 4» y ya. Eso es
                    justo lo que confundía: no dice de DÓNDE sale cada uno ni
                    por qué puerta se va, y esas son las dos únicas preguntas
                    que importan aquí. Ahora cada rama tiene su tarjeta, con
                    el que sale de ella y la salida que lo recoge.
                --}}

                <div class="flex w-[164px] shrink-0 flex-col" x-show="!endsWithWinner" x-cloak>

                    <div class="mb-1.5 flex items-center gap-1 rounded bg-teal-500/15 px-1 py-0.5">
                        <span class="text-[9px] font-black text-teal-300">Sobreviven</span>
                        <span class="ml-auto font-mono text-[9px] text-teal-400/70"
                            x-text="targetSurvivors"></span>
                    </div>

                    <div class="flex flex-1 flex-col justify-around gap-1">

                        <template x-for="branch in branches" :key="'br' + branch.number">
                            <div class="rounded-lg border px-1.5 py-1 transition"
                                :class="survivorOfBranch(branch)
                                    ? branch.color.border + ' ' + branch.color.soft
                                    : 'border-dashed border-slate-700'">

                                <div class="flex items-center gap-1">
                                    <span class="flex h-3.5 w-3.5 items-center justify-center rounded text-[8px] font-black text-slate-950"
                                        :class="branch.color.solid" x-text="branch.letter"></span>

                                    <span class="truncate text-[8px] font-black uppercase tracking-wider"
                                        :class="branch.color.text" x-text="branch.label"></span>
                                </div>

                                <div class="mt-1 flex items-center gap-1">
                                    <span class="h-6 w-6 shrink-0 overflow-hidden rounded bg-slate-800">
                                        <template x-if="survivorOfBranch(branch)?.image_url">
                                            <img :src="survivorOfBranch(branch).image_url" alt=""
                                                class="h-full w-full object-cover">
                                        </template>
                                    </span>

                                    <span class="min-w-0 flex-1 truncate text-[9px] font-black"
                                        :class="survivorOfBranch(branch) ? 'text-slate-100' : 'text-slate-600'"
                                        x-text="survivorOfBranch(branch)?.short ?? '· · ·'"></span>
                                </div>

                                {{-- Por qué puerta se va --}}

                                <template x-if="exitOfBranch(branch.number)">
                                    <p class="mt-1 flex items-center gap-1 truncate rounded px-1 py-0.5 text-[8px] font-bold"
                                        :class="exitOfBranch(branch.number).color.soft + ' ' + exitOfBranch(branch.number).color.text">
                                        <span>↦</span>
                                        <span class="truncate" x-text="exitOfBranch(branch.number).name"></span>
                                    </p>
                                </template>

                                <template x-if="!exitOfBranch(branch.number)">
                                    <p class="mt-1 truncate text-[8px] text-slate-600">
                                        sin puerta propia
                                    </p>
                                </template>

                            </div>
                        </template>

                        <template x-if="branches.length === 0">
                            <p class="rounded-lg border border-dashed border-teal-400/40 p-2 text-center text-[9px] leading-relaxed text-teal-200/70">
                                <span class="block font-mono text-base font-black" x-text="targetSurvivors"></span>
                                salen sin<br>jugar la final
                            </p>
                        </template>

                    </div>

                </div>

            </div>


            {{-- ============ CUADROS DE CLASIFICACIÓN ============ --}}

            {{--
                Lo que se juega para separar a los que el cuadro dejó
                empatados. El partido por el tercer puesto es uno de estos,
                el de dos; con cuatro o con ocho hace falta más de una ronda,
                porque después de la primera hay que ordenar tanto a los que
                ganaron como a los que perdieron.
            --}}

            <template x-for="bracket in placementBrackets" :key="'pb' + bracket.key">
                <div class="mt-3 border-t border-dashed border-slate-800 pt-3">

                    <div class="mb-1.5 flex items-center gap-1.5">
                        <span class="text-[9px]">🎖</span>

                        <span class="text-[9px] font-black uppercase tracking-wider text-orange-300"
                            x-text="bracket.label"></span>

                        <span class="font-mono text-[8px] text-slate-600"
                            x-text="'puestos ' + bracket.from + '–' + bracket.to"></span>

                        <button type="button" @click="simulateBracket(bracket)"
                            class="ml-auto rounded px-1.5 py-0.5 text-[9px] font-black text-orange-300 transition hover:bg-amber-500/20">
                            ⚡ jugar
                        </button>
                    </div>

                    <div class="arena-scroll overflow-x-auto">
                        <div class="flex min-w-max items-stretch gap-1">

                            <template x-for="(round, i) in bracket.rounds" :key="'pbr' + bracket.key + round.number">
                                <div class="flex items-stretch">

                                    <div class="flex w-[132px] shrink-0 flex-col">

                                        <div class="mb-1.5 flex items-center gap-1 rounded bg-orange-500/10 px-1 py-0.5">
                                            <span class="h-2.5 w-1 rounded-full bg-orange-400"></span>
                                            <span class="min-w-0 flex-1 truncate text-[9px] font-black text-orange-300"
                                                x-text="round.label"></span>
                                        </div>

                                        <div class="flex flex-1 flex-col justify-around gap-1">
                                            <template x-for="match in round.matches" :key="'pbm' + match.index">
                                                <div>
                                                    @include('tournaments.phase-templates.super.single-elimination.match', [
                                                        'roundColor' => 'orange',
                                                    ])

                                                    {{-- Qué puesto reparte este duelo --}}

                                                    <template x-if="match.awards">
                                                        <p class="mt-0.5 text-center font-mono text-[8px] text-slate-600">
                                                            gana <span class="text-emerald-400" x-text="match.awards.win + 'º'"></span>
                                                            · pierde <span x-text="match.awards.lose + 'º'"></span>
                                                        </p>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>

                                    </div>

                                    <div class="flex w-4 shrink-0 items-center justify-center"
                                        x-show="i < bracket.rounds.length - 1">
                                        <span class="text-[10px] text-orange-400/60">→</span>
                                    </div>

                                </div>
                            </template>

                        </div>
                    </div>

                </div>
            </template>


            {{-- ============ LOS QUE QUEDAN EMPATADOS ============ --}}

            {{--
                Decirlo es más honesto que repartir puestos a dedo: los
                cuatro que caen en cuartos son cuartofinalistas, no un
                quinto, un sexto, un séptimo y un octavo. Si hacen falta
                separados, se marca su grupo en la izquierda.
            --}}

            <template x-if="tiedGroups.length">
                <div class="mt-3 space-y-1.5">

                    <template x-for="group in tiedGroups" :key="'tg' + group.key">
                        <div class="rounded-lg border border-dashed border-slate-700 px-3 py-2">

                            <div class="flex flex-wrap items-center gap-1.5">

                                <span class="text-[9px] font-black text-slate-400">
                                    Empatados en
                                    <span x-text="group.from === group.to
                                        ? ('el puesto ' + group.from)
                                        : ('los puestos ' + group.from + '–' + group.to)"></span>:
                                </span>

                                <template x-for="p in group.members" :key="'tgm' + group.key + p.index">
                                    <span class="inline-flex items-center gap-1 rounded bg-slate-900 px-1 py-0.5">
                                        <span class="h-3.5 w-3.5 overflow-hidden rounded-sm bg-slate-800">
                                            <template x-if="p.image_url">
                                                <img :src="p.image_url" alt="" class="h-full w-full object-cover">
                                            </template>
                                        </span>
                                        <span class="text-[9px] text-slate-300" x-text="p.short"></span>
                                    </span>
                                </template>

                                {{--
                                    En la ficha no hay boton de guardar, asi
                                    que separarlos ahi cambiaria el preview
                                    sin guardar nada.
                                --}}
                                <button type="button" x-show="!readonly" @click="togglePlacement(group.key)"
                                    class="ml-auto rounded border border-slate-700 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-slate-400 transition hover:border-amber-500 hover:text-amber-300">
                                    separarlos · +<span x-text="group.cost"></span>
                                </button>

                            </div>

                        </div>
                    </template>

                </div>
            </template>

        </div>

    </div>
{{-- ============ SALIDAS ============ --}}

    <div class="mt-3 overflow-hidden rounded-xl border border-slate-800 bg-slate-900/30">

        <div class="flex items-center justify-between gap-2 border-b border-slate-800 px-3 py-1.5">
            <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-500">
                ▲ Salen de la fase
            </p>
            <p class="text-[9px] text-slate-600">
                <span x-show="!hasResults">se resuelven al jugar</span>
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

                    <span class="shrink-0 text-[9px] text-slate-600" x-text="exit.summary"></span>

                    <div class="flex flex-wrap items-center gap-1">
                        <template x-for="member in membersOfExit(exit)" :key="'exm' + exit.id + '-' + member.seed">
                            <span class="flex items-center gap-0.5 rounded bg-slate-900 px-1 py-0.5"
                                :title="atSeed(member.seed)?.name + ' · ' + member.position + 'º'">
                                <span class="h-3.5 w-3.5 overflow-hidden rounded-sm bg-slate-800 ring-1"
                                    :class="exit.color.ring">
                                    <template x-if="atSeed(member.seed)?.image_url">
                                        <img :src="atSeed(member.seed).image_url" alt=""
                                            class="h-full w-full object-cover">
                                    </template>
                                </span>
                                <span class="font-mono text-[8px]" :class="exit.color.text"
                                    x-text="member.position + 'º'"></span>
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


    {{-- ============ SIEMBRA POR RANKING ============ --}}

    <div x-show="showsRanking" x-cloak
        class="mt-3 overflow-hidden rounded-xl border border-slate-800 bg-slate-900/30">

        <div class="border-b border-slate-800 px-3 py-1.5">
            <p class="text-[9px] font-black uppercase tracking-[0.16em] text-slate-500">
                Con qué clasificación se siembra
            </p>
        </div>

        <div class="p-3">

            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($payload['catalog']['ranking_sources'] as $key => $source)
                    <button type="button" @click="rankingSource = @js($key)"
                        class="rounded-lg border p-2 text-left transition"
                        :class="rankingSource === @js($key)
                            ? 'border-amber-500 bg-amber-500/10'
                            : 'border-slate-700 hover:border-slate-600'">

                        <span class="flex items-center gap-1.5">
                            <span class="h-1.5 w-1.5 rounded-full"
                                :class="rankingSource === @js($key) ? 'bg-amber-400' : 'bg-slate-700'"></span>
                            <span class="text-[10px] font-black"
                                :class="rankingSource === @js($key) ? 'text-amber-300' : 'text-slate-300'">
                                {{ $source['label'] }}
                            </span>
                        </span>

                        <span class="mt-0.5 block pl-3 text-[9px] leading-tight text-slate-500">
                            {{ $source['hint'] }}
                        </span>
                    </button>
                @endforeach
            </div>

            <div class="arena-scroll mt-2 flex gap-1 overflow-x-auto pb-1">
                <template x-for="seed in castSize" :key="'rk' + seed">
                    <div class="flex w-12 shrink-0 flex-col items-center gap-0.5 rounded-md bg-slate-900 px-1 py-1">
                        <span class="font-mono text-[9px] font-black text-amber-400" x-text="'#' + seed"></span>

                        <div class="h-7 w-7 overflow-hidden rounded bg-slate-800">
                            <template x-if="atSeed(seed)?.image_url">
                                <img :src="atSeed(seed).image_url" alt="" class="h-full w-full object-cover">
                            </template>
                        </div>

                        <span class="w-full truncate text-center text-[8px] text-slate-500"
                            x-text="atSeed(seed)?.short"></span>
                    </div>
                </template>
            </div>

            <p class="mt-1 text-[9px] leading-relaxed text-slate-600">
                Esta siembra es una demostración. Una fase vive en tu biblioteca
                y no pertenece a ningún universo ni torneo, así que la
                clasificación real no existe hasta que un torneo la use: lo que
                se guarda es <strong class="text-slate-500">cuál de las dos
                fuentes</strong> leer entonces.
            </p>

        </div>

    </div>

</div>
