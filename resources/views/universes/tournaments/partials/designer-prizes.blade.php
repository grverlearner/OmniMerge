@php
    /*
     * 05 · TROFEO Y PREMIOS — qué se lleva quien gana.
     *
     * Todo se hace aquí. Ni un enlace que te saque de la pantalla: estás en
     * medio de configurar un torneo y mandarte a otra ventana te haría
     * perder lo que llevas escrito.
     *
     * Dos cosas distintas, y por eso viajan por caminos distintos:
     *
     *   TROFEOS      son del universo y se comparten entre torneos. Llevan
     *                imagen, así que se crean y editan por su cuenta, sin
     *                recargar. Un formulario dentro de otro no existe en
     *                HTML, y una imagen necesita FormData.
     *
     *   RECOMPENSAS  son de ESTE torneo. Viajan dentro del formulario, como
     *                las reglas de participación: al crear un torneo aún no
     *                hay fila a la que colgarlas, y un premio que solo se
     *                puede poner después de guardar obliga a guardar dos
     *                veces.
     */

    $t = $universeTournament ?? null;

    $existing = $t
        ? $t->rewards()->where('is_active', true)->orderBy('id')->get()
        : collect();

    /* Lo que se quedó a medias tras un error de validación manda sobre lo guardado */
    $currentRewards = old('rewards') !== null
        ? array_values((array) old('rewards'))
        : $existing->map(fn ($r) => [
            'trigger' => $r->trigger,
            'threshold' => $r->threshold,
            'universe_trophy_id' => $r->universe_trophy_id,
            'stat_key' => $r->stat_key,
            'operation' => $r->operation,
            'amount' => $r->amount,
            'label' => $r->label,
        ])->all();

    $triggers = \App\Models\UniverseTournamentReward::TRIGGERS;
    $operations = \App\Models\UniverseTournamentReward::OPERATIONS;
    $tiers = \App\Models\UniverseTrophy::TIERS;
@endphp

