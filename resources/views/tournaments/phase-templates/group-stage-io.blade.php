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
        {{--
            Una salida y el criterio que la cruza son la misma decision, y
            aqui se toman juntas.

            Vivian separadas: se creaba la puerta arriba y, en una tercera
            seccion mas abajo, una "regla de clasificacion" que habia que
            acordarse de apuntar a esa puerta. Puerta sin criterio no la
            cruza nadie; criterio sin puerta no lleva a ningun sitio. Lo
            unico que conseguia la separacion era dejar la mitad hecha.
        --}}

        <section class="rounded-3xl border border-slate-200 bg-white"
            x-data="{
                creating: {{ $phaseExits->isEmpty() ? 'true' : 'false' }},
                editingRule: null,
                addingTo: null,
                advanced: false,
            }">

            <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 p-6">

                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 to-violet-600 text-xl text-white shadow-lg shadow-violet-600/20">
                    ⇥
                </div>

                <div class="min-w-0 flex-1">
                    <h3 class="text-lg font-black text-slate-900">Puertas de salida</h3>
                    <p class="text-xs text-slate-500">Quién continúa, y por qué puerta sale.</p>
                </div>

                <button type="button"
                    @click="creating = !creating; editingRule = null; addingTo = null"
                    class="shrink-0 rounded-xl bg-violet-600 px-4 py-2.5 text-xs font-black text-white transition hover:bg-violet-700">
                    <span x-show="!creating">+ Nueva salida</span>
                    <span x-show="creating" x-cloak>Cerrar</span>
                </button>

            </div>


            {{-- ALTA: la puerta y su criterio, de una vez --}}

            <div x-show="creating" x-cloak class="border-b border-slate-100 bg-violet-50/40 p-5">

                <form method="POST"
                    action="{{ route('tournaments.group-stage.exits.store', $phaseTemplate) }}"
                    class="space-y-4">

                    @csrf

                    <div>
                        <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                            Cómo se llama la salida
                        </label>

                        <input type="text" name="name" value="{{ old('name') }}"
                            placeholder="Ej. Clasificados a cuartos"
                            class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-violet-400 focus:ring-violet-400">

                        <p class="mt-1 text-[10px] text-slate-500">
                            Es el nombre que verás al conectar esta fase con el resto del torneo.
                        </p>
                    </div>


                    @include('tournaments.phase-templates.partials.group-stage-rule-fields', [
                        'phaseTemplate' => $phaseTemplate,
                        'ruleTypes' => $ruleTypes,
                        'groupDefinitions' => $activeGroupDefinitions,
                        'advancementRule' => null,
                    ])


                    {{-- Lo que casi nunca hay que tocar --}}

                    <div>
                        <button type="button" @click="advanced = !advanced"
                            class="text-[11px] font-black text-slate-500 hover:text-violet-700">
                            <span x-show="!advanced">Opciones avanzadas</span>
                            <span x-show="advanced" x-cloak>Ocultar opciones avanzadas</span>
                        </button>

                        <div x-show="advanced" x-cloak class="mt-3 space-y-3 rounded-2xl bg-white p-4">

                            <div class="grid gap-3 sm:grid-cols-2">

                                <div>
                                    <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                                        Prioridad
                                    </label>

                                    <input type="number" name="priority" min="1" max="999"
                                        value="{{ old('priority', 10) }}"
                                        class="mt-1.5 w-full rounded-xl border-slate-300 text-sm">

                                    <p class="mt-1 text-[10px] text-slate-500">
                                        Si dos salidas se disputan al mismo competidor, gana la del número
                                        más bajo. Deja 10 si no tienes un motivo.
                                    </p>
                                </div>

                                <div>
                                    <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                                        Estado
                                    </label>

                                    <select name="status"
                                        class="mt-1.5 w-full rounded-xl border-slate-300 text-sm">
                                        <option value="ACTIVE">Activa</option>
                                        <option value="INACTIVE">Inactiva</option>
                                    </select>

                                    <p class="mt-1 text-[10px] text-slate-500">
                                        Una salida inactiva se conserva pero no saca a nadie.
                                    </p>
                                </div>

                            </div>

                            <div>
                                <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                                    Descripción
                                </label>

                                <textarea name="description" rows="2"
                                    placeholder="Para qué sirve esta salida dentro del torneo..."
                                    class="mt-1.5 w-full rounded-xl border-slate-300 text-sm">{{ old('description') }}</textarea>
                            </div>

                        </div>
                    </div>


                    <button type="submit"
                        class="w-full rounded-xl bg-slate-950 px-5 py-3 text-xs font-black text-white transition hover:bg-slate-800">
                        Crear salida
                    </button>

                </form>

            </div>


            @forelse ($phaseExits as $exit)

                @php
                    $rules = $rulesByExit->get($exit->id, collect())
                        ->sortBy('sort_order');

                    /*
                     * Cuantos salen de verdad por esta puerta: lo producen
                     * los criterios, que es lo que ejecuta el motor.
                     */
                    $emits = $exitForecast['by_exit'][$exit->id] ?? null;

                    /*
                     * Puertas de antes, que todavia guardan un selector
                     * propio ademas de sus criterios. El motor ya ignora ese
                     * selector; el aviso esta para que el grafo no siga
                     * validando un numero que nadie va a producir.
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

                    {{-- CABECERA DE LA PUERTA --}}

                    <div class="flex flex-wrap items-start justify-between gap-3">

                        <div class="min-w-0">

                            <p class="text-sm font-black text-slate-900">{{ $exit->name }}</p>

                            <div class="mt-1 flex flex-wrap items-center gap-2">

                                <span class="font-mono text-[10px] text-slate-400">{{ $exit->code }}</span>

                                @if ((int) $exit->priority !== 10)
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-black text-slate-600">
                                        Prioridad {{ $exit->priority }}
                                    </span>
                                @endif

                                @if ($exit->status !== 'ACTIVE')
                                    <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[9px] font-black text-slate-600">
                                        Inactiva
                                    </span>
                                @endif

                            </div>

                            @if ($exit->description)
                                <p class="mt-1.5 max-w-md text-[11px] leading-relaxed text-slate-500">
                                    {{ $exit->description }}
                                </p>
                            @endif

                        </div>

                        <div class="flex shrink-0 items-center gap-2">

                            @if ($emits !== null)
                                <span @class([
                                    'rounded-full px-3 py-1 text-[10px] font-black',
                                    'bg-violet-100 text-violet-700' => $emits > 0,
                                    'bg-amber-100 text-amber-700' => $emits === 0,
                                ])>
                                    Salen {{ $emits }} de {{ $exitForecast['participants'] }}
                                </span>
                            @endif

                            <form method="POST"
                                action="{{ route('tournaments.group-stage.exits.destroy', [$phaseTemplate, $exit]) }}"
                                onsubmit="return confirm('¿Eliminar «{{ $exit->name }}» y sus criterios?')">
                                @csrf
                                @method('DELETE')

                                <button class="rounded-lg px-2 py-1 text-[11px] font-black text-slate-400 transition hover:bg-red-50 hover:text-red-600">
                                    Eliminar
                                </button>
                            </form>

                        </div>

                    </div>


                    {{-- QUIEN LA CRUZA --}}

                    <div class="mt-3 rounded-2xl bg-slate-50 p-3">

                        <div class="flex items-center justify-between gap-3">

                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                                Quién sale por aquí
                            </p>

                            <button type="button"
                                @click="addingTo = addingTo === {{ $exit->id }} ? null : {{ $exit->id }}; editingRule = null; creating = false"
                                class="text-[11px] font-black text-violet-700 hover:underline">
                                <span x-show="addingTo !== {{ $exit->id }}">+ Añadir criterio</span>
                                <span x-show="addingTo === {{ $exit->id }}" x-cloak>Cerrar</span>
                            </button>

                        </div>

                        @forelse ($rules as $rule)

                            <div class="mt-2 rounded-xl bg-white p-2.5">

                                <div class="flex flex-wrap items-center justify-between gap-2">

                                    <div class="min-w-0">

                                        <p class="text-xs font-bold text-slate-800">
                                            {{ $rule->rule_summary ?? $rule->rule_type }}

                                            @if ($rule->status !== 'ACTIVE')
                                                <span class="ml-1 rounded-full bg-slate-200 px-1.5 py-0.5 text-[9px] font-black text-slate-600">
                                                    Inactivo
                                                </span>
                                            @endif
                                        </p>

                                        @if (isset($exitForecast['by_rule'][$rule->id]))
                                            <p class="mt-0.5 text-[10px] font-bold text-violet-600">
                                                {{ $exitForecast['by_rule'][$rule->id] }}
                                                {{ $exitForecast['by_rule'][$rule->id] === 1 ? 'competidor' : 'competidores' }}
                                            </p>
                                        @endif

                                    </div>

                                    <div class="flex shrink-0 gap-2">

                                        <button type="button"
                                            @click="editingRule = editingRule === {{ $rule->id }} ? null : {{ $rule->id }}; addingTo = null; creating = false"
                                            class="text-[11px] font-black text-slate-500 hover:text-violet-700">
                                            Editar
                                        </button>

                                        <form method="POST"
                                            action="{{ route('tournaments.group-stage.advancement-rules.destroy', [$phaseTemplate, $rule]) }}"
                                            onsubmit="return confirm('¿Quitar este criterio?')">
                                            @csrf
                                            @method('DELETE')

                                            <button class="text-[11px] font-black text-slate-400 hover:text-red-600">
                                                Quitar
                                            </button>
                                        </form>

                                    </div>

                                </div>

                                <div x-show="editingRule === {{ $rule->id }}" x-cloak
                                    class="mt-3 border-t border-slate-100 pt-3">

                                    @include('tournaments.phase-templates.partials.group-stage-advancement-rule-form', [
                                        'phaseTemplate' => $phaseTemplate,
                                        'advancementRule' => $rule,
                                        'lockedExit' => $exit,
                                    ])

                                </div>

                            </div>

                        @empty

                            <p class="mt-2 rounded-xl bg-amber-50 p-2.5 text-[11px] font-bold leading-relaxed text-amber-800">
                                Sin criterio, esta salida no la cruza nadie. Añádele uno.
                            </p>

                        @endforelse


                        {{-- ALTA DE OTRO CRITERIO --}}

                        <div x-show="addingTo === {{ $exit->id }}" x-cloak
                            class="mt-3 rounded-xl border border-violet-200 bg-white p-3">

                            @include('tournaments.phase-templates.partials.group-stage-advancement-rule-form', [
                                'phaseTemplate' => $phaseTemplate,
                                'advancementRule' => null,
                                'lockedExit' => $exit,
                            ])

                        </div>

                    </div>


                    {{-- PUERTA ANTIGUA QUE TODAVIA GUARDA SU PROPIO NUMERO --}}

                    @if ($mismatch)
                        <div class="mt-3 rounded-2xl border border-red-200 bg-red-50 p-3">

                            <p class="text-xs font-black text-red-800">
                                Esta salida arrastra un «{{ $exit->selector_type }} {{ $declares }}» de antes
                            </p>

                            <p class="mt-1.5 text-[11px] leading-relaxed text-red-700">
                                Manda el {{ $emits }}: el motor entrega la lista que producen los criterios.
                                Pero el número que leen el grafo y sus validaciones es el {{ $declares }},
                                así que el torneo se valida con una cantidad y se juega con otra. Guarda
                                cualquiera de los criterios de arriba y la salida dejará de llevar su
                                propio número.
                            </p>

                        </div>
                    @endif

                </div>

            @empty

                <div x-show="!creating" class="p-8 text-center">

                    <div class="text-4xl opacity-25">⇥</div>

                    <h4 class="mt-3 text-base font-black text-slate-900">Sin salidas</h4>

                    <p class="mx-auto mt-2 max-w-sm text-sm text-slate-500">
                        Nadie avanzaría a la siguiente fase. Una salida dice cómo se llama la puerta
                        y quién sale por ella.
                    </p>

                    <button type="button" @click="creating = true"
                        class="mt-4 rounded-xl bg-violet-600 px-5 py-2.5 text-xs font-black text-white transition hover:bg-violet-700">
                        Crear la primera salida
                    </button>

                </div>

            @endforelse

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

</x-app-layout>
