@php
    /*
     * 06 · QUIÉN ENTRA — y por qué puerta.
     *
     * Un recorrido puede tener varias entradas: «los campeones entran en
     * semifinales, el resto desde la primera ronda». Hasta ahora repartir
     * eso era marcar competidor por competidor en cada caja.
     *
     * Dos formas, y las dos valen:
     *
     *   con reglas   «los que lleven doujutsu → sharingan entran por la
     *                puerta de invitados». Se guarda con la edición, así
     *                que la siguiente se copia sin volver a marcar a nadie.
     *   a mano       cuando no hay ningún atributo que los distinga.
     *
     * Con reglas, quién entra dónde lo calcula el SERVIDOR y no esta
     * pantalla: enseñar un número que luego no coincide con lo que se
     * guarda es peor que no enseñar ninguno.
     */
@endphp

<section x-show="isOpen('doors')" x-cloak
    class="mb-3 overflow-hidden rounded-2xl border border-rose-500/30 bg-slate-900/50">

    <div class="flex items-center gap-2 border-b border-slate-800 bg-rose-500/10 px-4 py-2">
        <span class="font-mono text-[9px] text-slate-600">06</span>
        <span class="text-[11px]">⇥</span>
        <h2 class="text-[11px] font-black uppercase tracking-wider text-rose-300">Quién entra</h2>
        <span class="ml-auto text-[10px] text-slate-600">Y por qué puerta del recorrido</span>
    </div>

    <div class="space-y-3 p-4">

