@php
    /*
     * 06 · QUIÉN COMPITE — las reglas de participación.
     *
     * Se escriben con los ATRIBUTOS de los competidores, y el catálogo no
     * se inventa: sale de las entidades que ya viven en este universo. Si
     * nadie tiene «doujutsu», «doujutsu» no aparece. Ofrecer un filtro que
     * no puede casar con nadie es ofrecer un callejón sin salida.
     *
     * Una regla tiene dos formas:
     *
     *   solo el atributo    cualquiera que lo TENGA, con el valor que sea
     *   con valores         solo los que lo tengan con uno de esos valores
     *
     * Y se combinan de dos maneras: todas (Y) o cualquiera (O).
     *
     * Debajo, la galería. Sin ver las caras, elegir atributos es escribir a
     * ciegas: la diferencia entre 19 y 2 competidores no se adivina leyendo
     * el nombre de un filtro. Y se recalcula en la propia pantalla, así que
     * responde en el mismo clic que la provoca.
     */
@endphp

<section x-show="isOpen('who')" x-cloak
    class="mb-3 overflow-hidden rounded-2xl border border-rose-500/30 bg-slate-900/50">

    <div class="flex items-center gap-2 border-b border-slate-800 bg-rose-500/10 px-4 py-2">
        <span class="font-mono text-[9px] text-slate-600">06</span>
        <span class="text-[11px]">⚑</span>
        <h2 class="text-[11px] font-black uppercase tracking-wider text-rose-300">Quién compite</h2>
        <span class="ml-auto text-[10px] text-slate-600">Reglas por atributos de tus competidores</span>
    </div>

    @if (empty($eligibilityCatalog))
        <div class="p-6 text-center">
            <p class="text-[12px] font-black text-slate-300">
                Los competidores de este universo no tienen atributos
            </p>
            <p class="mx-auto mt-1 max-w-md text-[10px] leading-relaxed text-slate-500">
                Las reglas de participación se escriben con los atributos de las entidades
                importadas. Sin atributos no hay nada por lo que filtrar, así que este torneo
                queda abierto a todos.
            </p>
        </div>
    @else

    <div class="p-4">

        {{-- ==================== LAS REGLAS ==================== --}}

