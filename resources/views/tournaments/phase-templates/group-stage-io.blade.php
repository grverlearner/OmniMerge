@php
    /*
     * Entradas y salidas de la fase de grupos.
     *
     * Group Stage no tenía esta pantalla: sus salidas se editaban dentro
     * de "Reglas" y sus entradas no se veían en ninguna parte. Aquí se
     * ven las dos juntas, que es como se piensan.
     */
@endphp

<x-app-layout>

    <x-slot name="header">
        Entradas y salidas · {{ $phaseTemplate->name }}
    </x-slot>

    @include('tournaments.phase-templates.partials.workspace-navigation', [
        'current' => 'io',
        'phaseTemplate' => $phaseTemplate,
        'settings' => $settings,
    ])


    <section class="mt-5 rounded-3xl border border-slate-200 bg-white p-6">

        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-indigo-600">Contrato de la fase</p>

        <h2 class="mt-2 text-2xl font-black text-slate-900">Por dónde entran y por dónde se van</h2>

        <p class="mt-2 max-w-3xl text-sm leading-relaxed text-slate-500">
            Las <strong class="font-black text-slate-700">entradas</strong> deciden quién llega y a qué
            grupo. Las <strong class="font-black text-slate-700">salidas</strong> deciden quién continúa.
            Entre medias, la fase solo juega.
        </p>

    </section>


    @if ($errors->any())
        <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4">
            @foreach ($errors->all() as $error)
                <p class="text-sm font-bold text-red-700">{{ $error }}</p>
            @endforeach
        </div>
    @endif


    <div class="mt-5 grid gap-6 xl:grid-cols-2">

        {{-- ============================================ --}}
        {{-- ENTRADAS --}}
        {{-- ============================================ --}}

        <section class="rounded-3xl border border-slate-200 bg-white"
            x-data="{ editing: null, creating: {{ $gates->isEmpty() ? 'true' : 'false' }} }">

            <div class="flex items-center gap-3 border-b border-slate-100 p-6">

                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 text-xl text-white shadow-lg shadow-emerald-600/20">
                    ⇢
                </div>

                <div class="min-w-0 flex-1">
                    <h3 class="text-lg font-black text-slate-900">Puertas de entrada</h3>
                    <p class="text-xs text-slate-500">Por dónde llegan y a qué grupo van.</p>
                </div>

                <button type="button" @click="creating = !creating; editing = null"
                    class="shrink-0 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-black text-white hover:bg-emerald-700">
                    <span x-show="!creating">+ Nueva</span>
                    <span x-show="creating" x-cloak>Cerrar</span>
                </button>

            </div>


            {{-- ALTA --}}

            <div x-show="creating" x-cloak class="border-b border-slate-100 bg-emerald-50/40 p-5">

                <p class="mb-3 text-[10px] font-black uppercase tracking-wider text-emerald-700">
                    Nueva puerta de entrada
                </p>

                @include('tournaments.phase-templates.partials.group-stage-gate-form', ['gate' => null])

            </div>


            @forelse ($gates as $gate)

                @php
                    $target = $gate->settings['target_group_code'] ?? null;
                    $targetGroup = $groupDefinitions->firstWhere('code', $target);
                @endphp

                <div class="border-b border-slate-100 p-5 last:border-0">

                    <div class="flex flex-wrap items-start justify-between gap-3">

                        <div class="min-w-0">
                            <p class="text-sm font-black text-slate-900">{{ $gate->name }}</p>
                            <p class="mt-0.5 font-mono text-[10px] text-slate-400">{{ $gate->code }}</p>

                            <div class="mt-2 flex flex-wrap gap-1.5">

                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-black text-slate-600">
                                    {{ $gate->input_type }}
                                </span>

                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-black text-slate-600">
                                    {{ $gate->merge_policy }}
                                </span>

                                @if ($gate->is_required)
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[9px] font-black text-amber-700">
                                        Obligatoria
                                    </span>
                                @endif

                                @if ($gate->status !== 'ACTIVE')
                                    <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[9px] font-black text-slate-600">
                                        Inactiva
                                    </span>
                                @endif

                            </div>
                        </div>

                        <div class="flex shrink-0 flex-col items-end gap-2">

                            @if ($targetGroup)
                                <span class="rounded-full bg-indigo-100 px-3 py-1 text-[10px] font-black text-indigo-700">
                                    → {{ $targetGroup->name }}
                                </span>
                            @else
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-black text-slate-500">
                                    Reparto {{ $gate->distribution_mode }}
                                </span>
                            @endif

                            <div class="flex gap-2">

                                <button type="button" @click="editing = editing === {{ $gate->id }} ? null : {{ $gate->id }}; creating = false"
                                    class="text-[11px] font-black text-slate-500 hover:text-indigo-700">
                                    Editar
                                </button>

                                <form method="POST"
                                    action="{{ route('tournaments.group-stage.gates.destroy', [$phaseTemplate, $gate]) }}"
                                    onsubmit="return confirm('¿Eliminar esta puerta de entrada?')">
                                    @csrf
                                    @method('DELETE')

                                    <button class="text-[11px] font-black text-slate-400 hover:text-red-600">
                                        Eliminar
                                    </button>
                                </form>

                            </div>

                        </div>

                    </div>


                    {{-- EDICIÓN --}}

                    <div x-show="editing === {{ $gate->id }}" x-cloak class="mt-4 rounded-2xl bg-slate-50 p-4">
                        @include('tournaments.phase-templates.partials.group-stage-gate-form', ['gate' => $gate])
                    </div>

                </div>
            @empty

                <div x-show="!creating" class="p-8 text-center">

                    <div class="text-4xl opacity-25">⇢</div>

                    <h4 class="mt-3 text-base font-black text-slate-900">Sin puertas de entrada</h4>

                    <p class="mx-auto mt-2 max-w-sm text-sm text-slate-500">
                        La fase acepta participantes por la vía por defecto y los reparte según
                        <strong class="font-black text-slate-700">{{ $settings->distribution_mode }}</strong>.
                        Crea puertas si quieres controlar quién entra en qué grupo.
                    </p>

                    <button type="button" @click="creating = true"
                        class="mt-4 rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-black text-white hover:bg-emerald-700">
                        Crear la primera puerta
                    </button>

                </div>
            @endforelse

        </section>


        {{-- ============================================ --}}
        {{-- SALIDAS --}}
        {{-- ============================================ --}}

        <section class="rounded-3xl border border-slate-200 bg-white">

            <div class="flex items-center gap-3 border-b border-slate-100 p-6">

                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 to-violet-600 text-xl text-white shadow-lg shadow-violet-600/20">
                    ⇥
                </div>

                <div class="min-w-0">
                    <h3 class="text-lg font-black text-slate-900">Salidas</h3>
                    <p class="text-xs text-slate-500">Puertas por las que se avanza a la siguiente fase.</p>
                </div>

            </div>


            @forelse ($phaseExits as $exit)

                @php
                    $rules = $rulesByExit->get($exit->id, collect());

                    /*
                     * Cuantos salen de verdad por esta puerta. Es el numero
                     * que ejecuta el motor: lo producen las reglas, no el
                     * selector de la puerta.
                     */
                    $emits = $exitForecast['by_exit'][$exit->id] ?? null;

                    /*
                     * Lo que la puerta dice de si misma. Solo tiene efecto
                     * cuando ninguna regla la alimenta; con reglas, es el
                     * numero que leen el grafo y sus validaciones, y por eso
                     * conviene que coincida.
                     */
                    $declares = match ($exit->selector_type) {
                        'TOP_N', 'BOTTOM_N' => (int) $exit->selector_from,
                        'RANK_POSITION' => 1,
                        'RANK_RANGE' => max(0, (int) $exit->selector_to - (int) $exit->selector_from + 1),
                        default => null,
                    };

                    $mismatch = $emits !== null
                        && $emits > 0
                        && $declares !== null
                        && $declares !== $emits;
                @endphp

                <div class="border-b border-slate-100 p-5 last:border-0">

                    <div class="flex flex-wrap items-start justify-between gap-3">

                        <div class="min-w-0">
                            <p class="text-sm font-black text-slate-900">{{ $exit->name }}</p>
                            <p class="mt-0.5 font-mono text-[10px] text-slate-400">{{ $exit->code }}</p>
                        </div>

                        <div class="flex shrink-0 flex-col items-end gap-2">

                          <div class="flex flex-wrap justify-end gap-1.5">

                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-black text-slate-600">
                                {{ $exit->selector_type }}
                            </span>

                            <span @class([
                                'rounded-full px-2 py-0.5 text-[9px] font-black',
                                'bg-amber-100 text-amber-700' => $exit->exit_timing === 'ON_ELIMINATION',
                                'bg-slate-100 text-slate-600' => $exit->exit_timing !== 'ON_ELIMINATION',
                            ])>
                                {{ $exit->exit_timing }}
                            </span>

                          </div>

                          @if ($rules->isEmpty())
                            <form method="POST"
                                action="{{ route('tournaments.phase-exits.destroy', [$phaseTemplate, $exit]) }}"
                                onsubmit="return confirm('Eliminar esta puerta de salida?')">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="return_to" value="group_stage_io">

                                <button class="text-[11px] font-black text-slate-400 hover:text-red-600">
                                    Eliminar
                                </button>
                            </form>
                          @endif

                        </div>

                    </div>


                    {{-- Quién cruza esta puerta --}}

                    <div class="mt-3 rounded-2xl bg-slate-50 p-3">

                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            La cruzan
                        </p>

                        @forelse ($rules as $rule)
                            <p class="mt-1.5 text-xs font-bold text-violet-700">
                                → {{ $rule->rule_summary ?? $rule->rule_type }}
                                @if ($rule->group)
                                    <span class="text-slate-500">· {{ $rule->group->name }}</span>
                                @endif

                                @if (isset($exitForecast['by_rule'][$rule->id]))
                                    <span class="text-slate-500">
                                        · {{ $exitForecast['by_rule'][$rule->id] }}
                                        {{ $exitForecast['by_rule'][$rule->id] === 1 ? 'participante' : 'participantes' }}
                                    </span>
                                @endif
                            </p>
                        @empty
                            <p class="mt-1.5 text-xs text-amber-700">
                                Ninguna regla de clasificación apunta aquí: esta puerta no la cruzará nadie.
                            </p>
                        @endforelse

                        @if ($emits !== null && $rules->isNotEmpty())
                            <p class="mt-2 border-t border-slate-200 pt-2 text-[11px] font-black text-slate-700">
                                Salen {{ $emits }} de {{ $exitForecast['participants'] }}
                            </p>
                        @endif

                    </div>


                    {{-- Las reglas y la puerta no dicen lo mismo --}}

                    @if ($mismatch)
                        <div class="mt-3 rounded-2xl border border-red-200 bg-red-50 p-3">

                            <p class="text-xs font-black text-red-800">
                                La puerta declara {{ $declares }} y las reglas mandan {{ $emits }}
                            </p>

                            <p class="mt-1.5 text-[11px] leading-relaxed text-red-700">
                                Manda el {{ $emits }}: con reglas de clasificación el motor entrega
                                la lista que ellas producen y el selector de la puerta no se aplica.
                                Pero el número que leen el grafo y sus validaciones es el
                                {{ $declares }}, así que el torneo se valida con una cantidad y se
                                juega con otra. Ajusta la regla, o el selector de la puerta, hasta
                                que digan lo mismo.
                            </p>

                        </div>
                    @endif

                </div>
            @empty

                <div class="p-8 text-center">

                    <div class="text-4xl opacity-25">⇥</div>

                    <h4 class="mt-3 text-base font-black text-slate-900">Sin salidas</h4>

                    <p class="mx-auto mt-2 max-w-sm text-sm text-slate-500">
                        Nadie avanzaría a la siguiente fase. Las salidas se crean junto a las reglas
                        de clasificación.
                    </p>

                </div>
            @endforelse


            {{-- ALTA DE SALIDA --}}

            <div class="border-t border-slate-100 bg-violet-50/40 p-5"
                x-data="{ open: {{ $phaseExits->isEmpty() ? 'true' : 'false' }}, selector: 'TOP_N', timing: 'PHASE_END' }">

                <button type="button" @click="open = !open"
                    class="text-xs font-black text-violet-700 hover:underline">
                    <span x-show="!open">+ Nueva puerta de salida</span>
                    <span x-show="open" x-cloak>Cerrar</span>
                </button>

                <form x-show="open" x-cloak method="POST"
                    action="{{ route('tournaments.phase-exits.store', $phaseTemplate) }}"
                    class="mt-4 space-y-4">

                    @csrf

                    {{-- Devuelve aqui en vez de al resumen --}}
                    <input type="hidden" name="return_to" value="group_stage_io">

                    <div>
                        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">Nombre *</label>
                        <input type="text" name="name" value="{{ old('name') }}"
                            placeholder="Ej. Clasificados a cuartos"
                            class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
                        <p class="mt-1 text-[10px] text-slate-500">
                            El nombre con el que veras esta puerta al conectar la fase con el resto del torneo.
                        </p>
                    </div>


                    {{-- A QUIEN SELECCIONA --}}

                    <div>
                        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            A quien selecciona *
                        </label>

                        <select name="selector_type" x-model="selector"
                            class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
                            <option value="TOP_N">Los primeros de cada grupo</option>
                            <option value="BOTTOM_N">Los ultimos de cada grupo</option>
                            <option value="RANK_POSITION">Un puesto concreto</option>
                            <option value="RANK_RANGE">Un rango de puestos</option>
                            <option value="REMAINING">Todos los que no salieron por otra puerta</option>
                            <option value="ALL">Todos, sin filtrar</option>
                            <option value="ELIMINATED">Los eliminados</option>
                        </select>

                        <p class="mt-1 text-[10px] leading-relaxed text-slate-500">
                            <span x-show="selector === 'TOP_N'">Se llevara a los N mejores de la clasificacion general de la fase, no N por grupo.</span>
                            <span x-show="selector === 'BOTTOM_N'" x-cloak>Se llevara a los N peores de la clasificacion general de la fase, no N por grupo.</span>
                            <span x-show="selector === 'RANK_POSITION'" x-cloak>Solo a quien acabe exactamente en ese puesto. Util para «los mejores terceros».</span>
                            <span x-show="selector === 'RANK_RANGE'" x-cloak>Desde un puesto hasta otro, ambos incluidos.</span>
                            <span x-show="selector === 'REMAINING'" x-cloak>Recoge a quien no haya salido ya por otra puerta. Buena para «Eliminados».</span>
                            <span x-show="selector === 'ALL'" x-cloak>Saca a todos los participantes de la fase.</span>
                            <span x-show="selector === 'ELIMINATED'" x-cloak>Quien queda fuera. Es la unica compatible con la salida inmediata.</span>
                        </p>
                    </div>


                    {{-- PUESTOS --}}

                    <div class="grid gap-3 sm:grid-cols-2"
                        x-show="['TOP_N', 'BOTTOM_N', 'RANK_POSITION', 'RANK_RANGE'].includes(selector)" x-cloak>

                        <div>
                            <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                                <span x-show="selector === 'TOP_N'">Cuantos primeros</span>
                                <span x-show="selector === 'BOTTOM_N'" x-cloak>Cuantos ultimos</span>
                                <span x-show="selector === 'RANK_POSITION'" x-cloak>Puesto</span>
                                <span x-show="selector === 'RANK_RANGE'" x-cloak>Desde el puesto</span>
                            </label>

                            <input type="number" name="selector_from" min="1" max="512"
                                value="{{ old('selector_from', 1) }}"
                                class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
                        </div>

                        <div x-show="selector === 'RANK_RANGE'" x-cloak>
                            <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                                Hasta el puesto
                            </label>

                            <input type="number" name="selector_to" min="1" max="512"
                                value="{{ old('selector_to', 2) }}"
                                class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
                        </div>

                    </div>


                    {{-- CUANDO SE CRUZA --}}

                    <div>
                        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            Cuando se cruza *
                        </label>

                        <select name="exit_timing" x-model="timing"
                            class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
                            <option value="PHASE_END">Al terminar la fase</option>
                            <option value="ON_ELIMINATION">En cuanto queda eliminado</option>
                            <option value="ON_RULE_TRIGGER">Cuando lo dispare una regla</option>
                        </select>

                        <p class="mt-1 text-[10px] leading-relaxed text-slate-500">
                            <span x-show="timing === 'PHASE_END'">Lo normal: se espera a que acabe toda la fase de grupos y entonces salen los clasificados.</span>
                            <span x-show="timing === 'ON_ELIMINATION'" x-cloak>Sale en el momento en que se queda fuera, sin esperar al final.</span>
                            <span x-show="timing === 'ON_RULE_TRIGGER'" x-cloak>Sale cuando una regla de clasificacion lo decide.</span>
                        </p>

                        {{--
                            Aviso concreto: el motor rechaza en ejecucion la
                            combinacion de salida inmediata con un selector
                            por puesto. Es mejor decirlo aqui que dejar que
                            reviente a mitad de una competicion.
                        --}}
                        <div x-show="timing === 'ON_ELIMINATION' && !['ELIMINATED'].includes(selector)" x-cloak
                            class="mt-2 rounded-xl border border-amber-200 bg-amber-50 p-3">
                            <p class="text-[10px] font-bold leading-relaxed text-amber-800">
                                Cuidado: la salida inmediata solo funciona con «Los eliminados».
                                Con un selector por puesto el motor detendra la competicion cuando
                                intente usarla, porque a mitad de fase todavia no hay clasificacion firme.
                            </p>
                        </div>
                    </div>


                    <div class="grid gap-3 sm:grid-cols-2">

                        <div>
                            <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                                Prioridad *
                            </label>

                            <input type="number" name="priority" min="1" max="999"
                                value="{{ old('priority', 10) }}"
                                class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">

                            <p class="mt-1 text-[10px] text-slate-500">
                                Cuando dos puertas podrian llevarse al mismo participante, gana la del numero
                                mas bajo. Deja 10 si no tienes un motivo.
                            </p>
                        </div>

                        <div>
                            <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">Estado</label>
                            <select name="status"
                                class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">
                                <option value="ACTIVE">Activa</option>
                                <option value="INACTIVE">Inactiva</option>
                            </select>

                            <p class="mt-1 text-[10px] text-slate-500">
                                Una puerta inactiva se conserva pero no saca a nadie.
                            </p>
                        </div>

                    </div>


                    <div>
                        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            Descripcion
                        </label>

                        <textarea name="description" rows="2"
                            placeholder="Para que sirve esta puerta dentro del torneo..."
                            class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">{{ old('description') }}</textarea>
                    </div>


                    <button class="rounded-xl bg-slate-950 px-5 py-2.5 text-xs font-black text-white hover:bg-slate-800">
                        Crear salida
                    </button>

                </form>

            </div>

        </section>

    </div>


    <section class="mt-5 flex flex-wrap gap-3">

        <a href="{{ route('tournaments.group-stage.structure', $phaseTemplate) }}"
            class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-xs font-black text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">
            ◇ Ver la estructura
        </a>

        <a href="{{ route('tournaments.group-stage.simulator.show', $phaseTemplate) }}"
            class="rounded-xl bg-slate-950 px-5 py-3 text-xs font-black text-white transition hover:bg-slate-800">
            ▶ Probarlo en el simulador
        </a>

    </section>



    {{-- ============================================ --}}
    {{-- REGLAS DE CLASIFICACION --}}
    {{-- ============================================ --}}
    {{--
        Viven aqui, junto a las puertas, porque una regla NO tiene sentido
        sin la salida que alimenta: decir "los dos primeros de cada grupo"
        no significa nada hasta que dices por donde salen.
    --}}

    <section class="mt-6 rounded-3xl border border-slate-200 bg-white"
        x-data="{ creating: {{ $advancementRules->isEmpty() ? 'true' : 'false' }}, editing: null }">

        <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 p-6">

            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 to-amber-600 text-xl text-white shadow-lg shadow-amber-600/20">
                ⇅
            </div>

            <div class="min-w-0 flex-1">
                <h3 class="text-lg font-black text-slate-900">Quien cruza cada salida</h3>
                <p class="text-xs text-slate-500">
                    La puerta es el hueco; la regla decide a quien deja pasar.
                </p>
            </div>

            @if ($phaseExits->isNotEmpty())
                <button type="button" @click="creating = !creating; editing = null"
                    class="shrink-0 rounded-xl bg-amber-600 px-4 py-2.5 text-xs font-black text-white hover:bg-amber-700">
                    <span x-show="!creating">+ Nueva regla</span>
                    <span x-show="creating" x-cloak>Cerrar</span>
                </button>
            @endif

        </div>


        @if ($phaseExits->isEmpty())

            <div class="p-8 text-center">
                <div class="text-4xl opacity-25">⇅</div>
                <h4 class="mt-3 text-base font-black text-slate-900">Primero crea una salida</h4>
                <p class="mx-auto mt-2 max-w-sm text-sm text-slate-500">
                    Una regla necesita una puerta por la que mandar a la gente. Crea arriba tu
                    primera puerta de salida y vuelve aqui.
                </p>
            </div>
        @else

            {{-- ALTA --}}

            <div x-show="creating" x-cloak class="border-b border-slate-100 bg-amber-50/40 p-5">

                <p class="mb-3 text-[10px] font-black uppercase tracking-wider text-amber-700">
                    Nueva regla de clasificacion
                </p>

                @include('tournaments.phase-templates.partials.group-stage-advancement-rule-form', [
                    'phaseTemplate' => $phaseTemplate,
                    'rule' => null,
                    'phaseExits' => $phaseExits,
                    'groupDefinitions' => $groupDefinitions,
                ])

            </div>


            @forelse ($advancementRules as $rule)

                <div class="flex flex-wrap items-start gap-3 border-b border-slate-100 p-5 last:border-0">

                    <div class="min-w-0 flex-1">

                        <div class="flex flex-wrap items-center gap-2">

                            <span class="rounded-full bg-slate-900 px-2.5 py-1 text-[10px] font-black text-white">
                                {{ $rule->rule_type_label ?? $rule->rule_type }}
                            </span>

                            @if ($rule->status !== 'ACTIVE')
                                <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[9px] font-black text-slate-600">
                                    Inactiva
                                </span>
                            @endif

                        </div>

                        <p class="mt-2 text-sm text-slate-600">
                            @if ($rule->group)
                                Solo en <strong class="font-black text-slate-900">{{ $rule->group->name }}</strong>
                            @else
                                En <strong class="font-black text-slate-900">todos los grupos</strong>
                            @endif

                            @if ($rule->position_from)
                                · desde el puesto {{ $rule->position_from }}@if ($rule->position_to) al {{ $rule->position_to }}@endif
                            @endif

                            @if ($rule->take)
                                · se lleva {{ $rule->take }}
                            @endif
                        </p>

                        <p class="mt-1.5 text-[11px] font-black text-violet-700">
                            → sale por «{{ $rule->phaseExit?->name ?? 'sin puerta' }}»
                        </p>

                    </div>

                    <div class="flex shrink-0 gap-2">

                        <button type="button"
                            @click="editing = editing === {{ $rule->id }} ? null : {{ $rule->id }}; creating = false"
                            class="text-[11px] font-black text-slate-500 hover:text-amber-700">
                            Editar
                        </button>

                        <form method="POST"
                            action="{{ route('tournaments.group-stage.advancement-rules.destroy', [$phaseTemplate, $rule]) }}"
                            onsubmit="return confirm('Eliminar esta regla de clasificacion?')">
                            @csrf
                            @method('DELETE')

                            <button class="text-[11px] font-black text-slate-400 hover:text-red-600">
                                Eliminar
                            </button>
                        </form>

                    </div>

                    <div x-show="editing === {{ $rule->id }}" x-cloak class="w-full rounded-2xl bg-slate-50 p-4">
                        @include('tournaments.phase-templates.partials.group-stage-advancement-rule-form', [
                            'phaseTemplate' => $phaseTemplate,
                            'rule' => $rule,
                            'phaseExits' => $phaseExits,
                            'groupDefinitions' => $groupDefinitions,
                        ])
                    </div>

                </div>
            @empty

                <div x-show="!creating" class="p-8 text-center">
                    <p class="text-sm text-slate-500">
                        Ninguna regla todavia: nadie cruzara ninguna salida.
                    </p>
                </div>
            @endforelse
        @endif

    </section>

</x-app-layout>