{{--
            Lo que bloquea no es estar editando: es haber EMPEZADO.

            Una edición en borrador tiene su cuadro dibujado en limpio y
            rehacerlo no estropea nada. Una que ya se juega tiene rondas
            hechas, y cambiar la gente dejaría enfrentamientos apuntando a
            quien ya no está.
        --}}

        @if ($competition && ! $canReassign)
            <p class="rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2 text-[10px] leading-relaxed text-slate-500">
                Esta edición <span class="font-bold text-slate-300">ya empezó a jugarse</span>:
                su cuadro está hecho con estos competidores y cambiarlos dejaría
                enfrentamientos apuntando a quien ya no está. Para jugar con otros,
                copia esta edición en una nueva.
            </p>

            {{--
                Aunque no se toquen, se ven. Todas las puertas, no solo la
                primera: una edicion con dos entradas enseñaba media lista.
            --}}

            @foreach ($currentAssignments as $startId => $ids)
                @php
                    $puerta = collect($chosenTemplate['starts'] ?? [])->firstWhere('id', (int) $startId);
                @endphp

                <div>
                    <p class="mb-1.5 text-[9px] font-black uppercase tracking-wider text-slate-500">
                        {{ $puerta['name'] ?? 'Entrada' }}
                        <span class="font-mono text-slate-600">· {{ count($ids) }}</span>
                    </p>

                    <div class="grid gap-1.5 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-7">
                        @foreach ($ids as $id)
                            @php $c = collect($competitors)->firstWhere('id', (int) $id); @endphp
                            @if ($c)
                                <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-950/50">
                                    <span class="relative block aspect-square overflow-hidden bg-slate-950">
                                        @if ($c['image_url'])
                                            <img src="{{ $c['image_url'] }}" alt="" class="h-full w-full object-cover">
                                        @else
                                            <span class="flex h-full w-full items-center justify-center font-mono text-[15px] font-black text-slate-700">
                                                {{ mb_strtoupper(mb_substr($c['name'], 0, 2)) }}
                                            </span>
                                        @endif

                                        <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950 to-transparent px-1.5 pb-1 pt-4">
                                            <span class="block truncate text-[10px] font-black text-slate-100">{{ $c['name'] }}</span>
                                        </span>
                                    </span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach

        @else

        @if ($competition)
            <p class="rounded-xl border border-amber-500/30 bg-amber-500/5 px-3 py-2 text-[10px] leading-relaxed text-amber-200">
                Todavía no ha empezado, así que aún puedes cambiar quién compite. Al
                guardar, su cuadro se <span class="font-bold">vuelve a dibujar</span> con
                la gente que dejes marcada.
            </p>
        @endif

        {{-- ============ EL BALANCE ============ --}}

        <div class="flex flex-wrap items-center gap-3 rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2">

            <span>
                <span class="block font-mono text-[18px] font-black text-rose-300" x-text="totalIn"></span>
                <span class="text-[9px] uppercase tracking-wider text-slate-600">entran</span>
            </span>

            <span class="text-slate-700">de</span>

            <span>
                <span class="block font-mono text-[18px] font-black text-slate-400">{{ count($competitors) }}</span>
                <span class="text-[9px] uppercase tracking-wider text-slate-600">en el universo</span>
            </span>

            <span class="ml-auto text-right">
                <span class="block font-mono text-[14px] font-black"
                    :class="leftovers > 0 ? 'text-amber-400' : 'text-slate-600'"
                    x-text="leftovers"></span>
                <span class="text-[9px] uppercase tracking-wider text-slate-600">se quedan fuera</span>
            </span>
        </div>


        {{-- ============ CÓMO SE REPARTE ============ --}}

        <div class="grid gap-2 sm:grid-cols-2">
            @foreach ([
                'RULES' => ['Con una regla', 'Por sus atributos. Se guarda, y la edición siguiente se copia sin volver a marcar a nadie.'],
                'MANUAL' => ['Uno a uno', 'Marcándolos. Cuando no hay ningún atributo que los distinga.'],
            ] as $value => [$label, $help])
                <button type="button" @click="assignMode = '{{ $value }}'; refreshRouting()"
                    class="rounded-xl border p-2.5 text-left transition"
                    :class="assignMode === '{{ $value }}'
                        ? 'border-rose-400/60 bg-rose-500/10'
                        : 'border-slate-800 bg-slate-950/50 hover:border-slate-700'">

                    <div class="flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full border-2 transition"
                            :class="assignMode === '{{ $value }}'
                                ? 'border-rose-400 bg-rose-400'
                                : 'border-slate-600'"></span>

                        <span class="text-[11px] font-black"
                            :class="assignMode === '{{ $value }}' ? 'text-rose-300' : 'text-slate-300'">
                            {{ $label }}
                        </span>
                    </div>

                    <p class="mt-0.5 text-[9px] leading-relaxed text-slate-500">{{ $help }}</p>
                </button>
            @endforeach
        </div>


        {{-- ============ LO QUE LA FASE VA A REHACER ============ --}}

        {{--
            Repartir por puertas dice QUIÉN entra. En qué grupo cae cada uno lo
            decide la fase de grupos, con el modo que tenga configurado.

            Con «serpiente», «aleatorio» o «bombos» el reparto de aquí se vuelve
            a barajar, y descubrirlo al pulsar «empezar la competición» —cuando
            ya no se puede tocar— es tarde. Se dice antes, con el nombre de la
            fase y del modo, y con lo que hay que cambiar para que se respete.
        --}}

        <template x-if="reshufflingPhases.length">
            <div class="rounded-xl border border-amber-500/40 bg-amber-500/10 px-3 py-2.5">

                <p class="text-[10px] font-black uppercase tracking-wider text-amber-300">
                    ⚠ El reparto por puertas no decidirá los grupos
                </p>

                <template x-for="ph in reshufflingPhases" :key="'rs' + ph.id">
                    <p class="mt-1 text-[10px] leading-relaxed text-slate-300">
                        <strong class="font-black text-slate-100" x-text="ph.name"></strong>
                        reparte en grupos con
                        <strong class="font-black text-amber-200"
                            x-text="distributionLabel(ph.distribution_mode)"></strong>,
                        así que decidirá por su cuenta quién cae en cada grupo.
                        Lo que marques aquí decide <span class="font-bold">quién entra</span>,
                        no en qué grupo acaba.
                    </p>
                </template>

                <p class="mt-1.5 text-[9px] leading-relaxed text-slate-500">
                    Para que cada puerta llene su grupo, cambia el reparto de esa fase
                    a <span class="font-bold text-slate-400">orden de llegada</span> o
                    <span class="font-bold text-slate-400">a mano</span> en su Super Edición.
                </p>
            </div>
        </template>


        {{-- ============ LAS PUERTAS ============ --}}

        <template x-if="templateStarts.length === 0">
            <p class="rounded-xl border border-dashed border-rose-500/40 bg-rose-500/5 px-3 py-4 text-center text-[10px] leading-relaxed text-rose-300">
                La forma elegida no tiene ninguna puerta de entrada, así que nadie
                puede empezar. Añade un inicio a la plantilla en la Super Edición.
            </p>
        </template>

        <div class="space-y-1.5">

            <template x-for="st in templateStarts" :key="'door' + st.id">
                <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-950/50">

                    {{-- La cabecera: cuántos entran y cuántos faltan --}}

                    <div class="flex flex-wrap items-center gap-2 px-3 py-2">

                        <span class="text-[11px]">⇥</span>

                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[11px] font-black text-slate-200" x-text="st.name"></span>
                            <span class="font-mono text-[9px] text-slate-600"
                                x-text="st.capacity ? st.capacity + ' plazas' : 'sin límite'"></span>
                        </span>

                        {{--
                            Cuántos van, de cuántos caben.

                            Antes solo salía el número de dentro y, al lado,
                            una palabra suelta. «2» no dice nada si no
                            recuerdas que la puerta tiene tres plazas.
                        --}}
                        <span class="text-right">
                            <span class="block font-mono text-[15px] font-black leading-none">
                                <span class="text-rose-300" x-text="inDoor(st.id)"></span><span
                                    class="text-slate-600" x-show="st.capacity"
                                    x-text="'/' + st.capacity"></span>
                            </span>
                            <span class="text-[9px] font-bold" :class="doorTone(st.id)" x-text="doorHint(st.id)"></span>
                        </span>

                        <button type="button" @click="openDoor = openDoor === st.id ? null : st.id"
                            class="shrink-0 rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-rose-500 hover:text-rose-300"
                            x-text="openDoor === st.id ? 'listo' : '✎ elegir'"></button>
                    </div>

                    {{-- La barra de llenado --}}

                    <div class="h-1 bg-slate-900" x-show="st.capacity">
                        <div class="h-full transition-all"
                            :class="{
                                'bg-rose-500': doorState(st.id) === 'OVER',
                                'bg-emerald-500': doorState(st.id) === 'FULL',
                                'bg-amber-500': doorState(st.id) === 'PARTIAL' || doorState(st.id) === 'EMPTY',
                            }"
                            :style="'width:' + Math.min(100, (inDoor(st.id) / Math.max(1, st.capacity)) * 100) + '%'"></div>
                    </div>


                    {{-- ============ QUIÉN ESTÁ AQUÍ ============ --}}

                    {{--
                        Siempre visible, con la puerta abierta o cerrada.

                        Un contador no confirma nada: marcas a alguien, el
                        número sube, y sigues sin saber si subió por quien tú
                        querías. Con la cara y el nombre delante se ve de un
                        vistazo, y ese era justo el hueco: la elección a dedo
                        funcionaba y no lo parecía.

                        Vale igual con reglas —lo reparte el servidor— que a
                        mano: la pregunta es la misma.
                    --}}

                    <div class="border-t border-slate-800/70 px-3 py-2">

                        <template x-if="doorRoster(st.id).length === 0">
                            <p class="text-[10px] text-slate-600">
                                Todavía no entra nadie por aquí.
                                <span x-show="assignMode === 'MANUAL'">Pulsa <span class="font-bold text-slate-400">✎ elegir</span> y márcalos.</span>
                                <span x-show="assignMode === 'RULES'">Escribe una condición, o déjala vacía para que entre quien quede libre.</span>
                            </p>
                        </template>

                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="c in doorRoster(st.id)" :key="'in' + st.id + '-' + c.id">
                                <span class="flex items-center gap-1.5 rounded-lg border border-slate-800 bg-slate-950 py-0.5 pl-0.5 pr-2">

                                    <span class="relative block h-6 w-6 shrink-0 overflow-hidden rounded bg-slate-900">
                                        <template x-if="c.image_url">
                                            <img :src="c.image_url" alt="" loading="lazy" class="h-full w-full object-cover">
                                        </template>
                                        <template x-if="!c.image_url">
                                            <span class="flex h-full w-full items-center justify-center font-mono text-[8px] font-black text-slate-700"
                                                x-text="c.name.slice(0, 2).toUpperCase()"></span>
                                        </template>
                                    </span>

                                    <span class="max-w-[120px] truncate text-[10px] font-bold text-slate-200" x-text="c.name"></span>

                                    {{-- Sacarlo desde aquí, sin abrir la puerta --}}
                                    <button type="button" x-show="assignMode === 'MANUAL'"
                                        @click="pick(st.id, c.id)"
                                        title="Sacar de esta entrada"
                                        class="-mr-1 shrink-0 px-0.5 text-[11px] leading-none text-slate-600 transition hover:text-rose-400">×</button>
                                </span>
                            </template>
                        </div>

                        {{--
                            Y los que la regla eligió pero no caben.

                            «19 no caben» era exacto y no servía de nada: no
                            decía por qué —la condición captura a más gente
                            que plazas hay— ni a quiénes deja fuera.
                        --}}
                        <template x-if="assignMode === 'RULES' && doorOverflowRoster(st.id).length">
                            <div class="mt-2 rounded-lg border border-rose-500/30 bg-rose-500/5 p-2">

                                <p class="text-[10px] leading-relaxed text-rose-200">
                                    Tu condición elige a
                                    <span class="font-black" x-text="inDoor(st.id) + doorOverflowRoster(st.id).length"></span>,
                                    y esta entrada tiene
                                    <span class="font-black" x-text="st.capacity"></span>
                                    plazas. Estos
                                    <span class="font-black" x-text="doorOverflowRoster(st.id).length"></span>
                                    se quedan fuera:
                                </p>

                                <div class="mt-1.5 flex flex-wrap gap-1">
                                    <template x-for="c in doorOverflowRoster(st.id)" :key="'ov' + st.id + '-' + c.id">
                                        <span class="flex items-center gap-1 rounded border border-rose-500/20 bg-slate-950 py-0.5 pl-0.5 pr-1.5 opacity-70">
                                            <span class="relative block h-5 w-5 shrink-0 overflow-hidden rounded bg-slate-900">
                                                <template x-if="c.image_url">
                                                    <img :src="c.image_url" alt="" loading="lazy" class="h-full w-full object-cover grayscale">
                                                </template>
                                                <template x-if="!c.image_url">
                                                    <span class="flex h-full w-full items-center justify-center font-mono text-[7px] font-black text-slate-700"
                                                        x-text="c.name.slice(0, 2).toUpperCase()"></span>
                                                </template>
                                            </span>
                                            <span class="max-w-[100px] truncate text-[9px] font-bold text-slate-400" x-text="c.name"></span>
                                        </span>
                                    </template>
                                </div>

                                <p class="mt-1.5 text-[9px] leading-relaxed text-slate-500">
                                    Afina la condición para que elija justo a
                                    <span class="font-bold" x-text="st.capacity"></span>,
                                    o usa <span class="font-bold">✋ a dedo</span> para decidir cuáles.
                                </p>
                            </div>
                        </template>
                    </div>


                    <div x-show="openDoor === st.id" x-cloak class="border-t border-slate-800 bg-slate-900/40 p-3">

                        {{-- ---------------- CON REGLAS ---------------- --}}

                        <div x-show="assignMode === 'RULES'">

                            <div class="flex items-center gap-2">
                                <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                                    Quién entra por aquí
                                </p>

