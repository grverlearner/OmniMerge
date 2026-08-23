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


@php
    /* Solo las entidades se pueden copiar en lote desde aqui */
    $selectable = $itemType === 'entity';
@endphp

<div class="relative min-w-0"
    @if ($selectable) data-selectable-entity="{{ $item->id }}" @endif>

    {{-- CASILLA DE SELECCION --}}

    @if ($selectable)
        <button type="button" x-show="selecting" x-cloak
            @click.prevent.stop="toggleSelected({{ $item->id }})"
            class="absolute left-1.5 top-1.5 z-10 flex h-6 w-6 items-center justify-center rounded-md border-2 shadow transition"
            :class="isSelected({{ $item->id }})
                ? 'border-indigo-600 bg-indigo-600 text-white'
                : 'border-white bg-white/90 text-transparent hover:border-indigo-400'">
            <span class="text-[11px] font-black">✓</span>
        </button>
    @endif

<a href="{{ $url }}"
    @if ($selectable)
        @click="selecting ? ($event.preventDefault(), toggleSelected({{ $item->id }})) : null"
        :class="selecting && isSelected({{ $item->id }})
            ? 'ring-2 ring-indigo-500 ring-offset-1'
            : ''"
    @endif
    class="
        group
        block
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

</div>
