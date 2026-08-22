<x-universe-layout :universe="$universe">

    <x-slot name="header">Temporada {{ $season->number }}</x-slot>


    <div class="mb-5">
        <a href="{{ route('universes.seasons.index', $universe) }}"
            class="text-xs font-black text-slate-400 hover:text-violet-600">
            ← Temporadas
        </a>
    </div>


    {{-- CABECERA --}}

    <section
        class="overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-950 via-indigo-950 to-violet-950 p-7">

        <div class="flex flex-wrap items-center gap-2">

            <span
                class="rounded-full px-3 py-1 text-[9px] font-black uppercase
                    {{ match ($season->status) {
                        'ACTIVE' => 'bg-violet-500 text-white',
                        'PLANNED' => 'bg-amber-400 text-amber-950',
                        'COMPLETED' => 'bg-white text-slate-900',
                        default => 'bg-white/20 text-white',
                    } }}">
                {{ $season->status_label }}
            </span>

            <span class="rounded-full bg-white/10 px-3 py-1 text-[9px] font-black uppercase text-slate-300">
                {{ $season->period_label }}
            </span>

        </div>


        <p class="mt-5 text-[10px] font-black uppercase tracking-[0.22em] text-violet-300">
            Temporada {{ $season->number }}
        </p>

        <h1 class="mt-2 text-3xl font-black tracking-tight text-white">
            {{ $season->name }}
        </h1>

        @if ($season->description)
            <p class="mt-3 max-w-3xl whitespace-pre-line text-sm leading-7 text-slate-300">
                {{ $season->description }}
            </p>
        @endif


        <div class="mt-6 flex flex-wrap gap-2">

            @foreach ([
        ['Competiciones', $statistics['competitions']],
        ['Finalizadas', $statistics['completed']],
        ['Participaciones', $statistics['participants']],
    ] as [$label, $value])
                <div class="rounded-2xl bg-white/10 px-4 py-2.5 backdrop-blur">
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">{{ $label }}</p>
                    <p class="mt-0.5 text-xl font-black text-white">{{ $value }}</p>
                </div>
            @endforeach

        </div>

    </section>


    <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">

        {{-- COMPETICIONES --}}

        <section class="rounded-3xl border border-slate-200 bg-white p-6">

            <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-600">Lo que se jugó</p>
            <h2 class="mt-2 text-2xl font-black text-slate-900">⚔ Competiciones</h2>


            @if ($competitions->isEmpty())
                <p class="mt-5 rounded-2xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400">
                    Todavía no se ha jugado nada en esta temporada.
                </p>
            @else
                <div class="mt-5 space-y-3">

                    @foreach ($competitions as $competition)
                        @php $champion = $champions->get($competition->id); @endphp

                        <a href="{{ route('universes.competitions.show', [$universe, $competition]) }}"
                            class="group flex items-center gap-4 rounded-2xl bg-slate-50 p-4 transition hover:bg-violet-50">

                            <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-violet-100 text-2xl text-violet-400 ring-2 ring-violet-500/20">
                                @if ($champion?->universeEntity?->image_url)
                                    <img src="{{ $champion->universeEntity->image_url }}"
                                        alt="{{ $champion->name }}" class="h-full w-full object-cover">
                                @else
                                    ⚔
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">

                                <p class="truncate text-sm font-black text-slate-900">{{ $competition->name }}</p>

                                <p class="mt-0.5 truncate text-[10px] text-slate-400">
                                    {{ $competition->universeTournament?->name }}
                                    · {{ $competition->participant_count }} participantes
                                </p>

                                @if ($champion)
                                    <p class="mt-1 truncate text-[11px] font-black text-violet-600">
                                        🏆 {{ $champion->name }}
                                    </p>
                                @endif

                            </div>

                            <span
                                class="shrink-0 rounded-full px-2.5 py-1 text-[9px] font-black uppercase
                                    {{ match ($competition->status) {
                                        'COMPLETED' => 'bg-slate-900 text-white',
                                        'RUNNING' => 'bg-emerald-100 text-emerald-700',
                                        'PAUSED' => 'bg-amber-100 text-amber-700',
                                        'DRAFT' => 'bg-violet-100 text-violet-700',
                                        default => 'bg-red-100 text-red-700',
                                    } }}">
                                {{ $competition->status_label }}
                            </span>

                        </a>
                    @endforeach

                </div>
            @endif

        </section>


        {{-- PROGRAMADO --}}

        <section class="rounded-3xl border border-slate-200 bg-white p-6">

            <p class="text-xs font-black uppercase tracking-[0.18em] text-violet-600">Calendario</p>
            <h2 class="mt-2 text-xl font-black text-slate-900">Toca en esta temporada</h2>

            <p class="mt-2 text-xs text-slate-500">
                Según la recurrencia configurada en cada torneo.
            </p>


            @if ($scheduled->isEmpty())
                <p class="mt-4 rounded-2xl border border-dashed border-slate-200 p-6 text-center text-xs text-slate-400">
                    Ningún torneo tiene recurrencia que caiga aquí.
                </p>
            @else
                <div class="mt-4 space-y-2">

                    @foreach ($scheduled as $tournament)
                        <a href="{{ route('universes.tournaments.show', [$universe, $tournament]) }}"
                            class="group flex items-center gap-3 rounded-2xl bg-slate-50 p-3 transition hover:bg-violet-50">

                            <span class="text-lg">🏆</span>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-black text-slate-800">{{ $tournament->name }}</p>
                                <p class="mt-0.5 truncate text-[10px] text-slate-400">
                                    {{ $tournament->recurrence_label }}
                                </p>
                            </div>

                            <span class="text-violet-500 transition group-hover:translate-x-0.5">→</span>

                        </a>
                    @endforeach

                </div>
            @endif


            @can('update', $universe)
                <a href="{{ route('universes.seasons.edit', [$universe, $season]) }}"
                    class="mt-5 block rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-center text-xs font-black text-slate-700">
                    Editar temporada
                </a>
            @endcan

        </section>

    </div>

</x-universe-layout>
