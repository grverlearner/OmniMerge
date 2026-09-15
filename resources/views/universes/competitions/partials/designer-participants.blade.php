@php
    /*
     * 06 · QUIÉN ENTRA — en esta edición.
     *
     * Dos formas, y se ve siempre lo que dice el torneo:
     *
     *   como el torneo   lo que se configuró en la sala del torneo: quién
     *                    entra, el reparto por puertas y las caras
     *   distinta         lo propio de esta edición, que parte de lo del
     *                    torneo —o del universo entero— sin tocarlo
     *
     * Aquí se ve el resultado: cuántos juegan, cómo queda cada puerta y sus
     * caras. Para decidir, la sala se abre a pantalla completa ENCIMA del
     * diseñador —no en otra página—, con las mismas piezas que la del torneo.
     * Lo que se decide viaja con el formulario en `participant_design` y el
     * servidor lo recalcula al guardar.
     *
     * Ver docs/md/80-Participantes-De-Cada-Edicion.md
     */
@endphp

<section x-show="isOpen('doors')" x-cloak
    class="mb-3 overflow-hidden rounded-2xl border border-rose-500/30 bg-slate-900/50">

    <div class="flex items-center gap-2 border-b border-slate-800 bg-rose-500/10 px-4 py-2">
        <span class="font-mono text-[9px] text-slate-600">06</span>
        <x-omni-icon name="usuario" size="h-3.5 w-3.5" class="text-rose-300" />
        <h2 class="text-[11px] font-black uppercase tracking-wider text-rose-300">Quién entra</h2>
        <span class="ml-auto text-[10px] text-slate-600">Quién juega esta edición, por qué puerta y con qué cara</span>
    </div>

    <div class="space-y-3 p-4">

        @if ($competition && ! $canReassign)

            {{-- Ya empezó: se ve, no se toca --}}
            @php
                $jugados = \App\Models\TournamentInstanceParticipant::query()
                    ->where('tournament_instance_id', $competition->id)
                    ->with('universeEntity')
                    ->orderBy('source_start_id')
                    ->orderBy('seed')
                    ->get()
                    ->groupBy('source_start_id');
            @endphp

            <p class="rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2 text-[10px] leading-relaxed text-slate-500">
                Esta edición <span class="font-bold text-slate-300">ya empezó a jugarse</span>: su cuadro está hecho con
                estos competidores y cambiarlos dejaría enfrentamientos apuntando a quien ya no está. Para jugar con otros,
                copia esta edición en una nueva.
            </p>

            @foreach ($jugados as $startId => $filas)
                @php $puerta = collect($chosenTemplate['starts'] ?? [])->firstWhere('id', (int) $startId); @endphp

                <div>
                    <p class="mb-1.5 text-[9px] font-black uppercase tracking-wider text-slate-500">
                        {{ $puerta['name'] ?? 'Entrada' }} <span class="font-mono text-slate-600">· {{ $filas->count() }}</span>
                    </p>

                    <div class="grid gap-1.5 grid-cols-4 sm:grid-cols-6 lg:grid-cols-8">
                        @foreach ($filas as $fila)
                            <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-950/50">
                                <span class="relative block aspect-square overflow-hidden bg-slate-950">
                                    @if ($fila->face_url)
                                        <img src="{{ $fila->face_url }}" alt="" class="h-full w-full object-cover">
                                    @endif
                                    <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950 to-transparent px-1.5 pb-1 pt-4">
                                        <span class="block truncate text-[10px] font-black text-slate-100">{{ $fila->name }}</span>
                                        @if ($fila->entity_version_name)
                                            <span class="block truncate text-[9px] font-bold text-violet-300">{{ $fila->entity_version_name }}</span>
                                        @endif
                                    </span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

        @else

            <div x-data="participantRoom(@js($sala))" x-effect="setStarts(templateStarts, template?.id)" class="space-y-3">

                <input type="hidden" name="participant_design" :value="designJson">

                @if ($competition)
                    <p class="rounded-xl border border-amber-500/30 bg-amber-500/5 px-3 py-2 text-[10px] leading-relaxed text-amber-200">
                        Todavía no ha empezado, así que aún puedes cambiar quién compite. Al guardar, su cuadro se
                        <span class="font-bold">vuelve a dibujar</span> con lo que quede aquí.
                    </p>
                @endif


                {{-- ============ LAS DOS FORMAS ============ --}}

                <div class="grid gap-2 md:grid-cols-2">

                    <button type="button" @click="useTournament()"
                        class="rounded-2xl border p-3 text-left transition"
                        :class="inherits ? 'border-amber-400 bg-amber-500/10' : 'border-slate-800 bg-slate-950/50 hover:border-slate-600'">
                        <span class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full border-2" :class="inherits ? 'border-amber-400 bg-amber-400' : 'border-slate-600'"></span>
                            <x-omni-icon name="trofeo" size="h-4 w-4" class="text-amber-300" />
                            <span class="text-[13px] font-black text-white">Como dice el torneo</span>
                            <span class="ml-auto rounded bg-amber-500/15 px-1.5 py-0.5 font-mono text-[10px] font-black text-amber-300" x-text="baseCount + ' permitidos'"></span>
                        </span>
                        <span class="mt-1.5 block text-[10px] leading-4 text-slate-400">
                            Quién entra, el reparto por puertas y las caras que se decidieron en la sala del torneo.
                        </span>
                        <span class="mt-2 block rounded-lg border border-slate-800 bg-slate-950/70 px-2 py-1.5">
                            <span class="block truncate text-[10px] text-slate-300" x-text="ruleText(base)"></span>
                            <span class="block truncate text-[9px] text-slate-500" x-text="doorsText(tournamentDoors) + (doorsMismatch ? ' (el del torneo es para otra forma)' : '')"></span>
                        </span>
                    </button>

                    <button type="button" @click="customize()"
                        class="rounded-2xl border p-3 text-left transition"
                        :class="! inherits ? 'border-rose-400 bg-rose-500/10' : 'border-slate-800 bg-slate-950/50 hover:border-slate-600'">
                        <span class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full border-2" :class="! inherits ? 'border-rose-400 bg-rose-400' : 'border-slate-600'"></span>
                            <x-omni-icon name="pincel" size="h-4 w-4" class="text-rose-300" />
                            <span class="text-[13px] font-black text-white">Distinta en esta edición</span>
                        </span>
                        <span class="mt-1.5 block text-[10px] leading-4 text-slate-400">
                            Menos gente, otro reparto u otras caras solo para esta edición. Parte de lo del torneo, y el torneo no se toca.
                        </span>
                        <span x-show="! inherits" class="mt-2 block rounded-lg border border-slate-800 bg-slate-950/70 px-2 py-1.5">
                            <span class="block truncate text-[10px] text-slate-300"
                                x-text="(scope === 'TOURNAMENT' ? 'De lo que permite el torneo · ' : 'De todo el universo · ') + ruleText($data)"></span>
                            <span class="block truncate text-[9px] text-slate-500" x-text="doorsText(doors)"></span>
                        </span>
                    </button>
                </div>


                {{-- ============ LO QUE QUEDA ============ --}}

                <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-950/60">

                    <div class="flex flex-wrap items-center gap-3 px-3 py-2.5">

                        <span class="flex items-baseline gap-1.5">
                            <span class="font-mono text-3xl font-black leading-none" :class="totalIn ? 'text-emerald-300' : 'text-rose-300'" x-text="totalIn"></span>
                            <span class="font-mono text-[12px] text-slate-600" x-text="'/ ' + roster.length"></span>
                        </span>

                        <span class="min-w-0">
                            <span class="block text-[11px] font-black text-slate-200">juegan esta edición</span>
                            <span class="block text-[9px] text-slate-500"
                                x-text="(withVersionFace ? withVersionFace + ' con la cara de una versión' : 'todos con su imagen de siempre') + (noImage.length ? ' · ' + noImage.length + ' sin imagen' : '')"></span>
                        </span>

                        <span class="flex-1"></span>

                        <a :href="tournament.room_url" target="_blank" rel="noopener"
                            class="flex items-center gap-1.5 rounded-xl border border-slate-700 px-2.5 py-2 text-[10px] font-black text-slate-400 transition hover:border-amber-400 hover:text-amber-200"
                            title="Se abre en otra pestaña">
                            <x-omni-icon name="trofeo" size="h-3.5 w-3.5" />
                            Sala del torneo
                        </a>

                        <button type="button" @click="openRoom()"
                            class="flex items-center gap-1.5 rounded-xl bg-rose-500 px-3.5 py-2 text-[12px] font-black text-slate-950 transition hover:bg-rose-400">
                            <x-omni-icon name="usuario" size="h-4 w-4" />
                            Abrir la sala de esta edición
                        </button>
                    </div>

                    <div class="h-1 bg-slate-900">
                        <div class="h-full bg-emerald-500 transition-all" :style="`width: ${roster.length ? (totalIn / roster.length) * 100 : 0}%`"></div>
                    </div>

                    <template x-if="! starts.length">
                        <p class="m-3 rounded-xl border border-dashed border-rose-500/40 bg-rose-500/5 px-3 py-3 text-center text-[10px] text-rose-300">
                            La forma elegida no tiene ninguna puerta de entrada, así que nadie puede empezar.
                        </p>
                    </template>

                    {{-- Cada puerta, con sus caras --}}
                    <div class="grid gap-2 p-3 sm:grid-cols-2 xl:grid-cols-3">
                        <template x-for="s in starts" :key="'mini' + s.id">
                            <button type="button" @click="openRoom('doors'); view = 'doors'"
                                class="overflow-hidden rounded-xl border bg-slate-900/60 text-left transition hover:-translate-y-0.5"
                                :style="`border-color: ${doorColor(s.id)}55`">
                                <span class="flex items-center gap-2 px-2.5 py-1.5" :style="`background: linear-gradient(120deg, ${doorColor(s.id)}22, transparent 70%)`">
                                    <span class="rounded px-1 font-mono text-[9px] font-black text-slate-950" :style="`background-color: ${doorColor(s.id)}`" x-text="doorShort(s.id)"></span>
                                    <span class="min-w-0 flex-1 truncate text-[11px] font-black text-slate-200" x-text="s.name"></span>
                                    <span class="font-mono text-[11px] font-black" :style="`color: ${doorState(s.id).tone}`" x-text="doorCount(s.id) + (s.capacity ? '/' + s.capacity : '')"></span>
                                </span>
                                <span class="h-1 bg-slate-800 block">
                                    <span class="block h-full" :style="`width: ${doorFill(s.id)}%; background-color: ${doorColor(s.id)}`"></span>
                                </span>
                                <span class="flex flex-wrap gap-1 p-2">
                                    <template x-for="c in (calc.plan.assignments[s.id] ?? []).slice(0, 12).map((id) => roster.find((x) => x.id === id)).filter(Boolean)" :key="'mf' + s.id + '-' + c.id">
                                        <span class="h-8 w-8 overflow-hidden rounded-lg border border-slate-800 bg-slate-950" :title="c.name + (face(c).version_id ? ' · ' + face(c).name : '')">
                                            <template x-if="face(c).image_url"><img :src="face(c).image_url" alt="" class="h-full w-full object-cover"></template>
                                        </span>
                                    </template>
                                    <span x-show="doorCount(s.id) > 12" class="flex h-8 items-center px-1 font-mono text-[10px] text-slate-500" x-text="'+' + (doorCount(s.id) - 12)"></span>
                                    <span x-show="! doorCount(s.id)" class="py-1.5 text-[10px] text-slate-600">Nadie entra por aquí</span>
                                </span>
                                <span class="block px-2.5 pb-1.5 text-[9px] font-black" :style="`color: ${doorState(s.id).tone}`" x-text="doorState(s.id).text"></span>
                            </button>
                        </template>
                    </div>

                    <template x-if="starts.length && unplaced.length">
                        <p class="mx-3 mb-3 flex items-center gap-2 rounded-xl border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-[10px] leading-4 text-amber-200">
                            <x-omni-icon name="aviso" size="h-4 w-4" class="shrink-0" />
                            <span x-text="unplaced.length + (unplaced.length === 1 ? ' cumple pero no cabe en ninguna puerta: no jugaría.' : ' cumplen pero no caben en ninguna puerta: no jugarían.')"></span>
                            <button type="button" @click="openRoom('doors'); view = 'doors'" class="ml-auto shrink-0 font-black underline">Revisar</button>
                        </p>
                    </template>

                    <template x-if="totalIn === 0">
                        <p class="mx-3 mb-3 rounded-xl border border-rose-500/40 bg-rose-500/10 px-3 py-2 text-[10px] font-bold leading-4 text-rose-200">
                            Así no entra nadie y la edición no se podría crear.
                        </p>
                    </template>
                </div>

                <x-input-error :messages="$errors->get('assignments')" class="mt-1" />


                {{-- ============ LA SALA, A PANTALLA COMPLETA ============ --}}

                <div x-show="roomOpen" x-cloak x-transition.opacity
                    class="fixed inset-0 z-[60] overflow-y-auto bg-slate-950 p-2 sm:p-3"
                    @keydown.escape.window="if (roomOpen && ! ficha) cancelRoom()">

                    <div class="mx-auto max-w-[1700px] space-y-2">

                        @include('universes.competitions.partials.sala-cabecera')

                        <div class="grid items-start gap-2 xl:grid-cols-[400px_minmax(0,1fr)]">

                            <aside class="space-y-2 xl:sticky xl:top-2 xl:max-h-[calc(100vh-1.5rem)] xl:overflow-y-auto xl:pr-1">

                                <template x-if="inherits">
                                    @include('universes.tournaments.partials.sala.heredado')
                                </template>

                                <div x-show="editable" class="space-y-2">
                                    @include('universes.tournaments.partials.sala.pestanas')
                                    @include('universes.tournaments.partials.sala.panel-quien')
                                    @include('universes.tournaments.partials.sala.panel-puertas')
                                    @include('universes.tournaments.partials.sala.panel-caras')
                                </div>
                            </aside>

                            @include('universes.tournaments.partials.sala.escenario')
                        </div>

                        @include('universes.tournaments.partials.sala.ficha')
                    </div>
                </div>
            </div>

        @endif
    </div>
</section>
