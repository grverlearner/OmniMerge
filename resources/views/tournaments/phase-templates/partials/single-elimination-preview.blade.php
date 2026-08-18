<section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

    {{-- ENCABEZADO --}}

    <div class="bg-gradient-to-br from-slate-950 via-amber-950 to-orange-950 p-5 text-white">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-amber-300">
                    Vista previa
                </p>

                <h3 class="mt-2 text-xl font-black">
                    Estructura de la fase
                </h3>

                <p class="mt-1 text-[11px] leading-5 text-slate-300">
                    Cálculo temporal: no crea encuentros y recuerda la última cantidad utilizada.
                </p>
            </div>

            @if (
                $preview['valid']
                && ($preview['complete'] ?? true)
                && empty($preview['warnings'] ?? [])
            )
                <span
                    class="inline-flex w-fit items-center gap-2 rounded-full bg-emerald-400/15 px-3 py-2 text-[10px] font-black uppercase tracking-wider text-emerald-300">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    Vista previa válida
                </span>
            @elseif ($preview['valid'])
                <span
                    class="inline-flex w-fit items-center gap-2 rounded-full bg-amber-400/15 px-3 py-2 text-[10px] font-black uppercase tracking-wider text-amber-300">
                    <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                    Vista previa con advertencias
                </span>
            @else
                <span
                    class="inline-flex w-fit items-center gap-2 rounded-full bg-red-400/15 px-3 py-2 text-[10px] font-black uppercase tracking-wider text-red-300">
                    <span class="h-2 w-2 rounded-full bg-red-400"></span>
                    Configuración incompatible
                </span>
            @endif
        </div>

        <div class="mt-5 grid grid-cols-3 gap-2 rounded-2xl bg-white/5 p-1">
            <button type="button" class="rounded-xl px-3 py-2 text-[10px] font-black transition"
                :class="view === 'summary'
                    ?
                    'bg-amber-400 text-slate-950' :
                    'text-slate-300 hover:bg-white/10'"
                @click="setView('summary')">
                Resumen
            </button>

            <button type="button" class="rounded-xl px-3 py-2 text-[10px] font-black transition"
                :class="view === 'blocks'
                    ?
                    'bg-amber-400 text-slate-950' :
                    'text-slate-300 hover:bg-white/10'"
                @click="setView('blocks')">
                Bloques
            </button>

            <button type="button" class="rounded-xl px-3 py-2 text-[10px] font-black transition"
                :class="view === 'table'
                    ?
                    'bg-amber-400 text-slate-950' :
                    'text-slate-300 hover:bg-white/10'"
                @click="setView('table')">
                Tabla
            </button>
        </div>
    </div>

    @if (!empty($preview['warnings']))
        <div class="border-b border-amber-100 bg-amber-50 p-5">
            <p class="text-[10px] font-black uppercase tracking-wider text-amber-700">
                Advertencias de la vista previa
            </p>

            @foreach ($preview['warnings'] as $warning)
                <p class="mt-2 text-xs leading-5 text-amber-800">
                    • {{ $warning }}
                </p>
            @endforeach
        </div>
    @endif

    {{-- PARTICIPANTES --}}

    <div class="border-b border-slate-100 p-5">
        <form method="GET" action="{{ route('tournaments.single-elimination.show', $phaseTemplate) }}"
            @submit.prevent="schedulePreview(0)" class="flex flex-col gap-3 sm:flex-row">
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                        Participantes de prueba
                    </label>

                    <p class="mt-1 text-[11px] leading-4 text-slate-400">
                        Solo modifica este cálculo, no el contrato de la fase.
                    </p>
                </div>

                <input type="number" name="participants" min="2" max="512"
                    value="{{ $previewParticipants }}" data-preview-participants
                    @input.debounce.400ms="schedulePreview(0)"
                    class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400">
            </div>

            <button type="submit" :disabled="previewLoading"
                class="self-end rounded-xl bg-amber-500 px-5 py-3 text-sm font-black text-white shadow-lg shadow-amber-500/20 transition hover:bg-amber-600">
                <span x-show="! previewLoading">
                    Actualizar vista
                </span>

                <span x-show="previewLoading">
                    Actualizando...
                </span>
            </button>
        </form>

        @if ($phaseTemplate->exact_participants)
            <p class="mt-2 text-[11px] font-bold text-slate-400">
                Esta fase exige exactamente
                {{ $phaseTemplate->exact_participants }}
                participantes.
            </p>
        @else
            <p class="mt-2 text-[11px] font-bold text-slate-400">
                Contrato:
                mínimo {{ $phaseTemplate->min_participants }}

                @if ($phaseTemplate->max_participants)
                    · máximo {{ $phaseTemplate->max_participants }}
                @endif
            </p>
        @endif
    </div>

    @if (!$preview['valid'])

        {{-- INVÁLIDO --}}

        <div class="p-5">
            <div class="rounded-2xl border border-red-200 bg-red-50 p-5">
                <div class="flex items-start gap-3">
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-red-100 font-black text-red-700">
                        !
                    </span>

                    <div>
                        <p class="font-black text-red-900">
                            Configuración incompatible
                        </p>

                        <div class="mt-2 space-y-1.5">
                            @foreach ($preview['errors'] as $error)
                                <p class="text-xs leading-5 text-red-700">
                                    • {{ $error }}
                                </p>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- MÉTRICAS --}}

        <div class="grid grid-cols-2 gap-px bg-slate-100 lg:grid-cols-4">
            @foreach ([[($settings->configuration_mode ?? 'BASIC') === 'ADVANCED' ? 'Entrada' : 'Bracket', $preview['bracket_size'], 'amber'], ['BYEs', $preview['initial_byes'], 'cyan'], ['Rondas', $preview['round_count'], 'indigo'], ['Series', $preview['total_series'], 'violet']] as [$label, $value, $color])
                <div class="bg-white p-4">
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                        {{ $label }}
                    </p>

                    <p class="mt-1 text-2xl font-black text-slate-900">
                        {{ $value }}
                    </p>
                </div>
            @endforeach
        </div>

        {{-- VISTA RESUMEN --}}

        <div x-cloak x-show="view === 'summary'" x-transition.opacity class="p-5">
            <div class="grid gap-3 sm:grid-cols-3">
                <article class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4">
                    <p class="text-[9px] font-black uppercase tracking-wider text-indigo-500">
                        Entrada
                    </p>

                    <p class="mt-2 text-2xl font-black text-indigo-900">
                        {{ $preview['participants'] }}
                    </p>

                    <p class="mt-1 text-xs text-indigo-700">
                        participantes
                    </p>
                </article>

                <article class="rounded-2xl border border-red-200 bg-red-50 p-4">
                    <p class="text-[9px] font-black uppercase tracking-wider text-red-500">
                        Eliminados
                    </p>

                    <p class="mt-2 text-2xl font-black text-red-900">
                        {{ $preview['total_eliminated'] }}
                    </p>

                    <p class="mt-1 text-xs text-red-700">
                        terminan su ruta
                    </p>
                </article>

                <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                    <p class="text-[9px] font-black uppercase tracking-wider text-emerald-500">
                        Salida
                    </p>

                    <p class="mt-2 text-2xl font-black text-emerald-900">
                        {{ $preview['survivors_count'] }}
                    </p>

                    <p class="mt-1 text-xs text-emerald-700">
                        sobreviven
                    </p>
                </article>
            </div>

            <div class="mt-4 rounded-2xl bg-slate-950 p-4 text-white">
                <div class="flex flex-wrap items-center gap-2 text-xs font-black">
                    <span class="rounded-lg bg-indigo-400/20 px-3 py-2 text-indigo-200">
                        {{ $preview['participants'] }} entran
                    </span>

                    <span class="text-amber-400">→</span>

                    <span class="rounded-lg bg-amber-400/20 px-3 py-2 text-amber-200">
                        {{ $preview['round_count'] }}
                        {{ $preview['round_count'] === 1 ? 'ronda' : 'rondas' }}
                    </span>

                    <span class="text-amber-400">→</span>

                    <span class="rounded-lg bg-emerald-400/20 px-3 py-2 text-emerald-200">
                        {{ $preview['survivors_count'] }}
                        {{ $preview['survivors_count'] === 1 ? 'sale' : 'salen' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- VISTA BLOQUES --}}

        <div x-cloak x-show="view === 'blocks'" x-transition.opacity class="p-5">
            <div class="space-y-3">
                <article class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-[9px] font-black uppercase tracking-wider text-indigo-500">
                                Puerta de entrada
                            </p>

                            <p class="mt-1 font-black text-indigo-950">
                                Entrada general
                            </p>
                        </div>

                        <span class="rounded-xl bg-white px-3 py-2 text-xs font-black text-indigo-700 shadow-sm">
                            {{ $preview['participants'] }} participantes
                        </span>
                    </div>
                </article>

                @foreach ($preview['rounds'] as $round)
                    <div class="flex justify-center text-amber-500">
                        ↓
                    </div>

                    <article
                        class="rounded-2xl border p-4 {{ $round['has_override'] ? 'border-violet-300 bg-violet-50' : 'border-slate-200 bg-slate-50' }}">
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
                                    {{ $round['participants'] }} participantes
                                    ·
                                    {{ $round['series'] }}
                                    {{ $round['series'] === 1 ? 'serie' : 'series' }}

                                    @if ($round['byes'] > 0)
                                        · {{ $round['byes'] }} BYE
                                    @endif
                                </p>

                                @if (isset($round['entrants_per_match']))
                                    <p class="mt-2 text-[10px] font-black uppercase tracking-wider text-fuchsia-700">
                                        {{ $round['entrants_per_match'] }}
                                        →
                                        {{ $round['qualifiers_per_match'] }}

                                        ·

                                        {{ match ($round['encounter_profile']) {
                                            'DUEL' => 'Duelo',
                                            'MULTI_COMPETITOR' => 'Multicompetidor',
                                            'CUSTOM' => 'Personalizado',
                                            default => $round['encounter_profile'],
                                        } }}

                                        @if ($round['preliminary'])
                                            · Preliminar
                                        @endif
                                    </p>
                                @endif
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <span
                                    class="rounded-xl bg-white px-3 py-2 text-[10px] font-black text-violet-700 shadow-sm">
                                    {{ $round['series_label'] }}
                                </span>

                                <span
                                    class="rounded-xl bg-emerald-100 px-3 py-2 text-[10px] font-black text-emerald-700">
                                    {{ $round['survivors'] }} avanzan
                                </span>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-3 gap-2">
                            <div class="rounded-xl bg-white p-3 text-center">
                                <p class="text-[9px] font-black uppercase text-slate-400">
                                    Entran
                                </p>

                                <p class="mt-1 font-black text-slate-800">
                                    {{ $round['participants'] }}
                                </p>
                            </div>

                            <div class="rounded-xl bg-white p-3 text-center">
                                <p class="text-[9px] font-black uppercase text-red-400">
                                    Eliminados
                                </p>

                                <p class="mt-1 font-black text-red-700">
                                    {{ $round['eliminated'] }}
                                </p>
                            </div>

                            <div class="rounded-xl bg-white p-3 text-center">
                                <p class="text-[9px] font-black uppercase text-emerald-500">
                                    Avanzan
                                </p>

                                <p class="mt-1 font-black text-emerald-700">
                                    {{ $round['survivors'] }}
                                </p>
                            </div>
                        </div>
                    </article>
                @endforeach

                <div class="flex justify-center text-amber-500">
                    ↓
                </div>

                <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-[9px] font-black uppercase tracking-wider text-emerald-500">
                                Puerta de salida
                            </p>

                            <p class="mt-1 font-black text-emerald-950">
                                Supervivientes de la fase
                            </p>
                        </div>

                        <span class="rounded-xl bg-white px-3 py-2 text-xs font-black text-emerald-700 shadow-sm">
                            {{ $preview['survivors_count'] }} participantes
                        </span>
                    </div>
                </article>
            </div>
        </div>

        {{-- VISTA TABLA --}}

        <div x-cloak x-show="view === 'table'" x-transition.opacity class="p-5">
            <div class="overflow-x-auto rounded-2xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-left">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-wider text-slate-400">
                                Ronda
                            </th>

                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-wider text-slate-400">
                                Entran
                            </th>

                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-wider text-slate-400">
                                Series
                            </th>

                            @if (($settings->configuration_mode ?? 'BASIC') === 'ADVANCED')
                                <th class="px-4 py-3 text-[9px] font-black uppercase tracking-wider text-slate-400">
                                    K → Q
                                </th>
                            @endif

                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-wider text-slate-400">
                                BYEs
                            </th>

                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-wider text-slate-400">
                                Avanzan
                            </th>

                            <th class="px-4 py-3 text-[9px] font-black uppercase tracking-wider text-slate-400">
                                Regla
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($preview['rounds'] as $round)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-black text-slate-900">
                                            {{ $round['label'] }}
                                        </span>

                                        @if ($round['has_override'])
                                            <span
                                                class="rounded-full bg-violet-100 px-2 py-1 text-[8px] font-black text-violet-700">
                                                Override
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-xs font-bold text-slate-600">
                                    {{ $round['participants'] }}
                                </td>

                                <td class="px-4 py-3 text-xs font-bold text-slate-600">
                                    {{ $round['series'] }}
                                </td>

                                @if (($settings->configuration_mode ?? 'BASIC') === 'ADVANCED')
                                    <td class="whitespace-nowrap px-4 py-3 text-xs font-black text-fuchsia-700">
                                        {{ $round['entrants_per_match'] }}
                                        →
                                        {{ $round['qualifiers_per_match'] }}
                                    </td>
                                @endif

                                <td class="px-4 py-3 text-xs font-bold text-cyan-700">
                                    {{ $round['byes'] }}
                                </td>

                                <td class="px-4 py-3 text-xs font-black text-emerald-700">
                                    {{ $round['survivors'] }}
                                </td>

                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-lg bg-violet-50 px-2 py-1 text-[10px] font-black text-violet-700">
                                        {{ $round['series_label'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="mt-3 text-[11px] leading-5 text-slate-400">
                La tabla es la vista más compacta para fases
                con muchas rondas.
            </p>
        </div>
    @endif
</section>