{{--
                                    Cuatro maneras de combinar, las mismas
                                    que un torneo. «Ninguna de» es como se
                                    dice «todos menos los de Akatsuki».
                                --}}
                                <select x-model="doorRule(st.id).mode" @change="refreshRouting()"
                                    x-keep-selected="doorRule(st.id).mode"
                                    class="rounded-lg border-slate-700 bg-slate-950 px-2 py-0.5 text-[10px] text-slate-300 focus:border-rose-500 focus:ring-rose-500">
                                    <option value="ALL">cumple TODAS (Y)</option>
                                    <option value="ANY">cumple ALGUNA (O)</option>
                                    <option value="NONE">no cumple NINGUNA (NI)</option>
                                    <option value="ONE">cumple UNA sola (XOR)</option>
                                </select>

                                <button type="button" @click="addDoorRule(st.id)"
                                    class="ml-auto rounded-lg border border-rose-500/40 px-2 py-1 text-[10px] font-black text-rose-300 transition hover:bg-rose-500/20">
                                    + condición
                                </button>

                                <button type="button" @click="addDoorGroup(st.id)"
                                    class="rounded-lg border border-sky-500/40 px-2 py-1 text-[10px] font-black text-sky-300 transition hover:bg-sky-500/20">
                                    + grupo
                                </button>

                                <button type="button" @click="openDoorHand = openDoorHand === st.id ? null : st.id"
                                    class="rounded-lg border px-2 py-1 text-[10px] font-black transition"
                                    :class="doorHandCount(st.id)
                                        ? 'border-amber-500/60 bg-amber-500/10 text-amber-300'
                                        : 'border-slate-800 text-slate-400 hover:border-amber-500/50 hover:text-amber-300'">
                                    ✋ a dedo
                                    <span x-show="doorHandCount(st.id)" class="font-mono" x-text="doorHandCount(st.id)"></span>
                                </button>
                            </div>

                            <p class="mt-1 text-[10px] leading-relaxed text-slate-600"
                                x-show="doorRule(st.id).rules.length === 0">
                                Sin condiciones entra <span class="font-bold text-slate-400">todo el que
                                quede libre</span>. Es la puerta general, y con un solo inicio es
                                justo lo que hace falta.
                            </p>

                            <div class="mt-2 space-y-1.5">
                                <template x-for="(rule, ri) in doorRule(st.id).rules" :key="'r' + st.id + '-' + ri">
                                    <div class="rounded-lg border border-slate-800 bg-slate-950 p-2">

                                        <div class="flex items-center gap-1.5">
                                            <select x-model="rule.attribute" x-keep-selected="rule.attribute" @change="rule.values = []; refreshRouting()"
                                                class="min-w-0 flex-1 rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-[11px] text-slate-200 focus:border-rose-500 focus:ring-rose-500">
                                                <option value="">— elige un atributo —</option>
                                                <template x-for="a in catalog" :key="'a' + st.id + '-' + ri + '-' + a.name">
                                                    <option :value="a.name"
                                                        x-text="a.label + ' · ' + a.entities + ' lo llevan'"></option>
                                                </template>
                                            </select>

                                            <button type="button" @click="removeDoorRule(st.id, ri); refreshRouting()"
                                                class="shrink-0 px-1 text-[13px] text-slate-600 transition hover:text-rose-400">×</button>
                                        </div>

                                        {{--
                                            Sin valores marcados basta con
                                            TENER el atributo. Con alguno
                                            marcado, hay que llevar uno de
                                            esos: «doujutsu» frente a
                                            «doujutsu → sharingan».
                                        --}}
                                        <div class="mt-1.5 flex flex-wrap gap-1" x-show="rule.attribute">
                                            <template x-for="v in valuesFor(rule.attribute)" :key="'v' + st.id + '-' + ri + '-' + v.value">
                                                <button type="button" @click="toggleValue(rule, v.value); refreshRouting()"
                                                    class="rounded border px-1.5 py-0.5 text-[9px] font-bold transition"
                                                    :class="rule.values.includes(v.value)
                                                        ? 'border-rose-400/60 bg-rose-500/20 text-rose-200'
                                                        : 'border-slate-800 bg-slate-950 text-slate-500 hover:border-slate-600'"
                                                    x-text="v.label + ' (' + v.entities + ')'"></button>
                                            </template>

                                            <span class="text-[9px] text-slate-600" x-show="rule.values.length === 0">
                                                sin marcar ninguno: basta con tenerlo
                                            </span>
                                        </div>
                                    </div>
                                </template>
                            </div>


                            {{-- ============ LOS GRUPOS DE ESTA PUERTA ============ --}}

                            {{--
                                Un grupo es una condición más, con su propio
                                modo. Así se escribe «(aldea hoja Y anime
                                naruto) O (aldea arena)», que con condiciones
                                planas no se puede decir.
                            --}}

                            <div class="mt-2 space-y-1.5">
                                <template x-for="(grupo, gi) in doorRule(st.id).groups" :key="'dg' + st.id + '-' + gi">
                                    <div class="rounded-lg border border-sky-500/30 bg-sky-500/5 p-2">

                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="rounded bg-sky-500/20 px-1.5 py-0.5 text-[9px] font-black text-sky-300">
                                                grupo
                                            </span>

                                            <select x-model="grupo.mode" @change="refreshRouting()"
                                                x-keep-selected="grupo.mode"
                                                class="rounded-lg border-slate-700 bg-slate-950 px-1.5 py-0.5 text-[10px] text-slate-300 focus:border-sky-500 focus:ring-sky-500">
                                                <option value="ALL">Y</option>
                                                <option value="ANY">O</option>
                                                <option value="NONE">NI</option>
                                                <option value="ONE">XOR</option>
                                            </select>

                                            <button type="button" @click="addDoorGroupRule(st.id, gi)"
                                                class="rounded-lg border border-sky-500/40 px-1.5 py-0.5 text-[10px] font-black text-sky-300 transition hover:bg-sky-500/20">
                                                + condición
                                            </button>

                                            <button type="button" @click="removeDoorGroup(st.id, gi)"
                                                class="ml-auto px-1 text-[12px] text-slate-600 transition hover:text-rose-400">×</button>
                                        </div>

                                        <div class="mt-1.5 space-y-1 pl-2">
                                            <template x-for="(regla, ri) in grupo.rules" :key="'dgr' + st.id + '-' + gi + '-' + ri">
                                                <div class="rounded-lg border border-slate-800 bg-slate-950 p-1.5">

                                                    <div class="flex items-center gap-1.5">
                                                        <select x-model="regla.attribute"
                                                            x-keep-selected="regla.attribute"
                                                            @change="regla.values = []; refreshRouting()"
                                                            class="min-w-0 flex-1 rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-[10px] text-slate-200 focus:border-sky-500 focus:ring-sky-500">
                                                            <option value="">— elige un atributo —</option>
                                                            <template x-for="a in catalog" :key="'dga' + st.id + gi + ri + a.name">
                                                                <option :value="a.name" x-text="a.label + ' · ' + a.entities"></option>
                                                            </template>
                                                        </select>

                                                        <button type="button" @click="removeDoorGroupRule(st.id, gi, ri)"
                                                            class="shrink-0 px-1 text-[11px] text-slate-600 transition hover:text-rose-400">×</button>
                                                    </div>

                                                    <div class="mt-1 flex flex-wrap gap-1" x-show="regla.attribute">
                                                        <template x-for="v in valuesFor(regla.attribute)" :key="'dgv' + st.id + gi + ri + v.value">
                                                            <button type="button" @click="toggleDoorGroupValue(st.id, gi, ri, v.value)"
                                                                class="rounded border px-1.5 py-0.5 text-[9px] font-bold transition"
                                                                :class="regla.values.includes(v.value)
                                                                    ? 'border-sky-400/60 bg-sky-500/20 text-sky-200'
                                                                    : 'border-slate-800 bg-slate-950 text-slate-500 hover:border-slate-600'"
                                                                x-text="v.label + ' (' + v.entities + ')'"></button>
                                                        </template>
                                                    </div>

                                                    {{-- Lo que viaja al servidor --}}
                                                    <input type="hidden"
                                                        :name="'start_rules[' + startIndex(st.id) + '][groups][' + gi + '][rules][' + ri + '][attribute]'"
                                                        :value="regla.attribute">
                                                    <template x-for="(v, vi) in regla.values" :key="'dgh' + st.id + gi + ri + vi">
                                                        <input type="hidden"
                                                            :name="'start_rules[' + startIndex(st.id) + '][groups][' + gi + '][rules][' + ri + '][values][]'"
                                                            :value="v">
                                                    </template>
                                                </div>
                                            </template>
                                        </div>

                                        <input type="hidden"
                                            :name="'start_rules[' + startIndex(st.id) + '][groups][' + gi + '][mode]'"
                                            :value="grupo.mode">
                                    </div>
                                </template>
                            </div>


                            {{-- ============ A DEDO, EN ESTA PUERTA ============ --}}

                            {{--
                                Ninguna regla escrita con atributos captura
                                «este entra por aquí porque lo digo yo».
                                Un botón y tres estados: normal, dentro pase
                                lo que pase, fuera pase lo que pase.
                            --}}

                            <div x-show="openDoorHand === st.id" x-cloak
                                class="mt-2 rounded-lg border border-amber-500/30 bg-amber-500/5 p-2">

                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="text-[9px] font-black uppercase tracking-wider text-amber-300">
                                        Decidir a mano
                                    </span>

                                    <input type="search" x-model="search" placeholder="buscar…"
                                        class="min-w-0 flex-1 rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-[10px] text-slate-200 placeholder:text-slate-700 focus:border-amber-500 focus:ring-amber-500">

                                    <span class="rounded bg-emerald-500/20 px-1.5 py-0.5 text-[9px] font-black text-emerald-300">
                                        dentro <span class="font-mono" x-text="doorRule(st.id).include.length"></span>
                                    </span>

                                    <span class="rounded bg-rose-500/20 px-1.5 py-0.5 text-[9px] font-black text-rose-300">
                                        fuera <span class="font-mono" x-text="doorRule(st.id).exclude.length"></span>
                                    </span>

                                    <button type="button" @click="clearDoorHand(st.id)"
                                        x-show="doorHandCount(st.id)"
                                        class="rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-slate-600">
                                        quitar la mano
                                    </button>
                                </div>

                                <div class="mt-2 grid max-h-60 gap-1.5 overflow-y-auto grid-cols-4 sm:grid-cols-6 lg:grid-cols-10">
                                    <template x-for="c in visibleCompetitors" :key="'dh' + st.id + '-' + c.id">
                                        <button type="button" @click="cycleDoorHand(st.id, c.id)"
                                            class="overflow-hidden rounded-lg border transition"
                                            :class="{
                                                'border-emerald-400/60 bg-emerald-500/10': doorHandState(st.id, c.id) === 'IN',
                                                'border-rose-400/60 bg-rose-500/10': doorHandState(st.id, c.id) === 'OUT',
                                                'border-slate-800 bg-slate-950 hover:border-slate-600': doorHandState(st.id, c.id) === 'RULE',
                                            }">

                                            <span class="relative block aspect-square overflow-hidden bg-slate-950">
                                                <template x-if="c.image_url">
                                                    <img :src="c.image_url" alt="" loading="lazy"
                                                        class="h-full w-full object-cover"
                                                        :class="doorHandState(st.id, c.id) === 'OUT' ? 'opacity-25 grayscale' : ''">
                                                </template>
                                                <template x-if="!c.image_url">
                                                    <span class="flex h-full w-full items-center justify-center font-mono text-[10px] font-black text-slate-700"
                                                        x-text="c.name.slice(0, 2).toUpperCase()"></span>
                                                </template>

                                                <template x-if="doorHandState(st.id, c.id) !== 'RULE'">
                                                    <span class="absolute right-0.5 top-0.5 flex h-4 w-4 items-center justify-center rounded-full text-[9px] font-black text-slate-950"
                                                        :class="doorHandState(st.id, c.id) === 'IN' ? 'bg-emerald-400' : 'bg-rose-400'"
                                                        x-text="doorHandState(st.id, c.id) === 'IN' ? '✓' : '×'"></span>
                                                </template>

                                                <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950 to-transparent px-1 pb-0.5 pt-4">
                                                    <span class="block truncate text-[8px] font-black text-slate-100" x-text="c.name"></span>
                                                </span>
                                            </span>
                                        </button>
                                    </template>
                                </div>

                                {{-- Lo que viaja al servidor --}}
                                <template x-for="id in doorRule(st.id).include" :key="'dhi' + st.id + '-' + id">
                                    <input type="hidden" :name="'start_rules[' + startIndex(st.id) + '][include][]'" :value="id">
                                </template>
                                <template x-for="id in doorRule(st.id).exclude" :key="'dhe' + st.id + '-' + id">
                                    <input type="hidden" :name="'start_rules[' + startIndex(st.id) + '][exclude][]'" :value="id">
                                </template>
                            </div>

                            {{--
                                Quiénes entran ya se ve arriba, con sus caras,
                                y quiénes no caben también. Aquí solo queda
                                decir que el servidor está echando la cuenta.
                            --}}
                            <p class="mt-2 text-[10px] text-slate-600" x-show="routingBusy">
                                calculando quién entra…
                            </p>
                        </div>


                        {{-- ---------------- A MANO ---------------- --}}

                        <div x-show="assignMode === 'MANUAL'" x-cloak>

                            {{--
                                La barra de control.

                                El recuento vive AQUÍ y no solo arriba: el
                                balance general queda lejos de donde se
                                pulsa, y al marcar a alguien mirabas la
                                ficha, no la cabecera de la sección.
                            --}}

                            <div class="mb-2 flex flex-wrap items-center gap-1.5">

                                <span class="flex items-baseline gap-1 rounded-lg px-2 py-1"
                                    :class="{
                                        'bg-emerald-500/20': doorState(st.id) === 'FULL',
                                        'bg-rose-500/25': doorState(st.id) === 'OVER',
                                        'bg-rose-500/15': doorState(st.id) !== 'FULL' && doorState(st.id) !== 'OVER',
                                    }">
                                    <span class="font-mono text-[14px] font-black"
                                        :class="doorState(st.id) === 'FULL' ? 'text-emerald-300' : 'text-rose-300'"
                                        x-text="inDoor(st.id)"></span>
                                    <span class="font-mono text-[9px] text-slate-500"
                                        x-text="st.capacity ? '/ ' + st.capacity : 'dentro'"></span>
                                </span>

                                {{--
                                    Que la entrada esté llena hay que decirlo
                                    donde se marca, no solo en la cabecera:
                                    marcando caras no se mira arriba.

                                    No se bloquea a propósito —a veces quieres
                                    poner uno más y luego quitar otro— pero
                                    pasarse se ve en rojo y no se escapa.
                                --}}
                                <span x-show="doorState(st.id) === 'FULL'"
                                    class="rounded-lg bg-emerald-500/15 px-2 py-1 text-[10px] font-black text-emerald-300">
                                    ✓ completa
                                </span>

                                <span x-show="doorState(st.id) === 'OVER'"
                                    class="rounded-lg bg-rose-500/20 px-2 py-1 text-[10px] font-black text-rose-300">
                                    ⚠ te pasaste por
                                    <span class="font-mono" x-text="Math.abs(doorRoom(st.id))"></span>
                                </span>

                                <input type="search" x-model="search" placeholder="buscar por nombre o atributo…"
                                    class="min-w-0 flex-1 rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-[11px] text-slate-200 placeholder:text-slate-700 focus:border-rose-500 focus:ring-rose-500">

                                {{-- Cómo se miran --}}

                                <div class="flex rounded-lg border border-slate-800 bg-slate-950 p-0.5">
                                    @foreach ([
                                        'LIST' => ['☰', 'Lista: una línea, con todo su catálogo'],
                                        'GALLERY' => ['▤', 'Galería: la cara y sus atributos'],
                                        'TILES' => ['▦', 'Cuadritos: solo la cara, para ver muchos'],
                                    ] as $modo => [$icono, $ayuda])
                                        <button type="button" @click="setView('{{ $modo }}')"
                                            title="{{ $ayuda }}"
                                            class="rounded-md px-2 py-1 text-[11px] transition"
                                            :class="pickerView === '{{ $modo }}'
                                                ? 'bg-rose-500 text-slate-950'
                                                : 'text-slate-500 hover:text-slate-200'">{{ $icono }}</button>
                                    @endforeach
                                </div>

                                {{-- Y de qué tamaño --}}

                                <div class="flex items-center gap-0.5 rounded-lg border border-slate-800 bg-slate-950"
                                    x-show="pickerView !== 'LIST'">

                                    <button type="button" @click="setSize(pickerSize + 1)"
                                        title="Más pequeños"
                                        class="px-2 py-1 text-[13px] leading-none text-slate-500 transition hover:text-slate-100">−</button>

                                    <span class="font-mono text-[9px] text-slate-600" x-text="pickerSize"></span>

                                    <button type="button" @click="setSize(pickerSize - 1)"
                                        title="Más grandes"
                                        class="px-2 py-1 text-[13px] leading-none text-slate-500 transition hover:text-slate-100">+</button>
                                </div>

                                <button type="button" @click="pickVisible(st.id, true)"
                                    class="rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-rose-500 hover:text-rose-300">
                                    todos
                                </button>

                                <button type="button" @click="pickVisible(st.id, false)"
                                    class="rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-slate-600">
                                    ninguno
                                </button>

                                {{--
                                    Reordenar es a petición. Hacerlo en cada
                                    clic mandaba la ficha recién marcada a la
                                    cabeza y te dejaba sin saber por dónde
                                    ibas.
                                --}}
                                <button type="button" @click="freezeOrder(st.id)"
                                    title="Poner delante a los que ya están dentro"
                                    class="rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-slate-600">
                                    ⇅ reordenar
                                </button>
                            </div>


                            <div class="grid max-h-[28rem] gap-1.5 overflow-y-auto" :class="pickerGrid">

                                <template x-for="c in competitorsFor(st.id)" :key="'c' + st.id + '-' + c.id">
                                    <button type="button" @click="pick(st.id, c.id)"
                                        class="group overflow-hidden rounded-lg border text-left transition"
                                        :class="isPicked(st.id, c.id)
                                            ? 'border-rose-400/60 bg-rose-500/10'
                                            : 'border-slate-800 bg-slate-950 hover:border-slate-600'">


                                        {{-- ====== CUADRITOS: solo la cara ====== --}}

                                        <template x-if="pickerView === 'TILES'">
                                            <span class="relative block aspect-square overflow-hidden bg-slate-950"
                                                :title="c.name + (chipsOf(c).length ? ' · ' + chipsOf(c).map(x => x.value).join(', ') : '')">

                                                <template x-if="c.image_url">
                                                    <img :src="c.image_url" alt="" loading="lazy"
                                                        class="h-full w-full object-cover transition"
                                                        :class="isPicked(st.id, c.id) ? '' : 'opacity-50 group-hover:opacity-100'">
                                                </template>

                                                <template x-if="!c.image_url">
                                                    <span class="flex h-full w-full items-center justify-center font-mono text-[10px] font-black text-slate-700"
                                                        x-text="c.name.slice(0, 2).toUpperCase()"></span>
                                                </template>

                                                <template x-if="isPicked(st.id, c.id)">
                                                    <span class="absolute inset-0 ring-2 ring-inset ring-rose-400"></span>
                                                </template>
                                            </span>
                                        </template>


                                        {{-- ====== GALERÍA: cara y atributos ====== --}}

                                        <template x-if="pickerView === 'GALLERY'">
                                            <span class="block">
                                                <span class="relative block aspect-square overflow-hidden bg-slate-950">

                                                    <template x-if="c.image_url">
                                                        <img :src="c.image_url" alt="" loading="lazy"
                                                            class="h-full w-full object-cover transition duration-200 group-hover:scale-105"
                                                            :class="isPicked(st.id, c.id) ? '' : 'opacity-60 group-hover:opacity-100'">
                                                    </template>

                                                    <template x-if="!c.image_url">
                                                        <span class="flex h-full w-full items-center justify-center font-mono text-[14px] font-black text-slate-700"
                                                            x-text="c.name.slice(0, 2).toUpperCase()"></span>
                                                    </template>

                                                    <template x-if="isPicked(st.id, c.id)">
                                                        <span class="absolute right-1 top-1 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[9px] font-black text-slate-950">✓</span>
                                                    </template>

                                                    <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950 to-transparent px-1.5 pb-1 pt-4">
                                                        <span class="block truncate text-[10px] font-black text-slate-100" x-text="c.name"></span>
                                                    </span>
                                                </span>

                                                {{-- Su catálogo --}}

                                                <span class="flex flex-wrap gap-0.5 p-1">
                                                    <template x-for="chip in chipsOf(c).slice(0, 4)" :key="'g' + c.id + chip.key">
                                                        <span class="truncate rounded bg-slate-900 px-1 py-0.5 text-[8px] text-slate-400"
                                                            :title="chip.attribute + ': ' + chip.value"
                                                            x-text="chip.value"></span>
                                                    </template>

                                                    <template x-if="chipsOf(c).length === 0">
                                                        <span class="text-[8px] text-slate-700">sin atributos</span>
                                                    </template>
                                                </span>

                                                <span class="block truncate px-1 pb-1 text-[8px]"
                                                    :class="whereIs(c.id) && !isPicked(st.id, c.id) ? 'text-amber-400' : 'text-transparent'"
                                                    x-text="whereIs(c.id) && !isPicked(st.id, c.id) ? '→ ' + whereIs(c.id) : '·'"></span>
                                            </span>
                                        </template>


                                        {{-- ====== LISTA: una línea con todo ====== --}}

                                        <template x-if="pickerView === 'LIST'">
                                            <span class="flex items-center gap-2 p-1.5">

                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-900">
                                                    <template x-if="c.image_url">
                                                        <img :src="c.image_url" alt="" loading="lazy" class="h-full w-full object-cover">
                                                    </template>
                                                    <template x-if="!c.image_url">
                                                        <span class="font-mono text-[9px] font-black text-slate-700"
                                                            x-text="c.name.slice(0, 2).toUpperCase()"></span>
                                                    </template>
                                                </span>

                                                <span class="w-36 shrink-0">
                                                    <span class="block truncate text-[11px] font-black text-slate-100" x-text="c.name"></span>
                                                    <span class="block truncate text-[8px] text-slate-600" x-text="c.type ?? ''"></span>
                                                </span>

                                                {{--
                                                    En lista cabe TODO su
                                                    catálogo, con el nombre
                                                    del atributo delante: es
                                                    la vista para decidir por
                                                    lo que uno es, no por su
                                                    cara.
                                                --}}
                                                <span class="flex min-w-0 flex-1 flex-wrap gap-0.5">
                                                    <template x-for="chip in chipsOf(c)" :key="'l' + c.id + chip.key">
                                                        <span class="rounded bg-slate-900 px-1.5 py-0.5 text-[9px]">
                                                            <span class="text-slate-600" x-text="chip.attribute"></span>
                                                            <span class="ml-1 text-slate-300" x-text="chip.value"></span>
                                                        </span>
                                                    </template>

                                                    <template x-if="chipsOf(c).length === 0">
                                                        <span class="text-[9px] text-slate-700">sin atributos</span>
                                                    </template>
                                                </span>

                                                <span class="shrink-0 text-[9px]"
                                                    :class="whereIs(c.id) && !isPicked(st.id, c.id) ? 'text-amber-400' : 'text-slate-700'"
                                                    x-text="whereIs(c.id) && !isPicked(st.id, c.id) ? '→ ' + whereIs(c.id) : ''"></span>

                                                <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full text-[9px] font-black transition"
                                                    :class="isPicked(st.id, c.id)
                                                        ? 'bg-rose-500 text-slate-950'
                                                        : 'border border-slate-700 text-transparent'">✓</span>
                                            </span>
                                        </template>

                                    </button>
                                </template>

                                <template x-if="visibleCompetitors.length === 0">
                                    <p class="col-span-full py-4 text-center text-[10px] text-slate-600">
                                        Ningún competidor coincide con la búsqueda.
                                    </p>
                                </template>
                            </div>

                            {{--
                                Lo que de verdad se envía.

                                x-if y no x-show, al contrario que en casi toda
                                la aplicación: aquí lo que se quiere es que NO
                                viajen. El servidor prefiere el reparto a mano
                                sobre las reglas cuando llega relleno, así que
                                dejarlo en el DOM en modo «con una regla» haría
                                ganar siempre a un reparto que el usuario ya no
                                quiere.
                            --}}
                            <template x-if="assignMode === 'MANUAL'">
                                <span>
                                    <template x-for="id in (manual[st.id] ?? [])" :key="'h' + st.id + '-' + id">
                                        <input type="hidden" :name="'assignments[' + st.id + '][]'" :value="id">
                                    </template>
                                </span>
                            </template>
                        </div>

                    </div>
                </div>
            </template>
        </div>


        {{-- Las reglas, para que viajen con el formulario --}}

        <template x-for="(row, i) in startRules" :key="'sr' + row.start_id">
            <span x-show="assignMode === 'RULES'">
                <input type="hidden" :name="'start_rules[' + i + '][start_id]'" :value="row.start_id">
                <input type="hidden" :name="'start_rules[' + i + '][mode]'" :value="row.mode">

                <template x-for="(rule, ri) in row.rules" :key="'srr' + row.start_id + '-' + ri">
                    <span>
                        <input type="hidden" :name="'start_rules[' + i + '][rules][' + ri + '][attribute]'" :value="rule.attribute">
                        <template x-for="(v, vi) in rule.values" :key="'srv' + row.start_id + '-' + ri + '-' + vi">
                            <input type="hidden" :name="'start_rules[' + i + '][rules][' + ri + '][values][]'" :value="v">
                        </template>
                    </span>
                </template>
            </span>
        </template>

        <x-input-error :messages="$errors->get('assignments')" class="mt-1" />

        @endif

    </div>
</section>
