<x-app-layout>
    <x-slot name="header">
        Explorar entidad
    </x-slot>

    <div class="mb-5">
        <a href="{{ route('community.index', ['tab' => 'entities']) }}"
            class="text-sm font-bold text-indigo-600 hover:text-indigo-800">
            ← Volver al explorador
        </a>
    </div>

    <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="relative min-h-72 bg-gradient-to-br from-indigo-500 to-violet-600 sm:min-h-[420px]">
            @if ($entity->image_url)
                <img src="{{ $entity->image_url }}" alt="{{ $entity->name }}"
                    class="absolute inset-0 h-full w-full object-cover">

                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-950/20 to-transparent"></div>
            @else
                <div class="flex min-h-72 items-center justify-center text-9xl text-white/80 sm:min-h-[420px]">
                    {{ $entity->entityType?->icon ?: strtoupper(substr($entity->name, 0, 1)) }}
                </div>
            @endif

            <div class="absolute bottom-0 left-0 right-0 p-6 text-white sm:p-10">
                <div class="flex flex-wrap gap-2">
                    <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-bold backdrop-blur">
                        {{ $entity->entityType?->name ?? 'Sin tipo' }}
                    </span>

                    <span
                        class="rounded-full bg-emerald-400/20 px-3 py-1 text-xs font-bold text-emerald-100 backdrop-blur">
                        Contenido público
                    </span>
                </div>

                <h2 class="mt-4 text-4xl font-black sm:text-6xl">
                    {{ $entity->name }}
                </h2>

                <p class="
        mt-2
        text-sm
        text-white/70
    ">
                    Creado por

                    <a href="{{ route('community.creators.show', $entity->creator->username) }}"
                        class="
                            font-bold
                            text-white
                            hover:underline
                        ">
                        {{ $entity->creator->name }}
                        ·
                        {{ '@' . $entity->creator->username }}
                    </a>
                </p>
            </div>
        </div>

        <div class="p-6 sm:p-10">
            <div class="grid gap-8 xl:grid-cols-[1fr_320px]">
                <div>
                    <h3 class="text-sm font-black uppercase tracking-wider text-slate-400">
                        Descripción
                    </h3>

                    <p class="mt-4 whitespace-pre-line leading-8 text-slate-600">
                        {{ $entity->description ?: 'Esta entidad no tiene descripción.' }}
                    </p>

                    <section class="mt-10">
                        <h3 class="text-xl font-black text-slate-900">
                            Características
                        </h3>

                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            @forelse (
                                $entity->entityAttributes
                                as $entityAttribute
                            )
                                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                    <p class="text-xs font-black uppercase tracking-wider text-slate-400">
                                        {{ $entityAttribute->custom_label ?: $entityAttribute->attribute->name }}
                                    </p>

                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @forelse ($entityAttribute->values
                                            as $value)
                                            <span
                                                class="rounded-xl bg-white px-3 py-2 text-sm font-bold text-slate-800 shadow-sm">
                                                @if ($value->option?->image_url)
                                                    <img src="{{ $value->option->image_url }}" alt=""
                                                        class="mr-2 inline-block h-7 w-7 rounded-lg object-cover align-middle">
                                                @elseif ($value->option?->icon)
                                                    <span class="mr-1">
                                                        {{ $value->option->icon }}
                                                    </span>
                                                @endif

                                                {{ $value->displayValue() }}
                                            </span>
                                        @empty
                                            <span class="text-sm text-slate-400">
                                                Sin valor
                                            </span>
                                        @endforelse
                                    </div>
                                </article>
                            @empty
                                <p
                                    class="sm:col-span-2 rounded-2xl border border-dashed border-slate-300 p-10 text-center text-slate-500">
                                    Esta entidad no tiene atributos públicos.
                                </p>
                            @endforelse
                        </div>
                    </section>
                </div>

                <aside class="space-y-5">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
                        <p class="text-sm font-black text-slate-900">
                            Creador
                        </p>

                        <a href="{{ route('community.creators.show', $entity->creator->username) }}"
                            class="
                                mt-4
                                flex
                                items-center
                                gap-3
                                rounded-xl
                                transition
                                hover:opacity-80
                            ">

                            <x-user-avatar :user="$entity->creator" size="lg" />


                            <div class="min-w-0">

                                <p
                                    class="
                                        truncate
                                        font-bold
                                        text-slate-900
                                    ">
                                    {{ $entity->creator->name }}
                                </p>

                                <p
                                    class="
                                        truncate
                                        text-sm
                                        text-slate-500
                                    ">
                                    {{ '@' . $entity->creator->username }}
                                </p>

                                @if ($entity->creator->headline)
                                    <p
                                        class="
                                            mt-1
                                            line-clamp-1
                                            text-xs
                                            text-slate-400
                                        ">
                                        {{ $entity->creator->headline }}
                                    </p>
                                @endif

                            </div>

                        </a>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-2xl bg-slate-50 p-5 text-center">
                            <p class="text-2xl font-black text-slate-900">
                                {{ number_format($entity->views_count) }}
                            </p>

                            <p class="mt-1 text-xs font-bold uppercase tracking-wider text-slate-400">
                                Vistas
                            </p>
                        </div>

                        <div class="rounded-2xl bg-slate-50 p-5 text-center">
                            <p class="text-2xl font-black text-slate-900">
                                {{ number_format($entity->clones_count) }}
                            </p>

                            <p class="mt-1 text-xs font-bold uppercase tracking-wider text-slate-400">
                                Copias
                            </p>
                        </div>
                    </div>

                    @if ($entity->allow_cloning && $entity->user_id !== auth()->id())
                        <form method="POST" action="{{ route('community.entities.clone', $entity) }}"
                            onsubmit="return confirm(
                                'Se creará una copia privada e independiente en tu biblioteca. ¿Continuar?'
                            )">
                            @csrf

                            <button type="submit"
                                class="w-full rounded-2xl bg-indigo-600 px-6 py-4 font-black text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-700">
                                ⧉ Copiar a mi biblioteca
                            </button>
                        </form>
                    @elseif ($entity->user_id === auth()->id())
                        <a href="{{ route('entities.show', $entity) }}"
                            class="block w-full rounded-2xl bg-slate-900 px-6 py-4 text-center font-black text-white">
                            Administrar mi entidad
                        </a>
                    @else
                        <div class="rounded-2xl bg-amber-50 p-5 text-sm text-amber-800">
                            El creador no permite copiar esta entidad.
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </article>

    @if ($relatedEntities->isNotEmpty())
        <section class="mt-10">
            <h3 class="text-2xl font-black text-slate-900">
                Entidades relacionadas
            </h3>

            <div class="mt-5 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($relatedEntities as $relatedEntity)
                    @include('community.partials.entity-card', ['entity' => $relatedEntity])
                @endforeach
            </div>
        </section>
    @endif
</x-app-layout>
