<x-app-layout>
    <x-slot name="header">
        Entidades
    </x-slot>

    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h2 class="text-2xl font-black text-slate-900">
                Mis entidades
            </h2>

            <p class="mt-2 text-slate-500">
                Administra todas las creaciones de tu biblioteca.
            </p>
        </div>

        <a
            href="{{ route('entities.create') }}"
            class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 hover:bg-indigo-700"
        >
            + Nueva entidad
        </a>
    </div>

    <form
        method="GET"
        class="mt-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm lg:grid-cols-[1fr_220px_180px_auto]"
    >
        <input
            name="search"
            value="{{ $search }}"
            placeholder="Buscar entidades..."
            class="rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
        >

        <select
            name="type"
            class="rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
        >
            <option value="">Todos los tipos</option>

            @foreach ($entityTypes as $entityType)
                <option
                    value="{{ $entityType->id }}"
                    @selected($type == $entityType->id)
                >
                    {{ $entityType->name }}
                </option>
            @endforeach
        </select>

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
            Filtrar
        </button>
    </form>

    <div class="mt-6 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($entities as $entity)
            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                <div class="relative aspect-[16/9] bg-gradient-to-br from-indigo-50 to-violet-100">
                    @if ($entity->image_url)
                        <img
                            src="{{ $entity->image_url }}"
                            alt="{{ $entity->name }}"
                            class="h-full w-full object-cover"
                        >
                    @else
                        <div class="flex h-full items-center justify-center text-5xl font-black text-indigo-300">
                            {{ strtoupper(substr($entity->name, 0, 1)) }}
                        </div>
                    @endif

                    <div class="absolute left-4 top-4">
                        <x-status-badge :status="$entity->status" />
                    </div>

                    <div class="absolute right-4 top-4">
                        <x-status-badge :status="$entity->visibility" />
                    </div>
                </div>

                <div class="p-6">
                    <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">
                        {{ $entity->entityType?->name ?? 'Sin tipo' }}
                    </p>

                    <h3 class="mt-2 text-xl font-black text-slate-900">
                        {{ $entity->name }}
                    </h3>

                    <p class="mt-1 text-xs font-semibold text-slate-400">
                        {{ $entity->code }}
                    </p>

                    <p class="mt-4 line-clamp-3 text-sm leading-6 text-slate-500">
                        {{ $entity->description ?: 'Sin descripción.' }}
                    </p>

                    <div class="mt-6 flex items-center justify-between border-t border-slate-100 pt-4">
                        <span class="text-xs text-slate-400">
                            {{ $entity->created_at->format('d/m/Y') }}
                        </span>

                        <a
                            href="{{ route('entities.show', $entity) }}"
                            class="text-sm font-bold text-indigo-600 hover:text-indigo-800"
                        >
                            Abrir →
                        </a>
                    </div>
                </div>
            </article>
        @empty
            <div class="sm:col-span-2 xl:col-span-3 rounded-2xl border border-dashed border-slate-300 bg-white py-20 text-center">
                <p class="font-semibold text-slate-700">
                    No se encontraron entidades
                </p>

                <p class="mt-2 text-sm text-slate-500">
                    Comienza creando un personaje, país, animal,
                    objeto o concepto.
                </p>

                <a
                    href="{{ route('entities.create') }}"
                    class="mt-5 inline-block rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white"
                >
                    Crear primera entidad
                </a>
            </div>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $entities->links() }}
    </div>
</x-app-layout>