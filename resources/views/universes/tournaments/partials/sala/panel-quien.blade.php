{{--
    QUIÉN ENTRA

    Cómo se combinan las condiciones, las condiciones, el catálogo del que
    salen y lo que se decidió a mano. El catálogo está en árbol: un valor
    arrastra a sus hijos salvo que se diga lo contrario.
--}}

<div x-show="panel === 'who'" class="space-y-2">

    {{-- ============ CÓMO SE COMBINAN ============ --}}

    <section class="rounded-2xl border border-rose-500/25 bg-slate-900/50 p-3">
        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Cómo se combinan las condiciones</p>

        <div class="mt-1.5 grid grid-cols-4 gap-1">
            @foreach (['ALL' => ['Todas', 'Y'], 'ANY' => ['Alguna', 'O'], 'NONE' => ['Ninguna', 'NI'], 'ONE' => ['Solo una', 'XOR']] as $valor => [$texto, $simbolo])
                <button type="button" @click="setMode('{{ $valor }}')"
                    :class="mode === '{{ $valor }}' ? 'border-rose-400 bg-rose-500/20 text-rose-100' : 'border-slate-800 text-slate-400 hover:border-slate-600'"
                    class="rounded-lg border px-1 py-1.5 text-center transition">
                    <span class="block text-[11px] font-black">{{ $texto }}</span>
                    <span class="block font-mono text-[9px] opacity-60">{{ $simbolo }}</span>
                </button>
            @endforeach
        </div>

        <p class="mt-1.5 text-[10px] leading-4 text-slate-500"
            x-text="{
                ALL: 'Entra quien cumpla todas las condiciones.',
                ANY: 'Entra quien cumpla al menos una.',
                NONE: 'Entra quien no cumpla ninguna: sirve para dejar fuera a un grupo.',
                ONE: 'Entra quien cumpla exactamente una, ni dos ni ninguna.',
            }[mode]"></p>
    </section>


    {{-- ============ LAS CONDICIONES ============ --}}

    <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-3">

        <div class="flex items-center gap-2">
            <p class="mr-auto text-[12px] font-black text-white">
                Condiciones
                <span class="font-mono text-[10px] text-slate-500" x-text="rules.length + groups.length"></span>
            </p>

            <button type="button" @click="addGroup()"
                class="rounded-lg border border-sky-500/40 px-2 py-1 text-[10px] font-black text-sky-300 transition hover:bg-sky-500/10">
                + Grupo
            </button>

            <button type="button" x-show="rules.length || groups.length || include.length || exclude.length" @click="clearAll()"
                class="rounded-lg border border-slate-700 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-slate-500 hover:text-white">
                Abrir a todos
            </button>
        </div>

        <template x-if="! rules.length && ! groups.length">
            <p class="mt-2 rounded-xl border border-dashed border-slate-700 px-3 py-4 text-center text-[10px] leading-4 text-slate-500">
                Sin condiciones: entra <span class="font-black text-slate-300">todo el universo</span>.
                Abre un atributo del catálogo de abajo y añade un valor, o «todos los que lo tengan».
            </p>
        </template>

        <template x-for="(regla, i) in rules" :key="'r' + regla.attribute">
            <div class="mt-2 rounded-xl border border-rose-500/30 bg-rose-500/5 p-2">

                <div class="flex items-center gap-1.5">
                    <span class="h-4 w-1 shrink-0 rounded-full bg-rose-400"></span>
                    <span class="min-w-0 flex-1 truncate text-[12px] font-black text-white" x-text="attrLabel(regla.attribute)"></span>
                    <span class="shrink-0 font-mono text-[9px] text-slate-500" x-text="(attr(regla.attribute)?.entities ?? 0) + ' lo tienen'"></span>
                    <button type="button" @click="removeRule(i)" title="Quitar esta condición"
                        class="shrink-0 rounded p-0.5 text-slate-500 transition hover:text-rose-300">
                        <x-omni-icon name="cerrar" size="h-3.5 w-3.5" />
                    </button>
                </div>

                <p class="mt-1 pl-2.5 text-[10px] text-slate-500"
                    x-text="regla.values.length ? 'Con cualquiera de los marcados:' : 'Basta con tener el atributo, con el valor que sea. Marca valores para estrechar.'"></p>

                <div class="mt-1 flex flex-wrap gap-1 pl-2.5">
                    <template x-for="v in (attr(regla.attribute)?.values ?? [])" :key="regla.attribute + ':' + v.value">
                        <button type="button" @click="toggleValue(regla, v.value)"
                            class="flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-bold transition"
                            :class="regla.values.includes(v.value)
                                ? 'border-rose-400/70 bg-rose-500/25 text-rose-100'
                                : (regla.descendants !== false && regla.values.length && expand(regla.attribute, regla.values).includes(v.value)
                                    ? 'border-rose-400/30 bg-rose-500/5 text-rose-300'
                                    : 'border-slate-800 text-slate-500 hover:border-slate-600 hover:text-slate-300')"
                            :title="regla.descendants !== false && ! regla.values.includes(v.value) && regla.values.length && expand(regla.attribute, regla.values).includes(v.value) ? 'Entra por ser hijo de un valor marcado' : ''">
                            <template x-if="v.image"><img :src="v.image" alt="" class="h-3.5 w-3.5 rounded-full object-cover"></template>
                            <span x-show="! v.image && v.color" class="h-2 w-2 rounded-full" :style="`background-color: ${v.color}`"></span>
                            <span x-show="v.depth" class="text-slate-600" x-text="'·'.repeat(v.depth)"></span>
                            <span x-text="v.label"></span>
                            <span class="font-mono opacity-60" x-text="v.total"></span>
                        </button>
                    </template>
                </div>

                <label x-show="attr(regla.attribute)?.hierarchical" class="mt-1.5 flex items-center gap-1.5 pl-2.5 text-[10px] text-slate-400">
                    <input type="checkbox" :checked="regla.descendants !== false" @change="regla.descendants = $event.target.checked"
                        class="rounded border-slate-600 bg-slate-900 text-rose-500 focus:ring-rose-500">
                    Un valor arrastra a sus sub-elementos del catálogo
                </label>
            </div>
        </template>


        {{-- Los grupos --}}
        <template x-for="(g, gi) in groups" :key="'g' + gi">
            <div class="mt-2 rounded-xl border border-sky-500/30 bg-sky-500/5 p-2">

                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="rounded bg-sky-500/20 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider text-sky-300">Grupo</span>

                    <span class="flex rounded-lg border border-slate-800 bg-slate-950 p-0.5">
                        @foreach (['ALL' => 'Y', 'ANY' => 'O', 'NONE' => 'NI', 'ONE' => 'XOR'] as $valor => $simbolo)
                            <button type="button" @click="g.mode = '{{ $valor }}'"
                                :class="g.mode === '{{ $valor }}' ? 'bg-sky-500 text-slate-950' : 'text-slate-500 hover:text-slate-200'"
                                class="rounded px-1.5 py-0.5 font-mono text-[10px] font-black transition">{{ $simbolo }}</button>
                        @endforeach
                    </span>

                    <span class="min-w-0 flex-1 truncate text-[10px] text-sky-200/80"
                        x-text="g.rules.length ? 'cuenta como una sola condición' : 'vacío: añade atributos'"></span>

                    <button type="button" @click="removeGroup(gi)" title="Quitar el grupo"
                        class="rounded p-0.5 text-slate-500 transition hover:text-rose-300">
                        <x-omni-icon name="cerrar" size="h-3.5 w-3.5" />
                    </button>
                </div>

                <template x-for="(regla, ri) in g.rules" :key="'g' + gi + 'r' + regla.attribute">
                    <div class="mt-1.5 rounded-lg border border-slate-800 bg-slate-950/60 p-1.5">
                        <div class="flex items-center gap-1.5">
                            <span class="min-w-0 flex-1 truncate text-[11px] font-black text-slate-200" x-text="attrLabel(regla.attribute)"></span>
                            <button type="button" @click="g.rules.splice(ri, 1)" class="rounded p-0.5 text-slate-500 transition hover:text-rose-300">
                                <x-omni-icon name="cerrar" size="h-3 w-3" />
                            </button>
                        </div>
                        <div class="mt-1 flex flex-wrap gap-1">
                            <template x-for="v in (attr(regla.attribute)?.values ?? [])" :key="'g' + gi + regla.attribute + v.value">
                                <button type="button" @click="toggleValue(regla, v.value)"
                                    :class="regla.values.includes(v.value) ? 'border-sky-400/70 bg-sky-500/25 text-sky-100' : 'border-slate-800 text-slate-500 hover:border-slate-600'"
                                    class="rounded-full border px-1.5 py-0.5 text-[9px] font-bold transition">
                                    <span x-text="v.label"></span>
                                    <span class="font-mono opacity-60" x-text="v.total"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                <select @change="addGroupRule(gi, $event.target.value); $event.target.value = ''"
                    class="mt-1.5 w-full rounded-lg border-slate-700 bg-slate-950 py-1 text-[10px] text-slate-300 focus:border-sky-500 focus:ring-sky-500">
                    <option value="">+ añadir un atributo al grupo</option>
                    <template x-for="a in catalog" :key="'go' + gi + a.name">
                        <option :value="a.name" x-text="a.label + ' (' + a.entities + ')'"></option>
                    </template>
                </select>
            </div>
        </template>

        <p x-show="groups.length" class="mt-2 text-[9px] leading-4 text-slate-600">
            Un grupo cuenta como una sola condición. Así se dice «(aldea Hoja Y anime Naruto) O aldea Arena».
        </p>
    </section>


    {{-- ============ EL CATÁLOGO ============ --}}

    <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-3">

        <div class="flex items-center gap-2">
            <p class="mr-auto text-[12px] font-black text-white">Catálogo del universo</p>
            <span class="font-mono text-[10px] text-slate-500" x-text="catalog.length + ' atributos'"></span>
        </div>

        <p class="mt-0.5 text-[10px] leading-4 text-slate-500">
            Solo lo que llevan de verdad los habitantes de este universo, con cuántos lo llevan.
        </p>

        <input type="search" x-model="attrSearch" @keydown.enter.prevent placeholder="Buscar un atributo o un valor…"
            class="mt-2 w-full rounded-lg border-slate-700 bg-slate-950 py-1.5 text-[11px] text-slate-200 placeholder:text-slate-600 focus:border-rose-500 focus:ring-rose-500">

        <template x-if="! catalog.length">
            <p class="mt-2 rounded-xl border border-dashed border-slate-700 px-3 py-4 text-center text-[10px] leading-4 text-slate-500">
                Los habitantes de este universo no tienen atributos, así que no hay nada por lo que filtrar.
                Puedes decidir a mano pulsando sus caras.
            </p>
        </template>

        <template x-for="a in filteredCatalog" :key="'c' + a.name">
            <div class="mt-1.5 overflow-hidden rounded-xl border bg-slate-950/60 transition"
                :class="ruleFor(a.name) ? 'border-rose-500/40' : 'border-slate-800'">

                <button type="button" @click="openAttr = openAttr === a.name ? null : a.name"
                    class="flex w-full items-center gap-2 px-2.5 py-2 text-left transition hover:bg-slate-900">
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center gap-1.5">
                            <span class="truncate text-[12px] font-black text-slate-200" x-text="a.label"></span>
                            <span x-show="a.hierarchical" class="rounded bg-violet-500/15 px-1 text-[8px] font-black uppercase text-violet-300">árbol</span>
                            <span x-show="ruleFor(a.name)" class="rounded bg-rose-500/20 px-1 text-[8px] font-black uppercase text-rose-300">en uso</span>
                        </span>
                        <span class="mt-1 block h-1 overflow-hidden rounded-full bg-slate-800">
                            <span class="block h-full rounded-full bg-rose-400/70" :style="`width: ${roster.length ? (a.entities / roster.length) * 100 : 0}%`"></span>
                        </span>
                    </span>
                    <span class="shrink-0 font-mono text-[10px] text-slate-500" x-text="a.entities + '/' + roster.length"></span>
                    <x-omni-icon name="chevron-derecha" size="h-3.5 w-3.5" class="shrink-0 text-slate-600 transition" x-bind:class="openAttr === a.name ? 'rotate-90' : ''" />
                </button>

                <div x-show="openAttr === a.name" x-cloak class="border-t border-slate-800 p-2">

                    <button type="button" @click="addRule(a.name)"
                        class="mb-1.5 w-full rounded-lg border border-dashed border-rose-500/40 px-2 py-1.5 text-[10px] font-black text-rose-300 transition hover:bg-rose-500/10">
                        Todos los que tengan <span x-text="a.label"></span>
                    </button>

                    <template x-for="v in a.values" :key="'cv' + a.name + v.value">
                        <div class="flex items-center gap-1.5 rounded-lg py-1 pr-1 transition hover:bg-slate-900"
                            :style="`padding-left: ${4 + v.depth * 14}px`">

                            <span x-show="v.depth" class="h-2.5 w-2 shrink-0 border-b border-l border-slate-700"></span>

                            <span class="flex h-6 w-6 shrink-0 items-center justify-center overflow-hidden rounded-md bg-slate-900">
                                <template x-if="v.image"><img :src="v.image" alt="" class="h-full w-full object-cover"></template>
                                <template x-if="! v.image">
                                    <span class="h-2.5 w-2.5 rounded-full" :style="`background-color: ${v.color || '#475569'}`"></span>
                                </template>
                            </span>

                            <span class="min-w-0 flex-1 truncate text-[11px] font-bold text-slate-300" x-text="v.label"></span>

                            <span class="shrink-0 font-mono text-[9px] text-slate-500"
                                :title="v.total !== v.entities ? v.entities + ' directos, ' + v.total + ' contando sus hijos' : ''"
                                x-text="v.total"></span>

                            <button type="button"
                                @click="ruleFor(a.name)?.values.includes(v.value) ? toggleValue(ruleFor(a.name), v.value) : addRule(a.name, v.value)"
                                :class="ruleFor(a.name)?.values.includes(v.value)
                                    ? 'bg-rose-500 text-slate-950'
                                    : 'border border-slate-700 text-slate-400 hover:border-rose-400 hover:text-rose-300'"
                                class="shrink-0 rounded-md px-1.5 py-0.5 text-[9px] font-black transition"
                                x-text="ruleFor(a.name)?.values.includes(v.value) ? 'quitar' : '+ añadir'"></button>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </section>


    {{-- ============ A MANO ============ --}}

    <section class="rounded-2xl border border-amber-500/25 bg-slate-900/50 p-3">

        <div class="flex items-center gap-2">
            <p class="mr-auto text-[12px] font-black text-white">Decidido a mano</p>
            <span class="rounded bg-emerald-500/15 px-1.5 py-0.5 font-mono text-[9px] font-black text-emerald-300" x-text="include.length + ' dentro'"></span>
            <span class="rounded bg-rose-500/15 px-1.5 py-0.5 font-mono text-[9px] font-black text-rose-300" x-text="exclude.length + ' fuera'"></span>
        </div>

        <p class="mt-0.5 text-[10px] leading-4 text-slate-500">
            Pulsa cualquier cara para abrir su ficha y meterla o sacarla pase lo que digan las condiciones.
            Sacar gana a meter.
        </p>

        <div x-show="include.length || exclude.length" class="mt-2 flex flex-wrap gap-1">
            <template x-for="c in roster.filter((x) => handOf(x.id))" :key="'mano' + c.id">
                <span class="flex items-center gap-1 rounded-lg border bg-slate-950 py-0.5 pl-0.5 pr-1"
                    :class="handOf(c.id) === 'IN' ? 'border-emerald-500/40' : 'border-rose-500/40'">
                    <span class="h-5 w-5 overflow-hidden rounded bg-slate-900">
                        <template x-if="face(c).image_url"><img :src="face(c).image_url" alt="" class="h-full w-full object-cover"></template>
                    </span>
                    <button type="button" @click="ficha = c.id" class="max-w-[110px] truncate text-[10px] font-bold text-slate-200" x-text="c.name"></button>
                    <button type="button" @click="setHand(c.id, null)" title="Que decidan las condiciones" class="text-slate-500 transition hover:text-white">
                        <x-omni-icon name="cerrar" size="h-3 w-3" />
                    </button>
                </span>
            </template>
        </div>

        <div class="mt-2 grid grid-cols-2 gap-1">
            <button type="button" @click="handVisible('IN')" :disabled="! search.trim() && ! faceFilter"
                class="rounded-lg border border-emerald-500/30 px-2 py-1.5 text-[10px] font-black text-emerald-300 transition hover:bg-emerald-500/10 disabled:opacity-30"
                title="Mete a mano a los que deja ver la búsqueda">
                Meter a los buscados
            </button>
            <button type="button" @click="handVisible('OUT')" :disabled="! search.trim() && ! faceFilter"
                class="rounded-lg border border-rose-500/30 px-2 py-1.5 text-[10px] font-black text-rose-300 transition hover:bg-rose-500/10 disabled:opacity-30"
                title="Saca a mano a los que deja ver la búsqueda">
                Sacar a los buscados
            </button>
            <button type="button" x-show="noImage.length" @click="dropWithoutImage()"
                class="col-span-2 rounded-lg border border-amber-500/30 px-2 py-1.5 text-[10px] font-black text-amber-300 transition hover:bg-amber-500/10">
                Sacar a los <span x-text="noImage.length"></span> que salen sin imagen
            </button>
            <button type="button" x-show="include.length || exclude.length" @click="include = []; exclude = []"
                class="col-span-2 rounded-lg border border-slate-700 px-2 py-1.5 text-[10px] font-black text-slate-400 transition hover:border-slate-500 hover:text-white">
                Olvidar todo lo decidido a mano
            </button>
        </div>
    </section>
</div>
