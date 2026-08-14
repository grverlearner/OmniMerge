<x-tournament-layout>

    <x-slot name="header">
        Flow Preview · {{ $tournamentTemplate->name }}
    </x-slot>

    @include('tournaments.partials.template-navigation')

    @if ($errors->any())
        <section class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-5">

            <p class="font-black text-red-900">
                No fue posible ejecutar el Preview
            </p>

            <div class="mt-2 space-y-1">
                @foreach ($errors->all() as $error)
                    <p class="text-xs leading-5 text-red-700">
                        • {{ $error }}
                    </p>
                @endforeach
            </div>
        </section>
    @endif

    <section
        class="relative overflow-hidden rounded-[30px] bg-gradient-to-br from-slate-950 via-slate-900 to-violet-950 p-7 text-white shadow-xl">

        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-violet-500/20 blur-3xl">
        </div>

        <div class="relative flex flex-col justify-between gap-6 xl:flex-row xl:items-end">

            <div class="max-w-3xl">
                <div
                    class="inline-flex rounded-full border border-violet-300/20 bg-violet-400/10 px-4 py-2 text-[10px] font-black uppercase tracking-[0.18em] text-violet-300">
                    ▶ Tournament Flow Preview
                </div>

                <h1 class="mt-5 text-3xl font-black tracking-tight">
                    {{ $tournamentTemplate->name }}
                </h1>

                <p class="mt-3 text-sm leading-7 text-slate-300">
                    Carga participantes temporales y comprueba cómo avanzan
                    por todos los inicios, fases, bifurcaciones, convergencias
                    y destinos finales. Esta ejecución no guarda resultados.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <span class="rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-xs font-black text-white">
                    {{ $tournamentTemplate->code }}
                </span>

                <a href="{{ route('tournaments.graph.show', $tournamentTemplate) }}"
                    class="rounded-xl bg-amber-500 px-4 py-3 text-xs font-black text-white">
                    ← Volver al Builder
                </a>
            </div>
        </div>
    </section>

    <section class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-5">

        @foreach ([['Inicios', $flowAnalysis['stats']['starts']], ['Fases', $flowAnalysis['stats']['nodes']], ['Rutas', $flowAnalysis['stats']['connections']], ['Finales', $flowAnalysis['stats']['terminals']], ['Estado', $canPreview ? 'Listo' : 'Bloqueado']] as [$label, $value])
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">

                <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                    {{ $label }}
                </p>

                <p
                    class="mt-2 text-lg font-black
                    {{ $label === 'Estado' ? ($canPreview ? 'text-emerald-600' : 'text-red-600') : 'text-slate-900' }}">

                    {{ $value }}
                </p>
            </article>
        @endforeach
    </section>

    @if (!$canPreview)
        <section class="mt-5 rounded-3xl border border-red-200 bg-red-50 p-6">

            <div class="flex items-start gap-4">
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-red-100 font-black text-red-600">
                    !
                </div>

                <div>
                    <p class="font-black text-red-950">
                        El camino todavía no puede ejecutarse
                    </p>

                    <p class="mt-2 text-xs leading-6 text-red-700">
                        Corrige los problemas bloqueantes desde el Flow Builder.
                    </p>

                    <div class="mt-4 space-y-2">
                        @foreach (array_merge($graphValidation['errors'], $flowValidation['errors']) as $problem)
                            <p class="rounded-xl border border-red-200 bg-white px-4 py-3 text-xs text-red-800">
                                {{ $problem['message'] }}
                            </p>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    <div class="mt-6 grid items-start gap-5 xl:grid-cols-[360px_minmax(0,1fr)]">

        <aside class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-[10px] font-black uppercase tracking-[0.16em] text-violet-600">
                Configuración temporal
            </p>

            <h2 class="mt-2 font-black text-slate-950">
                Participantes del Preview
            </h2>

            <p class="mt-2 text-xs leading-6 text-slate-500">
                Configura la cantidad que comenzará en cada origen.
                El máximo total es de 512.
            </p>

            <form method="POST" action="{{ route('tournaments.graph.preview.run', $tournamentTemplate) }}"
                class="mt-5 space-y-5">

                @csrf

                <input type="hidden" name="participant_mode" value="GENERATED">

                <div>
                    <label class="text-[9px] font-black uppercase text-slate-500">
                        Estrategia provisional
                    </label>

                    <select name="resolution_strategy" class="mt-2 w-full rounded-xl border-slate-200 text-sm">

                        <option value="ORDERED" @selected(old('resolution_strategy', $preview['configuration']['resolution_strategy'] ?? 'ORDERED') === 'ORDERED')>
                            Orden original
                        </option>

                        <option value="SEEDED_RANDOM" @selected(old('resolution_strategy', $preview['configuration']['resolution_strategy'] ?? '') === 'SEEDED_RANDOM')>
                            Aleatorio reproducible
                        </option>
                    </select>
                </div>

                <div>
                    <label class="text-[9px] font-black uppercase text-slate-500">
                        Semilla
                    </label>

                    <input type="number" name="seed" min="1" max="2147483647"
                        value="{{ old('seed', $preview['configuration']['seed'] ?? random_int(1, 999999999)) }}"
                        class="mt-2 w-full rounded-xl border-slate-200 text-sm">
                </div>

                <div class="space-y-3">
                    @foreach ($tournamentTemplate->graphStarts as $index => $start)
                        <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">

                            <input type="hidden" name="starts[{{ $index }}][start_id]"
                                value="{{ $start->id }}">

                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-[9px] font-black uppercase text-emerald-600">
                                        {{ $start->code }}
                                    </p>

                                    <p class="mt-1 text-xs font-black text-slate-900">
                                        {{ $start->name }}
                                    </p>
                                </div>

                                <span class="rounded-full bg-white px-2.5 py-1 text-[9px] font-black text-emerald-700">
                                    Esperados:
                                    {{ $start->expected_participants ?? '?' }}
                                </span>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-[8px] font-black uppercase text-emerald-700">
                                        Cantidad
                                    </label>

                                    <input type="number" name="starts[{{ $index }}][count]" min="1"
                                        max="512" required
                                        value="{{ old("starts.$index.count", $start->expected_participants ?? 8) }}"
                                        class="mt-1 w-full rounded-xl border-emerald-200 bg-white text-sm">
                                </div>

                                <div>
                                    <label class="text-[8px] font-black uppercase text-emerald-700">
                                        Prefijo
                                    </label>

                                    <input name="starts[{{ $index }}][prefix]" maxlength="30"
                                        value="{{ old("starts.$index.prefix", $start->code) }}"
                                        class="mt-1 w-full rounded-xl border-emerald-200 bg-white text-sm">
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <button type="submit" @disabled(!$canPreview)
                    class="w-full rounded-xl bg-violet-600 px-4 py-3 text-xs font-black text-white transition hover:bg-violet-700 disabled:cursor-not-allowed disabled:opacity-40">
                    ▶ Ejecutar Preview
                </button>

                <p class="text-center text-[9px] leading-5 text-slate-400">
                    La ejecución se calculará en memoria y no creará
                    TournamentInstance, partidos ni resultados.
                </p>
            </form>
        </aside>

        <main class="min-w-0 space-y-5">

            @if ($preview)
                <section class="grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-8">

                    @foreach ([['Iniciales', $preview['summary']['initial_unique']], ['En finales', $preview['summary']['terminal_unique']], ['Apariciones', $preview['summary']['terminal_appearances']], ['Detenidos', $preview['summary']['stopped_unique']], ['Perdidos', $preview['summary']['lost_unique']], ['Duplicados', $preview['summary']['duplicated_unique']], ['Fases', $preview['summary']['nodes_processed']], ['Bloqueadas', $preview['summary']['nodes_blocked']]] as [$label, $value])
                        <article class="rounded-2xl border border-slate-200 bg-white p-4">

                            <p class="text-[8px] font-black uppercase text-slate-400">
                                {{ $label }}
                            </p>

                            <p
                                class="mt-2 text-lg font-black
                                {{ in_array($label, ['Perdidos', 'Bloqueadas']) && $value > 0 ? 'text-red-600' : 'text-slate-900' }}">
                                {{ $value }}
                            </p>
                        </article>
                    @endforeach
                </section>

                @if ($preview['errors'] !== [] || $preview['warnings'] !== [])
                    <section class="grid gap-4 lg:grid-cols-2">
                        <article class="rounded-3xl border border-red-200 bg-red-50 p-5">

                            <p class="text-[10px] font-black uppercase text-red-600">
                                Errores
                            </p>

                            <div class="mt-3 space-y-2">
                                @forelse ($preview['errors'] as $problem)
                                    <p class="rounded-xl bg-white px-4 py-3 text-xs text-red-800">
                                        {{ $problem['message'] }}
                                    </p>
                                @empty
                                    <p class="text-xs text-red-700">
                                        No se detectaron errores.
                                    </p>
                                @endforelse
                            </div>
                        </article>

                        <article class="rounded-3xl border border-amber-200 bg-amber-50 p-5">

                            <p class="text-[10px] font-black uppercase text-amber-600">
                                Advertencias
                            </p>

                            <div class="mt-3 max-h-64 space-y-2 overflow-y-auto">
                                @forelse ($preview['warnings'] as $problem)
                                    <p class="rounded-xl bg-white px-4 py-3 text-xs text-amber-900">
                                        {{ $problem['message'] }}
                                    </p>
                                @empty
                                    <p class="text-xs text-amber-700">
                                        No se detectaron advertencias.
                                    </p>
                                @endforelse
                            </div>
                        </article>
                    </section>
                @endif

                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

                    <header class="border-b border-slate-200 p-5">
                        <p class="text-[10px] font-black uppercase tracking-[0.16em] text-amber-600">
                            Ejecución por fases
                        </p>

                        <h2 class="mt-1 font-black text-slate-950">
                            Flujo procesado
                        </h2>
                    </header>

                    <div class="grid gap-4 p-5 lg:grid-cols-2">
                        @foreach ($preview['nodes'] as $nodeId => $state)
                            <article
                                class="rounded-2xl border p-4
                                {{ $state['status'] === 'PROCESSED' ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50' }}">

                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p
                                            class="text-[9px] font-black uppercase
                                            {{ $state['status'] === 'PROCESSED' ? 'text-emerald-600' : 'text-red-600' }}">
                                            {{ $state['status'] === 'PROCESSED' ? 'Procesada' : 'Bloqueada' }}
                                        </p>

                                        <h3 class="mt-1 font-black text-slate-950">
                                            {{ $state['name'] }}
                                        </h3>
                                    </div>

                                    <span class="rounded-full bg-white px-3 py-1 text-[9px] font-black text-slate-600">
                                        {{ $state['received'] }} recibidos
                                    </span>
                                </div>

                                @if ($state['status'] === 'PROCESSED')
                                    <div class="mt-4 grid grid-cols-2 gap-2">
                                        <div class="rounded-xl bg-white p-3">
                                            <p class="text-[8px] font-black uppercase text-slate-400">
                                                Recibidos
                                            </p>

                                            <p class="mt-1 font-black text-slate-900">
                                                {{ $state['received'] }}
                                            </p>
                                        </div>

                                        <div class="rounded-xl bg-white p-3">
                                            <p class="text-[8px] font-black uppercase text-slate-400">
                                                Envíos
                                            </p>

                                            <p class="mt-1 font-black text-slate-900">
                                                {{ $state['sent'] }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-3 space-y-2">
                                        @foreach ($state['exit_assignments'] as $assignment)
                                            <details class="rounded-xl border border-violet-200 bg-white p-3">

                                                <summary class="cursor-pointer text-[10px] font-black text-violet-700">
                                                    {{ $assignment['exit_name'] }}
                                                    · {{ $assignment['count'] }}
                                                    {{ $assignment['provisional'] ? '· Provisional' : '' }}
                                                </summary>

                                                <div class="mt-3 flex flex-wrap gap-1.5">
                                                    @forelse ($assignment['participants'] as $participant)
                                                        <span
                                                            class="rounded-full bg-violet-50 px-2.5 py-1 text-[9px] font-bold text-violet-700">
                                                            {{ $participant['name'] }}
                                                        </span>
                                                    @empty
                                                        <span class="text-[9px] text-slate-400">
                                                            Sin participantes
                                                        </span>
                                                    @endforelse
                                                </div>
                                            </details>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="mt-3 text-xs leading-6 text-red-700">
                                        {{ $state['reason'] }}
                                    </p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </section>

                <section class="overflow-hidden rounded-3xl border border-rose-200 bg-white shadow-sm">

                    <header class="border-b border-rose-100 bg-rose-50 p-5">

                        <p class="text-[10px] font-black uppercase tracking-[0.16em] text-rose-600">
                            Resultados temporales
                        </p>

                        <h2 class="mt-1 font-black text-slate-950">
                            Destinos finales
                        </h2>
                    </header>

                    <div class="grid gap-4 p-5 lg:grid-cols-2">
                        @foreach ($preview['terminals'] as $terminal)
                            <article
                                class="rounded-2xl border p-4
                                {{ $terminal['status'] === 'COMPLETE'
                                    ? 'border-emerald-200 bg-emerald-50'
                                    : ($terminal['status'] === 'EMPTY'
                                        ? 'border-slate-200 bg-slate-50'
                                        : 'border-amber-200 bg-amber-50') }}">

                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-[9px] font-black uppercase text-rose-600">
                                            {{ $terminal['type'] }}
                                        </p>

                                        <h3 class="mt-1 font-black text-slate-950">
                                            {{ $terminal['name'] }}
                                        </h3>
                                    </div>

                                    <span class="rounded-full bg-white px-3 py-1 text-[9px] font-black text-slate-600">
                                        {{ $terminal['count'] }}
                                        /
                                        {{ $terminal['expected'] ?? '?' }}
                                    </span>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    @forelse ($terminal['participants'] as $participant)
                                        <details class="rounded-xl border border-rose-200 bg-white px-3 py-2">

                                            <summary class="cursor-pointer text-[9px] font-black text-rose-700">
                                                {{ $participant['name'] }}
                                            </summary>

                                            <div class="mt-2 space-y-1 border-t border-slate-100 pt-2">
                                                @foreach ($participant['journey'] as $location)
                                                    <p class="text-[8px] text-slate-500">
                                                        → {{ $location['name'] }}
                                                    </p>
                                                @endforeach
                                            </div>
                                        </details>
                                    @empty
                                        <p class="text-xs text-slate-400">
                                            Este destino no recibió participantes.
                                        </p>
                                    @endforelse
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

                    <header class="border-b border-slate-200 p-5">
                        <p class="text-[10px] font-black uppercase tracking-[0.16em] text-violet-600">
                            Timeline
                        </p>

                        <h2 class="mt-1 font-black text-slate-950">
                            Cómo se ejecutó el recorrido
                        </h2>
                    </header>

                    <div class="max-h-[600px] space-y-3 overflow-y-auto p-5">
                        @foreach ($preview['timeline'] as $event)
                            <article class="flex items-start gap-3">
                                <div
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-[9px] font-black
                                    {{ $event['level'] === 'SUCCESS'
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : ($event['level'] === 'WARNING'
                                            ? 'bg-amber-100 text-amber-700'
                                            : 'bg-violet-100 text-violet-700') }}">
                                    {{ $event['step'] }}
                                </div>

                                <div class="flex-1 rounded-2xl border border-slate-200 bg-slate-50 p-3">

                                    <p class="text-[8px] font-black uppercase text-slate-400">
                                        {{ $event['type'] }}
                                    </p>

                                    <p class="mt-1 text-xs leading-5 text-slate-700">
                                        {{ $event['message'] }}
                                    </p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @else
                <section class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center">

                    <div
                        class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-violet-100 text-2xl text-violet-600">
                        ▶
                    </div>

                    <h2 class="mt-5 text-xl font-black text-slate-950">
                        El Preview todavía no se ha ejecutado
                    </h2>

                    <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-slate-500">
                        Configura los participantes de cada inicio y ejecuta
                        una prueba temporal para conocer cómo circularán por
                        el torneo.
                    </p>
                </section>
            @endif
        </main>
    </div>

</x-tournament-layout>
