<x-app-layout>
    <x-slot name="header">
        Detalle de colección
    </x-slot>

    <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="relative min-h-64 bg-gradient-to-br from-indigo-500 to-violet-600 sm:min-h-80"
            style="
                background:
                    linear-gradient(
                        135deg,
                        {{ $collection->color ?? '#6366F1' }},
                        #7C3AED
                    );
            ">
            @if ($collection->image_url)
                <img src="{{ $collection->image_url }}" alt="{{ $collection->name }}"
                    class="absolute inset-0 h-full w-full object-cover">

                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/20 to-transparent"></div>
            @else
                <div class="flex min-h-64 items-center justify-center text-8xl text-white/80 sm:min-h-80">
                    {{ $collection->icon ?: '▤' }}
                </div>

                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/60 via-transparent to-transparent"></div>
            @endif

            <div class="absolute bottom-0 left-0 right-0 p-6 text-white sm:p-8">
                <div class="flex flex-wrap gap-2">
                    <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-semibold backdrop-blur">
                        {{ $collection->visibility }}
                    </span>

                    <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-semibold backdrop-blur">
                        {{ $collection->status }}
                    </span>
                </div>

                <h2 class="mt-4 text-3xl font-black sm:text-4xl">
                    {{ $collection->name }}
                </h2>

                <p class="mt-1 text-sm text-white/75">
                    {{ $collection->code }}
                </p>
            </div>
        </div>

        <div class="p-6 sm:p-8">
            <div class="flex flex-col justify-between gap-6 sm:flex-row sm:items-start">
                <div class="max-w-3xl">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400">
                        Descripción
                    </h3>

                    <p class="mt-3 whitespace-pre-line leading-8 text-slate-600">
                        {{ $collection->description ?: 'Esta colección no tiene descripción.' }}
                    </p>
                </div>

                <div class="flex shrink-0 flex-wrap gap-2">
                    @if ($collection->allow_cloning && $collection->user_id !== auth()->id())
                        <form method="POST"
                            action="{{ route('community.collections.clone', $collection) }}"
                            data-omni-confirm data-confirm-variant="primary" data-confirm-icon="⧉"
                            data-confirm-title="Copiar Colección completa"
                            data-confirm-message="
        Se creará una copia privada
        de esta Colección y sus Entidades.
    "
                            data-confirm-subject="{{ $collection->name }}"
                            data-confirm-detail="
        La Colección original continuará
        perteneciendo a su creador y no será modificada.
    "
                            data-confirm-action="Copiar Colección"
                            data-confirm-image="{{ $collection->image_url ?? '' }}">
                            @csrf

                            <button type="submit"
                                class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-indigo-600/20">
                                ⧉ Copiar colección completa
                            </button>
                        </form>
                    @elseif ($collection->user_id === auth()->id())
                        <a href="{{ route('collections.show', $collection) }}"
                            class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-black text-white">
                            Administrar mi colección
                        </a>
                    @endif
                </div>
            </div>

            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-sm text-slate-500">
                        Entidades
                    </p>

                    <p class="mt-2 text-2xl font-black text-slate-900">
                        {{ $collection->entities->count() }}
                    </p>
                </div>

                <div class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-sm text-slate-500">
                        Visibilidad
                    </p>

                    <div class="mt-2">
                        <x-status-badge :status="$collection->visibility" />
                    </div>
                </div>

                <div class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-sm text-slate-500">
                        Creada
                    </p>

                    <p class="mt-2 font-bold text-slate-900">
                        {{ $collection->created_at->format('d/m/Y') }}
                    </p>
                </div>

                <div class="rounded-2xl bg-slate-50 p-5">

                    <p class="text-sm text-slate-500">
                        Copias
                    </p>

                    <p class="mt-2 text-2xl font-black text-slate-900">
                        {{ number_format($collection->clones_count) }}
                    </p>

                </div>
            </div>
        </div>
    </article>

    <section class="mt-8">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xl font-black text-slate-900">
                    Entidades de la colección
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Elementos incluidos actualmente.
                </p>
            </div>

            @if ($collection->user_id === auth()->id())
                <a href="{{ route('collections.edit', $collection) }}"
                    class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-700">
                    Administrar entidades
                </a>
            @endif
        </div>

        <div class="mt-5 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($collection->entities as $entity)
                <a href="{{ route('community.entities.show', $entity) }}"
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                    <div class="aspect-[16/9] bg-slate-100">
                        @if ($entity->base_display_image_url)
                            <img src="{{ $entity->base_display_image_url }}" alt="{{ $entity->name }}"
                                class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full items-center justify-center text-5xl text-indigo-400">
                                {{ $entity->entityType?->icon ?: strtoupper(substr($entity->name, 0, 1)) }}
                            </div>
                        @endif
                    </div>

                    <div class="p-5">
                        <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">
                            {{ $entity->entityType?->name ?? 'Sin tipo' }}
                        </p>

                        <h4 class="mt-2 text-lg font-black text-slate-900">
                            {{ $entity->name }}
                        </h4>

                        <p class="mt-3 line-clamp-2 text-sm text-slate-500">
                            {{ $entity->description ?: 'Sin descripción.' }}
                        </p>
                    </div>
                </a>
            @empty
                <div
                    class="sm:col-span-2 xl:col-span-3 rounded-2xl border border-dashed border-slate-300 bg-white py-16 text-center text-slate-500">
                    Esta colección todavía no tiene entidades.
                </div>
            @endforelse
        </div>
    </section>
</x-app-layout>
