<x-app-layout>
    <x-slot name="header">
        Colecciones
    </x-slot>

    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h2 class="text-2xl font-black text-slate-900">
                Mis colecciones
            </h2>

            <p class="mt-2 text-slate-500">
                Organiza tus entidades en grupos temáticos reutilizables.
            </p>
        </div>

        <a
            href="{{ route('collections.create') }}"
            class="rounded-xl bg-indigo-600 px-5 py-3 text-center text-sm font-bold text-white shadow-lg shadow-indigo-600/20 hover:bg-indigo-700"
        >
            + Nueva colección
        </a>
    </div>

    <form
        method="GET"
        action="{{ route('collections.index') }}"
        class="mt-6 flex gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
    >
        <input
            type="text"
            name="search"
            value="{{ $search }}"
            placeholder="Buscar por nombre o código..."
            class="min-w-0 flex-1 rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
        >

        <button
            type="submit"
            class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800"
        >
            Buscar
        </button>
    </form>

    <div class="mt-6 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($collections as $collection)
            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                <div
                    class="relative aspect-[16/9] bg-gradient-to-br from-indigo-50 to-violet-100"
                    style="
                        background:
                            linear-gradient(
                                135deg,
                                {{ $collection->color ?? '#6366F1' }}35,
                                #F8FAFC
                            );
                    "
                >
                    @if ($collection->image_url)
                        <img
                            src="{{ $collection->image_url }}"
                            alt="{{ $collection->name }}"
                            class="h-full w-full object-cover"
                        >

                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/60 to-transparent"></div>
                    @else
                        <div class="flex h-full items-center justify-center text-6xl">
                            {{ $collection->icon ?: '▤' }}
                        </div>
                    @endif

                    <div class="absolute left-4 top-4">
                        <x-status-badge :status="$collection->status" />
                    </div>

                    <div class="absolute right-4 top-4">
                        <x-status-badge :status="$collection->visibility" />
                    </div>
                </div>

                <div class="p-6">
                    <h3 class="text-xl font-black text-slate-900">
                        {{ $collection->name }}
                    </h3>

                    <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-400">
                        {{ $collection->code }}
                    </p>

                    <p class="mt-4 line-clamp-3 text-sm leading-6 text-slate-500">
                        {{ $collection->description ?: 'Sin descripción.' }}
                    </p>

                    <div class="mt-5 flex items-center justify-between rounded-xl bg-slate-50 p-4">
                        <div>
                            <p class="text-xs text-slate-500">
                                Entidades
                            </p>

                            <p class="mt-1 text-xl font-black text-slate-900">
                                {{ $collection->entities_count }}
                            </p>
                        </div>

                        <a
                            href="{{ route('collections.show', $collection) }}"
                            class="text-sm font-bold text-indigo-600 hover:text-indigo-800"
                        >
                            Abrir →
                        </a>
                    </div>
                </div>
            </article>
        @empty
            <div class="sm:col-span-2 xl:col-span-3 rounded-2xl border border-dashed border-slate-300 bg-white py-20 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-50 text-2xl text-indigo-600">
                    ▤
                </div>

                <h3 class="mt-5 font-bold text-slate-800">
                    Todavía no tienes colecciones
                </h3>

                <p class="mt-2 text-sm text-slate-500">
                    Crea una colección para agrupar personajes, países,
                    objetos o cualquier tipo de entidad.
                </p>

                <a
                    href="{{ route('collections.create') }}"
                    class="mt-5 inline-block rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white"
                >
                    Crear primera colección
                </a>
            </div>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $collections->links() }}
    </div>
</x-app-layout>