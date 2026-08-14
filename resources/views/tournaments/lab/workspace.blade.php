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
                        ⚗ Competition Lab · T9.4
                    </div>

                    <h1 class="mt-5 text-3xl font-black">
                        {{ $tournamentTemplate->name }}
                    </h1>

                    <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300">
                        Prueba motores individuales o ejecuta el Tournament Graph
                        completo. Todos los resultados son temporales y no se
                        guardan en la base de datos.
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

        {{-- WORKSPACE REORGANIZADO --}}

        <section x-cloak x-show="state" class="mt-6 space-y-5">

            <div x-show="error" class="rounded-2xl border border-red-200 bg-red-50 p-4">

                <p class="text-xs font-black text-red-800">
                    No fue posible completar la acción
                </p>

                <p class="mt-1 text-xs leading-6 text-red-700" x-text="error">
                </p>
            </div>

            @include('tournaments.lab.partials.mode-selector')

            @include('tournaments.lab.partials.automatic-runtime')

            @include('tournaments.lab.partials.manual-engine')

            <section x-show="state?.timeline?.length" class="rounded-3xl border border-slate-200 bg-white p-5">

                <div class="flex items-center justify-between">

                    <div>

                        <p class="text-[9px] font-black uppercase tracking-[0.14em] text-violet-600">
                            Historial
                        </p>

                        <h3 class="mt-1 font-black text-slate-950">
                            Timeline del Lab
                        </h3>
                    </div>

                    <span class="rounded-full bg-violet-100 px-3 py-1 text-[9px] font-black text-violet-700">

                        <span x-text="state?.timeline?.length ?? 0">
                        </span>
                        eventos
                    </span>
                </div>

                <div class="mt-4 max-h-[420px] space-y-2 overflow-y-auto">

                    <template x-for="event in [...(state?.timeline ?? [])].reverse()"
                        :key="`${event.step}-${event.type}`">

                        <article class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-3">

                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-violet-100 text-[9px] font-black text-violet-700"
                                x-text="event.step">
                            </div>

                            <div>

                                <p class="text-[8px] font-black uppercase text-violet-600" x-text="event.type">
                                </p>

                                <p class="mt-1 text-[10px] leading-5 text-slate-700" x-text="event.message">
                                </p>
                            </div>
                        </article>
                    </template>
                </div>
            </section>

            <div class="flex justify-end">

                <button type="button"
                    @click="
                if (
                    confirm(
                        '¿Cerrar y eliminar el Lab temporal de esta pestaña?'
                    )
                ) {
                    removeLocalState()
                }
            "
                    class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs font-black text-red-600">

                    Cerrar Competition Lab
                </button>
            </div>
        </section>
    </div>

</x-tournament-layout>
