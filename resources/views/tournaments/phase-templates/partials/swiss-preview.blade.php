<section class="overflow-hidden rounded-3xl border border-slate-200 bg-white">

    <div class="bg-gradient-to-br from-slate-950 via-violet-950 to-fuchsia-950 p-6 text-white">

        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-violet-300">
            Swiss Preview
        </p>

        <h3 class="mt-2 text-xl font-black">
            Previsualización estructural
        </h3>

        <p class="mt-2 text-xs leading-5 text-slate-300">
            La primera ronda puede calcularse.
            Las rondas posteriores dependen de resultados reales.
        </p>

    </div>


    <div class="border-b border-slate-100 p-5">

        <form method="GET" action="{{ route('tournaments.swiss.show', $phaseTemplate) }}"
            class="flex flex-col gap-3 sm:flex-row">

            <div class="flex-1">

                <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                    Participantes
                </label>

                <input type="number" name="participants" min="2" max="512"
                    value="{{ $previewParticipants }}"
                    class="mt-2 w-full rounded-xl border-slate-300 focus:border-violet-400 focus:ring-violet-400">

            </div>

            <button type="submit" class="self-end rounded-xl bg-violet-600 px-5 py-3 text-sm font-black text-white">

                Previsualizar

            </button>

        </form>

    </div>


    @if (!$preview['valid'])

        <div class="p-5">

            <div class="rounded-2xl border border-red-200 bg-red-50 p-5">

                <p class="font-black text-red-800">
                    Configuración incompatible
                </p>

                <div class="mt-3 space-y-2">

                    @foreach ($preview['errors'] as $error)
                        <p class="text-xs leading-5 text-red-600">
                            • {{ $error }}
                        </p>
                    @endforeach

                </div>

            </div>

        </div>
    @else
        {{-- WARNINGS --}}

        @if (!empty($preview['warnings']))

            <div class="border-b border-slate-100 p-5">

                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">

                    <p class="text-xs font-black text-amber-900">
                        Observaciones
                    </p>

                    @foreach ($preview['warnings'] as $warning)
                        <p class="mt-2 text-xs leading-5 text-amber-700">
                            • {{ $warning }}
                        </p>
                    @endforeach

                </div>

            </div>

        @endif


        {{-- METRICS --}}

        <div class="grid grid-cols-2 gap-px bg-slate-100 lg:grid-cols-3">

            @foreach ([['Participantes', $preview['participants']], ['Rondas límite', $preview['round_limit']], ['Rivales únicos', $preview['unique_opponents_available']], ['Series máx.', $preview['max_series_upper_bound']], ['BYEs máx.', $preview['bye_rounds_upper_bound']], ['Dinámico desde', 'R' . $preview['dynamic_pairing_from_round']]] as [$label, $value])
                <div class="bg-white p-4">

                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                        {{ $label }}
                    </p>

                    <p class="mt-2 text-xl font-black text-slate-900">
                        {{ $value }}
                    </p>

                </div>
            @endforeach

        </div>


        {{-- FIRST ROUND --}}

        <div class="border-t border-slate-100 p-5">

            <p class="text-[10px] font-black uppercase tracking-wider text-violet-600">
                Round 1 Blueprint
            </p>

            <h4 class="mt-2 font-black text-slate-900">
                Primera ronda
            </h4>


            @if ($preview['first_round']['manual_bye_required'])

                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4">

                    <p class="text-xs font-black text-amber-900">
                        BYE manual requerido
                    </p>

                    <p class="mt-1 text-xs text-amber-700">
                        No se puede cerrar el pairing estructural hasta elegir
                        quién recibe el BYE.
                    </p>

                </div>
            @elseif ($preview['first_round']['valid'])
                @if ($preview['first_round']['bye'])
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3">

                        <span class="text-xs font-black text-amber-800">
                            BYE →
                            {{ $preview['first_round']['bye']['label'] }}
                        </span>

                    </div>
                @endif


                <div class="mt-4 space-y-2">

                    @foreach ($preview['first_round']['pairings'] as $pairing)
                        <div class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50 p-3">

                            <span class="w-8 shrink-0 text-center font-mono text-[9px] font-black text-slate-400">
                                {{ $pairing['table'] }}
                            </span>

                            <span class="min-w-0 flex-1 truncate text-xs font-black text-slate-700">
                                {{ $pairing['participant_a']['label'] }}
                            </span>

                            <span class="text-[9px] font-black text-violet-400">
                                VS
                            </span>

                            <span class="min-w-0 flex-1 truncate text-right text-xs font-black text-slate-700">
                                {{ $pairing['participant_b']['label'] }}
                            </span>

                        </div>
                    @endforeach

                </div>

            @endif


            <div class="mt-4 rounded-xl border border-dashed border-violet-200 bg-violet-50 p-4 text-center">

                <p class="text-xs font-black text-violet-900">
                    Ronda 2+
                </p>

                <p class="mt-1 text-xs text-violet-700">
                    Pairing dinámico según resultados, score,
                    rivales anteriores, rematches y BYEs.
                </p>

            </div>

        </div>


        {{-- RECORD MAP --}}

        @if (!empty($preview['record_map']))

            <div class="border-t border-slate-100 p-5">

                <p class="text-[10px] font-black uppercase tracking-wider text-fuchsia-600">
                    Record Map
                </p>

                <h4 class="mt-2 font-black text-slate-900">
                    Caminos posibles
                </h4>


                <div class="mt-4 space-y-4">

                    @foreach ($preview['record_map'] as $round => $states)
                        <div>

                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">
                                {{ $round === 0 ? 'Inicio' : 'Ronda ' . $round }}
                            </p>

                            <div class="mt-2 flex flex-wrap gap-2">

                                @foreach ($states as $state)
                                    @php
                                        $stateClass = match ($state['status']) {
                                            'QUALIFIED' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                            'ELIMINATED' => 'bg-red-100 text-red-700 border-red-200',
                                            'FALLBACK' => 'bg-amber-100 text-amber-700 border-amber-200',
                                            default => 'bg-violet-50 text-violet-700 border-violet-100',
                                        };
                                    @endphp

                                    <span
                                        class="{{ $stateClass }} rounded-xl border px-3 py-2 text-[10px] font-black">

                                        {{ $state['label'] }}

                                        @if ($state['status'] === 'QUALIFIED')
                                            ✓
                                        @elseif ($state['status'] === 'ELIMINATED')
                                            ×
                                        @elseif ($state['status'] === 'FALLBACK')
                                            !
                                        @endif

                                    </span>
                                @endforeach

                            </div>

                        </div>
                    @endforeach

                </div>

            </div>

        @endif


        {{-- OUTPUT FORECAST --}}

        <div class="border-t border-slate-100 p-5">

            <p class="text-[10px] font-black uppercase tracking-wider text-emerald-600">
                Output Forecast
            </p>


            @if (empty($preview['advancement']['outputs']))

                <div class="mt-4 rounded-xl border border-dashed border-emerald-200 bg-emerald-50 p-4 text-center">

                    <p class="text-xs font-black text-emerald-900">
                        Todavía no hay reglas de salida
                    </p>

                </div>
            @else
                <div class="mt-4 space-y-3">

                    @foreach ($preview['advancement']['outputs'] as $output)
                        <div class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 p-4">

                            <div>

                                <p class="text-xs font-black text-slate-900">
                                    {{ $output['name'] }}
                                </p>

                                @if ($output['variable'])
                                    <p class="mt-1 text-[10px] font-bold text-violet-600">
                                        Depende de resultados reales
                                    </p>
                                @endif

                            </div>


                            <span class="rounded-lg bg-emerald-50 px-3 py-2 text-xs font-black text-emerald-700">

                                @if ($output['variable'])
                                    @if ($output['minimum_count'] > 0)
                                        {{ $output['minimum_count'] }}+
                                    @else
                                        Variable
                                    @endif
                                @else
                                    {{ $output['expected_count'] }}
                                @endif

                            </span>

                        </div>
                    @endforeach

                </div>

            @endif

        </div>

    @endif

</section>
