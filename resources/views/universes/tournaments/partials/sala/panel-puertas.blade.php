{{--
    PUERTAS

    Por qué entrada del recorrido entra cada uno. Con una sola puerta no hay
    nada que repartir, solo qué pasa si no caben todos. Con varias, tres
    formas: se reparte solo, por reglas, o a mano con un pincel.
--}}

<div x-show="panel === 'doors'" x-cloak class="space-y-2">

    {{-- ============ SIN PUERTAS ============ --}}

    <template x-if="! starts.length">
        <section class="rounded-2xl border border-dashed border-slate-700 bg-slate-900/40 px-4 py-6 text-center">
            <span class="inline-flex text-slate-600"><x-omni-icon name="puerta" size="h-8 w-8" /></span>
            <p class="mt-2 text-[12px] font-black text-white">La plantilla no tiene puertas de entrada</p>
            <p class="mx-auto mt-1 max-w-xs text-[10px] leading-4 text-slate-500">
                Sin una entrada activa no hay por dónde meter a nadie. Añádela en el taller de torneos, en la plantilla
                <span class="text-slate-300" x-text="tournament.template_name ?? ''"></span>.
            </p>
        </section>
    </template>


    {{-- ============ UNA PUERTA ============ --}}

    <template x-if="starts.length === 1">
        <section class="rounded-2xl border border-sky-500/30 bg-slate-900/50 p-3">
            <div class="flex items-center gap-2">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl text-slate-950" :style="`background-color: ${doorColor(starts[0].id)}`">
                    <x-omni-icon name="puerta" size="h-4 w-4" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-[13px] font-black text-white" x-text="starts[0].name"></p>
                    <p class="text-[10px] text-slate-500" x-text="starts[0].capacity ? 'Caben ' + starts[0].capacity : 'Sin límite de plazas'"></p>
                </div>
                <span class="font-mono text-lg font-black" :style="`color: ${doorState(starts[0].id).tone}`" x-text="doorCount(starts[0].id)"></span>
            </div>

            <p class="mt-2 text-[10px] leading-4 text-slate-500">
                Todos los que cumplen entran por aquí. Solo hay que decidir qué pasa si son más de los que caben.
            </p>

            <div x-show="starts[0].capacity" class="mt-2 grid grid-cols-2 gap-1">
                @foreach (['IN_ORDER' => ['Los primeros', 'Por orden de nombre'], 'RANDOM' => ['Al azar', 'Con una semilla fija']] as $valor => [$texto, $ayuda])
                    <button type="button" @click="doors.strategy = '{{ $valor }}'"
                        :class="doors.strategy === '{{ $valor }}' || ('{{ $valor }}' === 'IN_ORDER' && ! ['RANDOM'].includes(doors.strategy)) ? 'border-sky-400 bg-sky-500/15 text-sky-100' : 'border-slate-800 text-slate-400 hover:border-slate-600'"
                        class="rounded-lg border px-2 py-1.5 text-left transition">
                        <span class="block text-[11px] font-black">{{ $texto }}</span>
                        <span class="block text-[9px] opacity-70">{{ $ayuda }}</span>
                    </button>
                @endforeach
            </div>

            <button type="button" x-show="starts[0].capacity && doors.strategy === 'RANDOM'" @click="reshuffle()"
                class="mt-1.5 flex w-full items-center justify-center gap-1.5 rounded-lg border border-slate-700 px-2 py-1.5 text-[10px] font-black text-slate-300 transition hover:border-sky-400 hover:text-sky-200">
                <x-omni-icon name="barajar" size="h-3.5 w-3.5" />
                Barajar otra vez <span class="font-mono text-slate-500" x-text="'#' + doors.seed"></span>
            </button>

            <p x-show="doorOverflow(starts[0].id)" class="mt-2 rounded-lg border border-amber-500/30 bg-amber-500/10 px-2 py-1.5 text-[10px] text-amber-200">
                Sobran <span class="font-mono font-black" x-text="doorOverflow(starts[0].id) || unplaced.length"></span>: cumplen, pero no caben.
            </p>
        </section>
    </template>


    {{-- ============ VARIAS PUERTAS ============ --}}

    <template x-if="starts.length > 1">
        <div class="space-y-2">

            {{-- Cómo se reparte --}}
            <section class="rounded-2xl border border-sky-500/30 bg-slate-900/50 p-3">
                <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Cómo se reparte</p>

                <div class="mt-1.5 grid grid-cols-3 gap-1">
                    @foreach (['AUTO' => ['Solo', 'barajar'], 'RULES' => ['Por reglas', 'filtro'], 'MANUAL' => ['A mano', 'pincel']] as $valor => [$texto, $icono])
                        <button type="button" @click="setDoorsMode('{{ $valor }}')"
                            :class="doors.mode === '{{ $valor }}' ? 'border-sky-400 bg-sky-500/20 text-sky-100' : 'border-slate-800 text-slate-400 hover:border-slate-600'"
                            class="flex flex-col items-center gap-0.5 rounded-lg border px-1 py-2 transition">
                            <x-omni-icon :name="$icono" size="h-4 w-4" />
                            <span class="text-[11px] font-black">{{ $texto }}</span>
                        </button>
                    @endforeach
                </div>

                <p class="mt-1.5 text-[10px] leading-4 text-slate-500"
                    x-text="{
                        AUTO: 'La sala reparte a todos los que entran entre las puertas, respetando sus plazas.',
                        RULES: 'Cada puerta tiene su condición. La primera puerta de la lista que reclama a alguien se lo queda.',
                        MANUAL: 'Eliges una puerta como pincel y pulsas caras en el escenario para meterlas por ella.',
                    }[doors.mode]"></p>
            </section>


            {{-- Solo --}}
            <section x-show="doors.mode === 'AUTO'" class="rounded-2xl border border-slate-800 bg-slate-900/50 p-3">
                <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Con qué criterio</p>

                <div class="mt-1.5 grid grid-cols-2 gap-1">
                    @foreach (['BALANCED' => ['Equilibrado', 'Por turnos, una puerta cada vez'], 'IN_ORDER' => ['En orden', 'Se llena la primera, luego la siguiente'], 'RANDOM' => ['Al azar', 'Barajado, siempre igual con la misma semilla'], 'BY_ATTRIBUTE' => ['Por atributo', 'Cada valor a una puerta']] as $valor => [$texto, $ayuda])
                        <button type="button" @click="doors.strategy = '{{ $valor }}'"
                            :class="doors.strategy === '{{ $valor }}' ? 'border-sky-400 bg-sky-500/15 text-sky-100' : 'border-slate-800 text-slate-400 hover:border-slate-600'"
                            class="rounded-lg border px-2 py-1.5 text-left transition">
                            <span class="block text-[11px] font-black">{{ $texto }}</span>
                            <span class="block text-[9px] leading-3 opacity-70">{{ $ayuda }}</span>
                        </button>
                    @endforeach
                </div>

                <button type="button" x-show="doors.strategy === 'RANDOM'" @click="reshuffle()"
                    class="mt-2 flex w-full items-center justify-center gap-1.5 rounded-lg border border-slate-700 px-2 py-1.5 text-[10px] font-black text-slate-300 transition hover:border-sky-400 hover:text-sky-200">
                    <x-omni-icon name="barajar" size="h-3.5 w-3.5" />
                    Barajar otra vez <span class="font-mono text-slate-500" x-text="'#' + doors.seed"></span>
                </button>

                <div x-show="doors.strategy === 'BY_ATTRIBUTE'" class="mt-2 space-y-1.5">
                    <select x-model="doors.attribute"
                        class="w-full rounded-lg border-slate-700 bg-slate-950 py-1.5 text-[11px] text-slate-200 focus:border-sky-500 focus:ring-sky-500">
                        <option value="">Elige el atributo que reparte…</option>
                        <template x-for="a in catalog" :key="'da' + a.name">
                            <option :value="a.name" :selected="doors.attribute === a.name" x-text="a.label + ' (' + a.entities + ')'"></option>
                        </template>
                    </select>

                    <template x-for="(v, vi) in (attr(doors.attribute)?.values ?? []).filter((x) => x.entities > 0)" :key="'vd' + v.value">
                        <div class="flex items-center gap-2 rounded-lg border border-slate-800 bg-slate-950/60 px-2 py-1">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="`background-color: ${valueTone(v, vi)}`"></span>
                            <span class="min-w-0 flex-1 truncate text-[11px] font-bold text-slate-300" x-text="v.label"></span>
                            <span class="font-mono text-[9px] text-slate-500" x-text="v.entities"></span>
                            <select @change="setValueDoor(v.value, $event.target.value)"
                                class="w-28 rounded-md border-slate-700 bg-slate-900 py-0.5 text-[10px] text-slate-200 focus:border-sky-500 focus:ring-sky-500">
                                <option value="" :selected="valueDoor(v.value) === ''">Automática</option>
                                <template x-for="s in starts" :key="'vds' + v.value + s.id">
                                    <option :value="s.id" :selected="valueDoor(v.value) === s.id" x-text="doorShort(s.id) + ' · ' + s.name"></option>
                                </template>
                            </select>
                        </div>
                    </template>

                    <p class="text-[9px] leading-4 text-slate-600">
                        Cuenta el primer valor de cada competidor. Los valores sin puerta elegida se reparten solos,
                        los más poblados primero.
                    </p>
                </div>

                <label x-show="doors.strategy === 'BY_ATTRIBUTE'" class="mt-2 flex items-start gap-1.5 text-[10px] leading-4 text-slate-400">
                    <input type="checkbox" x-model="doors.fill_rest" class="mt-0.5 rounded border-slate-600 bg-slate-900 text-sky-500 focus:ring-sky-500">
                    Los que no caben o no tienen ese atributo se reparten entre las puertas con sitio
                </label>
            </section>


            {{-- Por reglas --}}
            <section x-show="doors.mode === 'RULES'" class="space-y-1.5">
                <template x-for="s in starts" :key="'dr' + s.id">
                    <div class="rounded-2xl border bg-slate-900/50 p-2.5" :style="`border-color: ${doorColor(s.id)}55`">

                        <div class="flex items-center gap-1.5">
                            <span class="rounded-md px-1.5 py-0.5 font-mono text-[10px] font-black text-slate-950" :style="`background-color: ${doorColor(s.id)}`" x-text="doorShort(s.id)"></span>
                            <span class="min-w-0 flex-1 truncate text-[12px] font-black text-white" x-text="s.name"></span>
                            <span class="font-mono text-[10px]" :style="`color: ${doorState(s.id).tone}`" x-text="doorCount(s.id) + (s.capacity ? '/' + s.capacity : '')"></span>

                            <template x-if="doorRuleOrder(s.id)">
                                <span class="flex items-center">
                                    <span class="rounded bg-slate-800 px-1 font-mono text-[9px] text-slate-400" title="Orden de preferencia" x-text="'#' + doorRuleOrder(s.id)"></span>
                                    <button type="button" @click="moveDoorRow(s.id, -1)" title="Antes" class="p-0.5 text-slate-500 hover:text-white">
                                        <x-omni-icon name="chevron-izquierda" size="h-3 w-3" class="rotate-90" />
                                    </button>
                                    <button type="button" @click="moveDoorRow(s.id, 1)" title="Después" class="p-0.5 text-slate-500 hover:text-white">
                                        <x-omni-icon name="chevron-derecha" size="h-3 w-3" class="rotate-90" />
                                    </button>
                                </span>
                            </template>
                        </div>

                        <p class="mt-1 text-[10px] leading-4 text-slate-400" x-text="doorRuleText(s.id)"></p>

                        <template x-if="doorRowOf(s.id)">
                            <div>
                                <div class="mt-1.5 flex rounded-lg border border-slate-800 bg-slate-950 p-0.5">
                                    @foreach (['ALL' => 'Todas', 'ANY' => 'Alguna', 'NONE' => 'Ninguna', 'ONE' => 'Solo una'] as $valor => $texto)
                                        <button type="button" @click="doorRowOf(s.id).mode = '{{ $valor }}'"
                                            :class="doorRowOf(s.id)?.mode === '{{ $valor }}' ? 'bg-sky-500 text-slate-950' : 'text-slate-500 hover:text-slate-200'"
                                            class="flex-1 rounded px-1 py-0.5 text-[9px] font-black transition">{{ $texto }}</button>
                                    @endforeach
                                </div>

                                <template x-for="(regla, ri) in doorRowOf(s.id).rules" :key="'drr' + s.id + regla.attribute">
                                    <div class="mt-1.5 rounded-lg border border-slate-800 bg-slate-950/60 p-1.5">
                                        <div class="flex items-center gap-1">
                                            <span class="min-w-0 flex-1 truncate text-[11px] font-black text-slate-200" x-text="attrLabel(regla.attribute)"></span>
                                            <button type="button" @click="removeDoorRule(s.id, ri)" class="p-0.5 text-slate-500 hover:text-rose-300">
                                                <x-omni-icon name="cerrar" size="h-3 w-3" />
                                            </button>
                                        </div>
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            <template x-for="v in (attr(regla.attribute)?.values ?? [])" :key="'drv' + s.id + regla.attribute + v.value">
                                                <button type="button" @click="toggleValue(regla, v.value)"
                                                    :style="regla.values.includes(v.value) ? `border-color: ${doorColor(s.id)}; background-color: ${doorColor(s.id)}33; color: #f8fafc` : ''"
                                                    class="rounded-full border border-slate-800 px-1.5 py-0.5 text-[9px] font-bold text-slate-500 transition hover:border-slate-600">
                                                    <span x-text="v.label"></span>
                                                    <span class="font-mono opacity-60" x-text="v.total"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <select @change="addDoorRule(s.id, $event.target.value); $event.target.value = ''"
                            class="mt-1.5 w-full rounded-lg border-slate-700 bg-slate-950 py-1 text-[10px] text-slate-300 focus:border-sky-500 focus:ring-sky-500">
                            <option value="">+ condición para esta puerta</option>
                            <template x-for="a in catalog" :key="'dra' + s.id + a.name">
                                <option :value="a.name" x-text="a.label"></option>
                            </template>
                        </select>
                    </div>
                </template>

                <label class="flex items-start gap-1.5 rounded-xl border border-slate-800 bg-slate-900/50 p-2 text-[10px] leading-4 text-slate-400">
                    <input type="checkbox" x-model="doors.fill_rest" class="mt-0.5 rounded border-slate-600 bg-slate-900 text-sky-500 focus:ring-sky-500">
                    A los que ninguna puerta reclame, repartirlos entre las puertas que aún tienen sitio
                </label>
            </section>


            {{-- A mano --}}
            <section x-show="doors.mode === 'MANUAL'" class="rounded-2xl border border-slate-800 bg-slate-900/50 p-3">
                <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">El pincel</p>
                <p class="mt-0.5 text-[10px] leading-4 text-slate-500">
                    Elige una puerta y pulsa caras en el escenario: entran por ella. Pulsar otra vez la saca.
                </p>

                <div class="mt-2 space-y-1">
                    <template x-for="s in starts" :key="'brush' + s.id">
                        <button type="button" @click="brush = brush === s.id ? null : s.id"
                            class="flex w-full items-center gap-2 rounded-xl border px-2 py-1.5 text-left transition"
                            :style="brush === s.id ? `border-color: ${doorColor(s.id)}; background-color: ${doorColor(s.id)}22` : 'border-color: #1e293b'">
                            <span class="flex h-6 w-6 items-center justify-center rounded-md text-slate-950" :style="`background-color: ${doorColor(s.id)}`">
                                <x-omni-icon name="pincel" size="h-3.5 w-3.5" />
                            </span>
                            <span class="min-w-0 flex-1 truncate text-[11px] font-black text-slate-200" x-text="s.name"></span>
                            <span class="font-mono text-[10px]" :style="`color: ${doorState(s.id).tone}`" x-text="doorCount(s.id) + (s.capacity ? '/' + s.capacity : '')"></span>
                        </button>
                    </template>
                </div>

                <div class="mt-2 grid grid-cols-2 gap-1">
                    <button type="button" @click="doors.mode = 'AUTO'; manualFromPlan()"
                        class="rounded-lg border border-sky-500/30 px-2 py-1.5 text-[10px] font-black text-sky-300 transition hover:bg-sky-500/10"
                        title="Copia el reparto equilibrado y déjalo para retocar">
                        Partir del reparto solo
                    </button>
                    <button type="button" @click="clearManual()"
                        class="rounded-lg border border-slate-700 px-2 py-1.5 text-[10px] font-black text-slate-400 transition hover:border-slate-500 hover:text-white">
                        Vaciar las puertas
                    </button>
                </div>
            </section>


            {{-- Cómo queda cada puerta --}}
            <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-3">
                <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Cómo queda cada puerta</p>

                <template x-for="s in starts" :key="'ds' + s.id">
                    <div class="mt-1.5">
                        <div class="flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 rounded-full" :style="`background-color: ${doorColor(s.id)}`"></span>
                            <span class="min-w-0 flex-1 truncate text-[11px] font-bold text-slate-300" x-text="s.name"></span>
                            <span class="text-[10px] font-black" :style="`color: ${doorState(s.id).tone}`" x-text="doorState(s.id).text"></span>
                        </div>
                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-800">
                            <div class="h-full rounded-full transition-all" :style="`width: ${doorFill(s.id)}%; background-color: ${doorColor(s.id)}`"></div>
                        </div>
                    </div>
                </template>
            </section>
        </div>
    </template>


    {{-- Los que cumplen y no tienen puerta --}}
    <template x-if="starts.length && unplaced.length">
        <section class="rounded-2xl border border-amber-500/30 bg-amber-500/5 p-3">
            <p class="flex items-center gap-1.5 text-[11px] font-black text-amber-200">
                <x-omni-icon name="aviso" size="h-4 w-4" />
                <span x-text="unplaced.length === 1 ? '1 cumple, pero no tiene puerta' : unplaced.length + ' cumplen, pero no tienen puerta'"></span>
            </p>
            <p class="mt-0.5 text-[10px] leading-4 text-slate-500">
                No caben o ninguna puerta los reclama. No jugarían: amplía plazas en la plantilla, cambia el reparto o sácalos a mano.
            </p>
            <div class="mt-2 flex flex-wrap gap-1">
                <template x-for="c in unplaced.slice(0, 24)" :key="'up' + c.id">
                    <button type="button" @click="ficha = c.id" class="h-8 w-8 overflow-hidden rounded-lg border border-amber-500/30 bg-slate-900" :title="c.name">
                        <template x-if="face(c).image_url"><img :src="face(c).image_url" alt="" class="h-full w-full object-cover"></template>
                    </button>
                </template>
            </div>
        </section>
    </template>
</div>
