<article
    class="group overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
    <a href="{{ route('community.entities.show', $entity) }}" class="block">
        <div class="relative aspect-[16/10] overflow-hidden bg-gradient-to-br from-indigo-50 to-violet-100">
            @if ($entity->base_display_image_url)
                <img src="{{ $entity->base_display_image_url }}" alt="{{ $entity->public_display_name }}"
                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
            @else
                <div class="flex h-full items-center justify-center text-6xl text-indigo-400">
                    {{ $entity->entityType?->icon ?: strtoupper(substr($entity->name, 0, 1)) }}
                </div>
            @endif

            <div class="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-slate-950/70 to-transparent"></div>

            <span
                class="absolute left-4 top-4 rounded-full bg-white/90 px-3 py-1 text-xs font-bold text-indigo-700 backdrop-blur">
                {{ $entity->entityType?->name ?? 'Sin tipo' }}
            </span>

            <div class="absolute bottom-4 left-4 right-4">
                <h3 class="text-xl font-black text-white">
                    {{ $entity->public_display_name }}
                </h3>
            </div>
        </div>
    </a>

    <div class="p-5">
        <a href="{{ route('community.creators.show', $entity->creator->username) }}"
            class="
        flex
        items-center
        gap-3
        rounded-xl
        transition
        hover:opacity-80
    ">

            <x-user-avatar :user="$entity->creator" size="sm" />


            <div class="min-w-0">

                <p
                    class="
                truncate
                text-sm
                font-bold
                text-slate-800
            ">
                    {{ $entity->creator->name }}
                </p>

                <p
                    class="
                truncate
                text-xs
                text-slate-500
            ">
                    {{ '@' . $entity->creator->username }}
                </p>

            </div>

        </a>

        <p class="mt-4 line-clamp-3 min-h-[4.5rem] text-sm leading-6 text-slate-500">
            {{ $entity->public_description ?: 'Sin descripción.' }}
        </p>

        <div class="mt-5 flex items-center gap-4 border-t border-slate-100 pt-4 text-xs font-semibold text-slate-500">
            <span>
                ◉ {{ number_format($entity->views_count) }}
            </span>

            <span>
                ⧉ {{ number_format($entity->clones_count) }}
            </span>

            <a href="{{ route('community.entities.show', $entity) }}"
                class="ml-auto text-sm font-bold text-indigo-600 hover:text-indigo-800">
                Ver detalle →
            </a>
        </div>
    </div>
</article>