<section x-show="isOpen('prizes')" x-cloak
    class="mb-3 overflow-hidden rounded-2xl border border-violet-500/30 bg-slate-900/50"
    x-data="tournamentPrizes({
        trophies: @js($trophies->map(fn ($tr) => [
            'id' => $tr->id,
            'name' => $tr->name,
            'description' => $tr->description,
            'icon' => $tr->icon,
            'tier' => $tr->tier,
            'tier_label' => $tiers[$tr->tier] ?? $tr->tier,
            'image_url' => $tr->image_url,
        ])->values()),
        rewards: @js($currentRewards),
        storeUrl: @js(route('universes.trophies.store', $universe)),
        updateUrlTemplate: @js(route('universes.trophies.update', [$universe, '__ID__'])),
        csrf: @js(csrf_token()),
    })"
    {{--
        Las estadísticas del juego bajan desde el diseñador. Una expresión
        inline SÍ llega al ámbito padre, que es lo que un getter del hijo no
        puede hacer; y con x-effect vuelven a bajar cuando cambias de juego.
    --}}
    x-effect="gameStats = game?.stats ?? []">

    <div class="flex items-center gap-2 border-b border-slate-800 bg-violet-500/10 px-4 py-2">
        <span class="font-mono text-[9px] text-slate-600">05</span>
        <span class="text-[11px]">🏆</span>
        <h2 class="text-[11px] font-black uppercase tracking-wider text-violet-300">Trofeo y premios</h2>
        <span class="ml-auto text-[10px] text-slate-600">Qué se lleva quien gana</span>
    </div>

    <div class="p-4">

        {{-- ==================== LOS TROFEOS ==================== --}}

        {{--
            Los de ESTE torneo, no los del universo entero.

            Antes esto era una vitrina con todo lo que existía, y eso no es
            una decisión: es un catálogo. Aquí lo que importa es cuáles
            entrega este torneo, y el resto queda detrás de un selector.

            Un trofeo es «de este torneo» cuando algún premio suyo lo
            otorga. No hay otra forma de que se entregue, así que elegir uno
            crea el premio que lo entrega —y se dice—.
        --}}

        <div class="flex flex-wrap items-center gap-2">
            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                Trofeos de este torneo
            </p>

            <span class="font-mono text-[9px] text-slate-600" x-text="tournamentTrophies.length"></span>

            <button type="button" @click="pickerOpen = !pickerOpen"
                class="ml-auto rounded-lg border border-slate-700 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-violet-500 hover:text-violet-300"
                x-show="availableTrophies.length">
                elegir de los <span x-text="availableTrophies.length"></span> del universo
            </button>

            <button type="button" @click="openTrophy(null)"
                class="rounded-lg border border-violet-500/40 px-2 py-1 text-[10px] font-black text-violet-300 transition hover:bg-violet-500/20">
                + crear trofeo
            </button>
        </div>


        {{-- Los que entrega --}}

        <div class="mt-2 grid gap-1.5 sm:grid-cols-2 lg:grid-cols-3">

            <template x-for="tr in tournamentTrophies" :key="'ut' + tr.id">
                <div class="flex items-center gap-2 rounded-xl border border-violet-500/30 bg-violet-500/5 p-2">

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
                        <span class="block truncate text-[9px] text-slate-500">
                            <span x-text="tr.tier_label"></span>
                            · lo entregan <span x-text="rewardsWithTrophy(tr.id)"></span>
                        </span>
                    </span>

                    <button type="button" @click="openTrophy(tr)"
                        class="shrink-0 px-1 text-[11px] text-slate-500 transition hover:text-violet-300"
                        title="Editar este trofeo">✎</button>
                </div>
            </template>

            <template x-if="tournamentTrophies.length === 0">
                <p class="rounded-xl border border-dashed border-slate-700 px-3 py-4 text-center text-[10px] leading-relaxed text-slate-600 sm:col-span-2 lg:col-span-3">
                    Este torneo no entrega ningún trofeo todavía. Elige uno del
                    universo o crea el suyo.
                </p>
            </template>

        </div>


        {{-- El catálogo del universo, solo cuando se pide --}}

        <div x-show="pickerOpen" x-cloak
            class="mt-2 rounded-2xl border border-slate-800 bg-slate-950/60 p-3">

            <div class="flex items-center gap-2">
                <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                    Trofeos del universo
                </p>

                <button type="button" @click="pickerOpen = false"
                    class="ml-auto px-1 text-[13px] text-slate-600 transition hover:text-slate-300">×</button>
            </div>

            <p class="mt-0.5 text-[9px] leading-relaxed text-slate-600">
                Elegir uno crea el premio que lo entrega, que es la única forma de
                que un trofeo sea de un torneo.
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
                            <span class="block truncate text-[9px] text-slate-500" x-text="tr.tier_label"></span>
                        </span>

                        <span class="shrink-0 text-[10px] text-violet-400">+</span>
                    </button>
                </template>

                <template x-if="availableTrophies.length === 0">
                    <p class="py-2 text-center text-[10px] text-slate-600 sm:col-span-2 lg:col-span-3">
                        Este torneo ya entrega todos los trofeos del universo.
                    </p>
                </template>

            </div>

        </div>


        {{-- El taller del trofeo --}}

        {{--
            No es un <form>: está dentro del formulario del torneo y HTML no
            admite formularios anidados. Se envía por su cuenta, con la
            imagen en un FormData.
        --}}

        <div x-show="trophyOpen" x-cloak
            class="mt-2 rounded-2xl border border-violet-500/40 bg-slate-950/70 p-3">

            <div class="flex items-center gap-2">
                <span class="text-[10px] font-black uppercase tracking-wider text-violet-300"
                    x-text="trophyForm.id ? 'Editando trofeo' : 'Trofeo nuevo'"></span>

                <button type="button" @click="closeTrophy()"
                    class="ml-auto px-1 text-[13px] text-slate-600 transition hover:text-rose-400">×</button>
            </div>

            <div class="mt-2 grid gap-3 sm:grid-cols-[auto_1fr]">

                {{-- La cara del trofeo --}}

                <label class="group relative block h-24 w-24 cursor-pointer overflow-hidden rounded-2xl border border-slate-700 bg-slate-900">

                    <template x-if="trophyPreview">
                        <img :src="trophyPreview" alt="" class="h-full w-full object-cover">
                    </template>

                    <template x-if="!trophyPreview">
                        <span class="flex h-full w-full items-center justify-center text-3xl"
                            x-text="trophyForm.icon || '🏆'"></span>
                    </template>

                    <span class="absolute inset-x-0 bottom-0 bg-slate-950/80 py-0.5 text-center text-[8px] font-black text-slate-300 opacity-0 transition group-hover:opacity-100">
                        imagen
                    </span>

                    <input type="file" accept="image/*" class="hidden" @change="pickTrophyImage($event)">
                </label>

                <div class="min-w-0 space-y-2">

                    <div class="grid gap-2 sm:grid-cols-[1fr_auto]">
                        <input type="text" x-model="trophyForm.name" maxlength="150"
                            placeholder="Nombre del trofeo"
                            class="w-full rounded-xl border-slate-700 bg-slate-950 px-3 py-1.5 text-[12px] font-black text-slate-100 placeholder:text-slate-700 focus:border-violet-500 focus:ring-violet-500">

                        <input type="text" x-model="trophyForm.icon" maxlength="8"
                            placeholder="🏆"
                            class="w-16 rounded-xl border-slate-700 bg-slate-950 px-2 py-1.5 text-center text-[15px] focus:border-violet-500 focus:ring-violet-500">
                    </div>

                    <input type="text" x-model="trophyForm.description" maxlength="500"
                        placeholder="Qué significa ganarlo (opcional)"
                        class="w-full rounded-xl border-slate-700 bg-slate-950 px-3 py-1.5 text-[11px] text-slate-300 placeholder:text-slate-700 focus:border-violet-500 focus:ring-violet-500">

                    {{-- La categoría, con su color --}}

                    <div class="flex flex-wrap gap-1">
                        @foreach ([
                            'GOLD' => ['Oro', 'border-amber-400/60 bg-amber-500/15 text-amber-300'],
                            'SILVER' => ['Plata', 'border-slate-400/60 bg-slate-400/15 text-slate-200'],
                            'BRONZE' => ['Bronce', 'border-orange-400/60 bg-orange-500/15 text-orange-300'],
                            'SPECIAL' => ['Especial', 'border-violet-400/60 bg-violet-500/15 text-violet-300'],
                        ] as $value => [$label, $on])
                            <button type="button" @click="trophyForm.tier = '{{ $value }}'"
                                class="rounded-lg border px-2.5 py-1 text-[10px] font-black transition"
                                :class="trophyForm.tier === '{{ $value }}'
                                    ? '{{ $on }}'
                                    : 'border-slate-800 bg-slate-950 text-slate-500 hover:border-slate-700'">{{ $label }}</button>
                        @endforeach
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="saveTrophy()" :disabled="trophySaving || !trophyForm.name"
                            class="rounded-lg bg-violet-600 px-3 py-1.5 text-[11px] font-black text-white transition hover:bg-violet-500 disabled:cursor-not-allowed disabled:opacity-40">
                            <span x-show="!trophySaving" x-text="trophyForm.id ? 'Guardar trofeo' : 'Crear trofeo'"></span>
                            <span x-show="trophySaving" x-cloak>Guardando…</span>
                        </button>

                        <p class="text-[9px] text-slate-600">
                            Se guarda al momento, sin tocar lo demás del torneo.
                        </p>
                    </div>

                    <template x-if="trophyError">
                        <p class="rounded-lg bg-rose-500/10 px-2 py-1 text-[10px] font-bold text-rose-300"
                            x-text="trophyError"></p>
                    </template>

                </div>

            </div>

        </div>


        {{-- ==================== LAS RECOMPENSAS ==================== --}}

        <div class="mt-5 border-t border-slate-800 pt-4">

            <div class="flex flex-wrap items-center gap-2">
                <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                    Qué se lleva cada uno
                </p>

                <span class="font-mono text-[9px] text-slate-600" x-text="rewards.length"></span>

                <button type="button" @click="addReward()"
                    class="ml-auto rounded-lg border border-amber-500/40 px-2 py-1 text-[10px] font-black text-amber-300 transition hover:bg-amber-500/20">
                    + premio
                </button>
            </div>

            <p class="mt-1 text-[9px] leading-relaxed text-slate-600">
                Un premio se otorga cuando pasa algo —quedar primero, participar,
                terminar invicto— y da un trofeo, una estadística, o ambos.
            </p>


            {{-- Atajos: los tres premios que casi todo torneo tiene --}}

            <div class="mt-2 flex flex-wrap items-center gap-1.5" x-show="rewards.length === 0">
                <span class="text-[9px] text-slate-600">De golpe:</span>

                <button type="button" @click="addPodium()"
                    class="rounded-lg border border-slate-800 bg-slate-950/60 px-2.5 py-1 text-[10px] font-black text-slate-300 transition hover:border-amber-500/50 hover:text-amber-300">
                    🥇🥈🥉 el podio
                </button>

                <span class="text-[9px] text-slate-600">
                    crea los tres primeros puestos y los rellenas
                </span>
            </div>


            <div class="mt-2 space-y-2">

                <template x-for="(rw, i) in rewards" :key="'rw' + i">
                    <div class="overflow-hidden rounded-2xl border border-amber-500/25 bg-amber-500/5">

                        {{--
                            Resumido por defecto.

                            Un premio ya configurado no necesita seis campos a
                            la vista: necesita decir qué hace. Se despliega al
                            pulsar «editar», y los recién creados vienen
                            abiertos porque están vacíos.
                        --}}

                        <div class="flex items-center gap-2 px-3 py-2">

                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-amber-500/20 font-mono text-[10px] font-black text-amber-300"
                                x-text="i + 1"></span>

                            {{-- La cara del trofeo, si lo da --}}

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
                            El detalle. Los campos viajan SIEMPRE, esté
                            abierto o cerrado: x-show oculta, no desmonta, y
                            un premio plegado tiene que llegar al servidor
                            igual que uno abierto.
                        --}}

                        <div x-show="isExpanded(i)" x-cloak
                            class="space-y-2 border-t border-amber-500/20 bg-slate-950/40 p-3">

                            <label class="block">
                                <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                                    Cómo se llama
                                </span>

                                <input type="text" :name="'rewards[' + i + '][label]'" x-model="rw.label"
                                    maxlength="150" placeholder="Campeón"
                                    class="mt-0.5 w-full rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-[11px] font-bold text-slate-100 placeholder:text-slate-700 focus:border-amber-500 focus:ring-amber-500">
                            </label>


                            {{-- Cuándo se otorga --}}

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


                            {{-- Qué da --}}

                            <div class="grid gap-2 lg:grid-cols-2">

                                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-2">
                                    <p class="text-[9px] font-black uppercase tracking-wider text-violet-300">Trofeo</p>

                                    <select :name="'rewards[' + i + '][universe_trophy_id]'"
                                        x-model="rw.universe_trophy_id" x-keep-selected="rw.universe_trophy_id"
                                        class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-[11px] text-slate-200 focus:border-violet-500 focus:ring-violet-500">
                                        <option value="">— ninguno —</option>
                                        <template x-for="tr in trophies" :key="'sel' + i + '-' + tr.id">
                                            <option :value="tr.id" x-text="(tr.icon || '🏆') + ' ' + tr.name"></option>
                                        </template>
                                    </select>

                                    <template x-if="trophyOf(rw)">
                                        <button type="button" @click="openTrophy(trophyOf(rw))"
                                            class="mt-1.5 flex w-full items-center gap-1.5 rounded-lg bg-slate-900/60 px-2 py-1 text-left transition hover:bg-slate-900">
                                            <span class="min-w-0 flex-1 truncate text-[10px] text-slate-400"
                                                x-text="trophyOf(rw).description || trophyOf(rw).tier_label"></span>
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
                                            Las estadísticas salen del juego
                                            elegido arriba: ofrecer otra sería
                                            prometer un premio que nadie puede
                                            cobrar.
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


                            {{-- Cómo se leerá --}}

                            <p class="rounded-lg bg-slate-950/60 px-2 py-1 text-[10px] leading-relaxed"
                                :class="rewardGivesNothing(rw) ? 'text-amber-300' : 'text-slate-400'"
                                x-text="rewardText(rw)"></p>

                        </div>

                    </div>
                </template>

                <template x-if="rewards.length === 0">
                    <div class="rounded-2xl border border-dashed border-slate-700 px-4 py-5 text-center">
                        <p class="text-[11px] font-black text-slate-400">Ganar esto no da nada</p>
                        <p class="mx-auto mt-1 max-w-sm text-[10px] leading-relaxed text-slate-600">
                            Se puede jugar igual, pero la victoria no dejará rastro en el
                            historial de nadie.
                        </p>
                    </div>
                </template>

            </div>

            <p class="mt-3 rounded-xl bg-slate-950/60 px-3 py-2 text-[9px] leading-relaxed text-slate-600">
                Esto lo hereda <span class="font-bold text-slate-400">toda</span> edición del
                torneo. Una competición concreta puede añadir premios propios sin tocarlo.
            </p>

        </div>

    </div>

</section>
