<section class="overflow-hidden rounded-3xl border border-slate-200 bg-white">

    {{-- HEADER --}}

    <div class="bg-gradient-to-br from-slate-950 via-cyan-950 to-emerald-950 p-6 text-white">

        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-cyan-300">
            Schedule Preview
        </p>

        <h3 class="mt-2 text-xl font-black">
            Previsualización del calendario
        </h3>

        <p class="mt-2 text-xs leading-5 text-slate-300">
            No crea partidos reales ni historial.
            Utiliza Seeds ficticios para comprobar la estructura.
        </p>

    </div>


    {{-- PARTICIPANTS --}}

    <div class="border-b border-slate-100 p-5">

        <form method="GET" action="{{ route('tournaments.round-robin.show', $phaseTemplate) }}"
            class="flex flex-col gap-3 sm:flex-row">

            <div class="flex-1">

                <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                    Participantes
                </label>

                <input type="number" name="participants" min="2" max="512"
                    value="{{ $previewParticipants }}"
                    class="mt-2 w-full rounded-xl border-slate-300 focus:border-cyan-400 focus:ring-cyan-400">

            </div>

            <button type="submit" class="self-end rounded-xl bg-cyan-600 px-5 py-3 text-sm font-black text-white">
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
        {{-- METRICS --}}

        <div class="grid grid-cols-2 gap-px bg-slate-100 lg:grid-cols-3">

            @foreach ([['Ciclos', $preview['cycles']], ['Jornadas', $preview['total_rounds']], ['Series', $preview['total_series']], ['Por jornada', $preview['series_per_round']], ['Descansos', $preview['total_rest_assignments']], ['Best of', 'BO' . $preview['default_best_of']]] as [$label, $value])
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


        {{-- INFORMATION --}}

        <div class="p-5">

            <div class="flex flex-wrap gap-2">

                <span class="rounded-xl bg-cyan-50 px-3 py-2 text-[10px] font-black text-cyan-700">
                    {{ $preview['participants'] }} participantes
                </span>

                <span class="rounded-xl bg-emerald-50 px-3 py-2 text-[10px] font-black text-emerald-700">
                    {{ $preview['series_per_cycle'] }} series/ciclo
                </span>

                @if ($preview['is_odd'])
                    <span class="rounded-xl bg-amber-50 px-3 py-2 text-[10px] font-black text-amber-700">
                        1 descanso/jornada
                    </span>
                @else
                    <span class="rounded-xl bg-slate-50 px-3 py-2 text-[10px] font-black text-slate-600">
                        Sin descansos
                    </span>
                @endif

            </div>

        </div>


        {{-- ROUNDS --}}

        <div class="border-t border-slate-100 p-5">

            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">
                Blueprint de jornadas
            </p>

            <div class="mt-4 space-y-4">

                @forelse ($preview['rounds'] as $round)
                    <article class="overflow-hidden rounded-2xl border border-slate-200">

                        <div class="flex items-center justify-between gap-4 bg-slate-50 px-4 py-3">

                            <div>

                                <p class="text-sm font-black text-slate-900">
                                    {{ $round['label'] }}
                                </p>

                                <p class="mt-0.5 text-[10px] font-bold text-slate-400">
                                    {{ $round['cycle_label'] }}
                                    ·
                                    {{ $round['series_count'] }}
                                    {{ $round['series_count'] === 1 ? 'serie' : 'series' }}
                                </p>

                            </div>

                            @if ($round['rest_participant'])
                                <span
                                    class="rounded-lg bg-amber-100 px-2.5 py-1.5 text-[9px] font-black text-amber-700">
                                    Descansa {{ $round['rest_participant'] }}
                                </span>
                            @endif

                        </div>

                        <div class="grid gap-2 p-4 sm:grid-cols-2">

                            @foreach ($round['pairings'] as $pairing)
                                <div class="flex items-center gap-2 rounded-xl border border-slate-100 bg-white p-3">

                                    <span class="min-w-0 flex-1 truncate text-xs font-black text-slate-700">
                                        {{ $pairing['participant_a'] }}
                                    </span>

                                    <span class="text-[9px] font-black text-slate-300">
                                        VS
                                    </span>

                                    <span class="min-w-0 flex-1 truncate text-right text-xs font-black text-slate-700">
                                        {{ $pairing['participant_b'] }}
                                    </span>

                                </div>
                            @endforeach

                        </div>

                    </article>

                @empty

                    <div class="rounded-2xl bg-slate-50 p-5 text-center text-sm text-slate-500">
                        No existen jornadas para mostrar.
                    </div>
                @endforelse

            </div>


            @if ($preview['has_more_rounds'])
                <div class="mt-4 rounded-xl border border-cyan-200 bg-cyan-50 p-4 text-center">

                    <p class="text-xs font-black text-cyan-800">
                        Preview limitado
                    </p>

                    <p class="mt-1 text-xs text-cyan-700">
                        Se muestran las primeras
                        {{ $preview['preview_rounds_count'] }}
                        jornadas de
                        {{ $preview['total_rounds'] }}.
                    </p>

                </div>
            @endif

        </div>

    @endif

</section>
