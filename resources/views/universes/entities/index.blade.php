<x-universe-layout :universe="$universe">

    <x-slot name="header">
        Entidades
    </x-slot>


    <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-start">

        <div>

            <p class="text-xs font-black uppercase tracking-wider text-violet-600">
                {{ $universe->name }} · Entidades
            </p>

            <h2 class="mt-2 text-3xl font-black text-slate-900">
                Entidades del Universo
            </h2>

            <p class="mt-2 max-w-2xl text-slate-500">
                Cada una es una <strong>copia independiente</strong> de una
                entidad de tu Biblioteca. Su historial y sus estadísticas
                pertenecen a este Universo: editarlas en la Biblioteca ya no
                las afecta.
            </p>

        </div>


        @can('update', $universe)
            <a href="{{ route('universes.entities.create', $universe) }}"
                class="shrink-0 rounded-xl bg-violet-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-violet-600/20 transition hover:bg-violet-700">
                + Agregar desde Biblioteca
            </a>
        @endcan

    </div>


    {{-- CIFRAS --}}

    <div class="mt-7 grid grid-cols-3 gap-3">

        @foreach ([['Total', $statistics['total']], ['Activas', $statistics['active']], ['Retiradas', $statistics['retired']]] as [$label, $value])
            <article class="rounded-2xl border border-slate-200 bg-white p-4">

                <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                    {{ $label }}
                </p>

                <p class="mt-2 text-2xl font-black text-slate-900">
                    {{ $value }}
                </p>

            </article>
        @endforeach

    </div>


    {{-- FILTROS --}}

    <form method="GET" class="mt-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 md:grid-cols-3">

        <input type="search" name="search" value="{{ $search }}" placeholder="Buscar entidad..."
            class="rounded-xl border-slate-300 text-sm text-slate-900 placeholder:text-slate-400 focus:border-violet-400 focus:ring-violet-400">

        <select name="status"
            class="rounded-xl border-slate-300 bg-white text-sm text-slate-900 focus:border-violet-400 focus:ring-violet-400">

            <option value="">Todo estado</option>

            @foreach (\App\Models\UniverseEntity::statuses() as $value => $label)
                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
            @endforeach

        </select>

        <button class="rounded-xl bg-slate-950 px-4 py-3 text-sm font-black text-white">
            Aplicar
        </button>

    </form>


    @if ($universeEntities->isEmpty())

        <div class="mt-8 rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center">

            <div class="text-5xl">✦</div>

            <h3 class="mt-4 text-xl font-black text-slate-900">
                Este Universo todavía no tiene entidades
            </h3>

            <p class="mx-auto mt-2 max-w-lg text-sm text-slate-500">
                Impórtalas desde tu Biblioteca. Se copiarán aquí con sus
                atributos y versiones, y a partir de ese momento serán
                independientes.
            </p>

            @can('update', $universe)
                <a href="{{ route('universes.entities.create', $universe) }}"
                    class="mt-5 inline-flex rounded-xl bg-violet-600 px-5 py-3 text-sm font-black text-white">
                    + Agregar desde Biblioteca
                </a>
            @endcan

        </div>
    @else

        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

            @foreach ($universeEntities as $universeEntity)
                @php
                    $record = $records->get($universeEntity->id);
                @endphp

                <a href="{{ route('universes.entities.show', [$universe, $universeEntity]) }}"
                    class="group overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:-translate-y-1 hover:border-violet-300 hover:shadow-lg hover:shadow-violet-950/5">

                    <div class="flex items-center gap-3 p-4">

                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-violet-100 text-2xl text-violet-500">

                            @if ($universeEntity->image_url)
                                <img src="{{ $universeEntity->image_url }}"
                                    alt="{{ $universeEntity->display_label }}"
                                    class="h-full w-full object-cover">
                            @else
                                ✦
                            @endif

                        </div>


                        <div class="min-w-0 flex-1">

                            <p class="truncate text-sm font-black text-slate-900">
                                {{ $universeEntity->display_label }}
                            </p>

                            <p class="mt-0.5 truncate text-[10px] text-slate-400">
                                {{ $universeEntity->entity_type_name ?: 'Sin tipo' }}
                            </p>

                            <span
                                class="mt-1.5 inline-flex rounded-full px-2 py-0.5 text-[9px] font-black uppercase
                                    {{ $universeEntity->status === 'ACTIVE'
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : ($universeEntity->status === 'RETIRED'
                                            ? 'bg-slate-200 text-slate-600'
                                            : 'bg-violet-100 text-violet-700') }}">
                                {{ $universeEntity->status_label }}
                            </span>

                        </div>

                    </div>


                    {{-- Vida competitiva DENTRO de este Universo --}}

                    <div class="grid grid-cols-3 gap-px border-t border-slate-100 bg-slate-100">

                        @foreach ([['Torneos', $record->tournaments ?? 0], ['Victorias', $record->wins ?? 0], ['Títulos', $record->titles ?? 0]] as [$label, $value])
                            <div class="bg-white px-2 py-3 text-center">

                                <p class="text-[8px] font-black uppercase tracking-wider text-slate-400">
                                    {{ $label }}
                                </p>

                                <p class="mt-1 text-sm font-black {{ $label === 'Títulos' && $value > 0 ? 'text-violet-600' : 'text-slate-800' }}">
                                    {{ $value }}
                                </p>

                            </div>
                        @endforeach

                    </div>

                </a>
            @endforeach

        </div>


        <div class="mt-8">
            {{ $universeEntities->links() }}
        </div>
    @endif

</x-universe-layout>
