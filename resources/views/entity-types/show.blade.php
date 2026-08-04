<x-app-layout>
    <x-slot name="header">
        Detalle del tipo
    </x-slot>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex flex-col justify-between gap-6 sm:flex-row sm:items-start">
            <div class="flex items-start gap-5">
                <div
                    class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl text-2xl"
                    style="
                        background-color: {{ $entityType->color ?? '#6366F1' }}20;
                        color: {{ $entityType->color ?? '#6366F1' }};
                    "
                >
                    {{ $entityType->icon ?: '◇' }}
                </div>

                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-3xl font-black text-slate-900">
                            {{ $entityType->name }}
                        </h2>

                        <x-status-badge :status="$entityType->status" />
                    </div>

                    <p class="mt-1 text-sm font-semibold text-slate-400">
                        {{ $entityType->code }}
                    </p>

                    <p class="mt-4 max-w-2xl leading-7 text-slate-600">
                        {{ $entityType->description ?: 'Este tipo no tiene descripción.' }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('entity-types.edit', $entityType) }}"
                    class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Editar
                </a>

                <form
                    method="POST"
                    action="{{ route('entity-types.destroy', $entityType) }}"
                    onsubmit="return confirm('¿Eliminar este tipo de entidad?')"
                >
                    @csrf
                    @method('DELETE')

                    <button
                        class="rounded-xl border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50"
                    >
                        Eliminar
                    </button>
                </form>
            </div>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl bg-slate-50 p-5">
                <p class="text-sm text-slate-500">
                    Entidades
                </p>

                <p class="mt-2 text-2xl font-black text-slate-900">
                    {{ $entityType->entities_count }}
                </p>
            </div>

            <div class="rounded-2xl bg-slate-50 p-5">
                <p class="text-sm text-slate-500">
                    Orden
                </p>

                <p class="mt-2 text-2xl font-black text-slate-900">
                    {{ $entityType->sort_order }}
                </p>
            </div>

            <div class="rounded-2xl bg-slate-50 p-5">
                <p class="text-sm text-slate-500">
                    Creado
                </p>

                <p class="mt-2 font-bold text-slate-900">
                    {{ $entityType->created_at->format('d/m/Y') }}
                </p>
            </div>
        </div>
    </div>

    <div class="mt-8 flex items-center justify-between">
        <h3 class="text-xl font-black text-slate-900">
            Entidades de este tipo
        </h3>

        <a
            href="{{ route('entities.create', ['type' => $entityType->id]) }}"
            class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-700"
        >
            + Crear entidad
        </a>
    </div>

    <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @forelse ($entities as $entity)
            <a
                href="{{ route('entities.show', $entity) }}"
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-lg"
            >
                <p class="font-bold text-slate-900">
                    {{ $entity->name }}
                </p>

                <p class="mt-2 line-clamp-2 text-sm text-slate-500">
                    {{ $entity->description ?: 'Sin descripción.' }}
                </p>
            </a>
        @empty
            <p class="sm:col-span-2 lg:col-span-4 rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">
                Todavía no existen entidades de este tipo.
            </p>
        @endforelse
    </div>
</x-app-layout>