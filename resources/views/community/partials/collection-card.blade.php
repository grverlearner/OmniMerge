<article class="group overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
    <a
        href="{{ route(
            'community.collections.show',
            $collection
        ) }}"
        class="block"
    >
        <div
            class="relative aspect-[16/10] overflow-hidden"
            style="
                background:
                    linear-gradient(
                        135deg,
                        {{ $collection->color ?? '#6366F1' }},
                        #7C3AED
                    );
            "
        >
            @if ($collection->image_url)
                <img
                    src="{{ $collection->image_url }}"
                    alt="{{ $collection->name }}"
                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                >

                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/75 to-transparent"></div>
            @else
                <div class="flex h-full items-center justify-center text-7xl text-white/80">
                    {{ $collection->icon ?: '▤' }}
                </div>
            @endif

            <div class="absolute bottom-4 left-4 right-4 text-white">
                <p class="text-xs font-bold uppercase tracking-wider text-white/70">
                    {{ $collection->entities_count }}
                    entidad(es)
                </p>

                <h3 class="mt-1 text-xl font-black">
                    {{ $collection->name }}
                </h3>
            </div>
        </div>
    </a>

    <div class="p-5">
        <p class="text-sm font-bold text-slate-800">
            {{ $collection->creator->name }}
        </p>

        <p class="text-xs text-slate-500">
            {{ '@'.$collection->creator->username }}
        </p>

        <p class="mt-4 line-clamp-3 min-h-[4.5rem] text-sm leading-6 text-slate-500">
            {{ $collection->description
                ?: 'Sin descripción.' }}
        </p>

        <div class="mt-5 flex items-center gap-4 border-t border-slate-100 pt-4 text-xs font-semibold text-slate-500">
            <span>
                ◉ {{ number_format($collection->views_count) }}
            </span>

            <span>
                ⧉ {{ number_format($collection->clones_count) }}
            </span>

            <a
                href="{{ route(
                    'community.collections.show',
                    $collection
                ) }}"
                class="ml-auto text-sm font-bold text-indigo-600"
            >
                Abrir →
            </a>
        </div>
    </div>
</article>