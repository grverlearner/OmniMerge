@php
    /*
     * Estructura de la fase de grupos.
     *
     * Todo lo de esta pantalla es una PREVISUALIZACIÓN: los grupos se
     * calculan con la configuración actual y las caras se toman prestadas
     * de las entidades del usuario solo para dibujar. Nada se guarda.
     */

    $valid = $preview['valid'] ?? false;
@endphp

<x-app-layout>

    <x-slot name="header">
        Estructura · {{ $phaseTemplate->name }}
    </x-slot>

    @include('tournaments.phase-templates.partials.workspace-navigation', [
        'current' => 'structure',
        'phaseTemplate' => $phaseTemplate,
        'settings' => $settings,
    ])


    {{-- CABECERA --}}

    <section class="mt-5 rounded-3xl border border-slate-200 bg-white p-6">

        <div class="flex flex-wrap items-start justify-between gap-5">

            <div class="max-w-2xl">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-indigo-600">
                    Previsualización
                </p>

                <h2 class="mt-2 text-2xl font-black text-slate-900">Cómo quedan los grupos</h2>

                <p class="mt-2 text-sm leading-relaxed text-slate-500">
                    Así se repartirían los participantes con la configuración actual.
                    Las caras son entidades tuyas <strong class="font-black text-slate-700">tomadas
                    prestadas para dibujar</strong>: no se inscribe a nadie ni se guarda nada.
                </p>
            </div>


            {{-- Cuántos participantes probar --}}

            <form method="GET" class="flex items-end gap-2">

                <div>
                    <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                        Participantes
                    </label>

                    <input type="number" name="participants" min="2" max="256"
                        value="{{ $participants }}"
                        class="mt-1.5 w-28 rounded-xl border-slate-300 text-sm focus:border-indigo-400 focus:ring-indigo-400">
                </div>

                <button class="rounded-xl bg-slate-950 px-4 py-2.5 text-xs font-black text-white hover:bg-slate-800">
                    Recalcular
                </button>

            </form>

        </div>

    </section>


    @if (!$valid)

        {{-- LA CONFIGURACIÓN NO PERMITE FORMAR GRUPOS --}}

        <section class="mt-5 rounded-3xl border border-amber-200 bg-amber-50 p-6">

            <p class="text-[10px] font-black uppercase tracking-wider text-amber-700">
                No se puede formar la estructura
            </p>

            <h3 class="mt-2 text-lg font-black text-amber-900">
                Con {{ $participants }} participantes esta configuración no cuadra
            </h3>

            <ul class="mt-3 space-y-1.5">
                @foreach ($preview['errors'] ?? [] as $error)
                    <li class="flex gap-2 text-sm text-amber-800">
                        <span class="shrink-0">•</span>
                        <span>{{ is_array($error) ? ($error['message'] ?? json_encode($error)) : $error }}</span>
                    </li>
                @endforeach
            </ul>

            <a href="{{ route('tournaments.group-stage.show', $phaseTemplate) }}"
                class="mt-5 inline-flex rounded-xl bg-amber-600 px-5 py-2.5 text-xs font-black text-white hover:bg-amber-700">
                Ajustar las reglas
            </a>

        </section>
    @else

        {{-- ============================================ --}}
        {{-- EL FLUJO: ENTRA → GRUPOS → SALE --}}
        {{-- ============================================ --}}

        <section class="mt-5 grid gap-4 lg:grid-cols-[260px_minmax(0,1fr)_260px]">

            {{-- ENTRADAS --}}

            <div class="rounded-3xl border border-slate-200 bg-white p-5">

                <p class="text-[10px] font-black uppercase tracking-wider text-emerald-600">
                    ⇢ Entran
                </p>

                <p class="mt-2 text-3xl font-black text-slate-900">{{ $participants }}</p>
                <p class="text-xs text-slate-500">participantes</p>

                <div class="mt-4 space-y-2">

                    @forelse ($gates as $gate)

                        @php
                            $target = $gate->settings['target_group_code'] ?? null;
                        @endphp

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">

                            <p class="truncate text-xs font-black text-slate-800">{{ $gate->name }}</p>

                            <p class="mt-1 text-[10px] font-bold text-slate-500">
                                @if ($target)
                                    <span class="text-indigo-600">→ {{ $target }}</span>
                                @else
                                    Reparto automático
                                @endif
                            </p>

                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 p-4 text-center">
                            <p class="text-[11px] text-slate-500">
                                Sin puertas de entrada definidas: todos entran por la vía por defecto.
                            </p>

                            <a href="{{ route('tournaments.group-stage.io', $phaseTemplate) }}"
                                class="mt-2 inline-block text-[10px] font-black text-indigo-600 hover:underline">
                                Configurar entradas
                            </a>
                        </div>
                    @endforelse

                </div>

            </div>


            {{-- GRUPOS --}}

            <div class="rounded-3xl border border-slate-200 bg-white p-5">

                <div class="flex items-center justify-between gap-3">
                    <p class="text-[10px] font-black uppercase tracking-wider text-indigo-600">
                        ▦ Se reparten en {{ $castByGroup->count() }} grupos
                    </p>

                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black text-slate-600">
                        {{ $settings->distribution_mode_label ?? $settings->distribution_mode }}
                    </span>
                </div>


                <div class="mt-4 grid gap-3 sm:grid-cols-2">

                    @foreach ($castByGroup as $group)

                        <div class="overflow-hidden rounded-2xl border border-slate-200">

                            <div class="flex items-center justify-between gap-2 border-b border-slate-100 bg-slate-50 px-3 py-2">

                                <p class="truncate text-xs font-black text-slate-900">
                                    {{ $group['name'] ?? ($group['code'] ?? 'Grupo') }}
                                </p>

                                <span class="shrink-0 rounded-full bg-white px-2 py-0.5 text-[9px] font-black text-slate-500">
                                    {{ $group['size'] ?? 0 }}
                                </span>

                            </div>


                            {{-- Retratos prestados --}}

                            <div class="grid grid-cols-4 gap-1.5 p-2.5">

                                @foreach ($group['cast'] ?? [] as $member)

                                    <div class="group/member relative">

                                        <div class="relative aspect-square overflow-hidden rounded-lg bg-slate-100 ring-1 ring-slate-200">

                                            @if ($member['image_url'])
                                                <img src="{{ $member['image_url'] }}" alt="{{ $member['name'] }}"
                                                    class="h-full w-full object-cover">
                                            @else
                                                <div class="flex h-full w-full items-center justify-center text-[11px] font-black text-slate-400">
                                                    {{ mb_substr($member['name'], -2) }}
                                                </div>
                                            @endif

                                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-900/90 to-transparent px-1 pb-0.5 pt-2">
                                                <p class="truncate text-[8px] font-black leading-tight text-white">
                                                    {{ $member['name'] }}
                                                </p>
                                            </div>

                                        </div>

                                    </div>
                                @endforeach

                            </div>


                            {{-- Qué se juega dentro --}}

                            <div class="border-t border-slate-100 px-3 py-2">
                                <p class="text-[9px] font-bold text-slate-500">
                                    {{ $group['series'] ?? $group['total_series'] ?? '—' }} enfrentamientos ·
                                    {{ $group['rounds'] ?? $group['total_rounds'] ?? '—' }} jornadas
                                </p>
                            </div>

                        </div>
                    @endforeach

                </div>

            </div>


            {{-- SALIDAS --}}

            <div class="rounded-3xl border border-slate-200 bg-white p-5">

                <p class="text-[10px] font-black uppercase tracking-wider text-violet-600">
                    ⇥ Salen
                </p>

                <p class="mt-2 text-3xl font-black text-slate-900">
                    {{ $preview['advancement']['total_qualified'] ?? $advancementRules->count() }}
                </p>
                <p class="text-xs text-slate-500">por las puertas de salida</p>

                <div class="mt-4 space-y-2">

                    @forelse ($phaseExits as $exit)

                        @php
                            $exitRules = $advancementRules->where('phase_exit_id', $exit->id);
                        @endphp

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">

                            <p class="truncate text-xs font-black text-slate-800">{{ $exit->name }}</p>

                            @forelse ($exitRules as $rule)
                                <p class="mt-1 truncate text-[10px] font-bold text-violet-600">
                                    → {{ $rule->rule_type_label ?? $rule->rule_type }}
                                    @if ($rule->group)
                                        · {{ $rule->group->name }}
                                    @endif
                                </p>
                            @empty
                                <p class="mt-1 text-[10px] text-slate-400">
                                    Sin regla que la alimente
                                </p>
                            @endforelse

                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 p-4 text-center">
                            <p class="text-[11px] text-slate-500">
                                Sin salidas definidas: nadie avanzaría a la siguiente fase.
                            </p>

                            <a href="{{ route('tournaments.group-stage.io', $phaseTemplate) }}"
                                class="mt-2 inline-block text-[10px] font-black text-violet-600 hover:underline">
                                Configurar salidas
                            </a>
                        </div>
                    @endforelse

                </div>

            </div>

        </section>


        {{-- ACCESOS --}}

        <section class="mt-5 flex flex-wrap gap-3">

            <a href="{{ route('tournaments.group-stage.io', $phaseTemplate) }}"
                class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-xs font-black text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">
                ⇄ Entradas y salidas
            </a>

            <a href="{{ route('tournaments.group-stage.simulator.show', $phaseTemplate) }}"
                class="rounded-xl bg-slate-950 px-5 py-3 text-xs font-black text-white transition hover:bg-slate-800">
                ▶ Probarlo en el simulador
            </a>

        </section>
    @endif

</x-app-layout>
