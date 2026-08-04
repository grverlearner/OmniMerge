<x-app-layout>
    <x-slot name="header">
        Detalle de entidad
    </x-slot>

    <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="relative h-56 bg-gradient-to-br from-indigo-500 via-violet-500 to-fuchsia-500 sm:h-72">
            @if ($entity->image_url)
                <img
                    src="{{ $entity->image_url }}"
                    alt="{{ $entity->name }}"
                    class="h-full w-full object-cover"
                >

                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/75 via-transparent to-transparent"></div>
            @endif

            <div class="absolute bottom-0 left-0 right-0 p-6 text-white sm:p-8">
                <div class="flex flex-wrap gap-2">
                    <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-semibold backdrop-blur">
                        {{ $entity->entityType?->name ?? 'Sin tipo' }}
                    </span>

                    <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-semibold backdrop-blur">
                        {{ $entity->status }}
                    </span>
                </div>

                <h2 class="mt-3 text-3xl font-black sm:text-4xl">
                    {{ $entity->name }}
                </h2>

                <p class="mt-1 text-sm text-white/75">
                    {{ $entity->code }}
                </p>
            </div>
        </div>

        <div class="p-6 sm:p-8">
            <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-start">
                <div class="max-w-3xl">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400">
                        Descripción
                    </h3>

                    <p class="mt-3 whitespace-pre-line leading-8 text-slate-600">
                        {{ $entity->description ?: 'Esta entidad todavía no tiene descripción.' }}
                    </p>
                </div>

                <div class="flex shrink-0 flex-wrap gap-2">
                    @can('update', $entity)
                        <a
                            href="{{ route('entities.edit', $entity) }}"
                            class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Editar
                        </a>
                    @endcan

                    @can('delete', $entity)
                        <form
                            method="POST"
                            action="{{ route('entities.destroy', $entity) }}"
                            onsubmit="return confirm('¿Eliminar esta entidad?')"
                        >
                            @csrf
                            @method('DELETE')

                            <button
                                class="rounded-xl border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50"
                            >
                                Eliminar
                            </button>
                        </form>
                    @endcan
                </div>
            </div>

            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-sm text-slate-500">
                        Tipo
                    </p>

                    <p class="mt-2 font-bold text-slate-900">
                        {{ $entity->entityType?->name ?? 'Sin tipo' }}
                    </p>
                </div>

                <div class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-sm text-slate-500">
                        Visibilidad
                    </p>

                    <div class="mt-2">
                        <x-status-badge :status="$entity->visibility" />
                    </div>
                </div>

                <div class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-sm text-slate-500">
                        Creación
                    </p>

                    <p class="mt-2 font-bold text-slate-900">
                        {{ $entity->created_at->format('d/m/Y H:i') }}
                    </p>
                </div>

                <div class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-sm text-slate-500">
                        Actualización
                    </p>

                    <p class="mt-2 font-bold text-slate-900">
                        {{ $entity->updated_at->format('d/m/Y H:i') }}
                    </p>
                </div>
            </div>

            <div class="mt-8 rounded-2xl border border-dashed border-indigo-200 bg-indigo-50 p-8 text-center">
                <p class="font-bold text-indigo-900">
                    Atributos personalizados
                </p>

                <p class="mt-2 text-sm text-indigo-700">
                    Aquí aparecerán elemento, poder, habilidades,
                    anime, país y todos los valores configurados
                    por el usuario.
                </p>

                <span class="mt-4 inline-block rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">
                    Sprint 2
                </span>
            </div>
        </div>
    </article>
</x-app-layout>