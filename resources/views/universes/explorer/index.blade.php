<x-universe-layout :universe="$universe">

    <x-slot name="header">Explorar</x-slot>


    <div>
        <p class="text-xs font-black uppercase tracking-wider text-violet-600">
            {{ $universe->name }} · Explorar
        </p>

        <h2 class="mt-2 text-3xl font-black text-slate-900">Explorar el Universo</h2>

        <p class="mt-2 max-w-2xl text-slate-500">
            Recorre a los habitantes de este mundo agrupados como quieras.
            Los atributos salen de lo que se copió al importarlos, no de la Biblioteca.
        </p>
    </div>


    {{-- AGRUPACIÓN --}}

    <form method="GET" class="mt-6 rounded-2xl border border-slate-200 bg-white p-4">

        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Agrupar por</p>

        <div class="mt-3 flex flex-wrap gap-2">

            <button name="group_by" value="TYPE"
                class="rounded-xl px-4 py-2 text-xs font-black transition
                    {{ $groupBy === 'TYPE'
                        ? 'bg-violet-600 text-white shadow-lg shadow-violet-600/20'
                        : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                Tipo
            </button>

            @foreach ($attributeNames as $name)
                <button name="group_by" value="{{ $name }}"
                    class="rounded-xl px-4 py-2 text-xs font-black transition
                        {{ $groupBy === $name
                            ? 'bg-violet-600 text-white shadow-lg shadow-violet-600/20'
                            : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                    {{ $name }}
                </button>
            @endforeach

        </div>

    </form>


    @if ($entities->isEmpty())

        <div class="mt-8 rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center">

            <div class="text-5xl">🧭</div>

            <h3 class="mt-4 text-xl font-black text-slate-900">Este mundo todavía está vacío</h3>

            <p class="mx-auto mt-2 max-w-lg text-sm text-slate-500">
                Importa entidades desde tu Biblioteca para empezar a poblarlo.
            </p>

            @can('update', $universe)
                <a href="{{ route('universes.entities.create', $universe) }}"
                    class="mt-5 inline-flex rounded-xl bg-violet-600 px-5 py-3 text-sm font-black text-white">
                    + Agregar desde Biblioteca
                </a>
            @endcan

        </div>
    @else

        <div class="mt-6 space-y-5">

            @foreach ($groups as $groupName => $members)
                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white">

                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 bg-slate-50/60 px-6 py-4">

                        <div class="flex items-center gap-3">

                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-600 text-sm font-black text-white">
                                {{ $members->count() }}
                            </span>

                            <div>
                                <p class="text-sm font-black text-slate-900">{{ $groupName }}</p>

                                <p class="mt-0.5 text-[10px] text-slate-400">
                                    {{ $groupBy === 'TYPE' ? 'Tipo de entidad' : $groupBy }}
                                </p>
                            </div>

                        </div>

                    </div>


                    <div class="grid gap-3 p-5 sm:grid-cols-3 lg:grid-cols-5 xl:grid-cols-6">

                        @foreach ($members as $member)
                            <a href="{{ route('universes.entities.show', [$universe, $member]) }}"
                                class="group overflow-hidden rounded-2xl border border-slate-200 transition hover:-translate-y-1 hover:border-violet-300 hover:shadow-lg hover:shadow-violet-950/5">

                                <div class="aspect-square bg-slate-100">

                                    @if ($member->image_url)
                                        <img src="{{ $member->image_url }}" alt="{{ $member->display_label }}"
                                            class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                    @else
                                        <div class="flex h-full items-center justify-center text-4xl text-violet-300">✦</div>
                                    @endif

                                </div>

                                <div class="p-2.5">

                                    <p class="truncate text-[11px] font-black text-slate-800">
                                        {{ $member->display_label }}
                                    </p>

                                    @php
                                        $featured = collect($member->attribute_snapshot ?? [])
                                            ->firstWhere('featured', true);
                                    @endphp

                                    @if ($featured)
                                        <p class="mt-0.5 truncate text-[9px] font-bold text-violet-500">
                                            {{ $featured['name'] }} {{ $featured['display'] }}
                                        </p>
                                    @elseif ($member->entity_type_name)
                                        <p class="mt-0.5 truncate text-[9px] text-slate-400">
                                            {{ $member->entity_type_name }}
                                        </p>
                                    @endif

                                </div>

                            </a>
                        @endforeach

                    </div>

                </section>
            @endforeach

        </div>
    @endif


    {{-- Honestidad sobre el alcance --}}

    <section class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">

        <p class="text-xs leading-6 text-slate-500">
            Esta es la base del explorador: agrupa y muestra. La visualización
            avanzada del mundo —relaciones, mapas, recorridos— llegará más
            adelante y se construirá sobre esta misma pantalla.
        </p>

    </section>

</x-universe-layout>
