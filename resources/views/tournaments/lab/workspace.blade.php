<x-tournament-layout>

    <x-slot name="header">
        Competition Lab · {{ $tournamentTemplate->name }}
    </x-slot>

    @include('tournaments.partials.template-navigation')

    @if ($errors->any())
        <section class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-5">

            <p class="font-black text-red-900">
                No fue posible preparar el Lab
            </p>

            @foreach ($errors->all() as $error)
                <p class="mt-1 text-xs text-red-700">
                    • {{ $error }}
                </p>
            @endforeach
        </section>
    @endif

    <div x-data="competitionLab({
        initialState: @js($labPayload['state'] ?? null),
        initialToken: @js($labPayload['state_token'] ?? null),
        actionUrl: @js(route('tournaments.lab.action', $tournamentTemplate)),
        storageKey: @js('omnimerge:competition-lab:' . auth()->id() . ':' . $tournamentTemplate->id),
    })">

        <section
            class="relative overflow-hidden rounded-[30px] bg-gradient-to-br from-slate-950 via-slate-900 to-violet-950 p-7 text-white shadow-xl">

            <div class="pointer-events-none absolute -right-20 -top-20 h-72 w-72 rounded-full bg-violet-500/20 blur-3xl">
            </div>

            <div class="relative flex flex-col justify-between gap-6 xl:flex-row xl:items-end">

                <div>
                    <div
                        class="inline-flex rounded-full border border-violet-300/20 bg-violet-400/10 px-4 py-2 text-[10px] font-black uppercase tracking-[0.18em] text-violet-300">
                        ⚗ T9.1 · Lab Foundation
                    </div>

                    <h1 class="mt-5 text-3xl font-black">
                        {{ $tournamentTemplate->name }}
                    </h1>

                    <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300">
                        Prepara una prueba temporal segura. Los partidos,
                        rondas y standings se incorporarán en los siguientes
                        bloques del Competition Lab.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <span class="rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-xs font-black">
                        Estado:
                        <span x-text="state?.status ?? 'SIN INICIAR'">
                        </span>
                    </span>

                    <a href="{{ route('tournaments.graph.preview.show', $tournamentTemplate) }}"
                        class="rounded-xl bg-violet-600 px-4 py-3 text-xs font-black text-white">
                        ← Flow Preview
                    </a>
                </div>
            </div>
        </section>

        @if (!$canInitialize)
            <section class="mt-5 rounded-3xl border border-red-200 bg-red-50 p-6">

                <p class="font-black text-red-950">
                    El Lab está bloqueado
                </p>

                <p class="mt-2 text-xs leading-6 text-red-700">
                    Corrige los errores del Tournament Graph antes de inicializar.
                </p>

                <div class="mt-4 space-y-2">
                    @foreach ($validation['errors'] as $problem)
                        <p class="rounded-xl bg-white px-4 py-3 text-xs text-red-800">
                            {{ $problem['message'] }}
                        </p>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- CONFIGURACIÓN --}}

        <section x-show="!state" class="mt-6 grid items-start gap-5 xl:grid-cols-[380px_minmax(0,1fr)]">

            <form method="POST" action="{{ route('tournaments.lab.initialize', $tournamentTemplate) }}"
                class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

                @csrf

                <p class="text-[10px] font-black uppercase tracking-[0.16em] text-violet-600">
                    Inicialización
                </p>

                <h2 class="mt-2 font-black text-slate-950">
                    Participantes temporales
                </h2>

                <input type="hidden" name="participant_mode" value="GENERATED">

                <div class="mt-5">
                    <label class="text-[9px] font-black uppercase text-slate-500">
                        Orden
                    </label>

                    <select name="ordering_strategy" class="mt-2 w-full rounded-xl border-slate-200 text-sm">

                        <option value="ORDERED">
                            Orden original
                        </option>

                        <option value="SEEDED_RANDOM">
                            Aleatorio reproducible
                        </option>
                    </select>
                </div>

                <div class="mt-3">
                    <label class="text-[9px] font-black uppercase text-slate-500">
                        Semilla
                    </label>

                    <input type="number" name="seed" min="1" max="2147483647"
                        value="{{ old('seed', random_int(1, 999999999)) }}"
                        class="mt-2 w-full rounded-xl border-slate-200 text-sm">
                </div>

                <div class="mt-5 space-y-3">
                    @foreach ($tournamentTemplate->graphStarts as $index => $start)
                        <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">

                            <input type="hidden" name="starts[{{ $index }}][start_id]"
                                value="{{ $start->id }}">

                            <div class="flex items-start justify-between gap-3">

                                <div>
                                    <p class="text-[9px] font-black text-emerald-600">
                                        {{ $start->code }}
                                    </p>

                                    <p class="mt-1 text-xs font-black text-slate-900">
                                        {{ $start->name }}
                                    </p>
                                </div>

                                <span class="rounded-full bg-white px-2 py-1 text-[8px] font-black text-emerald-700">
                                    {{ $start->expected_participants ?? '?' }}
                                    esperados
                                </span>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <input type="number" name="starts[{{ $index }}][count]" min="1"
                                    max="512" required
                                    value="{{ old("starts.$index.count", $start->expected_participants ?? 8) }}"
                                    class="rounded-xl border-emerald-200 bg-white text-sm">

                                <input name="starts[{{ $index }}][prefix]"
                                    value="{{ old("starts.$index.prefix", $start->code) }}"
                                    class="rounded-xl border-emerald-200 bg-white text-sm">
                            </div>
                        </article>
                    @endforeach
                </div>

                <button type="submit" @disabled(!$canInitialize)
                    class="mt-5 w-full rounded-xl bg-violet-600 px-4 py-3 text-xs font-black text-white disabled:cursor-not-allowed disabled:opacity-40">
                    Preparar Competition Lab
                </button>
            </form>

            <div class="rounded-3xl border border-dashed border-violet-300 bg-violet-50 p-8">

                <p class="text-[10px] font-black uppercase tracking-[0.16em] text-violet-600">
                    Qué se preparará
                </p>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @foreach ([['Participantes', 'Identidades temporales únicas.'], ['Starts', 'Pools de entrada independientes.'], ['Fases', 'Estados LOCKED listos para activarse.'], ['Terminales', 'Resultados finales todavía vacíos.'], ['Seguridad', 'Estado cifrado por Laravel.'], ['Recorridos', 'Journey inicial de cada participante.']] as [$title, $description])
                        <div class="rounded-2xl border border-violet-200 bg-white p-4">

                            <p class="text-xs font-black text-violet-900">
                                {{ $title }}
                            </p>

                            <p class="mt-1 text-[10px] leading-5 text-slate-500">
                                {{ $description }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- WORKSPACE --}}

        <section x-cloak x-show="state" class="mt-6 space-y-5">

            <div x-show="error" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-xs text-red-700"
                x-text="error">
            </div>

            <div
                class="flex flex-col justify-between gap-3 rounded-3xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-center">

                <div class="flex flex-wrap gap-2">
                    <button type="button" x-show="state?.status === 'READY'" @click="execute('START')"
                        :disabled="loading"
                        class="rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-black text-white">
                        ▶ Iniciar
                    </button>

                    <button type="button" x-show="state?.status === 'RUNNING'" @click="execute('PAUSE')"
                        :disabled="loading"
                        class="rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-black text-white">
                        Pausar
                    </button>

                    <button type="button" x-show="state?.status === 'PAUSED'" @click="execute('RESUME')"
                        :disabled="loading"
                        class="rounded-xl bg-violet-600 px-4 py-2.5 text-xs font-black text-white">
                        Reanudar
                    </button>

                    <button type="button" @click="execute('RESET')" :disabled="loading"
                        class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-black text-slate-600">
                        Reiniciar estado
                    </button>
                </div>

                <button type="button"
                    @click="
                        if (confirm(
                            '¿Cerrar y eliminar el Lab temporal de esta pestaña?'
                        )) {
                            removeLocalState()
                        }
                    "
                    class="rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-xs font-black text-red-600">
                    Cerrar Lab
                </button>
            </div>

            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                <template
                    x-for="[label, value] in [
                        ['Participantes', state?.summary?.participants ?? 0],
                        ['Inicios', state?.summary?.starts ?? 0],
                        ['Fases', state?.summary?.nodes ?? 0],
                        ['Finales', state?.summary?.terminals ?? 0],
                    ]"
                    :key="label">

                    <article class="rounded-2xl border border-slate-200 bg-white p-4">

                        <p class="text-[8px] font-black uppercase text-slate-400" x-text="label">
                        </p>

                        <p class="mt-2 text-lg font-black text-slate-900" x-text="value">
                        </p>
                    </article>
                </template>
            </div>

            {{-- T9.2 · MOTORES DE FASE --}}

            <section class="rounded-3xl border border-violet-200 bg-gradient-to-br from-violet-50 to-white p-5">

                <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">

                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.16em] text-violet-600">
                            T9.2 · Phase Engines
                        </p>

                        <h2 class="mt-2 text-xl font-black text-slate-950">
                            Single Elimination y Round Robin
                        </h2>

                        <p class="mt-2 max-w-2xl text-xs leading-6 text-slate-500">
                            Selecciona una fase compatible y los competidores
                            temporales que deseas introducir. En T9.4 esta carga
                            será realizada automáticamente por las conexiones.
                        </p>
                    </div>

                    <select x-model="selectedNodeId" @change="selectedForEngine = []"
                        class="min-w-[270px] rounded-xl border-violet-200 bg-white text-sm font-bold text-slate-800">

                        <option value="">
                            Selecciona una fase
                        </option>

                        <template x-for="node in engineNodes()" :key="node.id">

                            <option :value="node.id"
                                x-text="`${node.code} · ${node.name} · ${node.phase_type_label}`">
                            </option>
                        </template>
                    </select>
                </div>

                <template x-if="selectedNode() && !selectedNode().runtime">

                    <div class="mt-5">

                        <p class="mb-3 text-[9px] font-black uppercase text-slate-400">
                            Participantes que entrarán en la fase
                        </p>

                        <div class="flex flex-wrap gap-2">

                            <template x-for="participant in participants()" :key="participant.lab_id">

                                <button type="button"
                                    @click="toggleEngineParticipant(
                            participant.lab_id
                        )"
                                    class="rounded-xl border px-3 py-2 text-[10px] font-black transition"
                                    :class="selectedForEngine.includes(
                                            participant.lab_id
                                        ) ?
                                        'border-violet-500 bg-violet-600 text-white' :
                                        'border-slate-200 bg-white text-slate-600'"
                                    x-text="participant.name">
                                </button>
                            </template>
                        </div>

                        <div class="mt-4 flex items-center justify-between gap-3">

                            <p class="text-xs font-bold text-slate-500">
                                <span x-text="selectedForEngine.length">
                                </span>
                                seleccionados
                            </p>

                            <button type="button" @click="prepareSelectedNode()"
                                :disabled="loading
                                    ||
                                    selectedForEngine.length < 2 ||
                                    state?.status !== 'RUNNING'"
                                class="rounded-xl bg-violet-600 px-5 py-3 text-xs font-black text-white disabled:cursor-not-allowed disabled:opacity-40">

                                Preparar motor
                            </button>
                        </div>
                    </div>
                </template>

                <template x-if="selectedNode()?.runtime">

                    <div class="mt-6 space-y-5">

                        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">

                            <template
                                x-for="[label, value] in [
                        ['Motor', selectedNode().runtime.engine],
                        ['Estado', selectedNode().runtime.status],
                        ['Partidos', selectedNode().runtime.matches_total],
                        ['Completados', selectedNode().runtime.matches_completed],
                    ]"
                                :key="label">

                                <div class="rounded-2xl border border-violet-100 bg-white p-4">

                                    <p class="text-[8px] font-black uppercase text-slate-400" x-text="label">
                                    </p>

                                    <p class="mt-2 text-sm font-black text-slate-900" x-text="value">
                                    </p>
                                </div>
                            </template>
                        </div>

                        <div class="flex justify-end">

                            <button type="button"
                                @click="
                        execute(
                            'SIMULATE_ROUND',
                            {
                                node_id:
                                    Number(
                                        selectedNodeId
                                    ),
                            }
                        )
                    "
                                :disabled="loading
                                    ||
                                    selectedNode().runtime.status === 'COMPLETED'"
                                class="rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-black text-white disabled:opacity-40">

                                ⚡ Simular ronda pendiente
                            </button>
                        </div>

                        <div class="space-y-4">

                            <template x-for="round in rounds()" :key="round.number">

                                <article class="rounded-2xl border border-slate-200 bg-white p-4">

                                    <div class="flex items-center justify-between">

                                        <h3 class="font-black text-slate-900" x-text="round.label">
                                        </h3>

                                        <span
                                            class="rounded-full bg-slate-100 px-3 py-1 text-[8px] font-black text-slate-600"
                                            x-text="round.status">
                                        </span>
                                    </div>

                                    <div class="mt-3 grid gap-3 xl:grid-cols-2">

                                        <template x-for="match in round.matches" :key="match.id">

                                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">

                                                <div class="flex items-center justify-between">

                                                    <span class="text-[8px] font-black text-violet-600"
                                                        x-text="match.id">
                                                    </span>

                                                    <span class="text-[8px] font-black text-slate-400"
                                                        x-text="match.status">
                                                    </span>
                                                </div>

                                                <div
                                                    class="mt-3 grid grid-cols-[minmax(0,1fr)_52px_18px_52px_minmax(0,1fr)] items-center gap-2">

                                                    <p class="truncate text-right text-[10px] font-black text-slate-800"
                                                        x-text="participantName(
                                                            match.participant_a_id
                                                        )">
                                                    </p>

                                                    {{-- SCORE A PENDIENTE --}}

                                                    <template x-if="match.status === 'PENDING'">

                                                        <input type="number" min="0"
                                                            x-model.number="resultForm(match).score_a"
                                                            class="rounded-lg border-slate-200 bg-white p-2 text-center text-xs font-black text-slate-900">
                                                    </template>

                                                    {{-- SCORE A COMPLETADO --}}

                                                    <template x-if="match.status !== 'PENDING'">

                                                        <div class="rounded-lg border border-violet-200 bg-violet-50 p-2 text-center text-xs font-black text-violet-800"
                                                            x-text="
                                                                match.score_a
                                                                ??
                                                                '—'
                                                            ">
                                                        </div>
                                                    </template>

                                                    <span class="text-center text-xs font-black text-slate-400">
                                                        –
                                                    </span>

                                                    {{-- SCORE B PENDIENTE --}}

                                                    <template x-if="match.status === 'PENDING'">

                                                        <input type="number" min="0"
                                                            x-model.number="resultForm(match).score_b"
                                                            class="rounded-lg border-slate-200 bg-white p-2 text-center text-xs font-black text-slate-900">
                                                    </template>

                                                    {{-- SCORE B COMPLETADO --}}

                                                    <template x-if="match.status !== 'PENDING'">

                                                        <div class="rounded-lg border border-violet-200 bg-violet-50 p-2 text-center text-xs font-black text-violet-800"
                                                            x-text="
                                                                match.score_b
                                                                ??
                                                                '—'
                                                            ">
                                                        </div>
                                                    </template>

                                                    <p class="truncate text-[10px] font-black text-slate-800"
                                                        x-text="participantName(
                                                            match.participant_b_id
                                                        )">
                                                    </p>
                                                </div>

                                                <div x-show="
                                            match.status === 'PENDING'
                                            &&
                                            match.participant_a_id
                                            &&
                                            match.participant_b_id
                                        "
                                                    class="mt-3 flex justify-end gap-2">

                                                    <button type="button"
                                                        @click="
                                                execute(
                                                    'SIMULATE_MATCH',
                                                    {
                                                        node_id:
                                                            Number(
                                                                selectedNodeId
                                                            ),

                                                        match_id:
                                                            match.id,
                                                    }
                                                )
                                            "
                                                        class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-[9px] font-black text-amber-700">

                                                        Simular
                                                    </button>

                                                    <button type="button" @click="submitResult(match)"
                                                        class="rounded-lg bg-violet-600 px-3 py-2 text-[9px] font-black text-white">

                                                        Guardar resultado
                                                    </button>
                                                </div>

                                                <p x-show="match.winner_id"
                                                    class="mt-2 text-[9px] font-bold text-emerald-600">

                                                    Ganador:
                                                    <span
                                                        x-text="participantName(
                                                match.winner_id
                                            )">
                                                    </span>
                                                </p>
                                            </div>
                                        </template>
                                    </div>
                                </article>
                            </template>
                        </div>

                        <div x-show="standings().length"
                            class="overflow-hidden rounded-2xl border border-slate-200 bg-white">

                            <div class="border-b border-slate-200 p-4">
                                <h3 class="font-black text-slate-950">
                                    Clasificación
                                </h3>
                            </div>

                            <div class="overflow-x-auto">

                                <table class="w-full text-left text-xs">

                                    <thead class="bg-slate-50 text-[8px] font-black uppercase text-slate-400">

                                        <tr>
                                            <th class="p-3">#</th>
                                            <th class="p-3">Participante</th>
                                            <th class="p-3">PJ</th>
                                            <th class="p-3">G</th>
                                            <th class="p-3">E</th>
                                            <th class="p-3">P</th>
                                            <th class="p-3">Pts.</th>
                                            <th class="p-3">Dif.</th>
                                        </tr>
                                    </thead>

                                    <tbody>

                                        <template x-for="row in standings()" :key="row.participant_id">

                                            <tr class="border-t border-slate-100">

                                                <td class="p-3 font-black" x-text="row.position">
                                                </td>

                                                <td class="p-3 font-black"
                                                    x-text="participantName(
                                            row.participant_id
                                        )">
                                                </td>

                                                <td class="p-3" x-text="row.played ?? '-'">
                                                </td>

                                                <td class="p-3" x-text="row.wins ?? '-'">
                                                </td>

                                                <td class="p-3" x-text="row.draws ?? '-'">
                                                </td>

                                                <td class="p-3" x-text="row.losses ?? '-'">
                                                </td>

                                                <td class="p-3 font-black text-violet-600" x-text="row.points ?? '-'">
                                                </td>

                                                <td class="p-3" x-text="row.score_difference ?? '-'">
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </template>
            </section>

            <div class="grid items-start gap-5 2xl:grid-cols-[300px_minmax(0,1fr)_350px]">

                {{-- PARTICIPANTES --}}

                <aside class="max-h-[720px] overflow-y-auto rounded-3xl border border-slate-200 bg-white p-4">

                    <p class="text-[10px] font-black uppercase tracking-[0.14em] text-violet-600">
                        Competidores
                    </p>

                    <div class="mt-4 space-y-2">
                        <template x-for="participant in participants()" :key="participant.lab_id">

                            <button type="button"
                                @click="selectParticipant(
                                    participant.lab_id
                                )"
                                class="w-full rounded-xl border p-3 text-left transition"
                                :class="selectedParticipantId === participant.lab_id ?
                                    'border-violet-500 bg-violet-50' :
                                    'border-slate-200 hover:bg-slate-50'">

                                <p class="truncate text-xs font-black text-slate-900" x-text="participant.name">
                                </p>

                                <p class="mt-1 text-[9px] text-slate-500">
                                    <span x-text="participant.status">
                                    </span>
                                    ·
                                    <span x-text="participant.current_location.name">
                                    </span>
                                </p>
                            </button>
                        </template>
                    </div>
                </aside>

                {{-- FASES --}}

                <main class="space-y-4">
                    <section class="rounded-3xl border border-slate-200 bg-white p-5">

                        <p class="text-[10px] font-black uppercase tracking-[0.14em] text-emerald-600">
                            Starts
                        </p>

                        <div class="mt-4 grid gap-3 md:grid-cols-2">

                            <template x-for="start in starts()" :key="start.id">

                                <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">

                                    <p class="text-[9px] font-black text-emerald-600" x-text="start.code">
                                    </p>

                                    <p class="mt-1 font-black text-slate-900" x-text="start.name">
                                    </p>

                                    <p class="mt-2 text-xs text-emerald-700">
                                        <span x-text="start.participant_count">
                                        </span>
                                        participantes
                                    </p>
                                </article>
                            </template>
                        </div>
                    </section>

                    <section class="rounded-3xl border border-slate-200 bg-white p-5">

                        <p class="text-[10px] font-black uppercase tracking-[0.14em] text-amber-600">
                            Fases del grafo
                        </p>

                        <div class="mt-4 grid gap-3 md:grid-cols-2">

                            <template x-for="node in nodes()" :key="node.id">

                                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">

                                    <div class="flex items-start justify-between gap-3">

                                        <div>
                                            <p class="text-[9px] font-black text-amber-600" x-text="node.code">
                                            </p>

                                            <p class="mt-1 font-black text-slate-900" x-text="node.name">
                                            </p>
                                        </div>

                                        <span
                                            class="rounded-full bg-slate-200 px-2 py-1 text-[8px] font-black text-slate-600"
                                            x-text="node.status">
                                        </span>
                                    </div>

                                    <p class="mt-2 text-[10px] text-slate-500" x-text="node.phase_type_label">
                                    </p>
                                </article>
                            </template>
                        </div>
                    </section>

                    <section class="rounded-3xl border border-rose-200 bg-rose-50 p-5">

                        <p class="text-[10px] font-black uppercase tracking-[0.14em] text-rose-600">
                            Terminales
                        </p>

                        <div class="mt-4 grid gap-3 md:grid-cols-2">

                            <template x-for="terminal in terminals()" :key="terminal.id">

                                <article class="rounded-2xl border border-rose-200 bg-white p-4">

                                    <p class="text-[9px] font-black text-rose-600" x-text="terminal.code">
                                    </p>

                                    <p class="mt-1 font-black text-slate-900" x-text="terminal.name">
                                    </p>

                                    <p class="mt-2 text-[9px] text-slate-500" x-text="terminal.status">
                                    </p>
                                </article>
                            </template>
                        </div>
                    </section>
                </main>

                {{-- INSPECTOR --}}

                <aside class="sticky top-5 rounded-3xl border border-slate-200 bg-white p-5">

                    <p class="text-[10px] font-black uppercase tracking-[0.14em] text-violet-600">
                        Inspector
                    </p>

                    <template x-if="selectedParticipant()">
                        <div>
                            <h2 class="mt-3 text-lg font-black text-slate-950" x-text="selectedParticipant().name">
                            </h2>

                            <p class="mt-1 text-xs text-slate-500" x-text="selectedParticipant().lab_id">
                            </p>

                            <div class="mt-4 grid grid-cols-2 gap-2">
                                <template
                                    x-for="[label, value] in [
                                        ['Estado', selectedParticipant().status],
                                        ['Seed', selectedParticipant().seed],
                                        ['Partidos', selectedParticipant().statistics.matches],
                                        ['Puntos', selectedParticipant().statistics.points],
                                    ]"
                                    :key="label">

                                    <div class="rounded-xl bg-slate-50 p-3">

                                        <p class="text-[8px] font-black uppercase text-slate-400" x-text="label">
                                        </p>

                                        <p class="mt-1 text-xs font-black text-slate-900" x-text="value">
                                        </p>
                                    </div>
                                </template>
                            </div>

                            <div class="mt-5">
                                <p class="text-[9px] font-black uppercase text-slate-400">
                                    Recorrido
                                </p>

                                <div class="mt-3 space-y-2">
                                    <template
                                        x-for="(
                                            location,
                                            index
                                        ) in selectedParticipant().journey"
                                        :key="`${location.type}-${location.id}-${index}`">

                                        <div class="flex items-start gap-3">

                                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-violet-100 text-[8px] font-black text-violet-700"
                                                x-text="index + 1">
                                            </div>

                                            <div class="rounded-xl bg-slate-50 px-3 py-2">

                                                <p class="text-[8px] font-black uppercase text-slate-400"
                                                    x-text="location.type">
                                                </p>

                                                <p class="mt-1 text-[10px] font-black text-slate-800"
                                                    x-text="location.name">
                                                </p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div x-show="!selectedParticipant()" class="py-10 text-center">

                        <p class="font-black text-slate-800">
                            Selecciona un competidor
                        </p>

                        <p class="mt-2 text-xs leading-6 text-slate-500">
                            Aquí aparecerán su estado, estadísticas y recorrido.
                        </p>
                    </div>
                </aside>
            </div>

            {{-- TIMELINE --}}

            <section class="rounded-3xl border border-slate-200 bg-white p-5">

                <p class="text-[10px] font-black uppercase tracking-[0.14em] text-violet-600">
                    Timeline del Lab
                </p>

                <div class="mt-4 space-y-3">
                    <template x-for="event in state?.timeline ?? []" :key="`${event.step}-${event.type}`">

                        <article class="flex items-start gap-3">

                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-violet-100 text-[9px] font-black text-violet-700"
                                x-text="event.step">
                            </div>

                            <div class="flex-1 rounded-2xl border border-slate-200 bg-slate-50 p-3">

                                <p class="text-[8px] font-black uppercase text-slate-400" x-text="event.type">
                                </p>

                                <p class="mt-1 text-xs text-slate-700" x-text="event.message">
                                </p>
                            </div>
                        </article>
                    </template>
                </div>
            </section>
        </section>
    </div>

</x-tournament-layout>
