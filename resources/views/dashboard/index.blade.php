<x-app-layout>
    <x-slot name="header">
        Dashboard
    </x-slot>

    <section class="rounded-3xl bg-gradient-to-br from-indigo-600 via-violet-600 to-fuchsia-600 p-8 text-white shadow-xl">
        <div class="max-w-3xl">
            <p class="text-sm font-semibold uppercase tracking-widest text-indigo-100">
                Bienvenido a OmniMerge
            </p>

            <h2 class="mt-3 text-3xl font-black sm:text-4xl">
                Convierte cualquier idea en una entidad reutilizable
            </h2>

            <p class="mt-4 max-w-2xl text-indigo-100">
                Crea personajes, países, animales, objetos, conceptos
                o cualquier elemento que puedas imaginar.
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                <a
                    href="{{ route('entities.create') }}"
                    class="rounded-xl bg-white px-5 py-3 text-sm font-bold text-indigo-700 shadow transition hover:-translate-y-0.5"
                >
                    Crear una entidad
                </a>

                <a
                    href="{{ route('entity-types.create') }}"
                    class="rounded-xl border border-white/30 bg-white/10 px-5 py-3 text-sm font-bold text-white backdrop-blur transition hover:bg-white/20"
                >
                    Crear un tipo
                </a>
            </div>
        </div>
    </section>

    <section class="mt-8 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $cards = [
                [
                    'label' => 'Tipos de entidad',
                    'value' => $statistics['entity_types'],
                    'icon' => '◇',
                ],
                [
                    'label' => 'Entidades',
                    'value' => $statistics['entities'],
                    'icon' => '✦',
                ],
                [
                    'label' => 'Entidades activas',
                    'value' => $statistics['active_entities'],
                    'icon' => '✓',
                ],
                [
                    'label' => 'Entidades públicas',
                    'value' => $statistics['public_entities'],
                    'icon' => '◎',
                ],
            ];
        @endphp

        @foreach ($cards as $card)
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500">
                            {{ $card['label'] }}
                        </p>

                        <p class="mt-3 text-3xl font-black text-slate-900">
                            {{ $card['value'] }}
                        </p>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-50 text-xl text-indigo-600">
                        {{ $card['icon'] }}
                    </div>
                </div>
            </article>
        @endforeach
    </section>

    <section class="mt-8 grid gap-6 xl:grid-cols-2">
        <article class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                <h3 class="font-bold text-slate-900">
                    Entidades recientes
                </h3>

                <a
                    href="{{ route('entities.index') }}"
                    class="text-sm font-semibold text-indigo-600 hover:text-indigo-800"
                >
                    Ver todas
                </a>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($recentEntities as $entity)
                    <a
                        href="{{ route('entities.show', $entity) }}"
                        class="flex items-center gap-4 px-6 py-4 transition hover:bg-slate-50"
                    >
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-indigo-50 font-bold text-indigo-600">
                            @if ($entity->image_url)
                                <img
                                    src="{{ $entity->image_url }}"
                                    alt="{{ $entity->name }}"
                                    class="h-full w-full object-cover"
                                >
                            @else
                                {{ strtoupper(substr($entity->name, 0, 1)) }}
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-slate-900">
                                {{ $entity->name }}
                            </p>

                            <p class="truncate text-sm text-slate-500">
                                {{ $entity->entityType?->name ?? 'Sin tipo' }}
                            </p>
                        </div>

                        <x-status-badge :status="$entity->status" />
                    </a>
                @empty
                    <div class="px-6 py-12 text-center text-sm text-slate-500">
                        Todavía no has creado entidades.
                    </div>
                @endforelse
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                <h3 class="font-bold text-slate-900">
                    Tipos recientes
                </h3>

                <a
                    href="{{ route('entity-types.index') }}"
                    class="text-sm font-semibold text-indigo-600 hover:text-indigo-800"
                >
                    Ver todos
                </a>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($recentTypes as $type)
                    <a
                        href="{{ route('entity-types.show', $type) }}"
                        class="flex items-center gap-4 px-6 py-4 transition hover:bg-slate-50"
                    >
                        <div
                            class="h-10 w-2 rounded-full"
                            style="background-color: {{ $type->color ?? '#6366F1' }}"
                        ></div>

                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-slate-900">
                                {{ $type->name }}
                            </p>

                            <p class="text-sm text-slate-500">
                                {{ $type->entities_count }}
                                entidad(es)
                            </p>
                        </div>

                        <x-status-badge :status="$type->status" />
                    </a>
                @empty
                    <div class="px-6 py-12 text-center text-sm text-slate-500">
                        Todavía no has creado tipos.
                    </div>
                @endforelse
            </div>
        </article>
    </section>
</x-app-layout>