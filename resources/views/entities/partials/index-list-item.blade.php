<article
    class="
        flex
        flex-col
        gap-4
        rounded-2xl
        border
        border-slate-200
        bg-white
        p-4
        shadow-sm
        lg:flex-row
        lg:items-center
    ">

    <div
        class="
            h-24
            w-full
            shrink-0
            overflow-hidden
            rounded-xl
            bg-slate-100
            lg:w-24
        ">

        @if ($entity->image_url)
            <img src="{{ $entity->image_url }}"
                class="
                    h-full
                    w-full
                    object-cover
                ">
        @else
            <div
                class="
                    flex
                    h-full
                    items-center
                    justify-center
                    text-3xl
                    font-black
                    text-indigo-300
                ">
                {{ $entity->entityType?->icon ?: '✦' }}
            </div>
        @endif

    </div>


    <div class="min-w-0 flex-1">

        <div
            class="
                flex
                flex-wrap
                items-center
                gap-2
            ">

            <a href="{{ route('entities.show', $entity) }}"
                class="
                    font-black
                    text-slate-900
                    hover:text-indigo-700
                ">
                {{ $entity->name }}
            </a>


            <x-status-badge :status="$entity->status" />

        </div>


        <p
            class="
                mt-1
                font-mono
                text-[10px]
                text-slate-400
            ">
            {{ $entity->code }}
            ·
            {{ $entity->entityType?->name ?? 'Sin tipo' }}
        </p>


        <p
            class="
                mt-2
                line-clamp-1
                text-sm
                text-slate-500
            ">
            {{ $entity->description ?: 'Sin descripción.' }}
        </p>

    </div>


    <div class="
            flex
            flex-wrap
            gap-6
            lg:shrink-0
        ">

        <div>

            <p
                class="
                    text-[9px]
                    font-black
                    uppercase
                    text-slate-400
                ">
                Características
            </p>

            <p class="mt-1 font-black text-slate-700">
                {{ $entity->entity_attributes_count }}
            </p>

        </div>


        <div>

            <p
                class="
                    text-[9px]
                    font-black
                    uppercase
                    text-slate-400
                ">
                Colecciones
            </p>

            <p class="mt-1 font-black text-slate-700">
                {{ $entity->collections_count }}
            </p>

        </div>


        <div>

            <p
                class="
                    text-[9px]
                    font-black
                    uppercase
                    text-slate-400
                ">
                Visibilidad
            </p>

            <p class="mt-1 text-sm font-bold text-slate-700">
                {{ $entity->visibility_label }}
            </p>

        </div>

    </div>


    <a href="{{ route('entities.show', $entity) }}"
        class="
            rounded-xl
            bg-indigo-50
            px-4
            py-2.5
            text-center
            text-xs
            font-black
            text-indigo-700
        ">
        Abrir →
    </a>

</article>
