@php

    $isCreator = $itemType === 'creator';

    if ($itemType === 'entity') {
        $title = $item->name;

        $subtitle = $item->entityType?->name ?? 'Sin tipo';

        $imageUrl = $item->image_url;

        $icon = $item->entityType?->icon ?: '✦';

        $creatorObject = $item->creator;

        $detailUrl = route('community.entities.show', $item);

        $cloneUrl = route('community.entities.clone', $item);

        $adminUrl = route('entities.show', $item);

        $metricOne = '◉ ' . number_format($item->views_count);

        $metricTwo = '⧉ ' . number_format($item->clones_count);

        $myClone = $item->clones->first();
    } elseif ($itemType === 'collection') {
        $title = $item->name;

        $subtitle = $item->entities_count . ' entidades';

        $imageUrl = $item->image_url;

        $icon = $item->icon ?: '▤';

        $creatorObject = $item->creator;

        $detailUrl = route('community.collections.show', $item);

        $cloneUrl = route('community.collections.clone', $item);

        $adminUrl = route('collections.show', $item);

        $metricOne = '◉ ' . number_format($item->views_count);

        $metricTwo = '⧉ ' . number_format($item->clones_count);

        $myClone = $item->clones->first();
    } elseif ($itemType === 'attribute') {
        $title = $item->name;

        $subtitle = $item->data_type_label;

        $imageUrl = $item->image_url;

        $icon = $item->icon ?: $item->data_type_icon;

        $creatorObject = $item->creator;

        $detailUrl = route('community.attributes.show', $item);

        $cloneUrl = route('community.attributes.clone', $item);

        $adminUrl = route('attributes.show', $item);

        $metricOne = $item->options_count . ' elementos';

        $metricTwo = '⧉ ' . number_format($item->clones_count);

        $myClone = $item->clones->first();
    } elseif ($itemType === 'catalog') {
        $title = $item->name;

        $subtitle = $item->attribute?->name ?? 'Catálogo';

        $imageUrl = $item->image_url;

        $icon = $item->icon ?: '◆';

        $creatorObject = $item->user;

        $detailUrl = route('community.catalogs.show', $item);

        $cloneUrl = route('community.catalogs.clone', $item);

        $adminUrl = route('attribute-options.show', $item);

        $metricOne = $item->values_count . ' usos';

        $metricTwo = $item->children_count . ' subelementos';

        $myClone = $item->clones->first();
    } else {
        $title = $item->name;

        $subtitle = '@' . $item->username;

        $imageUrl = $item->avatar_url;

        $icon = $item->initials;

        $creatorObject = null;

        $detailUrl = route('community.creators.show', $item->username);

        $cloneUrl = null;

        $adminUrl = null;

        $metricOne = $item->public_entities_count . ' entidades';

        $metricTwo = $item->public_attributes_count + $item->public_collections_count . ' recursos';

        $myClone = null;
    }

    $isOwner = !$isCreator && $item->user_id === auth()->id();
@endphp


