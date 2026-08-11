@php

    if ($itemType === 'entity') {
        $title = $item->name;
        $imageUrl = $item->base_display_image_url;
        $icon = $item->entityType?->icon ?: '✦';
        $url = route('community.entities.show', $item);
    } elseif ($itemType === 'collection') {
        $title = $item->name;
        $imageUrl = $item->image_url;
        $icon = $item->icon ?: '▤';
        $url = route('community.collections.show', $item);
    } elseif ($itemType === 'attribute') {
        $title = $item->name;
        $imageUrl = $item->image_url;
        $icon = $item->icon ?: $item->data_type_icon;
        $url = route('community.attributes.show', $item);
    } elseif ($itemType === 'catalog') {
        $title = $item->name;
        $imageUrl = $item->image_url;
        $icon = $item->icon ?: '◆';
        $url = route('community.catalogs.show', $item);
    } else {
        $title = $item->name;
        $imageUrl = $item->avatar_url;
        $icon = $item->initials;
        $url = route('community.creators.show', $item->username);
    }

@endphp


<a href="{{ $url }}"
    class="
        group
        min-w-0
        overflow-hidden
        rounded-xl
        border
        border-slate-200
        bg-white
        shadow-sm
        transition
        hover:-translate-y-0.5
        hover:border-indigo-300
        hover:shadow-md
    ">

    <div class="aspect-square overflow-hidden bg-slate-100">

        @if ($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ $title }}"
                class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
        @else
            <div
                class="flex h-full items-center justify-center bg-gradient-to-br from-indigo-50 to-violet-100 text-3xl font-black text-indigo-300">
                {{ $icon }}
            </div>
        @endif

    </div>


    <div class="px-2 py-2.5">

        <p class="truncate text-center text-xs font-black text-slate-800 group-hover:text-indigo-700"
            title="{{ $title }}">
            {{ $title }}
        </p>

    </div>

</a>