{{--
            Cuatro maneras de combinar, no dos.

            «Ninguna de» y «exactamente una» no son adornos: el primero es
            como se dice «todos menos los de Akatsuki», y el segundo como se
            dice «o eres de Konoha o eres de Suna, pero no las dos».
        --}}

        <div class="flex flex-wrap items-center gap-2">
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                Hay que
            </span>

            <div class="flex flex-wrap rounded-lg border border-slate-800 bg-slate-950 p-0.5">
                @foreach ([
                    'ALL' => ['cumplir TODAS', 'Y'],
                    'ANY' => ['cumplir ALGUNA', 'O'],
                    'NONE' => ['no cumplir NINGUNA', 'NI'],
                    'ONE' => ['cumplir UNA sola', 'XOR'],
                ] as $value => [$label, $simbolo])
                    <button type="button" @click="setEligibilityMode('{{ $value }}')"
                        class="rounded-md px-2.5 py-1 text-[10px] font-black transition"
                        :class="eligibilityMode === '{{ $value }}'
                            ? 'bg-rose-500 text-slate-950'
                            : 'text-slate-400 hover:text-slate-100'">
                        {{ $label }}
                        <span class="ml-0.5 font-mono opacity-50">{{ $simbolo }}</span>
                    </button>
                @endforeach
            </div>

            <span class="text-[10px] text-slate-600" x-text="modeHelp(eligibilityMode)"></span>

            <input type="hidden" name="eligibility_mode" :value="eligibilityMode">
        </div>


        <div class="mt-3 space-y-2">

            <template x-for="(rule, i) in rules" :key="'r' + rule.attribute">
                <div class="rounded-xl border border-rose-500/25 bg-rose-500/5 p-2.5">

                    <div class="flex items-center gap-1.5">
                        <span class="h-3.5 w-1 shrink-0 rounded-full bg-rose-400"></span>

                        <span class="min-w-0 flex-1 truncate text-[12px] font-black text-slate-100"
                            x-text="ruleText(i)"></span>

                        <span class="shrink-0 font-mono text-[9px] text-slate-500"
                            x-text="attributeOf(rule.attribute)?.entities + ' lo tienen'"></span>

                        <button type="button" @click="removeRule(i)"
                            class="shrink-0 px-1 text-[12px] text-slate-600 transition hover:text-rose-400"
                            title="Quitar esta regla">×</button>
                    </div>

                    {{--
                        Sin ningún valor marcado la regla es «que lo tenga,
                        con el valor que sea». Marcar valores la estrecha, y
                        la galería de abajo cambia en el mismo clic.
                    --}}
                    <div class="mt-1.5 flex flex-wrap gap-1 pl-2.5">
                        <template x-for="opt in (attributeOf(rule.attribute)?.values ?? [])" :key="rule.attribute + opt.value">
                            <button type="button" @click="toggleValue(i, opt.value)"
                                class="rounded-full border px-2 py-0.5 text-[9px] font-bold transition"
                                :class="hasValue(i, opt.value)
                                    ? 'border-rose-400/60 bg-rose-500/20 text-rose-200'
                                    : 'border-slate-800 bg-slate-950/60 text-slate-500 hover:border-slate-700'">
                                <span x-text="opt.label"></span>
                                <span class="ml-0.5 font-mono opacity-60" x-text="opt.entities"></span>
                            </button>
                        </template>
                    </div>

                    <p class="mt-1 pl-2.5 text-[9px] text-slate-600"
                        x-text="rule.values.length === 0
                            ? 'Sin marcar nada: vale cualquier valor de este atributo.'
                            : 'Vale con cualquiera de los marcados.'"></p>

                    {{-- Lo que viaja al servidor --}}
                    <input type="hidden" :name="'eligibility[' + i + '][attribute]'" :value="rule.attribute">
                    <template x-for="(v, vi) in rule.values" :key="'v' + rule.attribute + v">
                        <input type="hidden" :name="'eligibility[' + i + '][values][]'" :value="v">
                    </template>
                </div>
            </template>