<article
    class="
        group
        min-w-0
        overflow-hidden
        rounded-3xl
        border
        border-slate-200
        bg-white
        shadow-sm
        transition
        hover:-translate-y-1
        hover:border-indigo-200
        hover:shadow-xl
    ">

    {{-- VISUAL --}}
    <a href="{{ $detailUrl }}" class="block">

        <div class="
                relative
                overflow-hidden
                bg-gradient-to-br
                from-indigo-50
                to-violet-100
            "
            :class="{
                'aspect-[16/8]': density === 'compact',
            
                'aspect-[16/10]': density === 'medium',
            
                'aspect-[4/3]': density === 'large'
            }">

            @if ($imageUrl)
                <img src="{{ $imageUrl }}" alt="{{ $title }}"
                    class="
                        h-full
                        w-full
                        object-cover
                        transition
                        duration-500
                        group-hover:scale-105
                    ">
            @else
                <div
                    class="
                        flex
                        h-full
                        items-center
                        justify-center
                        text-6xl
                        font-black
                        text-indigo-300
                    ">
                    {{ $icon }}
                </div>
            @endif


            <div class="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-slate-950/75 to-transparent"></div>


            <span
                class="
                    absolute
                    left-3
                    top-3
                    max-w-[75%]
                    truncate
                    rounded-full
                    bg-white/90
                    px-2.5
                    py-1
                    text-[9px]
                    font-black
                    text-indigo-700
                    backdrop-blur
                ">
                {{ $subtitle }}
            </span>


            @if (!$isCreator && $myClone)
                <span
                    class="
                        absolute
                        right-3
                        top-3
                        rounded-full
                        bg-emerald-500
                        px-2.5
                        py-1
                        text-[9px]
                        font-black
                        text-white
                    ">
                    ✓ Copiado
                </span>
            @endif


            <h3
                class="
                    absolute
                    bottom-3
                    left-4
                    right-4
                    truncate
                    text-lg
                    font-black
                    text-white
                ">
                {{ $title }}
            </h3>

        </div>

    </a>


    {{-- INFO --}}
    <div
        :class="{
            'p-4': density === 'compact',
        
            'p-5': density === 'medium',
        
            'p-6': density === 'large'
        }">

        @if ($isCreator)
            <p class="line-clamp-2 text-sm leading-6 text-slate-500">
                {{ $item->headline ?: $item->bio ?: 'Creador de OmniMerge.' }}
            </p>
        @else
            <a href="{{ route('community.creators.show', $creatorObject->username) }}"
                class="flex min-w-0 items-center gap-2">

                <x-user-avatar :user="$creatorObject" size="xs" />

                <div class="min-w-0">

                    <p class="truncate text-xs font-bold text-slate-700">
                        {{ $creatorObject->name }}
                    </p>

                    <p class="truncate text-[9px] text-slate-400">
                        {{ '@' . $creatorObject->username }}
                    </p>

                </div>

            </a>


            <p x-show="
                    density !== 'compact'
                "
                class="mt-4 line-clamp-2 min-h-10 text-xs leading-5 text-slate-500">
                {{ $item->description ?: 'Sin descripción.' }}
            </p>
        @endif


        <div class="mt-4 flex items-center gap-3 border-t border-slate-100 pt-3 text-[10px] font-bold text-slate-400">

            <span>
                {{ $metricOne }}
            </span>

            <span>
                {{ $metricTwo }}
            </span>

        </div>


        <div class="mt-4 flex gap-2">

            <a href="{{ $detailUrl }}"
                class="
                    flex-1
                    rounded-xl
                    border
                    border-slate-200
                    px-3
                    py-2.5
                    text-center
                    text-xs
                    font-black
                    text-slate-600
                    hover:bg-slate-50
                ">
                Abrir
            </a>


            @if (!$isCreator)

                @if ($isOwner)
                    <a href="{{ $adminUrl }}"
                        class="
                            flex-1
                            rounded-xl
                            bg-slate-900
                            px-3
                            py-2.5
                            text-center
                            text-xs
                            font-black
                            text-white
                        ">
                        Administrar
                    </a>
                @elseif ($myClone)
                    @php

                        $myCloneUrl = match ($itemType) {
                            'entity' => route('entities.show', $myClone),

                            'collection' => route('collections.show', $myClone),

                            'attribute' => route('attributes.show', $myClone),

                            'catalog' => route('attribute-options.show', $myClone),

                            default => '#',
                        };

                    @endphp

                    <a href="{{ $myCloneUrl }}"
                        class="
                            flex-1
                            rounded-xl
                            bg-emerald-600
                            px-3
                            py-2.5
                            text-center
                            text-xs
                            font-black
                            text-white
                        ">
                        ✓ Mi copia
                    </a>
                @elseif ($itemType === 'catalog' || $item->allow_cloning)
                    <button type="button"
                        @click="
                            openClone(
                                @js($cloneUrl),
                                @js($title),
                                @js($itemType),
                                @js($subtitle)
                            )
                        "
                        class="
                            flex-1
                            rounded-xl
                            bg-indigo-600
                            px-3
                            py-2.5
                            text-xs
                            font-black
                            text-white
                            hover:bg-indigo-700
                        ">
                        ⧉ Copiar
                    </button>
                @else
                    <span
                        class="
                            flex-1
                            rounded-xl
                            bg-slate-100
                            px-3
                            py-2.5
                            text-center
                            text-xs
                            font-bold
                            text-slate-400
                        ">
                        Solo lectura
                    </span>
                @endif

            @endif

        </div>

    </div>

</article>
