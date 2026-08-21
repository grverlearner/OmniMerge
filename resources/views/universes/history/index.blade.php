<x-universe-layout :universe="$universe">

    <x-slot name="header">
        Historial
    </x-slot>


    <div>

        <p class="text-xs font-black uppercase tracking-wider text-violet-600">
            {{ $universe->name }} · Historial
        </p>

        <h2 class="mt-2 text-3xl font-black text-slate-900">
            Historial de competiciones
        </h2>

        <p class="mt-2 max-w-2xl text-slate-500">
            Todo lo que se ha jugado en este Universo. Cada competición
            conserva lo que realmente ocurrió, aunque después cambies la
            plantilla o las Entidades.
        </p>

    </div>


    {{-- CIFRAS --}}

    <div class="mt-7 grid grid-cols-3 gap-3">

        @foreach ([['Competiciones', $statistics['played'], '⚔'], ['Finalizadas', $statistics['completed'], '🏆'], ['Encuentros jugados', $statistics['matches'], '✓']] as [$label, $value, $icon])
            <article class="rounded-2xl border border-slate-200 bg-white p-4">

                <div class="flex items-center justify-between gap-2">

                    <div class="min-w-0">
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                            {{ $label }}
                        </p>

                        <p class="mt-1.5 text-2xl font-black text-slate-900">
                            {{ $value }}
                        </p>
                    </div>

                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                        {{ $icon }}
                    </span>

                </div>

            </article>
        @endforeach

    </div>


    {{-- FILTROS --}}

    <form method="GET"
        class="mt-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 md:grid-cols-4">

        <select name="engine"
            class="rounded-xl border-slate-300 bg-white text-sm text-slate-900 focus:border-violet-400 focus:ring-violet-400">

            <option value="">Todos los formatos</option>

            @foreach ([
        'SINGLE_ELIMINATION' => 'Eliminación directa',
        'ROUND_ROBIN' => 'Todos contra todos',
        'GROUP_STAGE' => 'Fase de grupos',
    ] as $value => $label)
                <option value="{{ $value }}" @selected($engine === $value)>
                    {{ $label }}
                </option>
            @endforeach

        </select>


        <select name="season"
            class="rounded-xl border-slate-300 bg-white text-sm text-slate-900 focus:border-violet-400 focus:ring-violet-400">

            <option value="">Todas las temporadas</option>

            @foreach ($seasons as $season)
                <option value="{{ $season->id }}" @selected($seasonId === $season->id)>
                    Temporada {{ $season->number }} · {{ $season->name }}
                </option>
            @endforeach

        </select>


        <select name="sort"
            class="rounded-xl border-slate-300 bg-white text-sm text-slate-900 focus:border-violet-400 focus:ring-violet-400">

            <option value="newest" @selected($sort === 'newest')>Más recientes</option>
            <option value="oldest" @selected($sort === 'oldest')>Más antiguas</option>

        </select>


        <button class="rounded-xl bg-slate-950 px-4 py-3 text-sm font-black text-white">
            Aplicar
        </button>

    </form>


    @if ($competitions->isEmpty())

        <div class="mt-8 rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center">

            <div class="text-5xl">◷</div>

            <h3 class="mt-4 text-xl font-black text-slate-900">
                Todavía no hay historia que contar
            </h3>

            <p class="mx-auto mt-2 max-w-lg text-sm text-slate-500">
                Cuando juegues una competición aparecerá aquí, con su campeón
                y todo su desarrollo.
            </p>

            <a href="{{ route('universes.competitions.index', $universe) }}"
                class="mt-5 inline-flex rounded-xl bg-violet-600 px-5 py-3 text-sm font-black text-white">
                Ver competiciones
            </a>

        </div>
    @else

        <div class="mt-6 grid gap-4 lg:grid-cols-2">

            @foreach ($competitions as $competition)
                @php
                    $champion = $champions->get($competition->id);
                @endphp

                <a href="{{ route('universes.competitions.show', [$universe, $competition]) }}"
                    class="group overflow-hidden rounded-3xl border border-slate-200 bg-white transition hover:-translate-y-1 hover:border-violet-300 hover:shadow-xl hover:shadow-violet-950/5">

                    {{-- CABECERA CON EL CAMPEÓN --}}

                    <div
                        class="relative flex items-center gap-4 bg-gradient-to-br from-slate-950 via-indigo-950 to-violet-950 p-5">

                        <div
                            class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-white/10 text-3xl text-violet-200 ring-2 ring-white/20">

                            @if ($champion?->universeEntity?->image_url)
                                <img src="{{ $champion->universeEntity->image_url }}"
                                    alt="{{ $champion->name }}"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            @else
                                ✦
                            @endif

                        </div>


                        <div class="min-w-0 flex-1">

                            <p class="text-[9px] font-black uppercase tracking-[0.18em] text-violet-300">
                                {{ $champion ? '🏆 Campeón' : 'Sin campeón todavía' }}
                            </p>

                            <p class="mt-1 truncate text-lg font-black text-white">
                                {{ $champion?->name ?? '—' }}
                            </p>

                            @if ($champion?->entity_type_name)
                                <p class="mt-0.5 truncate text-[10px] text-slate-400">
                                    {{ $champion->entity_type_name }}
                                </p>
                            @endif

                        </div>

                    </div>


                    {{-- DATOS --}}

                    <div class="p-5">

                        <div class="flex flex-wrap items-center gap-2">

                            <span class="rounded-full bg-slate-100 px-2.5 py-1 font-mono text-[9px] font-black text-slate-500">
                                {{ $competition->code }}
                            </span>

                            <span
                                class="rounded-full px-2.5 py-1 text-[9px] font-black uppercase tracking-wider
                                    {{ match ($competition->status) {
                                        'COMPLETED' => 'bg-slate-900 text-white',
                                        'RUNNING' => 'bg-emerald-100 text-emerald-700',
                                        'PAUSED' => 'bg-amber-100 text-amber-700',
                                        default => 'bg-red-100 text-red-700',
                                    } }}">
                                {{ $competition->status_label }}
                            </span>

                            @if ($competition->season)
                                <span class="rounded-full bg-violet-50 px-2.5 py-1 text-[9px] font-black uppercase text-violet-700">
                                    ◷ T{{ $competition->season->number }}
                                </span>
                            @endif

                        </div>


                        <p class="mt-3 text-lg font-black text-slate-900">
                            {{ $competition->name }}
                        </p>

                        <p class="mt-0.5 text-xs text-slate-500">
                            {{ $competition->universeTournament?->name }}
                        </p>


                        <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3">

                            <div class="flex gap-4 text-[11px] font-bold text-slate-500">

                                <span>
                                    <span class="font-black text-slate-800">{{ $competition->participant_count }}</span>
                                    competidores
                                </span>

                                <span>
                                    {{ $competition->started_at?->format('d/m/Y') ?? 'Sin fecha' }}
                                </span>

                            </div>

                            <span class="text-xs font-black text-violet-600 transition group-hover:translate-x-1">
                                Ver →
                            </span>

                        </div>

                    </div>

                </a>
            @endforeach

        </div>


        <div class="mt-8">
            {{ $competitions->links() }}
        </div>
    @endif

</x-universe-layout>
