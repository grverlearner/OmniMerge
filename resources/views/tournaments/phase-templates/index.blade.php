<x-tournament-layout>

    <x-slot name="header">
        Fases
    </x-slot>

    <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-start">

        <div>
            <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-600">Tournament Designer · Phase Library
            </p>

            <h2 class="mt-2 text-3xl font-black tracking-tight text-slate-900">
                Fases
            </h2>

            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                Construye mecanismos competitivos reutilizables.
                Una Fase define qué ocurre internamente y qué grupos
                de competidores salen por cada puerta, pero no decide
                todavía hacia dónde continúa cada salida.
            </p>
        </div>

        <a href="{{ route('tournaments.phase-templates.create') }}"
            class="rounded-xl bg-amber-500 px-5 py-3 text-center text-sm font-black text-white shadow-lg shadow-amber-500/20 transition hover:bg-amber-600">
            + Nueva Fase
        </a>

    </div>

    {{-- STATS --}}

    <section class="mt-7 grid grid-cols-2 gap-3 lg:grid-cols-4">

        @foreach ([['label' => 'Fases', 'value' => $stats['total'], 'icon' => '⌘'], ['label' => 'Activas', 'value' => $stats['active'], 'icon' => '●'], ['label' => 'Públicas', 'value' => $stats['public'], 'icon' => '◎'], ['label' => 'Con salidas', 'value' => $stats['with_exits'], 'icon' => '→']] as $stat)
            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                            {{ $stat['label'] }}
                        </p>

                        <p class="mt-2 text-3xl font-black text-slate-900">
                            {{ number_format($stat['value']) }}
                        </p>
                    </div>

                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-lg font-black text-amber-700">
                        {{ $stat['icon'] }}
                    </div>
                </div>
            </article>
        @endforeach
    </section>

    {{-- FILTERS --}}

    <form method="GET"
        class="mt-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 md:grid-cols-2 xl:grid-cols-6">

        <input type="search" name="search" value="{{ $search }}" placeholder="Buscar Fase..."
            class="rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400 xl:col-span-2">

        <select name="type" class="rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400">
            <option value="">Todos los tipos</option>

            @foreach ([
        'SINGLE_ELIMINATION' => 'Eliminación directa',
        'ROUND_ROBIN' => 'Todos contra todos',
        'GROUP_STAGE' => 'Grupos',
        'LEAGUE' => 'Liga / División',
        'SWISS' => 'Suizo',
        'CUSTOM' => 'Personalizada',
    ] as $value => $label)
                <option value="{{ $value }}" @selected($type === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>

        <select name="status" class="rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400">
            <option value="">Todo estado</option>
            <option value="DRAFT" @selected($status === 'DRAFT')>Borrador</option>
            <option value="ACTIVE" @selected($status === 'ACTIVE')>Activa</option>
            <option value="ARCHIVED" @selected($status === 'ARCHIVED')>Archivada</option>
        </select>

        <select name="sort" class="rounded-xl border-slate-300 text-sm focus:border-amber-400 focus:ring-amber-400">
            <option value="newest" @selected($sort === 'newest')>Más recientes</option>
            <option value="oldest" @selected($sort === 'oldest')>Más antiguas</option>
            <option value="name_asc" @selected($sort === 'name_asc')>Nombre A → Z</option>
            <option value="name_desc" @selected($sort === 'name_desc')>Nombre Z → A</option>
            <option value="exits_desc" @selected($sort === 'exits_desc')>Más salidas</option>
        </select>

        <button class="rounded-xl bg-slate-950 px-4 py-3 text-sm font-black text-white">
            Aplicar
        </button>

    </form>

    @if ($phaseTemplates->isEmpty())

        <div class="mt-8 rounded-3xl border border-dashed border-amber-300 bg-white p-12 text-center">

            <div class="text-5xl">⌘</div>

            <h3 class="mt-4 text-xl font-black text-slate-900">
                Todavía no tienes Fases
            </h3>

            <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-500">
                Empieza creando una Fase de eliminación directa.
                Después podrás reutilizarla en diferentes Torneos.
            </p>

            <a href="{{ route('tournaments.phase-templates.create') }}"
                class="mt-6 inline-flex rounded-xl bg-amber-500 px-5 py-3 text-sm font-black text-white">
                Crear primera Fase
            </a>

        </div>
    @else
        <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($phaseTemplates as $phaseTemplate)
                @include('tournaments.phase-templates.partials.card', [
                    'phaseTemplate' => $phaseTemplate,
                ])
            @endforeach
        </div>

        <div class="mt-8">
            {{ $phaseTemplates->links() }}
        </div>

    @endif

</x-tournament-layout>
