@php
    /*
     * 07 · TROFEOS Y PREMIOS — qué se lleva quien gane esta edición.
     *
     * Dos capas, y no se mezclan:
     *
     *   los del TORNEO       los hereda toda edición. Se ven, no se tocan:
     *                        corregirlos desde aquí cambiaría también las
     *                        ediciones ya jugadas que ya los entregaron.
     *   los de ESTA edición  se crean, se corrigen y se retiran aquí.
     *
     * Y un premio de edición puede colgar de una FASE —«quien gane los
     * grupos se lleva esto»—, que es algo que un premio de torneo no sabe
     * decir, porque el torneo no sabe con qué plantilla se jugará cada año.
     */

    $triggers = \App\Models\TournamentInstanceReward::TRIGGERS;
    $operations = \App\Models\TournamentInstanceReward::OPERATIONS;
    $tiers = \App\Models\UniverseTrophy::TIERS;
@endphp

<section x-show="isOpen('prizes')" x-cloak
    class="mb-3 overflow-hidden rounded-2xl border border-violet-500/30 bg-slate-900/50">

    <div class="flex items-center gap-2 border-b border-slate-800 bg-violet-500/10 px-4 py-2">
        <span class="font-mono text-[9px] text-slate-600">07</span>
        <span class="text-[11px]">🏆</span>
        <h2 class="text-[11px] font-black uppercase tracking-wider text-violet-300">Trofeos y premios</h2>
        <span class="ml-auto text-[10px] text-slate-600">Qué se lleva quien gane esta edición</span>
    </div>

    <div class="space-y-3 p-4"
        x-data="competitionPrizes({
            sharedTrophies: @js($sharedTrophies),
            ownTrophies: @js($ownTrophies),
            inheritedRewards: @js($inheritedRewards),
            ownRewards: @js($ownRewards),
            competitionId: @js($competition?->id),
            storeUrl: @js(route('universes.trophies.store', $universe)),
            updateUrlTemplate: @js(route('universes.trophies.update', [$universe, '__ID__'])),
            csrf: @js(csrf_token()),
        })"
        {{--
            gameStats y phases son propiedades, no getters: el `this` de un
            método declarado en un x-data anidado NO alcanza el padre,
            aunque las expresiones sí encadenen scopes. x-effect las baja, y
            además las vuelve a bajar al cambiar de juego o de forma.
        --}}
        x-effect="gameStats = game?.stats ?? []; phases = templatePhases"
        {{--
            Y al revés: el bloque de fases necesita saber qué premios cuelgan
            de cada una, y desde el padre no se puede llamar a un método de
            un x-data anidado. Así que el hijo se publica.
        --}}
        x-init="prizes = $data">


        {{-- ==================== LO QUE YA DA EL TORNEO ==================== --}}

        {{--
            Se enseña SIEMPRE, también vacío.

            Antes solo aparecía si el torneo tenía premios, así que al abrir
            esta pantalla no se veía nada y parecía que lo configurado
            arriba se había perdido. Un bloque vacío que explica por qué
            está vacío dice más que ningún bloque.
        --}}

        <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-3">

            <div class="flex flex-wrap items-center gap-2">
                <span class="text-[11px]">🏛</span>

                <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                    Lo que da «{{ $universeTournament->name }}» en todas sus ediciones
                </p>

                <span class="font-mono text-[9px] text-slate-600">{{ count($inheritedRewards) }}</span>

                <a href="{{ route('universes.tournaments.edit', [$universe, $universeTournament]) }}"
                    class="ml-auto rounded-lg border border-slate-700 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-slate-500 hover:text-slate-100">
                    configurarlos en el torneo →
                </a>
            </div>

            @if (count($inheritedRewards))
                <p class="mt-0.5 text-[10px] leading-relaxed text-slate-600">
                    Estos se entregan también en esta edición. No se editan desde aquí:
                    cambiarlos afectaría a las ediciones que ya se jugaron y ya los dieron.
                </p>

                <div class="mt-2 grid gap-1 sm:grid-cols-2">
                    @foreach ($inheritedRewards as $r)
                        <div class="flex items-center gap-2 rounded-lg bg-slate-900/60 px-2 py-1.5">

                            <span class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-950 text-sm">
                                @if ($r['trophy']['image_url'] ?? null)
                                    <img src="{{ $r['trophy']['image_url'] }}" alt="" class="h-full w-full object-cover">
                                @else
                                    {{ $r['trophy']['icon'] ?? '◆' }}
                                @endif
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[10px] font-black text-slate-300">
                                    {{ $r['label'] ?: 'Sin nombre' }}
                                </span>
                                <span class="block truncate text-[9px] text-slate-600">
                                    {{ $triggers[$r['trigger']] ?? $r['trigger'] }}{{ $r['threshold'] ? ' · ' . $r['threshold'] : '' }}
                                    @if ($r['trophy'] ?? null)
                                        · {{ $r['trophy']['name'] }}
                                    @endif
                                    @if ($r['stat_key'])
                                        · {{ $r['operation'] === 'SUBTRACT' ? '−' : '+' }}{{ rtrim(rtrim(number_format((float) $r['amount'], 2, ',', ''), '0'), ',') }} {{ $r['stat_key'] }}
                                    @endif
                                </span>
                            </span>

                            <span class="shrink-0 rounded bg-slate-950 px-1.5 py-0.5 text-[8px] font-black text-slate-600">
                                heredado
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mt-1.5 rounded-lg border border-dashed border-slate-700 px-3 py-3 text-center text-[10px] leading-relaxed text-slate-600">
                    Este torneo no da ningún premio todavía, así que esta edición no
                    hereda nada. Lo que configures ahí abajo será
                    <span class="font-bold text-slate-500">solo de esta edición</span>;
                    para que lo reciban todas, ponlo en el torneo.
                </p>
            @endif
        </div>


        {{-- ==================== LOS TROFEOS DE ESTA EDICIÓN ==================== --}}

        <div>
            <div class="flex flex-wrap items-center gap-2">
                <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                    Trofeos que entrega esta edición
                </p>

                <span class="font-mono text-[9px] text-slate-600" x-text="givenTrophies.length"></span>

                <button type="button" @click="pickerOpen = !pickerOpen"
                    x-show="availableTrophies.length"
                    class="ml-auto rounded-lg border border-slate-700 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-violet-500 hover:text-violet-300">
                    elegir de los <span x-text="availableTrophies.length"></span> que hay
                </button>

                <button type="button" @click="openTrophy(null)"
                    class="rounded-lg border border-violet-500/40 px-2 py-1 text-[10px] font-black text-violet-300 transition hover:bg-violet-500/20">
                    + crear uno para esta edición
                </button>
            </div>

            <div class="mt-2 grid gap-1.5 sm:grid-cols-2 lg:grid-cols-3">

                <template x-for="tr in givenTrophies" :key="'gt' + tr.id">
                    <div class="flex items-center gap-2 rounded-xl border p-2"
                        :class="tr.inherited
                            ? 'border-slate-700 bg-slate-900/60'
                            : (isOwnTrophy(tr)
                                ? 'border-violet-500/30 bg-violet-500/5'
                                : 'border-slate-800 bg-slate-950/60')">

                        <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-950 text-xl">
                            <template x-if="tr.image_url">
                                <img :src="tr.image_url" alt="" class="h-full w-full object-cover">
                            </template>
                            <template x-if="!tr.image_url">
                                <span x-text="tr.icon || '🏆'"></span>
                            </template>
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[11px] font-black text-slate-100" x-text="tr.name"></span>
                            {{--
                                De dónde viene. Un trofeo heredado y uno
                                inventado aquí se ven igual, y saber cuál es
                                cuál es lo que decide si se puede tocar.
                            --}}
                            <span class="block truncate text-[9px]"
                                :class="tr.inherited
                                    ? 'text-slate-500'
                                    : (isOwnTrophy(tr) ? 'text-violet-400' : 'text-slate-600')"
                                x-text="trophyOrigin(tr)
                                    + (tr.inherited ? '' : ' · lo entregan ' + rewardsWithTrophy(tr.id) + ' premios')"></span>
                        </span>

                        {{--
                            Solo los propios llevan lápiz. Uno del universo
                            lo comparten todos los torneos: corregirlo desde
                            una edición sería tocar lo de todos.
                        --}}
                        <template x-if="isOwnTrophy(tr)">
                            <button type="button" @click="openTrophy(tr)"
                                class="shrink-0 px-1 text-[11px] text-slate-500 transition hover:text-violet-300"
                                title="Editar este trofeo">✎</button>
                        </template>

                        <template x-if="tr.inherited">
                            <span class="shrink-0 rounded bg-slate-950 px-1.5 py-0.5 text-[8px] font-black text-slate-600">
                                del torneo
                            </span>
                        </template>
                    </div>
                </template>

                <template x-if="givenTrophies.length === 0">
                    <p class="rounded-xl border border-dashed border-slate-700 px-3 py-4 text-center text-[10px] leading-relaxed text-slate-600 sm:col-span-2 lg:col-span-3">
                        Esta edición no entrega ningún trofeo propio todavía.
                    </p>
                </template>
            </div>


            {{-- El catálogo, solo cuando se pide --}}

            <div x-show="pickerOpen" x-cloak
                class="mt-2 rounded-2xl border border-slate-800 bg-slate-950/60 p-3">

                <div class="flex items-center gap-2">
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                        Trofeos disponibles
                    </p>

                    <button type="button" @click="pickerOpen = false"
                        class="ml-auto px-1 text-[13px] text-slate-600 transition hover:text-slate-300">×</button>
                </div>

                <p class="mt-0.5 text-[9px] leading-relaxed text-slate-600">
                    Elegir uno crea el premio que lo entrega, que es la única forma de
                    que un trofeo sea de una edición.
                </p>

                <div class="mt-2 grid gap-1.5 sm:grid-cols-2 lg:grid-cols-3">
                    <template x-for="tr in availableTrophies" :key="'av' + tr.id">
                        <button type="button" @click="useTrophy(tr)"
                            class="flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-950/50 p-2 text-left transition hover:border-violet-500/50 hover:bg-violet-500/5">

                            <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-900 text-lg">
                                <template x-if="tr.image_url">
                                    <img :src="tr.image_url" alt="" class="h-full w-full object-cover">
                                </template>
                                <template x-if="!tr.image_url">
                                    <span x-text="tr.icon || '🏆'"></span>
                                </template>
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[11px] font-black text-slate-200" x-text="tr.name"></span>
                                <span class="block truncate text-[9px] text-slate-500"
                                    x-text="isOwnTrophy(tr) ? 'de esta edición' : tr.tier_label"></span>
                            </span>

                            <span class="shrink-0 text-[10px] text-violet-400">+</span>
                        </button>
                    </template>
                </div>
            </div>
        </div>


        {{-- ==================== EL TALLER DEL TROFEO ==================== --}}

        {{--
            No es un <form>: uno dentro de otro no existe en HTML, y el
            trofeo lleva imagen. Se envía por su cuenta y vuelve ya
            guardado, sin recargar y sin perder lo que se lleva escrito.
        --}}

        <div x-show="trophyOpen" x-cloak
            class="rounded-2xl border border-violet-500/40 bg-violet-500/5 p-3">

            <div class="flex items-center gap-2">
                <p class="text-[10px] font-black uppercase tracking-wider text-violet-300"
                    x-text="trophyEditing ? 'Corregir el trofeo' : 'Un trofeo para esta edición'"></p>

                <button type="button" @click="closeTrophy()"
                    class="ml-auto px-1 text-[14px] text-slate-500 transition hover:text-slate-200">×</button>
            </div>

            <p class="mt-1 rounded-lg bg-rose-500/10 px-2 py-1 text-[10px] text-rose-300"
                x-show="firstError('general')" x-text="firstError('general')"></p>

            <div class="mt-2 grid gap-2 sm:grid-cols-[auto_1fr]">

                <label class="flex h-24 w-24 cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-slate-700 bg-slate-950 transition hover:border-violet-500">
                    <template x-if="trophyPreview">
                        <img :src="trophyPreview" alt="" class="h-full w-full object-cover">
                    </template>
                    <template x-if="!trophyPreview">
                        <span class="px-2 text-center text-[9px] leading-tight text-slate-600">subir<br>imagen</span>
                    </template>

                    <input type="file" accept="image/*" class="hidden" @change="pickTrophyImage">
                </label>

                <div class="space-y-1.5">

                    <div class="grid grid-cols-[auto_1fr] gap-1.5">
                        <input type="text" x-model="trophyForm.icon" maxlength="4" placeholder="🏆"
                            class="w-14 rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-center text-[15px] focus:border-violet-500 focus:ring-violet-500">

                        <input type="text" x-model="trophyForm.name" maxlength="150" placeholder="Copa del aniversario"
                            class="w-full rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-[12px] font-black text-slate-100 placeholder:text-slate-700 focus:border-violet-500 focus:ring-violet-500">
                    </div>

                    <p class="text-[10px] text-rose-300" x-show="firstError('name')" x-text="firstError('name')"></p>

                    <input type="text" x-model="trophyForm.description" maxlength="500"
                        placeholder="Solo se entregó en esta edición."
                        class="w-full rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-[11px] text-slate-300 placeholder:text-slate-700 focus:border-violet-500 focus:ring-violet-500">

                    <div class="flex flex-wrap gap-1">
                        @foreach ($tiers as $value => $label)
                            <button type="button" @click="trophyForm.tier = '{{ $value }}'"
                                class="rounded-lg border px-2 py-1 text-[10px] font-black transition"
                                :class="trophyForm.tier === '{{ $value }}'
                                    ? 'border-violet-400/60 bg-violet-500/20 text-violet-200'
                                    : 'border-slate-800 bg-slate-950 text-slate-500 hover:border-slate-600'">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="mt-2 flex items-center gap-2">
                <p class="mr-auto text-[9px] leading-relaxed text-slate-600">
                    Se guarda al momento, sin salir de aquí ni perder lo que llevas escrito.
                </p>

                <button type="button" @click="saveTrophy()" :disabled="trophyBusy || !trophyForm.name"
                    class="rounded-lg bg-violet-500 px-3 py-1.5 text-[11px] font-black text-slate-950 transition hover:bg-violet-400 disabled:opacity-40"
                    x-text="trophyBusy ? 'guardando…' : (trophyEditing ? 'Guardar cambios' : 'Crear trofeo')"></button>
            </div>
        </div>


        {{-- ==================== LOS PREMIOS ==================== --}}

        <div>
            <div class="flex flex-wrap items-center gap-2">
                <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                    Premios que añade <span class="text-amber-300">solo esta edición</span>
                </p>

                <span class="font-mono text-[9px] text-slate-600" x-text="rewards.length"></span>

                <button type="button" @click="addPodium()"
                    class="ml-auto rounded-lg border border-slate-700 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-amber-500 hover:text-amber-300">
                    + el podio de golpe
                </button>

                <button type="button" @click="addReward()"
                    class="rounded-lg border border-amber-500/40 px-2 py-1 text-[10px] font-black text-amber-300 transition hover:bg-amber-500/20">
                    + premio
                </button>
            </div>

            <div class="mt-2 space-y-1.5">

                <template x-for="(rw, i) in rewards" :key="'rw' + i">
                    <div class="overflow-hidden rounded-2xl border border-amber-500/25 bg-amber-500/5">

                        {{-- Resumido: un premio configurado dice qué hace, no seis campos --}}

                        <div class="flex items-center gap-2 px-3 py-2">

                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-amber-500/20 font-mono text-[10px] font-black text-amber-300"
                                x-text="i + 1"></span>

                            <template x-if="trophyOf(rw)">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-950 text-base">
                                    <template x-if="trophyOf(rw).image_url">
                                        <img :src="trophyOf(rw).image_url" alt="" class="h-full w-full object-cover">
                                    </template>
                                    <template x-if="!trophyOf(rw).image_url">
                                        <span x-text="trophyOf(rw).icon || '🏆'"></span>
                                    </template>
                                </span>
                            </template>

                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[11px] font-black text-slate-100"
                                    x-text="rw.label || 'Sin nombre'"></span>

                                <span class="flex flex-wrap items-center gap-1.5">
                                    <span class="rounded bg-slate-950/60 px-1.5 py-0.5 text-[9px] font-bold text-amber-300"
                                        x-text="rewardWhen(rw)"></span>

                                    <span class="truncate text-[9px]"
                                        :class="rewardGivesNothing(rw) ? 'text-rose-400' : 'text-slate-400'"
                                        x-text="rewardGives(rw)"></span>
                                </span>
                            </span>

                            <button type="button" @click="toggleReward(i)"
                                class="shrink-0 rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-amber-500 hover:text-amber-300"
                                x-text="isExpanded(i) ? 'listo' : '✎ editar'"></button>

                            <button type="button" @click="removeReward(i)"
                                class="shrink-0 px-1 text-[13px] text-slate-600 transition hover:text-rose-400"
                                title="Quitar este premio">×</button>
                        </div>


                        {{--
                            x-show y no x-if: los campos tienen que seguir
                            viajando en el envío aunque estén plegados.
                        --}}

                        <div x-show="isExpanded(i)" x-cloak
                            class="space-y-2 border-t border-amber-500/20 bg-slate-950/40 p-3">

                            <div class="grid gap-2 sm:grid-cols-2">

                                <label class="block">
                                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Cómo se llama</span>

                                    <input type="text" :name="'rewards[' + i + '][label]'" x-model="rw.label"
                                        maxlength="150" placeholder="Campeón"
                                        class="mt-0.5 w-full rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-[11px] font-bold text-slate-100 placeholder:text-slate-700 focus:border-amber-500 focus:ring-amber-500">
                                </label>

                                {{--
                                    Dónde se gana. Vacío = al terminar la
                                    edición. Con una fase, «puesto 1»
                                    significa primero de ESA fase.
                                --}}
                                <label class="block">
                                    <span class="text-[9px] font-black uppercase tracking-wider text-cyan-300">Dónde se gana</span>

                                    <select :name="'rewards[' + i + '][node_id]'" x-model="rw.node_id" x-keep-selected="rw.node_id"
                                        class="mt-0.5 w-full rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-[11px] text-slate-200 focus:border-cyan-500 focus:ring-cyan-500">
                                        <option value="">Al terminar la edición</option>
                                        <template x-for="ph in phases" :key="'rn' + i + '-' + ph.id">
                                            <option :value="ph.id" x-text="'Al terminar «' + ph.name + '»'"></option>
                                        </template>
                                    </select>
                                </label>
                            </div>

                            <div class="grid gap-2 sm:grid-cols-[1fr_auto]">

                                <label class="block">
                                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">Cuándo</span>

                                    <select :name="'rewards[' + i + '][trigger]'" x-model="rw.trigger"
                                        class="mt-0.5 w-full rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-[11px] text-slate-200 focus:border-amber-500 focus:ring-amber-500">
                                        @foreach ($triggers as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="block" x-show="needsThreshold(rw)" x-cloak>
                                    <span class="text-[9px] font-black uppercase tracking-wider text-amber-300"
                                        x-text="thresholdLabel(rw)"></span>

                                    <input type="number" :name="'rewards[' + i + '][threshold]'" x-model="rw.threshold"
                                        min="1" max="999"
                                        class="mt-0.5 w-24 rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-center font-mono text-[13px] font-black text-slate-100 focus:border-amber-500 focus:ring-amber-500">
                                </label>
                            </div>

                            <div class="grid gap-2 lg:grid-cols-2">

                                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-2">
                                    <p class="text-[9px] font-black uppercase tracking-wider text-violet-300">Trofeo</p>

                                    <select :name="'rewards[' + i + '][universe_trophy_id]'"
                                        x-model="rw.universe_trophy_id" x-keep-selected="rw.universe_trophy_id"
                                        class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-[11px] text-slate-200 focus:border-violet-500 focus:ring-violet-500">
                                        <option value="">— ninguno —</option>
                                        <template x-for="tr in allTrophies" :key="'sel' + i + '-' + tr.id">
                                            <option :value="tr.id" x-text="(tr.icon || '🏆') + ' ' + tr.name"></option>
                                        </template>
                                    </select>

                                    <template x-if="trophyOf(rw) && isOwnTrophy(trophyOf(rw))">
                                        <button type="button" @click="openTrophy(trophyOf(rw))"
                                            class="mt-1.5 flex w-full items-center gap-1.5 rounded-lg bg-slate-900/60 px-2 py-1 text-left transition hover:bg-slate-900">
                                            <span class="min-w-0 flex-1 truncate text-[10px] text-slate-400"
                                                x-text="trophyOf(rw).description || 'de esta edición'"></span>
                                            <span class="shrink-0 text-[10px] text-slate-600">✎</span>
                                        </button>
                                    </template>
                                </div>

                                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-2">
                                    <p class="text-[9px] font-black uppercase tracking-wider text-emerald-300">
                                        Estadística del competidor
                                    </p>

                                    <div class="mt-1 grid grid-cols-[1fr_auto_auto] gap-1">

                                        {{--
                                            Salen del juego elegido arriba:
                                            premiar una que el juego no lleva
                                            sería prometer algo que nadie
                                            puede cobrar.
                                        --}}
                                        <select :name="'rewards[' + i + '][stat_key]'" x-model="rw.stat_key" x-keep-selected="rw.stat_key"
                                            class="w-full rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-[11px] text-slate-200 focus:border-emerald-500 focus:ring-emerald-500">
                                            <option value="">— ninguna —</option>
                                            <template x-for="st in gameStats" :key="'st' + i + '-' + st.key">
                                                <option :value="st.key" x-text="st.label"></option>
                                            </template>
                                        </select>

                                        <select :name="'rewards[' + i + '][operation]'" x-model="rw.operation"
                                            class="rounded-lg border-slate-700 bg-slate-950 px-1 py-1 text-[11px] text-slate-200 focus:border-emerald-500 focus:ring-emerald-500">
                                            @foreach ($operations as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>

                                        <input type="number" :name="'rewards[' + i + '][amount]'" x-model="rw.amount"
                                            step="0.1" min="-9999" max="9999"
                                            class="w-16 rounded-lg border-slate-700 bg-slate-950 px-1 py-1 text-center font-mono text-[12px] font-black text-slate-100 focus:border-emerald-500 focus:ring-emerald-500">
                                    </div>

                                    <p class="mt-1 text-[9px] leading-relaxed text-slate-600" x-show="!gameStats.length">
                                        El juego elegido no declara estadísticas, así que este
                                        premio solo puede dar un trofeo.
                                    </p>
                                </div>
                            </div>

                            {{--
                                Si la edición siguiente debe encontrarlo ya
                                puesto al copiarse. Un premio de aniversario
                                no debería arrastrarse solo.
                            --}}
                            <label class="flex cursor-pointer items-start gap-2 rounded-lg bg-slate-950/60 px-2 py-1.5">
                                <input type="hidden" :name="'rewards[' + i + '][carry_forward]'" value="0">
                                <input type="checkbox" :name="'rewards[' + i + '][carry_forward]'" value="1"
                                    x-model="rw.carry_forward"
                                    class="mt-0.5 rounded border-slate-600 bg-slate-950 text-amber-500 focus:ring-amber-500">

                                <span>
                                    <span class="text-[10px] font-black text-slate-300">
                                        Ofrecerlo también a la edición siguiente
                                    </span>
                                    <span class="mt-0.5 block text-[9px] leading-relaxed text-slate-600">
                                        Al copiar esta edición vendrá ya puesto. Sin marcar, se
                                        queda solo en esta.
                                    </span>
                                </span>
                            </label>

                            <p class="rounded-lg bg-slate-950/60 px-2 py-1 text-[10px] leading-relaxed"
                                :class="rewardGivesNothing(rw) ? 'text-amber-300' : 'text-slate-400'"
                                x-text="rewardText(rw)"></p>
                        </div>
                    </div>
                </template>

                <template x-if="rewards.length === 0">
                    <p class="rounded-xl border border-dashed border-slate-700 px-3 py-4 text-center text-[10px] leading-relaxed text-slate-600">
                        Esta edición no añade ningún premio propio.
                        @if (count($inheritedRewards))
                            Se entregarán solo los {{ count($inheritedRewards) }} del torneo.
                        @endif
                    </p>
                </template>
            </div>
        </div>

    </div>
</section>
