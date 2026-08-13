<section class="overflow-hidden rounded-3xl border border-slate-200 bg-white">

    <div class="bg-gradient-to-br from-slate-950 via-indigo-950 to-violet-950 p-6 text-white">
        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-indigo-300">Group Stage Preview</p>
        <h3 class="mt-2 text-xl font-black">Previsualización estructural</h3>
        <p class="mt-2 text-xs leading-5 text-slate-300">Los Seeds son ficticios. No se crean grupos históricos, partidos
            ni resultados reales.</p>
    </div>

    <div class="border-b border-slate-100 p-5">
        <form method="GET" action="{{ route('tournaments.group-stage.show', $phaseTemplate) }}"
            class="flex flex-col gap-3 sm:flex-row">

            <div class="flex-1">
                <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">Participantes</label>

                <input type="number" name="participants" min="4" max="512"
                    value="{{ $previewParticipants }}"
                    class="mt-2 w-full rounded-xl border-slate-300 focus:border-indigo-400 focus:ring-indigo-400">
            </div>

            <button type="submit" class="self-end rounded-xl bg-indigo-600 px-5 py-3 text-sm font-black text-white">
                Previsualizar
            </button>

        </form>
    </div>

    @if (!$preview['valid'])

        <div class="p-5">
            <div class="rounded-2xl border border-red-200 bg-red-50 p-5">
                <p class="font-black text-red-800">Configuración incompatible</p>

                <div class="mt-3 space-y-2">
                    @foreach ($preview['errors'] as $error)
                        <p class="text-xs leading-5 text-red-600">• {{ $error }}</p>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        <div class="grid grid-cols-2 gap-px bg-slate-100 lg:grid-cols-3">
            @foreach ([['Grupos', $preview['group_count']], ['Mín. grupo', $preview['min_size']], ['Máx. grupo', $preview['max_size']], ['Series', $preview['total_series']], ['Ventanas ronda', $preview['parallel_round_windows']], ['Descansos', $preview['total_rest_assignments']]] as [$label, $value])
                <div class="bg-white p-4">
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">{{ $label }}</p>
                    <p class="mt-2 text-xl font-black text-slate-900">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        @if ($preview['manual_assignment_required'])
            <div class="border-b border-slate-100 p-5">
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-xs font-black text-amber-900">Asignación manual pendiente</p>
                    <p class="mt-1 text-xs text-amber-700">El preview conoce los tamaños, pero los participantes reales
                        se colocarán durante la ejecución.</p>
                </div>
            </div>
        @endif

        <div class="p-5">
            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Grupos</p>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                @foreach (array_slice($preview['groups'], 0, 12) as $group)
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">

                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="font-black text-slate-900">{{ $group['name'] }}</p>
                                <p class="mt-0.5 font-mono text-[9px] font-bold text-slate-400">{{ $group['code'] }}</p>
                            </div>

                            <span class="rounded-lg bg-indigo-100 px-2.5 py-1.5 text-[9px] font-black text-indigo-700">
                                {{ $group['size'] }} participantes
                            </span>
                        </div>

                        <div class="mt-4 space-y-1.5">
                            @foreach ($group['members'] as $member)
                                <div class="rounded-lg bg-white px-3 py-2 text-xs font-bold text-slate-600">
                                    {{ $member['label'] }}
                                </div>
                            @endforeach
                        </div>

                        @if ($group['schedule']['valid'])
                            <div class="mt-4 flex flex-wrap gap-2">
                                <span class="rounded-lg bg-cyan-50 px-2.5 py-1.5 text-[9px] font-black text-cyan-700">
                                    {{ $group['schedule']['total_rounds'] }} jornadas
                                </span>

                                <span
                                    class="rounded-lg bg-emerald-50 px-2.5 py-1.5 text-[9px] font-black text-emerald-700">
                                    {{ $group['schedule']['total_series'] }} series
                                </span>
                            </div>
                        @endif

                    </article>
                @endforeach
            </div>

            @if (count($preview['groups']) > 12)
                <p class="mt-4 text-center text-xs font-bold text-slate-400">
                    Se muestran 12 de {{ count($preview['groups']) }} grupos.
                </p>
            @endif
        </div>


        {{-- OUTPUT FORECAST --}}

        <div class="border-t border-slate-100 p-5">

            <p class="text-[10px] font-black uppercase tracking-wider text-amber-600">Output Forecast</p>

            @if (empty($preview['advancement']['outputs']))

                <div class="mt-4 rounded-2xl border border-dashed border-amber-300 bg-amber-50 p-5 text-center">
                    <p class="text-sm font-black text-amber-900">Todavía no hay reglas de avance</p>
                    <p class="mt-1 text-xs text-amber-700">Crea puertas y después configura quién sale por ellas.</p>
                </div>
            @else
                <div class="mt-4 space-y-3">

                    @foreach ($preview['advancement']['outputs'] as $output)
                        <div
                            class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-4">

                            <div>
                                <p class="text-sm font-black text-slate-800">{{ $output['name'] }}</p>

                                @if ($output['variable_output'])
                                    <p class="mt-1 text-[10px] font-bold text-violet-600">Cantidad potencialmente
                                        variable por empate</p>
                                @endif
                            </div>

                            <span class="rounded-xl bg-amber-50 px-3 py-2 text-sm font-black text-amber-700">
                                {{ $output['expected_count'] }}{{ $output['variable_output'] ? '+' : '' }}
                            </span>

                        </div>
                    @endforeach

                </div>

                @if ($preview['advancement']['unselected_count'] > 0)
                    <div class="mt-3 rounded-xl bg-slate-100 p-3 text-xs font-bold text-slate-500">
                        {{ $preview['advancement']['unselected_count'] }} participantes todavía no pertenecen a ninguna
                        regla de salida.
                    </div>
                @endif

            @endif

        </div>

    @endif

</section>