{{-- ============ LOS GRUPOS ============ --}}

            {{--
                Un grupo es una condición más, con su propio modo dentro.
                Así se escribe «(aldea hoja Y anime naruto) O (aldea arena)»,
                que con reglas planas no se puede decir.
            --}}

            <template x-for="(grupo, gi) in groups" :key="'g' + gi">
                <div class="rounded-xl border border-sky-500/30 bg-sky-500/5 p-2.5">

                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="rounded bg-sky-500/20 px-1.5 py-0.5 text-[9px] font-black text-sky-300">
                            grupo
                        </span>

                        <div class="flex rounded-lg border border-slate-800 bg-slate-950 p-0.5">
                            @foreach (['ALL' => 'Y', 'ANY' => 'O', 'NONE' => 'NI', 'ONE' => 'XOR'] as $value => $simbolo)
                                <button type="button" @click="setGroupMode(gi, '{{ $value }}')"
                                    class="rounded px-1.5 py-0.5 font-mono text-[10px] font-black transition"
                                    :class="grupo.mode === '{{ $value }}'
                                        ? 'bg-sky-500 text-slate-950'
                                        : 'text-slate-500 hover:text-slate-200'">{{ $simbolo }}</button>
                            @endforeach
                        </div>

                        <span class="min-w-0 flex-1 truncate text-[10px] text-sky-200" x-text="groupText(gi)"></span>

                        <button type="button" @click="removeGroup(gi)"
                            class="shrink-0 px-1 text-[12px] text-slate-600 transition hover:text-rose-400"
                            title="Quitar este grupo">×</button>
                    </div>

                    <div class="mt-1.5 space-y-1 pl-2">
                        <template x-for="(regla, ri) in grupo.rules" :key="'gr' + gi + '-' + ri">
                            <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-1.5">

                                <div class="flex items-center gap-1.5">
                                    <span class="min-w-0 flex-1 truncate text-[10px] font-black text-slate-200"
                                        x-text="attributeOf(regla.attribute)?.label ?? regla.attribute"></span>

                                    <button type="button" @click="removeGroupRule(gi, ri)"
                                        class="shrink-0 px-1 text-[11px] text-slate-600 transition hover:text-rose-400">×</button>
                                </div>

                                <div class="mt-1 flex flex-wrap gap-1">
                                    <template x-for="opt in (attributeOf(regla.attribute)?.values ?? [])"
                                        :key="'go' + gi + '-' + ri + '-' + opt.value">
                                        <button type="button" @click="toggleGroupValue(gi, ri, opt.value)"
                                            class="rounded-full border px-1.5 py-0.5 text-[9px] font-bold transition"
                                            :class="hasGroupValue(gi, ri, opt.value)
                                                ? 'border-sky-400/60 bg-sky-500/20 text-sky-200'
                                                : 'border-slate-800 bg-slate-950 text-slate-500 hover:border-slate-600'">
                                            <span x-text="opt.label"></span>
                                            <span class="ml-0.5 font-mono opacity-60" x-text="opt.entities"></span>
                                        </button>
                                    </template>
                                </div>

                                {{-- Lo que viaja al servidor --}}
                                <input type="hidden"
                                    :name="'eligibility_groups[' + gi + '][rules][' + ri + '][attribute]'"
                                    :value="regla.attribute">
                                <template x-for="(v, vi) in regla.values" :key="'gv' + gi + '-' + ri + '-' + vi">
                                    <input type="hidden"
                                        :name="'eligibility_groups[' + gi + '][rules][' + ri + '][values][]'"
                                        :value="v">
                                </template>
                            </div>
                        </template>

                        <div class="flex flex-wrap gap-1">
                            <template x-for="a in catalog" :key="'ga' + gi + '-' + a.name">
                                <button type="button" @click="addGroupRule(gi, a.name)"
                                    x-show="!grupo.rules.some((r) => r.attribute === a.name)"
                                    class="rounded-lg border border-slate-800 bg-slate-950/60 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-sky-500/50 hover:text-sky-300">
                                    + <span x-text="a.label"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <input type="hidden" :name="'eligibility_groups[' + gi + '][mode]'" :value="grupo.mode">
                </div>
            </template>


            <template x-if="rules.length === 0 && groups.length === 0 && handCount === 0">
                <div class="rounded-xl border border-dashed border-slate-700 px-4 py-5 text-center">
                    <p class="text-[11px] font-black text-slate-400">Abierto a todos</p>
                    <p class="mt-1 text-[10px] leading-relaxed text-slate-600">
                        Sin reglas compite cualquier competidor del universo. Añade una
                        abajo, agrupa varias, o elige a dedo.
                    </p>
                </div>
            </template>

        </div>


        {{-- Añadir una --}}

        <div class="mt-3">
            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                Añadir un atributo
            </p>

            <div class="mt-1.5 flex flex-wrap gap-1">
                <template x-for="a in availableAttributes" :key="'a' + a.name">
                    <button type="button" @click="addRule(a.name)"
                        class="rounded-lg border border-slate-800 bg-slate-950/60 px-2.5 py-1.5 text-left transition hover:border-rose-500/50 hover:bg-rose-500/5">
                        <span class="block text-[11px] font-black text-slate-300" x-text="a.label"></span>
                        <span class="block font-mono text-[9px] text-slate-600"
                            x-text="a.entities + ' · ' + a.values.length + ' valores'"></span>
                    </button>
                </template>

                <template x-if="availableAttributes.length === 0">
                    <p class="py-2 text-[10px] text-slate-600">
                        Ya has usado todos los atributos disponibles.
                    </p>
                </template>

                <button type="button" @click="addGroup()"
                    class="rounded-lg border border-sky-500/40 px-2.5 py-1.5 text-[10px] font-black text-sky-300 transition hover:bg-sky-500/10">
                    + un grupo
                </button>

                <button type="button" @click="pickerOpen = !pickerOpen"
                    class="rounded-lg border px-2.5 py-1.5 text-[10px] font-black transition"
                    :class="handCount
                        ? 'border-amber-500/60 bg-amber-500/10 text-amber-300'
                        : 'border-slate-800 bg-slate-950/60 text-slate-400 hover:border-amber-500/50 hover:text-amber-300'">
                    ✋ elegir a dedo
                    <span x-show="handCount" class="ml-0.5 font-mono" x-text="handCount"></span>
                </button>
            </div>
        </div>


        {{-- ============ A DEDO ============ --}}

        {{--
            Ninguna regla escrita con atributos va a capturar «este sí,
            porque lo digo yo». Aquí se decide a mano, y con la cara
            delante: elegir competidores de una lista de texto es peor que
            elegirlos mirándolos.

            Un botón y tres estados, porque uno para incluir y otro para
            excluir en cada ficha duplicaría la rejilla entera.
        --}}

        <div x-show="pickerOpen" x-cloak class="mt-3 rounded-2xl border border-amber-500/30 bg-amber-500/5 p-2.5">

            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-[9px] font-black uppercase tracking-wider text-amber-300">
                    Decidir a mano
                </span>

                <input type="search" x-model="pickerSearch" placeholder="buscar…"
                    class="min-w-0 flex-1 rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-[10px] text-slate-200 placeholder:text-slate-700 focus:border-amber-500 focus:ring-amber-500">

                <span class="rounded bg-emerald-500/20 px-1.5 py-0.5 text-[9px] font-black text-emerald-300">
                    dentro <span class="font-mono" x-text="include.length"></span>
                </span>

                <span class="rounded bg-rose-500/20 px-1.5 py-0.5 text-[9px] font-black text-rose-300">
                    fuera <span class="font-mono" x-text="exclude.length"></span>
                </span>

                <button type="button" @click="clearHand()" x-show="handCount"
                    class="rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-400 transition hover:border-slate-600">
                    quitar la mano
                </button>
            </div>

            <p class="mt-1 text-[9px] leading-relaxed text-slate-500">
                Pulsa una vez para meterlo pase lo que pase, dos para dejarlo fuera pase
                lo que pase, tres para devolverlo a lo que digan las reglas.
            </p>

            <div class="mt-2 grid max-h-72 gap-1.5 overflow-y-auto grid-cols-3 sm:grid-cols-5 lg:grid-cols-8">
                <template x-for="c in pickerList" :key="'pick' + c.id">
                    <button type="button" @click="cycleHand(c.id)"
                        class="group overflow-hidden rounded-xl border transition"
                        :class="{
                            'border-emerald-400/60 bg-emerald-500/10': handState(c.id) === 'IN',
                            'border-rose-400/60 bg-rose-500/10': handState(c.id) === 'OUT',
                            'border-slate-800 bg-slate-950 hover:border-slate-600': handState(c.id) === 'RULE',
                        }">

                        <span class="relative block aspect-square overflow-hidden bg-slate-950">
                            <template x-if="c.image_url">
                                <img :src="c.image_url" alt="" loading="lazy"
                                    class="h-full w-full object-cover transition"
                                    :class="handState(c.id) === 'OUT' ? 'opacity-25 grayscale' : ''">
                            </template>
                            <template x-if="!c.image_url">
                                <span class="flex h-full w-full items-center justify-center font-mono text-[11px] font-black text-slate-700"
                                    x-text="c.name.slice(0, 2).toUpperCase()"></span>
                            </template>

                            <template x-if="handState(c.id) !== 'RULE'">
                                <span class="absolute right-0.5 top-0.5 flex h-4 w-4 items-center justify-center rounded-full text-[9px] font-black text-slate-950"
                                    :class="handState(c.id) === 'IN' ? 'bg-emerald-400' : 'bg-rose-400'"
                                    x-text="handState(c.id) === 'IN' ? '✓' : '×'"></span>
                            </template>

                            {{-- Si la regla ya lo mete, se dice: incluirlo a mano no aporta --}}
                            <template x-if="handState(c.id) === 'RULE' && matching.some((m) => m.id === c.id)">
                                <span class="absolute left-0.5 top-0.5 h-1.5 w-1.5 rounded-full bg-slate-400"
                                    title="Ya entra por las reglas"></span>
                            </template>

                            <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950 to-transparent px-1 pb-0.5 pt-4">
                                <span class="block truncate text-[9px] font-black text-slate-100" x-text="c.name"></span>
                            </span>
                        </span>
                    </button>
                </template>
            </div>

            {{-- Lo que viaja al servidor --}}
            <template x-for="id in include" :key="'inc' + id">
                <input type="hidden" name="eligibility_include[]" :value="id">
            </template>
            <template x-for="id in exclude" :key="'exc' + id">
                <input type="hidden" name="eligibility_exclude[]" :value="id">
            </template>
        </div>

    </div>


    {{-- ==================== LOS PARTICIPANTES ==================== --}}

    {{--
        La mitad que hace útil a la otra.

        Se calcula en la propia pantalla, así que cambia en el mismo clic
        que marca un valor. El servidor sigue siendo la autoridad y se le
        sigue preguntando: si contase otra cosa, se dice justo aquí en vez
        de esconderlo.
    --}}

    <div class="border-t border-slate-800 bg-slate-950/60">

        {{-- La barra: cuántos, y qué se está mirando --}}

        <div class="flex flex-wrap items-center gap-3 px-4 py-3">

            <span class="flex items-baseline gap-1.5">
                <span class="font-mono text-3xl font-black leading-none"
                    :class="eligibilityEmpty ? 'text-rose-300' : 'text-emerald-300'"
                    x-text="shown.length"></span>

                <span class="font-mono text-[12px] text-slate-600" x-text="'/ ' + roster.length"></span>
            </span>

            <span class="min-w-0">
                <span class="block text-[11px] font-black text-slate-200"
                    x-text="rosterView === 'OUT' ? 'Se quedan fuera' : 'Entran en el torneo'"></span>

                <span class="block text-[9px] text-slate-600"
                    x-text="rules.length === 0
                        ? 'Sin ninguna regla: compite todo el universo'
                        : (rules.length === 1 ? 'con 1 regla' : 'con ' + rules.length + ' reglas')"></span>
            </span>

            {{-- Ver a los que entran o a los que no --}}

            <div class="ml-auto flex rounded-lg border border-slate-800 bg-slate-950 p-0.5">
                @foreach (['IN' => 'Entran', 'OUT' => 'Fuera'] as $value => $label)
                    <button type="button" @click="rosterView = '{{ $value }}'"
                        class="rounded-md px-2.5 py-1 text-[10px] font-black transition"
                        :class="rosterView === '{{ $value }}'
                            ? '{{ $value === 'IN' ? 'bg-emerald-500' : 'bg-slate-600' }} text-slate-950'
                            : 'text-slate-500 hover:text-slate-200'">
                        {{ $label }}
                        <span class="ml-0.5 font-mono opacity-70"
                            x-text="{{ $value === 'IN' ? 'matching.length' : 'excluded.length' }}"></span>
                    </button>
                @endforeach
            </div>
        </div>


        {{--
            La barra de proporción: cuánto del universo entra.

            Contenedor en bloque y no flex. Un flex de un solo hijo no
            aporta nada aquí, y deja el ancho de la barra a merced del
            algoritmo de reparto en vez de al porcentaje que se le pone.
        --}}

        <div class="mx-4 h-1.5 overflow-hidden rounded-full bg-slate-900">
            <div class="h-full bg-emerald-500 transition-all duration-200"
                :style="'width:' + (roster.length ? (matching.length / roster.length) * 100 : 0) + '%'"></div>
        </div>


        {{-- El aviso de que nadie cumple --}}

        <template x-if="eligibilityEmpty && rosterView === 'IN'">
            <p class="mx-4 mt-3 rounded-xl border border-rose-500/40 bg-rose-500/10 px-3 py-2 text-[10px] font-bold leading-relaxed text-rose-200">
                Ningún competidor cumple. Con esta regla el torneo no podría celebrarse:
                afloja algún filtro o cambia a «al menos una».
            </p>
        </template>

        {{-- El servidor contó otra cosa --}}

        <template x-if="previewDisagrees">
            <p class="mx-4 mt-3 rounded-xl border border-amber-500/40 bg-amber-500/10 px-3 py-2 text-[10px] leading-relaxed text-amber-200">
                El servidor cuenta <span class="font-mono font-black" x-text="preview.matching"></span>
                y esta pantalla <span class="font-mono font-black" x-text="matching.length"></span>.
                Manda el servidor: guarda y vuelve a abrir para ver la lista buena.
            </p>
        </template>


        {{-- ============ LA GALERÍA ============ --}}

        <div class="grid gap-2 p-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">

            <template x-for="c in shown" :key="'roster' + c.id">
                <div class="group overflow-hidden rounded-xl border transition"
                    :class="rosterView === 'OUT'
                        ? 'border-slate-800 bg-slate-900/40'
                        : 'border-emerald-500/25 bg-emerald-500/5'">

                    {{--
                        La cara, en cuadrado. Un competidor se reconoce por
                        su imagen mucho antes que por su nombre, y de eso va
                        exactamente esta galería.
                    --}}
                    <span class="relative block aspect-square overflow-hidden bg-slate-950">
                        <template x-if="c.image_url">
                            <img :src="c.image_url" alt="" loading="lazy"
                                class="h-full w-full object-cover transition duration-200 group-hover:scale-105"
                                :class="rosterView === 'OUT' ? 'opacity-30 grayscale' : ''">
                        </template>

                        <template x-if="!c.image_url">
                            <span class="flex h-full w-full items-center justify-center font-mono text-[18px] font-black text-slate-700"
                                x-text="c.name.slice(0, 2).toUpperCase()"></span>
                        </template>

                        <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950 to-transparent px-1.5 pb-1 pt-4">
                            <span class="block truncate text-[10px] font-black text-slate-100" x-text="c.name"></span>
                        </span>
                    </span>

                    {{--
                        Sus atributos. Los que le dejan entrar van
                        encendidos: el número de los que pasan no dice POR
                        QUÉ pasan, y con dos reglas encima eso deja de ser
                        evidente.
                    --}}
                    <span class="flex flex-wrap gap-0.5 p-1.5">
                        <template x-for="a in c.attributes" :key="c.id + a.name">
                            <template x-for="(etiqueta, vi) in a.labels" :key="c.id + a.name + vi">
                                <span class="truncate rounded px-1 py-0.5 text-[8px] font-bold transition"
                                    :class="isMatched(c, a, vi)
                                        ? 'bg-rose-500/25 text-rose-200'
                                        : 'bg-slate-950/70 text-slate-500'"
                                    :title="a.label + ': ' + etiqueta"
                                    x-text="etiqueta"></span>
                            </template>
                        </template>

                        <template x-if="c.attributes.length === 0">
                            <span class="rounded bg-slate-950/70 px-1 py-0.5 text-[8px] text-slate-700">
                                sin atributos
                            </span>
                        </template>
                    </span>
                </div>
            </template>

            <template x-if="shown.length === 0">
                <p class="rounded-xl border border-dashed border-slate-700 px-4 py-6 text-center text-[10px] leading-relaxed text-slate-600 sm:col-span-3 md:col-span-4 lg:col-span-6"
                    x-text="rosterView === 'OUT'
                        ? 'No queda nadie fuera: compite todo el universo.'
                        : 'Ningún competidor cumple estas reglas.'"></p>
            </template>

        </div>

        <p class="px-4 pb-3 text-[9px] leading-relaxed text-slate-600">
            Estos son los competidores que <span class="font-bold text-slate-500">hoy</span>
            cumplen. La lista cambia sola cuando importes entidades nuevas: lo que se guarda
            es la regla, no la gente.
        </p>

    </div>

    @endif

</section>
