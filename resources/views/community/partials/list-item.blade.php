@php

    if ($itemType === 'entity') {
        $title = $item->name;
        $subtitle = $item->entityType?->name ?? 'Sin tipo';
        $imageUrl = $item->base_display_image_url;
        $icon = $item->entityType?->icon ?: '✦';
        $creatorObject = $item->creator;
        $url = route('community.entities.show', $item);
        $metric = number_format($item->views_count) . ' vistas · ' . number_format($item->clones_count) . ' copias';
    } elseif ($itemType === 'collection') {
        $title = $item->name;
        $subtitle = $item->entities_count . ' entidades';
        $imageUrl = $item->image_url;
        $icon = $item->icon ?: '▤';
        $creatorObject = $item->creator;
        $url = route('community.collections.show', $item);
        $metric = number_format($item->views_count) . ' vistas · ' . number_format($item->clones_count) . ' copias';
    } elseif ($itemType === 'attribute') {
        $title = $item->name;
        $subtitle = $item->data_type_label;
        $imageUrl = $item->image_url;
        $icon = $item->icon ?: $item->data_type_icon;
        $creatorObject = $item->creator;
        $url = route('community.attributes.show', $item);
        $metric = $item->options_count . ' elementos · ' . $item->entity_attributes_count . ' usos';
    } elseif ($itemType === 'catalog') {
        $title = $item->name;
        $subtitle = $item->attribute?->name ?? 'Catálogo';
        $imageUrl = $item->image_url;
        $icon = $item->icon ?: '◆';
        $creatorObject = $item->user;
        $url = route('community.catalogs.show', $item);
        $metric = $item->values_count . ' usos · ' . $item->children_count . ' subelementos';
    } else {
        $title = $item->name;
        $subtitle = '@' . $item->username;
        $imageUrl = $item->avatar_url;
        $icon = $item->initials;
        $creatorObject = null;
        $url = route('community.creators.show', $item->username);

        $metric = $item->public_entities_count . ' entidades · ' . $item->public_attributes_count . ' atributos';
    }

@endphp


<a href="{{ $url }}"
    class="
        flex
        min-w-0
        flex-col
        gap-4
        rounded-2xl
        border
        border-slate-200
        bg-white
        p-4
        shadow-sm
        transition
        hover:border-indigo-200
        hover:shadow-md
        sm:flex-row
        sm:items-center
    ">

    <div class="h-16 w-full shrink-0 overflow-hidden rounded-xl bg-slate-100 sm:w-16">

        @if ($imageUrl)
            <img src="{{ $imageUrl }}" class="h-full w-full object-cover">
        @else
            <div class="flex h-full items-center justify-center text-xl font-black text-indigo-400">
                {{ $icon }}
            </div>
        @endif

    </div>


    <div class="min-w-0 flex-1">

        <p class="truncate font-black text-slate-900">
            {{ $title }}
        </p>

        <p class="mt-1 truncate text-xs text-slate-400">
            {{ $subtitle }}
        </p>

    </div>


    @if ($creatorObject)
        <div class="hidden min-w-0 items-center gap-2 md:flex">

            <x-user-avatar :user="$creatorObject" size="xs" />

            <span class="max-w-32 truncate text-xs font-bold text-slate-500">
                {{ '@' . $creatorObject->username }}
            </span>

        </div>
    @endif


    <p class="text-xs font-bold text-slate-400">
        {{ $metric }}
    </p>


    <span class="text-xs font-black text-indigo-600">
        Abrir →
    </span>

</a>
