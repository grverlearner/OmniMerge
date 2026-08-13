<x-tournament-layout>

    <x-slot name="header">
        {{ $phaseTemplate->name }}
    </x-slot>

    @php

        $typeIcon = match ($phaseTemplate->phase_type) {
            'SINGLE_ELIMINATION' => '⚔',
            'ROUND_ROBIN' => '↻',
            'GROUP_STAGE' => '▦',
            'LEAGUE' => '⇅',
            'SWISS' => '◆',
            'CUSTOM' => '✦',
            default => '⌘',
        };

        $typeAccent = match ($phaseTemplate->phase_type) {
            'SINGLE_ELIMINATION' => 'amber',
            'ROUND_ROBIN' => 'cyan',
            'GROUP_STAGE' => 'indigo',
            'LEAGUE' => 'emerald',
            'SWISS' => 'violet',
            'CUSTOM' => 'rose',
            default => 'amber',
        };

        $statusClass = match ($phaseTemplate->status) {
            'ACTIVE' => 'bg-emerald-100 text-emerald-700',
            'DRAFT' => 'bg-amber-100 text-amber-700',
            'ARCHIVED' => 'bg-slate-200 text-slate-600',
            default => 'bg-slate-100 text-slate-600',
        };

        $visibilityClass = match ($phaseTemplate->visibility) {
            'PUBLIC' => 'bg-violet-100 text-violet-700',
            'PRIVATE' => 'bg-slate-100 text-slate-600',
            'UNLISTED' => 'bg-cyan-100 text-cyan-700',
            default => 'bg-slate-100 text-slate-600',
        };

        $typeDescription = match ($phaseTemplate->phase_type) {
            'SINGLE_ELIMINATION'
                => 'Los competidores se enfrentan en cruces eliminatorios. La Fase puede separar ganadores y perdedores mediante distintas puertas de salida.',

            'ROUND_ROBIN'
                => 'Los participantes compiten entre sí para construir una clasificación general basada en resultados y puntuación.',

            'GROUP_STAGE'
                => 'Los participantes se distribuyen en grupos independientes y se clasifican según la posición obtenida dentro de cada grupo.',

            'LEAGUE'
                => 'Los participantes forman una clasificación prolongada que posteriormente podrá producir ascensos, permanencias o descensos.',

            'SWISS'
                => 'Los participantes disputan rondas sucesivas contra rivales con rendimiento similar sin utilizar una eliminación inmediata.',

            'CUSTOM' => 'Fase preparada para admitir un comportamiento competitivo personalizado.',

            default => 'Mecanismo competitivo reutilizable dentro de Torneos.',
        };

        $suggestedExits = match ($phaseTemplate->phase_type) {
            'SINGLE_ELIMINATION' => [
                ['Supervivientes', 'SURVIVORS'],
                ['Eliminados', 'ELIMINATED'],
                ['Eliminados por ronda', 'ELIMINATED_IN_ROUND'],
            ],

            'ROUND_ROBIN' => [['Clasificados', 'TOP_N'], ['Eliminados', 'REMAINING']],

            'GROUP_STAGE' => [['Clasificados', 'TOP_N'], ['Repechaje', 'RANK_POSITION'], ['Eliminados', 'REMAINING']],

            'LEAGUE' => [['Ascenso', 'TOP_N'], ['Descenso', 'BOTTOM_N'], ['Permanencia', 'REMAINING']],

            'SWISS' => [['Clasificados', 'TOP_N'], ['Eliminados', 'REMAINING']],

            default => [],
        };

    @endphp


    {{-- ========================================================= --}}
    {{-- VOLVER --}}
    {{-- ========================================================= --}}

    <div class="mb-5">

        <a href="{{ route('tournaments.phase-templates.index') }}"
            class="inline-flex items-center gap-2 text-sm font-bold text-slate-400 transition hover:text-amber-600">
            <span>←</span>
            Fases
        </a>

    </div>


    {{-- ========================================================= --}}
    {{-- HERO --}}
    {{-- ========================================================= --}}

    <article id="summary" class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

        <div class="grid lg:grid-cols-[420px_minmax(0,1fr)]">

            {{-- IMAGEN / PORTADA --}}

            <div class="relative min-h-[390px] overflow-hidden bg-slate-950">

                @if ($phaseTemplate->image_url)
                    <img src="{{ $phaseTemplate->image_url }}" alt="{{ $phaseTemplate->name }}"
                        class="h-full min-h-[390px] w-full object-cover">

                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/10 to-transparent">
                    </div>
                @else
                    <div
                        class="flex h-full min-h-[390px] items-center justify-center bg-gradient-to-br from-slate-950 via-amber-950 to-orange-950">

                        <div class="relative">

                            <div class="absolute inset-0 scale-150 rounded-full bg-amber-400/20 blur-3xl"></div>

                            <div
                                class="relative flex h-36 w-36 items-center justify-center rounded-[36px] border border-amber-300/20 bg-white/5 text-7xl text-amber-300 shadow-2xl backdrop-blur">
                                {{ $typeIcon }}
                            </div>

                        </div>

                    </div>
                @endif


                <div class="absolute bottom-0 left-0 right-0 p-6">

                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-amber-300">
                        Phase Template
                    </p>

                    <p class="mt-2 font-mono text-xs font-bold text-white/60">
                        {{ $phaseTemplate->code }}
                    </p>

                </div>

            </div>


            {{-- INFORMACIÓN --}}

            <div class="flex flex-col justify-between p-6 sm:p-8">

                <div>

                    {{-- BADGES --}}

                    <div class="flex flex-wrap gap-2">

                        <span
                            class="rounded-full bg-amber-50 px-3 py-1 font-mono text-[10px] font-black text-amber-700">
                            {{ $phaseTemplate->code }}
                        </span>

                        <span class="rounded-full bg-indigo-50 px-3 py-1 text-[10px] font-black text-indigo-700">
                            {{ $typeIcon }}
                            {{ $phaseTemplate->type_label }}
                        </span>

                        <span class="{{ $statusClass }} rounded-full px-3 py-1 text-[10px] font-black uppercase">
                            {{ $phaseTemplate->status_label }}
                        </span>

                        <span class="{{ $visibilityClass }} rounded-full px-3 py-1 text-[10px] font-black uppercase">
                            {{ $phaseTemplate->visibility_label }}
                        </span>

                    </div>


                    {{-- NOMBRE --}}

                    <h1 class="mt-5 text-4xl font-black tracking-tight text-slate-900">
                        {{ $phaseTemplate->name }}
                    </h1>


                    <p class="mt-2 font-mono text-xs text-slate-400">
                        {{ $phaseTemplate->slug }}
                    </p>


                    {{-- DESCRIPCIÓN --}}

                    <p class="mt-6 max-w-3xl whitespace-pre-line text-sm leading-7 text-slate-600">
                        {{ $phaseTemplate->description ?: 'Esta Fase todavía no tiene una descripción.' }}</p>


                    {{-- EXPLICACIÓN TIPO --}}

                    <div class="mt-6 rounded-2xl border border-amber-100 bg-amber-50/60 p-4">

                        <div class="flex items-start gap-3">

                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-lg text-amber-700">
                                {{ $typeIcon }}
                            </div>

                            <div>

                                <p class="text-xs font-black uppercase tracking-wider text-amber-700">
                                    Cómo funciona
                                </p>

                                <p class="mt-1 text-sm leading-6 text-amber-950/70">
                                    {{ $typeDescription }}
                                </p>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- ACCIONES --}}

                <div class="mt-8 flex flex-wrap gap-3">

                    <a href="{{ route('tournaments.phase-templates.edit', $phaseTemplate) }}"
                        class="rounded-xl bg-amber-500 px-5 py-3 text-sm font-black text-white shadow-lg shadow-amber-500/20 transition hover:bg-amber-600">
                        ✎ Editar Fase
                    </a>


                    <form method="POST" action="{{ route('tournaments.phase-templates.duplicate', $phaseTemplate) }}">

                        @csrf

                        <button type="submit"
                            class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 transition hover:border-amber-300 hover:bg-amber-50">
                            ⧉ Duplicar
                        </button>

                    </form>


                    @if ($phaseTemplate->status !== 'ARCHIVED')
                        <form method="POST"
                            action="{{ route('tournaments.phase-templates.archive', $phaseTemplate) }}"
                            data-omni-confirm data-confirm-variant="warning" data-confirm-icon="!"
                            data-confirm-title="Archivar Fase" data-confirm-message="Esta Fase dejará de estar activa."
                            data-confirm-subject="{{ $phaseTemplate->name }}"
                            data-confirm-detail="No será eliminada. Su configuración y sus puertas de salida se conservarán."
                            data-confirm-action="Archivar Fase">

                            @csrf
                            @method('PATCH')

                            <button type="submit"
                                class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-500 transition hover:bg-slate-50">
                                Archivar
                            </button>

                        </form>
                    @endif

                </div>

            </div>

        </div>

    </article>


    {{-- ========================================================= --}}
    {{-- ESTADÍSTICAS --}}
    {{-- ========================================================= --}}

    <section class="mt-6 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">

        @foreach ([
        [
            'label' => 'Tipo',
            'value' => $phaseTemplate->type_label,
            'icon' => $typeIcon,
        ],
        [
            'label' => 'Entrada',
            'value' => $phaseTemplate->participant_contract_label,
            'icon' => '⇢',
        ],
        [
            'label' => 'Salidas',
            'value' => $phaseTemplate->exits_count,
            'icon' => '→',
        ],
        [
            'label' => 'Modalidad',
            'value' => $phaseTemplate->participant_mode_label,
            'icon' => '◉',
        ],
        [
            'label' => 'Best of',
            'value' => $phaseTemplate->best_of,
            'icon' => '×',
        ],
        [
            'label' => 'BYE',
            'value' => $phaseTemplate->allow_byes ? 'Permitido' : 'No',
            'icon' => '◇',
        ],
    ] as $stat)
            <article class="rounded-2xl border border-slate-200 bg-white p-4">

                <div class="flex items-start justify-between gap-3">

                    <div class="min-w-0">

                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                            {{ $stat['label'] }}
                        </p>

                        <p class="mt-2 truncate text-sm font-black text-slate-900">
                            {{ $stat['value'] }}
                        </p>

                    </div>

                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-sm font-black text-amber-700">
                        {{ $stat['icon'] }}
                    </div>

                </div>

            </article>
        @endforeach

    </section>


    {{-- ========================================================= --}}
    {{-- MAPA CONCEPTUAL --}}
    {{-- ========================================================= --}}

    <section class="mt-8 overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 p-6 sm:p-7">

        <div class="flex flex-col gap-7 xl:flex-row xl:items-center xl:justify-between">

            <div class="max-w-xl">

                <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-400">
                    Contrato competitivo
                </p>

                <h2 class="mt-2 text-2xl font-black text-white">
                    Qué hace esta Fase
                </h2>

                <p class="mt-3 text-sm leading-6 text-slate-400">
                    La Fase recibe competidores, aplica sus reglas internas
                    y finalmente separa a los participantes mediante sus
                    puertas de salida.
                </p>

            </div>


            <div class="flex flex-1 flex-col items-stretch gap-3 sm:flex-row sm:items-center sm:justify-end">

                {{-- INPUT --}}

                <div class="min-w-[170px] rounded-2xl border border-white/10 bg-white/5 p-4">

                    <p class="text-[9px] font-black uppercase tracking-wider text-cyan-300">
                        Entrada
                    </p>

                    <p class="mt-2 text-sm font-black text-white">
                        {{ $phaseTemplate->participant_contract_label }}
                    </p>

                </div>


                <div class="hidden text-2xl font-black text-amber-400 sm:block">
                    →
                </div>


                {{-- PHASE --}}

                <div class="min-w-[180px] rounded-2xl border border-amber-400/30 bg-amber-400/10 p-4">

                    <p class="text-[9px] font-black uppercase tracking-wider text-amber-300">
                        Fase
                    </p>

                    <p class="mt-2 text-sm font-black text-white">
                        {{ $phaseTemplate->name }}
                    </p>

                    <p class="mt-1 text-xs text-slate-400">
                        {{ $phaseTemplate->type_label }}
                    </p>

                </div>


                <div class="hidden text-2xl font-black text-amber-400 sm:block">
                    →
                </div>


                {{-- OUTPUT --}}

                <div class="min-w-[160px] rounded-2xl border border-white/10 bg-white/5 p-4">

                    <p class="text-[9px] font-black uppercase tracking-wider text-violet-300">
                        Salidas
                    </p>

                    <p class="mt-2 text-sm font-black text-white">
                        {{ $phaseTemplate->exits_count }}
                        {{ $phaseTemplate->exits_count === 1 ? 'puerta' : 'puertas' }}
                    </p>

                </div>

            </div>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- CONFIGURACIÓN --}}
    {{-- ========================================================= --}}

    <section id="configuration" class="mt-10">

        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">

            <div>

                <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-600">
                    Phase Definition
                </p>

                <h2 class="mt-2 text-2xl font-black text-slate-900">
                    Configuración de la Fase
                </h2>

                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                    Estas propiedades forman el contrato que utilizará
                    posteriormente el constructor de Torneos.
                </p>

            </div>


            <a href="{{ route('tournaments.phase-templates.edit', $phaseTemplate) }}"
                class="text-sm font-black text-amber-600">
                Editar configuración →
            </a>

        </div>


        <div class="mt-5 grid gap-5 lg:grid-cols-2">

            {{-- INPUT CONTRACT --}}

            <article class="rounded-3xl border border-slate-200 bg-white p-6">

                <div class="flex items-start gap-4">

                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-cyan-50 text-xl font-black text-cyan-700">
                        ⇢
                    </div>

                    <div>

                        <p class="text-[10px] font-black uppercase tracking-wider text-cyan-600">
                            Input Contract
                        </p>

                        <h3 class="mt-1 text-lg font-black text-slate-900">
                            Entrada de participantes
                        </h3>

                    </div>

                </div>


                <div class="mt-6 grid grid-cols-2 gap-3">

                    <div class="rounded-2xl bg-slate-50 p-4">

                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                            Mínimo
                        </p>

                        <p class="mt-2 text-xl font-black text-slate-900">
                            {{ $phaseTemplate->min_participants }}
                        </p>

                    </div>


                    <div class="rounded-2xl bg-slate-50 p-4">

                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                            Máximo
                        </p>

                        <p class="mt-2 text-xl font-black text-slate-900">
                            {{ $phaseTemplate->max_participants ?? '∞' }}
                        </p>

                    </div>


                    <div class="rounded-2xl bg-slate-50 p-4">

                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                            Exacto
                        </p>

                        <p class="mt-2 text-xl font-black text-slate-900">
                            {{ $phaseTemplate->exact_participants ?? '—' }}
                        </p>

                    </div>


                    <div class="rounded-2xl bg-slate-50 p-4">

                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                            Múltiplo
                        </p>

                        <p class="mt-2 text-xl font-black text-slate-900">
                            {{ $phaseTemplate->participant_multiple ?? '—' }}
                        </p>

                    </div>

                </div>


                <div class="mt-4 flex items-center gap-3 rounded-2xl border border-slate-100 bg-white p-4">

                    <div
                        class="{{ $phaseTemplate->allow_byes ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}
                            flex h-10 w-10 items-center justify-center rounded-xl font-black">
                        {{ $phaseTemplate->allow_byes ? '✓' : '×' }}
                    </div>

                    <div>

                        <p class="text-sm font-black text-slate-800">
                            BYE
                        </p>

                        <p class="text-xs text-slate-500">
                            {{ $phaseTemplate->allow_byes ? 'Esta Fase permite avances automáticos.' : 'Esta Fase no permite BYE.' }}
                        </p>

                    </div>

                </div>

            </article>


            {{-- INTERNAL RULES --}}

            <article class="rounded-3xl border border-slate-200 bg-white p-6">

                <div class="flex items-start gap-4">

                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-50 text-xl font-black text-amber-700">
                        {{ $typeIcon }}
                    </div>

                    <div>

                        <p class="text-[10px] font-black uppercase tracking-wider text-amber-600">
                            Internal Rules
                        </p>

                        <h3 class="mt-1 text-lg font-black text-slate-900">
                            Comportamiento competitivo
                        </h3>

                    </div>

                </div>


                <div class="mt-6 space-y-3">

                    <div class="flex items-center justify-between gap-4 rounded-2xl bg-slate-50 p-4">

                        <span class="text-xs font-bold text-slate-500">
                            Tipo de Fase
                        </span>

                        <span class="text-xs font-black text-slate-900">
                            {{ $phaseTemplate->type_label }}
                        </span>

                    </div>


                    <div class="flex items-center justify-between gap-4 rounded-2xl bg-slate-50 p-4">

                        <span class="text-xs font-bold text-slate-500">
                            Participación
                        </span>

                        <span class="text-xs font-black text-slate-900">
                            {{ $phaseTemplate->participant_mode_label }}
                        </span>

                    </div>


                    <div class="flex items-center justify-between gap-4 rounded-2xl bg-slate-50 p-4">

                        <span class="text-xs font-bold text-slate-500">
                            Formato
                        </span>

                        <span class="text-xs font-black text-slate-900">
                            Best of {{ $phaseTemplate->best_of }}
                        </span>

                    </div>


                    <div class="rounded-2xl border border-amber-100 bg-amber-50/60 p-4">

                        <p class="text-[9px] font-black uppercase tracking-wider text-amber-700">
                            Próxima evolución
                        </p>

                        <p class="mt-2 text-xs leading-5 text-amber-900/70">

                            @if ($phaseTemplate->phase_type === 'SINGLE_ELIMINATION')
                                <div
                                    class="rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 to-orange-50 p-5">

                                    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">

                                        <div>

                                            <p class="text-[9px] font-black uppercase tracking-wider text-amber-700">
                                                Single Elimination Engine
                                            </p>

                                            <p class="mt-2 text-sm font-black text-amber-950">
                                                Motor disponible
                                            </p>

                                            <p class="mt-1 max-w-xl text-xs leading-5 text-amber-800/80">
                                                Configura objetivo, seeding, pairing,
                                                BYEs, Best of, reglas por ronda y
                                                previsualiza automáticamente el bracket.
                                            </p>

                                        </div>

                                        <a href="{{ route('tournaments.single-elimination.show', $phaseTemplate) }}"
                                            class="shrink-0 rounded-xl bg-amber-500 px-4 py-3 text-center text-xs font-black text-white shadow-lg shadow-amber-500/20">
                                            ⚔ Configurar Engine
                                        </a>

                                    </div>

                                </div>
                            @elseif ($phaseTemplate->phase_type === 'ROUND_ROBIN')
                                <div
                                    class="rounded-2xl border border-cyan-200 bg-gradient-to-br from-cyan-50 to-emerald-50 p-5">

                                    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">

                                        <div>

                                            <p class="text-[9px] font-black uppercase tracking-wider text-cyan-700">
                                                Round Robin Engine
                                            </p>

                                            <p class="mt-2 text-sm font-black text-cyan-950">
                                                Motor disponible
                                            </p>

                                            <p class="mt-1 max-w-xl text-xs leading-5 text-cyan-800/80">
                                                Configura ciclos, calendario, empates,
                                                puntuación, Best of, clasificación,
                                                criterios de desempate y preview de jornadas.
                                            </p>

                                        </div>

                                        <a href="{{ route('tournaments.round-robin.show', $phaseTemplate) }}"
                                            class="shrink-0 rounded-xl bg-cyan-600 px-4 py-3 text-center text-xs font-black text-white shadow-lg shadow-cyan-600/20">
                                            ↻ Configurar Engine
                                        </a>

                                    </div>

                                </div>
                            @elseif ($phaseTemplate->phase_type === 'GROUP_STAGE')
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">

                                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                                        Próxima evolución
                                    </p>

                                    <p class="mt-2 text-xs leading-5 text-slate-600">
                                        Esta Fase recibirá cantidad de grupos,
                                        tamaño, distribución y clasificación interna.
                                    </p>

                                </div>
                            @elseif ($phaseTemplate->phase_type === 'LEAGUE')
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">

                                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                                        Próxima evolución
                                    </p>

                                    <p class="mt-2 text-xs leading-5 text-slate-600">
                                        Esta Fase recibirá sistema de puntos,
                                        ascensos, permanencias y descensos.
                                    </p>

                                </div>
                            @elseif ($phaseTemplate->phase_type === 'SWISS')
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">

                                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                                        Próxima evolución
                                    </p>

                                    <p class="mt-2 text-xs leading-5 text-slate-600">
                                        Esta Fase recibirá rondas, emparejamientos,
                                        historial de rivales y desempates avanzados.
                                    </p>

                                </div>
                            @else
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">

                                    <p class="text-xs leading-5 text-slate-500">
                                        La configuración avanzada se añadirá
                                        progresivamente al Phase Designer.
                                    </p>

                                </div>
                            @endif

                        </p>

                    </div>

                </div>

            </article>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- PUERTAS DE SALIDA --}}
    {{-- ========================================================= --}}

    <section id="exits" class="mt-10">

        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">

            <div>

                <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-600">
                    Output Contract
                </p>

                <h2 class="mt-2 text-2xl font-black text-slate-900">
                    Puertas de salida
                </h2>

                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    Cada puerta identifica qué competidores abandonan esta Fase.
                    <strong class="text-slate-700">
                        Aquí no se decide todavía hacia dónde irán.
                    </strong>
                    Ese destino pertenecerá al futuro Tournament Graph.
                </p>

            </div>


            <div class="rounded-xl bg-violet-50 px-4 py-2.5 text-xs font-black text-violet-700">
                {{ $phaseTemplate->exits_count }}
                {{ $phaseTemplate->exits_count === 1 ? 'salida configurada' : 'salidas configuradas' }}
            </div>

        </div>


        <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">

            {{-- ================================================= --}}
            {{-- LISTA SALIDAS --}}
            {{-- ================================================= --}}

            <div>

                @if ($phaseTemplate->exits->isEmpty())

                    <div class="overflow-hidden rounded-3xl border border-dashed border-violet-300 bg-white">

                        <div class="bg-gradient-to-r from-violet-50 to-amber-50 p-8 text-center">

                            <div
                                class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-3xl shadow-sm">
                                →
                            </div>

                            <h3 class="mt-4 text-xl font-black text-slate-900">
                                Esta Fase todavía no tiene puertas
                            </h3>

                            <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-500">
                                Una Fase necesita definir qué conjuntos de
                                participantes puede producir al finalizar.
                            </p>

                        </div>


                        @if (count($suggestedExits) > 0)

                            <div class="border-t border-slate-100 p-5">

                                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">
                                    Sugeridas para {{ $phaseTemplate->type_label }}
                                </p>


                                <div class="mt-3 grid gap-2 sm:grid-cols-2">

                                    @foreach ($suggestedExits as [$suggestedName, $suggestedType])
                                        <div class="rounded-xl bg-slate-50 p-3">

                                            <p class="text-xs font-black text-slate-700">
                                                {{ $suggestedName }}
                                            </p>

                                            <p class="mt-1 font-mono text-[9px] font-bold text-slate-400">
                                                {{ $suggestedType }}
                                            </p>

                                        </div>
                                    @endforeach

                                </div>

                            </div>

                        @endif

                    </div>
                @else
                    <div class="space-y-4">

                        @foreach ($phaseTemplate->exits as $phaseExit)
                            @php

                                $exitIcon = match ($phaseExit->selector_type) {
                                    'SURVIVORS' => '★',
                                    'ELIMINATED' => '×',
                                    'ELIMINATED_IN_ROUND' => '⌁',
                                    'MATCH_WINNERS' => '▲',
                                    'MATCH_LOSERS' => '▼',
                                    'TOP_N' => '↑',
                                    'BOTTOM_N' => '↓',
                                    'RANK_POSITION' => '#',
                                    'RANK_RANGE' => '↕',
                                    'ALL' => '●',
                                    'REMAINING' => '○',
                                    default => '→',
                                };

                                $exitColor = match ($phaseExit->selector_type) {
                                    'MATCH_WINNERS' => [
                                        'box' => 'bg-emerald-50 border-emerald-200',
                                        'icon' => 'bg-emerald-500 text-white',
                                        'text' => 'text-emerald-700',
                                    ],

                                    'MATCH_LOSERS' => [
                                        'box' => 'bg-red-50 border-red-200',
                                        'icon' => 'bg-red-500 text-white',
                                        'text' => 'text-red-700',
                                    ],

                                    'TOP_N' => [
                                        'box' => 'bg-indigo-50 border-indigo-200',
                                        'icon' => 'bg-indigo-500 text-white',
                                        'text' => 'text-indigo-700',
                                    ],

                                    'BOTTOM_N' => [
                                        'box' => 'bg-orange-50 border-orange-200',
                                        'icon' => 'bg-orange-500 text-white',
                                        'text' => 'text-orange-700',
                                    ],

                                    'RANK_POSITION', 'RANK_RANGE' => [
                                        'box' => 'bg-violet-50 border-violet-200',
                                        'icon' => 'bg-violet-500 text-white',
                                        'text' => 'text-violet-700',
                                    ],

                                    default => [
                                        'box' => 'bg-slate-50 border-slate-200',
                                        'icon' => 'bg-slate-700 text-white',
                                        'text' => 'text-slate-700',
                                    ],
                                };

                            @endphp


                            <article x-data="{ editing: false }"
                                class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

                                <div class="p-5">

                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">

                                        {{-- ICON --}}

                                        <div
                                            class="{{ $exitColor['icon'] }} flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl text-xl font-black shadow-sm">
                                            {{ $exitIcon }}
                                        </div>


                                        {{-- INFORMATION --}}

                                        <div class="min-w-0 flex-1">

                                            <div class="flex flex-wrap items-center gap-2">

                                                <h3 class="text-base font-black text-slate-900">
                                                    {{ $phaseExit->name }}
                                                </h3>


                                                <span
                                                    class="{{ $exitColor['box'] }} {{ $exitColor['text'] }} rounded-full border px-2.5 py-1 text-[9px] font-black uppercase">
                                                    {{ $phaseExit->selector_label }}
                                                </span>


                                                @if ($phaseExit->status !== 'ACTIVE')
                                                    <span
                                                        class="rounded-full bg-slate-100 px-2.5 py-1 text-[9px] font-black uppercase text-slate-500">
                                                        Inactiva
                                                    </span>
                                                @endif

                                            </div>


                                            <p class="mt-2 text-sm font-semibold text-slate-700">
                                                {{ $phaseExit->selection_summary }}
                                            </p>


                                            @if ($phaseExit->description)
                                                <p class="mt-2 max-w-2xl text-xs leading-5 text-slate-400">
                                                    {{ $phaseExit->description }}
                                                </p>
                                            @endif


                                            <div class="mt-4 flex flex-wrap gap-2">

                                                <span
                                                    class="rounded-lg bg-slate-50 px-2.5 py-1.5 font-mono text-[9px] font-bold text-slate-500">
                                                    {{ $phaseExit->code }}
                                                </span>

                                                <span
                                                    class="rounded-lg bg-slate-50 px-2.5 py-1.5 text-[9px] font-black uppercase text-slate-500">
                                                    Prioridad {{ $phaseExit->priority }}
                                                </span>

                                            </div>

                                        </div>


                                        {{-- ACTIONS --}}

                                        <div class="flex shrink-0 gap-2">

                                            <button type="button" @click="editing = !editing"
                                                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-600 transition hover:bg-slate-50">
                                                ✎
                                            </button>


                                            <form method="POST"
                                                action="{{ route('tournaments.phase-exits.destroy', [$phaseTemplate, $phaseExit]) }}"
                                                data-omni-confirm data-confirm-variant="danger" data-confirm-icon="×"
                                                data-confirm-title="Eliminar puerta"
                                                data-confirm-message="Esta puerta será eliminada de la Fase."
                                                data-confirm-subject="{{ $phaseExit->name }}"
                                                data-confirm-detail="La Fase continuará existiendo. Solo se eliminará este canal de salida."
                                                data-confirm-action="Eliminar puerta">

                                                @csrf
                                                @method('DELETE')

                                                <button type="submit"
                                                    class="rounded-xl bg-red-50 px-3 py-2 text-xs font-black text-red-600 transition hover:bg-red-100">
                                                    ×
                                                </button>

                                            </form>

                                        </div>

                                    </div>

                                </div>


                                {{-- EDITOR INLINE --}}

                                <div x-show="editing" x-transition style="display: none;"
                                    class="border-t border-slate-100 bg-slate-50/70 p-5">

                                    <div class="mb-4">

                                        <p class="text-xs font-black uppercase tracking-wider text-amber-600">
                                            Editar puerta
                                        </p>

                                        <p class="mt-1 text-xs text-slate-400">
                                            Modifica quién debe salir por este canal.
                                        </p>

                                    </div>


                                    @include('tournaments.phase-templates.partials.exit-form', [
                                        'phaseTemplate' => $phaseTemplate,
                                        'phaseExit' => $phaseExit,
                                    ])

                                </div>

                            </article>
                        @endforeach

                    </div>

                @endif

            </div>


            {{-- ================================================= --}}
            {{-- CREAR SALIDA --}}
            {{-- ================================================= --}}

            <aside class="h-fit xl:sticky xl:top-28">

                <div class="overflow-hidden rounded-3xl border border-amber-200 bg-white shadow-sm">

                    <div class="bg-gradient-to-br from-slate-950 via-amber-950 to-orange-950 p-5 text-white">

                        <div class="flex items-center gap-3">

                            <div
                                class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-400 text-xl font-black text-slate-950">
                                +
                            </div>

                            <div>

                                <p class="text-[9px] font-black uppercase tracking-[0.18em] text-amber-300">
                                    Output Builder
                                </p>

                                <h3 class="mt-1 font-black">
                                    Nueva puerta
                                </h3>

                            </div>

                        </div>


                        <p class="mt-4 text-xs leading-5 text-slate-300">
                            Define únicamente qué competidores salen.
                            El destino se configurará posteriormente
                            dentro del Torneo.
                        </p>

                    </div>


                    <div class="p-5">

                        @include('tournaments.phase-templates.partials.exit-form', [
                            'phaseTemplate' => $phaseTemplate,
                            'phaseExit' => null,
                        ])

                    </div>

                </div>


                {{-- REGLA ARQUITECTÓNICA --}}

                <div class="mt-4 rounded-2xl border border-indigo-200 bg-indigo-50 p-4">

                    <p class="text-[9px] font-black uppercase tracking-wider text-indigo-600">
                        Regla de OmniMerge
                    </p>

                    <p class="mt-2 text-xs leading-5 text-indigo-800">
                        Una salida sabe
                        <strong>quién sale</strong>,
                        pero nunca
                        <strong>a dónde va</strong>.
                    </p>

                </div>

            </aside>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- FUTURO TOURNAMENT GRAPH --}}
    {{-- ========================================================= --}}

    <section
        class="mt-10 rounded-3xl border border-violet-200 bg-gradient-to-br from-violet-50 via-white to-amber-50 p-6 sm:p-7">

        <div class="grid gap-7 lg:grid-cols-[1fr_auto] lg:items-center">

            <div>

                <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-600">
                    Próxima conexión
                </p>

                <h2 class="mt-2 text-2xl font-black text-slate-900">
                    Esta Fase después será una pieza de un Torneo
                </h2>

                <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-500">
                    Cuando implementemos el Tournament Graph,
                    podrás colocar esta PhaseTemplate dentro de un Torneo,
                    utilizarla varias veces y conectar cada una de sus
                    puertas con otras Fases o con resultados terminales.
                </p>


                <div class="mt-5 flex flex-wrap items-center gap-2">

                    <span class="rounded-xl bg-white px-3 py-2 text-xs font-black text-slate-600 shadow-sm">
                        {{ $phaseTemplate->name }}
                    </span>

                    <span class="text-lg font-black text-violet-500">
                        →
                    </span>

                    <span class="rounded-xl bg-white px-3 py-2 text-xs font-black text-slate-600 shadow-sm">
                        PhaseNode
                    </span>

                    <span class="text-lg font-black text-violet-500">
                        →
                    </span>

                    <span class="rounded-xl bg-white px-3 py-2 text-xs font-black text-slate-600 shadow-sm">
                        Connection
                    </span>

                    <span class="text-lg font-black text-violet-500">
                        →
                    </span>

                    <span class="rounded-xl bg-white px-3 py-2 text-xs font-black text-slate-600 shadow-sm">
                        Siguiente Fase
                    </span>

                </div>

            </div>


            <a href="{{ route('tournaments.templates.index') }}"
                class="rounded-xl bg-violet-600 px-5 py-3 text-center text-sm font-black text-white shadow-lg shadow-violet-600/20">
                Ver Torneos →
            </a>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- METADATA --}}
    {{-- ========================================================= --}}

    <section class="mt-8 rounded-3xl border border-slate-200 bg-white p-6">

        <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">
            Información
        </p>


        <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

            <div>
                <p class="text-[9px] font-black uppercase text-slate-400">
                    Creada
                </p>

                <p class="mt-2 text-sm font-black text-slate-700">
                    {{ $phaseTemplate->created_at->format('d/m/Y H:i') }}
                </p>
            </div>


            <div>
                <p class="text-[9px] font-black uppercase text-slate-400">
                    Última actualización
                </p>

                <p class="mt-2 text-sm font-black text-slate-700">
                    {{ $phaseTemplate->updated_at->format('d/m/Y H:i') }}
                </p>
            </div>


            <div>
                <p class="text-[9px] font-black uppercase text-slate-400">
                    Visibilidad
                </p>

                <p class="mt-2 text-sm font-black text-slate-700">
                    {{ $phaseTemplate->visibility_label }}
                </p>
            </div>


            <div>
                <p class="text-[9px] font-black uppercase text-slate-400">
                    Clonación
                </p>

                <p class="mt-2 text-sm font-black text-slate-700">
                    {{ $phaseTemplate->allow_cloning ? 'Permitida' : 'Desactivada' }}
                </p>
            </div>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- DANGER ZONE --}}
    {{-- ========================================================= --}}

    <section class="mt-8 rounded-3xl border border-red-200 bg-red-50 p-6">

        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-center">

            <div>

                <p class="text-[10px] font-black uppercase tracking-wider text-red-500">
                    Zona de peligro
                </p>

                <h3 class="mt-2 text-lg font-black text-red-900">
                    Eliminar esta Fase
                </h3>

                <p class="mt-1 text-xs leading-5 text-red-600">
                    La eliminación será lógica mediante Soft Delete.
                    Las puertas asociadas dejarán de estar disponibles junto con ella.
                </p>

            </div>


            <form method="POST" action="{{ route('tournaments.phase-templates.destroy', $phaseTemplate) }}"
                data-omni-confirm data-confirm-variant="danger" data-confirm-icon="×"
                data-confirm-title="Eliminar Fase" data-confirm-message="Vas a eliminar esta plantilla de Fase."
                data-confirm-subject="{{ $phaseTemplate->name }}"
                data-confirm-detail="La Fase dejará de estar disponible en tu Biblioteca de Fases."
                data-confirm-action="Sí, eliminar Fase" data-confirm-image="{{ $phaseTemplate->image_url ?? '' }}">

                @csrf
                @method('DELETE')


                <button type="submit"
                    class="rounded-xl bg-red-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-red-600/20 transition hover:bg-red-700">
                    Eliminar Fase
                </button>

            </form>

        </div>

    </section>

</x-tournament-layout>
