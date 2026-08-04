<x-app-layout>
    <x-slot name="header">
        Tipos de entidad
    </x-slot>

    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h2 class="text-2xl font-black text-slate-900">
                Tipos de entidad
            </h2>

            <p class="mt-2 text-slate-500">
                Organiza tus entidades mediante categorías flexibles.
            </p>
        </div>

        <a
            href="{{ route('entity-types.create') }}"
            class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 hover:bg-indigo-700"
        >
            + Nuevo tipo
        </a>
    </div>

    <form
        method="GET"
        class="mt-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_200px_auto]"
    >
        <input
            name="search"
            value="{{ $search }}"
            placeholder="Buscar por nombre o código..."
            class="rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
        >

        <select
            name="status"
            class="rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
        >
            <option value="">Todos los estados</option>
            <option value="ACTIVE" @selected($status === 'ACTIVE')>
                Activos
            </option>
            <option value="INACTIVE" @selected($status === 'INACTIVE')>
                Inactivos
            </option>
            <option value="ARCHIVED" @selected($status === 'ARCHIVED')>
                Archivados
            </option>
        </select>

        <button class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">
            Buscar
        </button>
    </form>

    <div class="mt-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($entityTypes as $type)
            <article class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                <div class="flex items-start justify-between">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl text-xl"
                        style="
                            background-color: {{ $type->color ?? '#6366F1' }}20;
                            color: {{ $type->color ?? '#6366F1' }};
                        "
                    >
                        {{ $type->icon ?: '◇' }}
                    </div>

                    <x-status-badge :status="$type->status" />
                </div>

                <h3 class="mt-5 text-lg font-bold text-slate-900">
                    {{ $type->name }}
                </h3>

                <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-400">
                    {{ $type->code }}
                </p>

                <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-500">
                    {{ $type->description ?: 'Sin descripción.' }}
                </p>

                <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4">
                    <span class="text-sm text-slate-500">
                        {{ $type->entities_count }}
                        entidad(es)
                    </span>

                    <a
                        href="{{ route('entity-types.show', $type) }}"
                        class="text-sm font-bold text-indigo-600 hover:text-indigo-800"
                    >
                        Abrir →
                    </a>
                </div>
            </article>
        @empty
            <div class="sm:col-span-2 xl:col-span-3 rounded-2xl border border-dashed border-slate-300 bg-white py-16 text-center">
                <p class="font-semibold text-slate-700">
                    No se encontraron tipos de entidad
                </p>

                <a
                    href="{{ route('entity-types.create') }}"
                    class="mt-4 inline-block text-sm font-bold text-indigo-600"
                >
                    Crear el primero
                </a>
            </div>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $entityTypes->links() }}
    </div>
</x-app-layout>