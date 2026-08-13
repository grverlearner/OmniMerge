<section class="overflow-hidden rounded-3xl border border-slate-200 bg-white">

    {{-- HEADER --}}

    <div class="bg-gradient-to-br from-slate-950 via-amber-950 to-orange-950 p-6 text-white">

        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-amber-300">
            Bracket Preview
        </p>

        <h3 class="mt-2 text-xl font-black">
            Previsualización matemática
        </h3>

        <p class="mt-2 text-xs leading-5 text-slate-300">
            No crea participantes, partidos ni historial.
            Solo calcula cómo se comportaría esta Fase.
        </p>

    </div>

    {{-- SELECT PARTICIPANTS --}}

    <div class="border-b border-slate-100 p-5">

        <form method="GET" action="{{ route('tournaments.single-elimination.show', $phaseTemplate) }}"
            class="flex flex-col gap-3 sm:flex-row">

            <div class="flex-1">
                <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                    Participantes
                </label>

                <input type="number" name="participants" min="2" max="512"
                    value="{{ $previewParticipants }}"
                    class="mt-2 w-full rounded-xl border-slate-300 focus:border-amber-400 focus:ring-amber-400">
            </div>

            <button type="submit" class="self-end rounded-xl bg-amber-500 px-5 py-3 text-sm font-black text-white">
                Previsualizar
            </button>

        </form>

    </div>

    {{-- INVALID --}}

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

        <div class="grid grid-cols-2 gap-px bg-slate-100 lg:grid-cols-4">

            @foreach ([['Bracket', $preview['bracket_size']], ['BYEs iniciales', $preview['initial_byes']], ['Rondas', $preview['round_count']], ['Series', $preview['total_series']]] as [$label, $value])
                <div class="bg-white p-4">
                    <p class="text-[9px] font-black uppercase text-slate-400">
                        {{ $label }}
                    </p>

                    <p class="mt-2 text-2xl font-black text-slate-900">
                        {{ $value }}
                    </p>
                </div>
            @endforeach

        </div>

        {{-- FLOW --}}

        <div class="p-5">

            <div class="flex flex-wrap items-center gap-2 rounded-2xl bg-slate-950 p-4 text-xs font-black text-white">

                <span class="rounded-lg bg-white/10 px-3 py-2">
                    {{ $preview['participants'] }}
                    entran
                </span>

                <span class="text-amber-400">→</span>

                <span class="rounded-lg bg-white/10 px-3 py-2">
                    {{ $preview['total_eliminated'] }}
                    eliminados
                </span>

                <span class="text-amber-400">→</span>

                <span class="rounded-lg bg-emerald-500/20 px-3 py-2 text-emerald-300">
                    {{ $preview['survivors_count'] }}
                    sobreviven
                </span>

            </div>

        </div>

        {{-- ROUNDS --}}

        <div class="border-t border-slate-100 p-5">

            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">
                Blueprint de rondas
            </p>

            <div class="mt-4 space-y-3">

                @forelse ($preview['rounds'] as $round)
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">

                        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">

                            <div>

                                <div class="flex flex-wrap items-center gap-2">

                                    <p class="font-black text-slate-900">
                                        {{ $round['label'] }}
                                    </p>

                                    @if ($round['has_override'])
                                        <span
                                            class="rounded-full bg-violet-100 px-2 py-1 text-[9px] font-black uppercase text-violet-700">
                                            Override
                                        </span>
                                    @endif

                                </div>

                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $round['participants'] }}
                                    participantes

                                    ·

                                    {{ $round['series'] }}
                                    {{ $round['series'] === 1 ? 'serie' : 'series' }}

                                    @if ($round['byes'] > 0)
                                        ·
                                        {{ $round['byes'] }}
                                        BYE
                                    @endif
                                </p>

                            </div>

                            <div class="flex gap-2">

                                <span
                                    class="rounded-xl bg-white px-3 py-2 text-[10px] font-black text-slate-600 shadow-sm">
                                    BO{{ $round['best_of'] }}
                                </span>

                                <span
                                    class="rounded-xl bg-emerald-50 px-3 py-2 text-[10px] font-black text-emerald-700">
                                    {{ $round['survivors'] }} avanzan
                                </span>

                            </div>

                        </div>

                        <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-slate-200">
                            <div class="h-full rounded-full bg-amber-500"
                                style="width: {{ ($round['survivors'] / max(1, $round['slots'])) * 100 }}%">
                            </div>
                        </div>

                    </article>

                @empty

                    <div class="rounded-2xl bg-slate-50 p-5 text-center text-sm text-slate-500">
                        La Fase ya se encuentra en su objetivo.
                    </div>
                @endforelse

            </div>

        </div>

    @endif

</section>
