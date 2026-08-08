<article
    class="group overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
    <a href="{{ route('community.attributes.show', $attribute) }}" class="block">
        <div class="relative aspect-[16/8] overflow-hidden"
            style="
                background:
                    linear-gradient(
                        135deg,
                        {{ $attribute->color ?? '#6366F1' }}45,
                        #F8FAFC
                    );
            ">
            @if ($attribute->image_url)
                <img src="{{ $attribute->image_url }}" alt="{{ $attribute->name }}"
                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
            @else
                <div class="flex h-full items-center justify-center text-6xl">
                    {{ $attribute->icon ?: '☷' }}
                </div>
            @endif

            <span class="absolute left-4 top-4 rounded-full bg-white/90 px-3 py-1 text-xs font-black text-indigo-700">
                {{ $attribute->data_type }}
            </span>
        </div>
    </a>

    <div class="p-5">
        <h3 class="text-xl font-black text-slate-900">
            {{ $attribute->name }}
        </h3>

        <a href="{{ route('community.creators.show', $attribute->creator->username) }}"
            class="
        mt-3
        flex
        items-center
        gap-2
        transition
        hover:opacity-80
    ">

            <x-user-avatar :user="$attribute->creator" size="xs" />


            <div class="min-w-0">

                <p
                    class="
                truncate
                text-xs
                font-bold
                text-slate-600
            ">
                    {{ $attribute->creator->name }}
                </p>

                <p
                    class="
                    truncate
                    text-[10px]
                    text-slate-400
                ">
                    {{ '@' . $attribute->creator->username }}
                </p>

            </div>

        </a>

        <p class="mt-4 line-clamp-3 min-h-[4.5rem] text-sm leading-6 text-slate-500">
            {{ $attribute->description ?: 'Sin descripción.' }}
        </p>

        <div class="mt-4 flex flex-wrap gap-2">
            @if ($attribute->allows_multiple)
                <span class="rounded-full bg-violet-100 px-2.5 py-1 text-[11px] font-bold text-violet-700">
                    Multiselección
                </span>
            @endif

            @if ($attribute->usesCatalog())
                <span class="rounded-full bg-indigo-100 px-2.5 py-1 text-[11px] font-bold text-indigo-700">
                    {{ $attribute->options_count }}
                    opciones
                </span>
            @endif

            @if ($attribute->is_filterable)
                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-bold text-emerald-700">
                    Filtrable
                </span>
            @endif
        </div>

        <div class="mt-5 flex items-center gap-4 border-t border-slate-100 pt-4 text-xs font-semibold text-slate-500">
            <span>
                ◉ {{ number_format($attribute->views_count) }}
            </span>

            <span>
                ⧉ {{ number_format($attribute->clones_count) }}
            </span>

            <a href="{{ route('community.attributes.show', $attribute) }}"
                class="ml-auto text-sm font-bold text-indigo-600">
                Abrir →
            </a>
        </div>
    </div>
</article>
